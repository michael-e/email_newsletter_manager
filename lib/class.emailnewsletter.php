<?php

if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");

require_once(ENMDIR . '/lib/class.recipientgroupmanager.php');
require_once(ENMDIR . '/lib/class.sendermanager.php');

require_once(EXTENSIONS . '/email_template_manager/lib/class.emailtemplatemanager.php');

class EmailNewsletterException extends Exception{
}

class EmailNewsletter{

	public $limit = 10;

	protected $_id;
	protected $_pid;

	protected $_template;
	protected $_sender;
	protected $_recipientgroups = array();

	public function __construct($id){
		$this->_id = $id;
		$this->getSender();
		$this->getRecipientGroups();
		$this->getTemplate();
	}

	public function getId(){
		return $this->_id;
	}

	public function getPid(){
		if(empty($this->_pid)){
			// get PID
		}
		return $this->_pid;
	}

	public function start(){
	}

	public function pause(){
	}

	public function stop(){
	}

	public function sendEmail($pid){
		if($this->getPid() != $pid){
			throw new EmailNewsletterException('Incorrect PID used. This usually means there is more than one process running. Aborting.');
		}
		$recipients = $this->_getRecipients($this->limit);
		$template = $this->getTemplate();
		foreach($recipients as $recipient){
			try{
				$template->recipients = '"'.$recipient['name'] . '" <' . $recipient['email'] . '>';
				$template->addParams(array('etm-recipient' => $recipient['email']));

				$template->reply_to_name = 'need_to_add_logic!';
				$template->addParams(array('etm-reply-to-name' => 'need_to_add_logic!'));

				$template->reply_to_email = 'TODO@need_to_add_logic.com';
				$template->addParams(array('etm-reply-to-email' => 'TODO@need_to_add_logic.com'));

				$email = Email::create();

				$xml = $template->processDatasources();
				$template->setXML($xml->generate());

				$content = $template->render();

				if(!empty($content['subject'])){
					$email->subject = $content['subject'];
				}
				else{
					throw new EmailTemplateException("Can not send emails without a subject");
				}

				if(isset($content['reply-to-name'])){
					$email->reply_to_name = $content['reply-to-name'];
				}

				if(isset($content['reply-to-email-address'])){
					$email->reply_to_email_address = $content['reply-to-email-address'];
				}

				if(isset($content['plain']))
					$email->text_plain = $content['plain'];
				if(isset($content['html']))
					$email->text_html = $content['html'];

				require_once(TOOLKIT . '/util.validators.php');
				if(General::validateString($recipient['email'], $validators['email'])){
					$email->recipients = array($recipient['name'] => $recipient['email']);
				}
				else{
					throw new EmailTemplateException("Email address invalid: $recipient['email']");
				}

				$email->send();
				
				$this->_markRecipient($recipient['email'], 'sent');
			}
			catch(EmailTemplateException $e){
				Symphony::$Log->pushToLog(__('Email Newsletter Manager: ') . $e->getMessage(), null, true);
				$this->_markRecipient($recipient['email'], 'failed');
				continue;
			}
		}
	}

	public function getRecipientGroups(){
		$gr = array();
		$groups = Symphony::Database()->fetch('SELECT groups from tbl_email_newsletters where id = \' . '$this->getId() .'\'';
		$groups_arr = array_map('trim', explode(', ', $groups));
		foreach($groups_arr as $group){
			try{
				$gr[] = RecipientGroupManager::create($group);
			}
			catch(Exception $e){
			}
		}
		return $gr;
	}

	public function getSender(){
		$sender = Symphony::Database()->fetch('SELECT sender from tbl_email_newsletters where id = \' . '$this->getId() .'\'';
		try{
			$sndr = SenderManager::create($sender);
		}
		catch(Exception $e){
		}
		return $sndr;
	}
	
	public function getTemplate(){
		$tmpl = Symphony::Database()->fetch('SELECT template from tbl_email_newsletters where id = \' . '$this->getId() .'\'';
		try{
			$template = EmailTemplateManager::create($tmpl);
		}
		catch(Exception $e){
		}
		return $template;
	}

	protected function _getRecipients($limit = 10){
	}

	protected function _markRecipient($recipient, $status = 'sent'){
		return Symphony::Database()->insert(array('email'=>$recipient, 'result'=>$status), 'tbl_email_newsletters_sent_' . $this->getId());
	}

	protected function _markRecipientGroup($group, $status = 'sent'){
		$groups = $this->_getCompletedRecipientGroups();
		$completed = array_merge(explode(', ', $groups), array($group));
		return Symphony::Database()->update(array('completed_recipients'=>implode(', ', $completed)), 'tbl_email_newsletters', 'id = ' . $this->getId());
	}

	public function setRecipientGroups($recipients){
		if(!array($recipients)){
			$recipients = array($recipients);
		}
		return Symphony::Database()->update(array('recipients', implode(', ', $recipients)), 'tbl_email_newsletters', 'id = \'' . $this->getId() . '\'');
	}
	
	public function setSender($sender){
		return Symphony::Database()->update(array('sender', $sender, 'tbl_email_newsletters', 'id = \'' . $this->getId() . '\'');
	}
	
	public function setTemplate($template){
		return Symphony::Database()->update(array('sender', $template, 'tbl_email_newsletters', 'id = \'' . $this->getId() . '\'');
	}
	
	protected function generatePid(){
		return uniqueid();
	}
}