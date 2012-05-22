<?php

require_once(TOOLKIT . '/class.datasource.php');


Class RecipientSource extends DataSource{

	// Used to filter out addresses that have been sent to already.
	public $newsletter_id;

	// Fields taken from datasource. Overwrite these in your group file.
	public $dsParamFILTERS;
	public $dsParamLIMIT = '10';
	public $dsParamSTARTPAGE = '1';
	public $_dependencies = array();

	// Properties.
	protected $_count = NULL;
	protected $_param_pool = array();
	protected $_XSLTProc;
	protected $_where;
	protected $_joins;

	public function __construct(&$parent, $env = array(), $process_params=true, $param_pool = array()){
		parent::__construct($parent, (array)$env, $process_params);
		$this->_param_pool = $param_pool;
		$this->_XSLTProc = new XsltProcess();
	}

	public function getCount(){
		$this->processDependencies();
	}

	public function getSlice($page = 1, $count = 10){
	}

	public function grab(&$param_pool=NULL){
		$this->processDependencies();
	}

	public function getHandle(){
		$about = $this->about();
		return Lang::createHandle($about['name'], 255, '-');
	}

	public function getProperties(){
		return array(
			'source' => $this->getSource(),
			'filters' => $this->dsParamFILTERS
		);
	}

	public function processDependencies(array $params = array()) {
		$this->DatasourceManager = new DatasourceManager($this->_Parent);

		$datasources = $this->getDependencies();

		if(!is_array($datasources) || empty($datasources)){
			return;
		}

		$datasources = array_map(create_function('$a', "return str_replace('\$ds-', '', \$a);"), $datasources);
		$datasources = array_map(create_function('$a', "return str_replace('-', '_', \$a);"), $datasources);

		$this->_env['pool'] = $params;
		$pool = $params;
		$dependencies = array();

		foreach ($datasources as $handle) {
			Frontend::instance()->Profiler->seed();

			$pool[$handle] =& $this->DatasourceManager->create($handle, NULL, false);
			$dependencies[$handle] = $pool[$handle]->getDependencies();
		}

		$dsOrder = $this->__findDatasourceOrder($dependencies);

		foreach ($dsOrder as $handle) {
			$ds = $pool[$handle];
			$ds->processParameters(array('env' => &$this->_env, 'param' => &$this->_param));
			$ds->grab($this->_env['pool']);
			unset($ds);
		}
		$this->processParameters(array('env' => $this->_env, 'param' => $this->_param));
	}

	public function __findDatasourceOrder($dependenciesList){
		if(!is_array($dependenciesList) || empty($dependenciesList)) return array();

		$orderedList = array();
		$dsKeyArray = $this->__buildDatasourcePooledParamList(array_keys($dependenciesList));

		## 1. First do a cleanup of each dependency list, removing non-existant DS's and find
		##	the ones that have no dependencies, removing them from the list
		foreach($dependenciesList as $handle => $dependencies){

			$dependenciesList[$handle] = @array_intersect($dsKeyArray, $dependencies);

			if(empty($dependenciesList[$handle])){
				unset($dependenciesList[$handle]);
				$orderedList[] = str_replace('_', '-', $handle);
			}
		}

		## 2. Iterate over the remaining DS's. Find if all their dependencies are
		##	in the $orderedList array. Keep iterating until all DS's are in that list
		##	  or there are circular dependencies (list doesn't change between iterations of the while loop)
		do{

			$last_count = count($dependenciesList);

			foreach($dependenciesList as $handle => $dependencies){
				if(General::in_array_all(array_map(create_function('$a', "return str_replace('\$ds-', '', \$a);"), $dependencies), $orderedList)){
					$orderedList[] = str_replace('_', '-', $handle);
					unset($dependenciesList[$handle]);
				}
			}

		}while(!empty($dependenciesList) && $last_count > count($dependenciesList));

		if(!empty($dependenciesList)) $orderedList = array_merge($orderedList, array_keys($dependenciesList));

		return array_map(create_function('$a', "return str_replace('-', '_', \$a);"), $orderedList);
	}

	public function __buildDatasourcePooledParamList($datasources){
		if(!is_array($datasources) || empty($datasources)) return array();

		$list = array();

		foreach($datasources as $handle){
			$rootelement = str_replace('_', '-', $handle);
			$list[] = '$ds-' . $rootelement;
		}

		return $list;
	}
}
