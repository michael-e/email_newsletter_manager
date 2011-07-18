<?php

require_once('class.recipientsource.php');

Class RecipientSourceStatic extends RecipientSource{
	
	public $dsParamLIMIT = 10;
	public $dsParamSTARTPAGE = 1;
	protected $_emailValidator;
	
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
		$recipients = array_map(array(__CLASS__, '_parseNameAndEmail'), array_slice(explode(',', $this->recipients), (((int)$this->dsParamSTARTPAGE - 1) * (int)$this->dsParamLIMIT), (int)$this->dsParamLIMIT));
		return $recipients;
	}

	/**
	 * Fetch number of recipients, DIRTY!
	 *
	 * @return int
	 */
	public function getCount(){
		return count(explode(',', $this->recipients));
	}
	
	protected function _parseNameAndEmail($string){
		$string = trim($string);
		$name = trim(strstr($string, '<', true), '" \t\n\r\0\x0B');
		$email = trim(strstr($string, '<'), '<> \t\n\r\0\x0B');
		return array(
			'name'	=> $name,
			'email' => $email,
			'valid' => preg_match($this->_emailValidator, $email)?true:false
		);
	}
}