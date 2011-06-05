<?php

require_once(TOOLKIT . '/class.datasource.php');

Class RecipientSource extends DataSource{

	// custom source fields -> section source
	public $emailField = null;
	public $nameFields = Array();
	public $nameXslt = null;
	
	// custom field -> static recipients
	public $recipientList = null;

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
	
	public function __construct(&$parent, $env = array(), $process_params=true, $param_pool = array()){
		parent::__construct($parent, (array)$env, $process_params);
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
			$generated_xml = $xml->generate();
			$name = trim($this->_XSLTProc->process($generated_xml, $this->nameXslt, $this->param_pool));
			$email = trim($this->_XSLTProc->process($generated_xml, $this->emailXslt, $this->param_pool));
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
		$this->dsParamINCLUDEDELEMENTS = array_merge($this->nameFields, array($this->emailField), array('system:pagination'));
		if(is_null($param_pool)){
			$param_pool = $this->_param_pool;
		}
		$result = new XMLElement($this->dsParamROOTELEMENT);
		try{
			switch($this->getSource()){
				case 'authors':
					include(TOOLKIT . '/data-sources/datasource.author.php');
					$entries = $authors;
					$entries['total-entries'] = count($authors);
					break;
				case 'static_recipients':
					break;
				default:
					include(TOOLKIT . '/data-sources/datasource.section.php');
					break;
			}
		}
		catch(Exception $e){
			throw $e;
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
	
	function __processAuthorFilter($field, $filter, $database){
		if(!is_array($filter)){
			$bits = preg_split('/,\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);
			$bits = array_map('trim', $bits);
		}
		else{
			$bits = $filter;
		}
		$sql = "SELECT count(id) as count, `id` FROM `tbl_authors` WHERE `".$field."` IN ('".implode("', '", $bits)."') LIMIT " . ((int)$this->dsParamSTARTPAGE - 1) * (int)$this->dsParamLIMIT . ",  " . ((int)$this->dsParamSTARTPAGE) * (int)$this->dsParamLIMIT;
		$results = $database->fetch($sql);
		return (is_array($authors) && !empty($authors) ? $authors : NULL);
	}
}
