<?php

require_once(TOOLKIT . '/class.datasource.php');

Class RecipientSource extends DataSource{

	public $emailField = null;
	public $nameFields = Array();
	public $nameXslt = null;
	public $dsParamLIMIT = '10';
	public $dsParamSTARTPAGE = '1';
	// We are not displaying the results to the end-user, so a 404 page would not make sense.
	public $dsParamREDIRECTONEMPTY = 'no';
	
	protected $_count = null;
	protected $_param_pool = null;
	
	protected $_XSLTProc;

	public function __construct(&$parent, $env=NULL, $process_params=true, $param_pool = NULL){
		parent::__construct($parent, $env, $process_params);
		$this->_dependencies = array();
		$this->_param_pool = $param_pool;
		$this->_XSLTProc = new XsltProcess();
	}

	public function getCount(){
		if(is_null($this->_count)){
			$this->grab();
		}
		return $this->_count;
	}

	// todo
	public function buildList(){
	}

	public function getSlice($page = 1, $count = 10){
		$this->dsParamLIMIT = $count;
		$this->dsParamSTARTPAGE = $page;
		$xml = $this->grab();
		try{
			$generated-xml = $xml->generate();
			$name = trim($this->_XSLTProc->process($generated-xml, $this->nameXslt, $this->param_pool));
			$email = trim($this->_XSLTProc->process($generated-xml, $this->emailXslt, $this->param_pool));
			require_once(TOOLKIT . '/util.validators.php');
			if(!General::validateString($email, $validators['email'])){
				throw new Exception();
			}
		}
		catch(Exception $e){
			// write to log
		}		
	}

	public function grab(&$param_pool=NULL){
		if(!is_array($this->nameFields)){
			$this->nameFields = array($this->nameFields);
		}
		$this->dsParamINCLUDEDELEMENTS = array_merge($this->nameFields, array($this->emailField));
		if(is_null($param_pool)){
			$param_pool = $this->_param_pool;
		}
		$result = new XMLElement($this->dsParamROOTELEMENT);
		try{
			include(TOOLKIT . '/data-sources/datasource.section.php');
		}
		catch(Exception $e){
			$result->appendChild(new XMLElement('error', $e->getMessage()));
			return $result;
		}
		if($this->_force_empty_result){
			$result = $this->emptyXMLSet();
			$this->_count = 0;
		}
		else{
			$this->_count = $entries['total-entries'];
		}
		return $result;
	}	
}
