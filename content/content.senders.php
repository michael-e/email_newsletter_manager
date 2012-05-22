<?php

if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");
if(!defined('ENVIEWS')) define('ENVIEWS', ENMDIR . "/content/templates");

if(!class_exists('ExtensionPage')){
	require_once(ENMDIR . '/lib/class.extensionpage.php');
}

require_once(TOOLKIT . '/class.xsltprocess.php');
require_once(TOOLKIT . '/class.emailgatewaymanager.php');
require_once(ENMDIR . '/lib/class.sendermanager.php');
require_once(ENMDIR . '/lib/class.emailnewslettermanager.php');

Class contentExtensionemail_newsletter_managersenders extends ExtensionPage{

	protected $_type;
	protected $_function;

	protected $_XSLTProc;
	protected $_XML;

	function __construct(){
		$this->_XSLTProc = new XsltProcess();
		$this->_XML = new XMLElement("data");
		$this->viewDir = ENVIEWS . '/senders';
		parent::__construct(Symphony::Engine());

	}

	function __viewIndex(){
		$this->setPageType('index');
		$this->setTitle(__("Symphony - Email Senders"));
		$results = SenderManager::listAll();
		$senders = new XMLElement('senders');
		foreach($results as $result){
			$entry = new XMLElement('entry');
			General::array_to_xml($entry, $result);
			$senders->appendChild($entry);
		}
		$this->_XML->appendChild($senders);
	}

	function __viewNew(){
		$this->_context[1] = 'New';
		$this->_useTemplate = 'viewEdit';
		$this->__viewEdit(true);
	}

	function __viewEdit($new = false){
		$this->setPageType('form');
		if($this->_context[2] == 'saved' || $this->_context[3] == 'saved'){
			$this->pageAlert(
				__(
					__('Email Sender updated at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Senders</a>'),
					array(
						DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
						SYMPHONY_URL . '/extension/email_newsletter_manager/senders/new/',
						SYMPHONY_URL . '/extension/email_newsletter_manager/senders/',
					)
				),
				Alert::SUCCESS
			);
		}

		$senders = new XMLElement('senders');

		if(!$new){
			$sender = SenderManager::create($this->_context[1]);

			// Make sure the POSTED values are always shown when present.
			// This will make sure the form is always up-to-date, even where there are errors.
			if(!empty($_POST['fields']) && !empty($_POST['settings'])){
				$posted_array = $_POST['fields'];
				$posted_array[$_POST['settings']['gateway']] = $_POST['settings']['email_' . $_POST['settings']['gateway']];
			}
			$about = (empty($_POST['fields']) && empty($_POST['settings']))?(array)$sender->about():$posted_array;
			$about['handle'] = Lang::createHandle($about['name'], 225, '-');
			$entry = new XMLElement('entry');
			General::array_to_xml($entry, $about);
			$senders->appendChild($entry);
		}

		$el_gateways = new XMLElement('gateways');
		$emailGatewayManager = new EmailGatewayManager($this->_Parent);
		$gateways = $emailGatewayManager->listAll();
		foreach($gateways as $gateway){
			// to be removed in later versions. Right now only smtp and sendmail are supported.
			if(in_array($gateway['handle'], array('smtp', 'sendmail', 'amazon_ses'))){
				$gw = $emailGatewayManager->create($gateway['handle']);
				if(!empty($about[$gateway['handle']])){
					$config = $about[$gateway['handle']];
					if($gateway['handle'] == 'smtp'){
						$gw->setFrom($config['from_address'], $config['from_name']);
						$gw->setHost($config['host']);
						$gw->setSecure($config['secure']);
						$gw->setPort($config['port']);
						$gw->setAuth($config['auth']);
						$gw->setUser($config['username']);
						$gw->setPass($config['password']);
					}
					if($gateway['handle'] == 'amazon_ses'){
						$gw->setFrom($config['from_address'], $config['from_name']);
						$gw->setAwsKey($config['aws_key']);
						$gw->setAwsSecretKey($config['aws_secret_key']);
						$gw->setFallback($config['fallback']);
						$gw->setReturnPath($config['return_path']);
					}
					if($gateway['handle'] == 'sendmail'){
						$gw->setFrom($config['from_address'], $config['from_name']);
					}
				}
				$entry = new XMLElement('entry');
				General::array_to_xml($entry, $gateway);
				$config_panel = new XMLElement('config_panel');
				$config_panel->appendChild($gw->getPreferencesPane());
				$entry->appendChild($config_panel);
				$el_gateways->appendChild($entry);
			}
		}
		$senders->appendChild($el_gateways);

		$this->_XML->appendChild($senders);
	}

	function __actionIndex(){
		if($_POST['with-selected'] == 'delete'){
			foreach((array)$_POST['items'] as $item=>$status){
				SenderManager::delete($item);
			}
		}
	}

	function __actionEdit($new = false){
		$fields = array_merge($_POST['fields'], $_POST['settings']);

		try{
			$result = SenderManager::create($this->_context[1]);
			$fields['additional_headers'] = $result->additional_headers;
		}
		catch(Exception $e){
		}
		if(empty($result) && !$new){
			redirect(SYMPHONY_URL . '/extension/email_newsletter_manager/senders/');
			return false;
		}

		if(isset($_POST['action']['delete'])){
			if(SenderManager::delete($this->_context[1])){
				redirect(SYMPHONY_URL . '/extension/email_newsletter_manager/senders/');
				return;
			}
			else{
				$this->pageAlert(
					__('Could not delete: ' . Symphony::Database()->getLastError()),
					Alert::ERROR
				);
				return true;
			}
		}

		$errors = new XMLElement('errors');
		require_once(TOOLKIT . '/util.validators.php');
		if(empty($fields['name'])){
			$errors->appendChild(new XMLElement('name', __('This field can not be empty.')));
		}
		elseif(strlen(Lang::createHandle($fields['name'])) == 0){
			$errors->appendChild(new XMLElement('name', __('This field must at least contain a number or a letter')));
		}
		else{
			try{
				if(SenderManager::save(str_replace('_', '-', $this->_context[1]), $fields)){
					redirect(SYMPHONY_URL . '/extension/email_newsletter_manager/senders/edit/' . Lang::createHandle($fields['name'], 225, '_') . '/saved');
					return true;
				}
			}
			catch(Exception $e){
				$this->pageAlert(__('Could not save: ' . $e->getMessage()),Alert::ERROR);
			}
		}
		$this->_XML->appendChild($errors);
	}

	function __actionNew(){
		return $this->__actionEdit(true);
	}

	function view(){
		$context = new XMLElement('context');
		General::array_to_xml($context, $this->_context);
		$this->_XML->appendChild($context);
		parent::view();
	}

	function action(){
		if($this->_context[2] == 'saved'){
			$this->_context[2] = NULL;
		}
		$fields = new XMLElement('fields');
		General::array_to_xml($fields, (array)$_POST['fields']);
		$this->_XML->appendChild($fields);
		parent::action();
	}

}