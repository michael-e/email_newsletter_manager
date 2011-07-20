<?php

if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");

require_once(ENMDIR . '/lib/class.recipientgroupmanager.php');
require_once(ENMDIR . '/lib/class.sendermanager.php');

require_once(EXTENSIONS . '/email_template_manager/lib/class.emailtemplatemanager.php');

class EmailNewsletterException extends Exception{
}

class EmailNewsletter{

	protected $_properties;
	
	protected $_template;
	protected $_recipient_group;
	protected $_sender;
	
	protected $_batch = array();

	public function __construct($properties){
		$this->_properties = $properties;

		$tplm = new EmailTemplateManager($this->_Parent);
		$this->_template = $tplm->load($properties['template']);

		$rcptm = new RecipientgroupManager($this->_Parent);
		$groups = array_map('trim', (array)explode(',', $properties['recipients']));
		foreach($groups as $group){
			$this->_recipient_group[] = $rcptm->create($properties['group']);
		}

		$sndrm = new SenderManager($this->_Parent);
		$this->_sender = $sndrm->create($properties['sender']);
	}

	public function start(){
		$this->setFlag('start');
		if(Symphony::Database()->query('CREATE TABLE `sym2`.`sym_email_newsletters_sent_'.Symphony::Database()->cleanValue($this->_properties['id']).'` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`email` VARCHAR( 255 ) NOT NULL ,
			`date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		)')){
			return true;
		}
		else{
			throw new EmailNewsletterException(Symphony::Database()->getLastError());
		}
		
	}

	public function stop(){
		$this->setFlag('stop');
	}

	public function pause(){
		$this->setFlag('pause');
	}

	public function sendNextEmail(){
	}

	public function getFlag(){
	}

	public function getProperties(){
		return $this->_properties;
	}

	protected function setFlag($value){
		if(Symphony::Database()->update(array('flag' => Symphony::Database()->cleanValue($value)), 'tbl_email_newsletters', '`id` = ' . Symphony::Database()->cleanValue($this->_properties['id']))){
			return true;
		}
		else{
			throw new EmailNewsletterException(Symphony::Database()->getLastError());
		}
	}
}