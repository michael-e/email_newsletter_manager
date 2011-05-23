<?php

if(!defined('ENDIR')) define('ENDIR', EXTENSIONS . "/email_newsletters");
if(!defined('ENVIEWS')) define('ENVIEWS', ENDIR . "/content/templates");

if(!class_exists('ExtensionPage')){
	require_once(ENDIR . '/lib/class.extensionpage.php');
}

require_once(TOOLKIT . '/class.xsltprocess.php');

Class contentExtensionemail_newslettersrecipientgroups extends ExtensionPage{

	function __construct(){
		$this->_XSLTProc = new XsltProcess();
		$this->_XML = new XMLElement("data");
		$this->viewDir = ENVIEWS . '/recipientgroups';
		parent::__construct(Symphony::Engine());
	}

	function __viewIndex(){
		$this->setPageType('index');
		$this->setTitle(__("Symphony - Email Recipients"));
		$results = Symphony::Database()->fetch('SELECT DISTINCT groups.name, groups.id, count(params.name) as params from `tbl_email_newsletters_recipientgroups` AS groups LEFT JOIN `tbl_email_newsletters_recipientgroups_params` as params ON groups.id = params.recipientgroup_id GROUP BY groups.id ORDER BY groups.name ASC');
		$senders = new XMLElement('recipientgroups');
		foreach($results as $result){
			$entry = new XMLElement('entry');
			General::array_to_xml($entry, $result);
			$senders->appendChild($entry);
		}
		$this->_XML->appendChild($senders);
	}

	function __actionIndex(){
		if($_POST['with-selected'] == 'delete'){
			foreach((array)$_POST['items'] as $item=>$status){
				Symphony::Database()->query('DELETE FROM `tbl_email_newsletters_recipientgroups` where `id` = "' . Symphony::Database()->cleanValue($item) . '" LIMIT 1');
			}
		}
	}

	function __viewEdit($new = false){
		$this->setPageType('form');
		$this->addScriptToHead(URL . '/extensions/email_newsletters/assets/admin.js', 140);
		$this->addStylesheetToHead(URL . '/extensions/email_newsletters/assets/admin.css', 'screen', 103);
		$recipientgroup = new XMLElement('recipientgroup');

		if($this->_context[2] == 'saved' || $this->_context[3] == 'saved'){
			$this->pageAlert(
				__(
					__('Email Recipient updated at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Recipient Groups</a>'),
					array(
						DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
						SYMPHONY_URL . '/extension/email_newsletters/recipientgroups/new/',
						SYMPHONY_URL . '/extension/email_newsletters/recipientgroups/',
					)
				),
				Alert::SUCCESS
			);
		}

		if($new == false){
			$group = Symphony::Database()->fetch('SELECT * FROM `tbl_email_newsletters_recipientgroups` WHERE `id` = "' . Symphony::Database()->cleanValue($this->_context[1]) . '"');
			if(!empty($group)){
				$entry = new XMLElement('entry');
				General::array_to_xml($entry, $group[0]);
				$params = Symphony::Database()->fetch('SELECT * FROM `tbl_email_newsletters_recipientgroups_params` WHERE `recipientgroup_id` = "' . Symphony::Database()->cleanValue($this->_context[1]) . '"');
				$parameters = new XMLElement('params');
				General::array_to_xml($parameters, $params);
				$entry->appendChild($parameters);
				$recipientgroup->appendChild($entry);
				$this->_XML->appendChild($recipientgroup);
			}
			else{
				Administration::instance()->errorPageNotFound();
			}
		}
	}
	
	function __actionEdit($new = false){
		$fields = $_POST['fields'];

		$errors = new XMLElement('errors');
		if(isset($_POST['action']['delete'])){
			if(Symphony::Database()->query('DELETE FROM `tbl_email_newsletters_recipientgroups` where `id` = "' . Symphony::Database()->cleanValue($this->_context[1]) . '" LIMIT 1')){
				Symphony::Database()->delete('tbl_email_newsletters_recipientgroups_params', 'recipientgroup_id = "' . Symphony::Database()->cleanValue($this->_context[1]) . '"');
				redirect(SYMPHONY_URL . '/extension/email_newsletters/recipientgroups/');
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
		if(!empty($fields['name']) && !empty($fields['recipients'])){
			if($new){
				Symphony::Database()->insert(array('name'=>$fields['name'], 'recipients'=>$fields['recipients'], 'id'=>$this->_context[1]), 'tbl_email_newsletters_recipientgroups', true);
				$id = Symphony::Database()->getInsertId();
			}
			else{
				$id = Symphony::Database()->cleanValue($this->_context[1]);
				Symphony::Database()->update(array('name'=>$fields['name'], 'recipients'=>$fields['recipients']), 'tbl_email_newsletters_recipientgroups', 'id = "' . $id . '"');
			}
			Symphony::Database()->delete('tbl_email_newsletters_recipientgroups_params', '`recipientgroup_id` = "' . $id . '"');
			foreach((array)$fields['params'] as $param){
				Symphony::Database()->insert(array('name'=>$param['name'], 'value'=>$param['value'], 'recipientgroup_id'=>$id, 'id'=>$param['id']), 'tbl_email_newsletters_recipientgroups_params', true);
			}
			redirect(SYMPHONY_URL . '/extension/email_newsletters/recipientgroups/edit/' . $id . '/saved');
			return true;
		}
		if(empty($fields['name'])){
			$errors->appendChild(new XMLElement('name', __('This field can not be empty.')));
		}
		if(empty($fields['recipients'])){
			$errors->appendChild(new XMLElement('recipients', __('This field can not be empty.')));
		}
		$this->_XML->appendChild($errors);
	}
	
	function __actionNew(){
		$this->__actionEdit(true);
	}
	
	function __viewNew(){
		$this->_context[1] = 'New';
		$this->_useTemplate = 'viewEdit';
		$this->__viewEdit(true);
	}
}