<?php

require_once('class.recipientsource.php');

Class RecipientSourceSection extends RecipientSource{
	
	public $emailField = null;
	public $nameFields = Array();
	public $nameXslt = null;
	
	public $dsParamLIMIT = 10;
	public $dsParamPAGINATERESULTS = 'yes';
	public $dsParamSTARTPAGE = 1;

	/**
	 * Fetch generated recipient data.
	 * 
	 * Returns parsed recipient data. This means the xslt provided by the user
	 * will be ran on the raw data, returning a name and email direcly useable
	 * by the email API.
	 *
	 * This is the preferred way of getting recipient data.
	 * 
	 * @todo bugtesting and error handling
	 * @return array
	 */
	public function getSlice($newsletter_id = 10){
		$entries = $this->grab($newsletter_id);
		$return['total-entries'] = (string)$entries['total-entries'];
		$return['total-pages'] = (string)$entries['total-pages'];
		$return['remaining-pages'] = (string)$entries['remaining-pages'];
		$return['remaining-entries'] = (string)$entries['remaining-entries'];
		$return['entries-per-page'] = (string)$entries['limit'];
		$return['start'] = (string)$entries['start'];
		$return['current-page'] = (string)$this->dsParamSTARTPAGE;
		$field_ids = array();
		$entryManager = new EntryManager($this->_Parent);
		$xsltproc = new XsltProcess();
		foreach($this->nameFields as $nameField){
			$field_ids[] = $entryManager->fieldManager->fetchFieldIDFromElementName($nameField);
		}
		$email_field_id = $entryManager->fieldManager->fetchFieldIDFromElementName($this->emailField);
		require_once(TOOLKIT . '/util.validators.php');
		foreach((array)$entries['records'] as $entry){
			$entry_data = $entry->getData();
			$element = new XMLElement('entry');
			$name = '';
			$email = '';
			foreach($entry_data as $field_id => $data){
				if(in_array($field_id, $field_ids)){
					$field = $entryManager->fieldManager->fetch($field_id);
					$field->appendFormattedElement($element, $data);
				}
				if($field_id == $email_field_id){
					$email = $data['value'];
				}
			}
			$name = trim($xsltproc->process($element->generate(), $this->nameXslt));
			$return[] = array(
				'id'	=> $entry->get('id'),
				'email' => $email,
				'name'	=> $name,
				'valid' => preg_match($validators['email'], $email)?true:false
			);
		}
		return $return;
	}

	/**
	 * Fetch raw recipient data.
	 * 
	 * Usage of the getSlice function, which also parses the XSLT for the name
	 * and checks the email is recommended. This function is here mainly for
	 * internal reasons.
	 * 
	 * Be advised, this function returns an array of entry objects.
	 *
	 * @todo bugtesting and error handling
	 * @return array
	 */
	public function grab($newsletter_id = NULL){
		$where_and_joins = $this->getWhereJoinsAndGroup();
		$entryManager = new EntryManager($this->_Parent);
		
		$entries = $entryManager->fetchByPage(
			($this->dsParamSTARTPAGE > 0 ? $this->dsParamSTARTPAGE : 1),
			$this->getSource(),
			($this->dsParamLIMIT >= 0 ? $this->dsParamLIMIT : NULL),
			$where_and_joins['where'],
			$where_and_joins['joins'],
			false,
			false,
			true,
			array_merge(array($this->emailField), $this->nameFields) 
		);
		return $entries;
	}

	/**
	 * Fetch number of recipients
	 *
	 * @return int
	 */
	public function getCount(){
		// To get the exact count for the newsletter requires a very slow query.
		// This value is not used anywhere, so for performance reasons count will not return anything.
		if($this->newsletter_id !== NULL){
			return -1;
		}
		$entryManager = new EntryManager($this->_Parent);
		// I want the total count, not the remaining count, so I do not want the additional joins.
		$newsletter_id = $this->newsletter_id;
		$this->newsletter_id = null;
		$where_and_joins = $this->getWhereJoinsAndGroup();
		$count = $entryManager->fetchCount($this->getSource(), $where_and_joins['where'], $where_and_joins['joins'], true);
		// Saving the newsletter back, so nobody will ever notice this hack.
		$this->newsletter_id = $newsletter_id;
		return $count;
	}

	/**
	 * Get where and join information to build a query.
	 * 
	 * The information returned by this function can be used in the
	 * fetch() methods of the EntryManager class. If you only need
	 * to fetch data the getSlice function is recommended.
	 *
	 * @return array
	 */
	public function getWhereJoinsAndGroup(){
		$where = null;
		$joins = null;
		$entryManager = new EntryManager($this->_Parent);
		if(is_array($this->dsParamFILTERS) && !empty($this->dsParamFILTERS)){
			foreach($this->dsParamFILTERS as $field_id => $filter){

				if((is_array($filter) && empty($filter)) || trim($filter) == '') continue;

				if(!is_array($filter)){
					$filter_type = $this->__determineFilterType($filter);

					$value = preg_split('/'.($filter_type == DS_FILTER_AND ? '\+' : '(?<!\\\\),').'\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);
					$value = array_map('trim', $value);

					$value = array_map(array('Datasource', 'removeEscapedCommas'), $value);
				}

				else $value = $filter;

				if(!isset($fieldPool[$field_id]) || !is_object($fieldPool[$field_id]))
					$fieldPool[$field_id] =& $entryManager->fieldManager->fetch($field_id);

				if($field_id != 'id' && $field_id != 'system:date' && !($fieldPool[$field_id] instanceof Field)){
					throw new Exception(
						__(
							'Error creating field object with id %1$d, for filtering in data source "%2$s". Check this field exists.',
							array($field_id, $this->dsParamROOTELEMENT)
						)
					);
				}

				if($field_id == 'id') {
					$where = " AND `e`.id IN ('".implode("', '", $value)."') ";
				}
				else if($field_id == 'system:date') {
					require_once(TOOLKIT . '/fields/field.date.php');
					$date = new fieldDate(Frontend::instance());

					// Create an empty string, we don't care about the Joins, we just want the WHERE clause.
					$empty = "";
					$date->buildDSRetrievalSQL($value, $empty, $where, ($filter_type == DS_FILTER_AND ? true : false));

					$where = preg_replace('/`t\d+`.value/', '`e`.creation_date', $where);
				}
				else{
					if(!$fieldPool[$field_id]->buildDSRetrievalSQL($value, $joins, $where, ($filter_type == DS_FILTER_AND ? true : false))){ $this->_force_empty_result = true; return; }
					if(!$group) $group = $fieldPool[$field_id]->requiresSQLGrouping();
				}
			}
		}

		$where .= ' AND `f`.`value` IS NOT NULL';
		
		$joins .= 'LEFT JOIN (
				SELECT `d`.`entry_id` , `d`.value
				FROM tbl_entries_data_2 AS `d`';

		if($this->newsletter_id !== NULL){
			$where .= ' GROUP BY `f`.`value`';
			$joins .= 'LEFT OUTER JOIN tbl_email_newsletters_sent_'.$this->newsletter_id.' AS `n` ON `d`.`value` = `n`.`email`
						WHERE `n`.`email` IS NULL ORDER BY `d`.`entry_id` '.($this->dsParamSTARTPAGE > 0 ? '  LIMIT ' . $this->dsParamSTARTPAGE * $this->dsParamLIMIT * 2:'');
		}
		else{
			$joins .= 'GROUP BY `d`.`value`';
		}

		$joins .= ') AS `f` ON `e`.`id` = `f`.`entry_id`';

		return array(
			'where' => $where,
			'joins'	=> $joins
		);
	}
	
	public function getProperties(){
		$properties = array(
			'email' => $this->emailField,
			'name' => array(
				'fields' => $this->nameFields,
				'xslt' 	=> $this->nameXslt
			)
		);
		return array_merge(parent::getProperties(), $properties);
	}
}