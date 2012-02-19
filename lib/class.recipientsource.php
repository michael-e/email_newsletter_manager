<?php

require_once(TOOLKIT . '/class.datasource.php');

Class RecipientSource extends DataSource{

	// Used to filter out addresses that have been sent to already.
	public $newsletter_id;

	// Fields taken from datasource. Overwrite these in your group file.
	public $dsParamFILTERS;
	public $dsParamLIMIT = '10';
	public $dsParamSTARTPAGE = '1';
	
	// Properties.
	protected $_count = null;
	protected $_param_pool = array();
	protected $_XSLTProc;
	protected $_where;
	protected $_joins;
	
	public function __construct(&$parent, $env = array(), $process_params=true, $param_pool = array()){
		parent::__construct($parent, (array)$env, $process_params);
		$this->_dependencies = array();
		$this->_param_pool = $param_pool;
		$this->_XSLTProc = new XsltProcess();
	}

	public function getCount(){
	}

	public function getSlice($page = 1, $count = 10){
	}

	public function grab(&$param_pool=NULL){
	}

	public function getHandle(){
		$about = $this->about();
		return Lang::createHandle($about['name'], 255, '_');
	}

	public function getProperties(){
		return array(
			'source' => $this->getSource(),
			'filters' => $this->dsParamFILTERS
		);
	}
}
