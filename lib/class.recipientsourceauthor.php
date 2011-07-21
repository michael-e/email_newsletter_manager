<?php

require_once('class.recipientsource.php');

Class RecipientSourceAuthor extends RecipientSource{
	
	public $dsParamLIMIT = 10;
	public $dsParamPAGINATERESULTS = 'yes';
	public $dsParamSTARTPAGE = 1;

	/**
	 * Fetch recipient data.
	 * 
	 * @todo bugtesting and error handling
	 * @return array
	 */
	public function getSlice(){
		$authors = $this->grab();
		$return['total-entries'] = $this->getCount();
		$pages = ((int)$return['total-entries']/(int)$this->dsParamLIMIT);
		$return['total-pages'] = round($pages);
		$return['remaining-pages'] = max(0, (int)$return['total-pages'] - (int)$this->dsParamSTARTPAGE);
		$return['remaining-entries'] = max(0, ((int)$return['total-entries'] - ((int)$this->dsParamSTARTPAGE * (int)$this->dsParamLIMIT)));
		$return['entries-per-page'] = $this->dsParamLIMIT;
		$return['start'] = (((int)$this->dsParamSTARTPAGE - 1) * (int)$this->dsParamLIMIT) + 1;
		$return['current-page'] = (int)$this->dsParamSTARTPAGE;
		
		require_once(TOOLKIT . '/util.validators.php');
		
		foreach($authors as $author){
			$return[] = array(
				'id' => $author->get('id'),
				'name' => $author->get('first_name') . ' ' . $author->get('last_name'),
				'email' => $author->get('email'),
				'valid' => preg_match($validators['email'], $author->get('email'))?true:false
			);
		}
		
		return $return;
	}

	public function grab(){
		$author_ids = $this->_getAuthorIds();
		$authors = AuthorManager::fetchByID(array_values($author_ids), 'id', $this->dsParamORDER, $this->dsParamLIMIT, ($dsParamSTARTPAGE - 1) * $this->dsParamLIMIT);
		return (array)$authors;
	}

	/**
	 * Fetch number of recipients
	 *
	 * @return int
	 */
	public function getCount(){
		if(!is_null($this->newsletter_id)){
			return -1;
		}
		$where_and_joins = $this->_getWhereAndJoins();
		$count = Symphony::Database()->fetchCol('count', "SELECT count(`a`.`id`) as `count` FROM `tbl_authors` as `a` " . $where_and_joins['joins'] . ' WHERE 1 ' . $where_and_joins['where']);
		return $count[0];
	}

	protected function _getAuthorIds(){
		$where_and_joins = $this->_getWhereAndJoins();
		return Symphony::Database()->fetchCol('id', "SELECT `a`.`id` FROM `tbl_authors` as `a` " . $where_and_joins['joins'] . ' WHERE 1 ' . $where_and_joins['where']);
	}

	protected function _getWhereAndJoins(){
		$wheres = array();
		if(is_array($this->dsParamFILTERS) && !empty($this->dsParamFILTERS)){
			foreach($this->dsParamFILTERS as $field => $value){
				if(!is_array($value) && trim($value) == ''){
					continue;
				}
				if(!is_array($value)){
					$bits = preg_split('/,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
					$bits = array_map('trim', $bits);
				}
				else{
					$bits = $value;
				}
				$where .= "AND `".$field."` IN ('".implode("', '", $bits)."')";
			}
		}
		if(!is_null($this->newsletter_id)){
			$joins .= ' LEFT OUTER JOIN `tbl_email_newsletters_sent_' . $this->newsletter_id . '` as `s` ON `s`.`email` = `a`.`email`';
			$where .= 'AND `s`.`email` IS NULL';
		}
		return array(
			'where' => $where,
			'joins' => $joins
		);
	}
}