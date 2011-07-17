<?php

require_once(TOOLKIT . '/class.datasource.php');

Class RecipientSource extends DataSource{

	// custom field -> static recipients
	// public $recipientList = null;

	// fields taken from datasource. Overwrite these in your group file.
	public $dsParamREQUIREDPARAM;
	public $dsParamFILTERS;
	public $dsParamROOTELEMENT;

	// fields taken from datasources. Do not change these.
	public $dsParamLIMIT = '10';
	public $dsParamSTARTPAGE = '1';
	public $dsParamREDIRECTONEMPTY = 'no';
	public $dsParamORDER = 'desc';
	public $dsParamSORT = 'id';
	
	// properties.
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
	
	public function getProperties(){
		return array(
			'section' => $this->getSource(),
			'elements' => $this->dsParamINCLUDEDELEMENTS,
			'filters' => $this->dsParamFILTERS,
			'required_param' => $this->dsParamREQUIREDPARAM,
			'email' => $this->emailField,
			'name' => array(
				'fields' => $this->nameFields,
				'xslt' 	=> $this->nameXslt
			)
		);
	}
}
