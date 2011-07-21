<?php

require_once('class.recipientsource.php');

Class RecipientSourceStatic extends RecipientSource{
	
	public $dsParamLIMIT = 10;
	public $dsParamSTARTPAGE = 1;
	protected $_emailValidator;
	protected $_tempTable;
	
	public function __construct(){
		require_once(TOOLKIT . '/util.validators.php');
		$this->_emailValidator = $validators['email'];
		parent::__construct($this->_Parent);
	}
	
	/**
	 * Fetch recipient data, and include useful data.
	 *
	 * This function is used internally to fetch the recipient data.
	 * It is the preferred way of getting data out of the system,
	 * because it will also return pagination and other useful data.
	 * 
	 * @return array
	 */
	public function getSlice(){
		$recipients = $this->grab();
		$return['total-entries'] = $this->getCount();
		$pages = ((int)$return['total-entries']/(int)$this->dsParamLIMIT);
		$return['total-pages'] = (int)round($pages);
		$return['remaining-pages'] = max(0, (int)$return['total-pages'] - (int)$this->dsParamSTARTPAGE);
		$return['remaining-entries'] = max(0, ((int)$return['total-entries'] - ((int)$this->dsParamSTARTPAGE * (int)$this->dsParamLIMIT)));
		$return['entries-per-page'] = $this->dsParamLIMIT;
		$return['start'] = (((int)$this->dsParamSTARTPAGE - 1) * (int)$this->dsParamLIMIT) + 1;
		$return['current-page'] = (int)$this->dsParamSTARTPAGE;
		return array_merge($return, $recipients);
	}

	/**
	 * Fetch recipient data.
	 * 
	 * @return array
	 */
	public function grab(){
		$this->_createTempTable();
		
		if($this->newsletter_id !== NULL){
			$where .= ' GROUP BY `f`.`email`';
			$joins .= ' LEFT OUTER JOIN tbl_email_newsletters_sent_'.$this->newsletter_id.' AS `n` ON `d`.`email` = `n`.`email`
						WHERE `n`.`email` IS NULL ORDER BY `d`.`id` '.($this->dsParamSTARTPAGE > 0 ? '  LIMIT ' . $this->dsParamSTARTPAGE * $this->dsParamLIMIT * 10:'');
		}
		else{
			$joins .= 'GROUP BY `d`.`email`';
		}
		
		$limit = ' LIMIT ' . ($this->dsParamSTARTPAGE - 1) * $this->dsParamLIMIT . ', ' . $this->dsParamLIMIT;
		
		$rows = Symphony::Database()->fetch('SELECT * FROM (SELECT `d`.`id`, `d`.`name`, `d`.`email`, `d`.`valid` from ' . $this->_tempTable . ' as `d` ' . $joins . ') as `f`' . $where . 'ORDER BY `f`.`id`' .  $limit);
		return $rows;
	}

	/**
	 * Fetch number of recipients
	 *
	 * @return int
	 */
	public function getCount(){
		if($this->newsletter_id !== NULL){
			return -1;
		}
		$this->_createTempTable();
		$rows = Symphony::Database()->fetchCol('count','SELECT count(DISTINCT email) as count from ' . $this->_tempTable);
		return $rows[0];
	}
	
	protected function _parseNameAndEmail($string){
		$string = trim($string);
		
		if(strstr($string, '<')){		
			$name = trim(strstr($string, '<', true), "\" \t\n\r\0\x0B");
			$email = trim(strstr($string, '<'), "<> \t\n\r\0\x0B");
		}
		else{
			$email = trim($string, " \t\n\r\0\x0B");
			$name = null;
		}
		return array(
			'name'	=> $name,
			'email' => $email,
			'valid' => preg_match($this->_emailValidator, $email)?true:false
		);
	}
	
	protected function _createTempTable(){
		if($this->_tempTable == NULL){
			$name = 'email_newsletters_static_recipients_' . substr(md5(microtime()), 0, 10);
			if(Symphony::Database()->query('CREATE TEMPORARY TABLE ' . $name . ' (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY ( `id` ), email varchar(255), name varchar(255),`valid` BOOL NOT NULL)')){
				if(count($this->recipients) > 0){
					$rcpts = array_map(array(__CLASS__, '_parseNameAndEmail'), explode(',', $this->recipients));
					foreach($rcpts as $recipient){
						$values[] = '(\'' . $recipient['email'] . '\', \'' . $recipient['name'] . '\', '. ($recipient['valid']?1:0) . ')';
					}
					$value = implode(', ', $values);
					Symphony::Database()->query("INSERT IGNORE INTO " . $name . " (email, name, valid) values " . $value);
				}
				$this->_tempTable = $name;
				return true;
			}
			else{
				throw new Exception(Symphony::Database()->getLastError());
			}
		}
	}
}