<?php

if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");
if(!defined('ENVIEWS')) define('ENVIEWS', ENMDIR . "/content/templates");

if(!class_exists('ExtensionPage')){
	require_once(ENMDIR . '/lib/class.extensionpage.php');
}

require_once(TOOLKIT . '/class.xsltprocess.php');

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
		$results = Symphony::Database()->fetch('SELECT * from `tbl_email_newsletter_manager_senders` ORDER BY name ASC');
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

	function __viewEdit(){
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

		$result = Symphony::Database()->fetch('SELECT * from `tbl_email_newsletter_manager_senders` WHERE id = "' . Symphony::Database()->cleanValue($this->_context[1]) . '"');
		$senders = new XMLElement('senders');
		$entry = new XMLElement('entry');
		General::array_to_xml($entry, (array)$result[0]);
		$senders->appendChild($entry);
		$this->_XML->appendChild($senders);
	}

	function __actionIndex(){
		if($_POST['with-selected'] == 'delete'){
			foreach((array)$_POST['items'] as $item=>$status){
				Symphony::Database()->query('DELETE FROM `tbl_email_newsletter_manager_senders` where `id` = "' . Symphony::Database()->cleanValue($item) . '" LIMIT 1');
			}
		}
	}

	function __actionEdit($new = false){
		$fields = $_POST['fields'];

		$result = Symphony::Database()->fetch('SELECT id FROM `tbl_email_newsletter_manager_senders` where id = "' . Symphony::Database()->cleanValue($this->_context[1]) . '"');
		if(empty($result) && !$new){
			redirect(SYMPHONY_URL . '/extension/email_newsletter_manager/senders/');
			return false;
		}

		if(isset($_POST['action']['delete'])){
			if(Symphony::Database()->query('DELETE FROM `tbl_email_newsletter_manager_senders` where `id` = "' . Symphony::Database()->cleanValue($this->_context[1]) . '" LIMIT 1')){
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
		if(!empty($fields['name']) && !empty($fields['email']) && General::validateString($fields['email'], $validators['email'])){
			unset($fields['id']);
			if(!$new){
				Symphony::Database()->update($fields, 'tbl_email_newsletter_manager_senders', 'id = "' . Symphony::Database()->cleanValue($this->_context[1]) . '"');
			}
			else{
				Symphony::Database()->insert($fields, 'tbl_email_newsletter_manager_senders', false);
				$this->_context[1] = Symphony::Database()->getInsertId();
			}
			redirect(SYMPHONY_URL . '/extension/email_newsletter_manager/senders/edit/' . Symphony::Database()->cleanValue($this->_context[1]) . '/saved');
		}
		if(empty($fields['name'])){
			$errors->appendChild(new XMLElement('name', __('This field can not be empty.')));
		}
		if(empty($fields['email'])){
			$errors->appendChild(new XMLElement('email', __('This field can not be empty.')));
		}
		if(!General::validateString($fields['email'], $validators['email'])){
			$errors->appendChild(new XMLElement('email', __('This is not a valid email address.')));
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
			$this->_context[2] = null;
		}
		$fields = new XMLElement('fields');
		General::array_to_xml($fields, (array)$_POST['fields']);
		$this->_XML->appendChild($fields);
		parent::action();
	}
	
}