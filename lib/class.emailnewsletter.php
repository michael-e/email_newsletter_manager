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
	protected $_pauth;

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

	public function getPId(){
		if(empty($this->_pid)){
			$pid = Symphony::Database()->fetchCol('pid','SELECT pid from tbl_email_newsletters where id = \'' . $this->getId() .'\'');
			$this->_pid = $pid[0];
		}
		return $this->_pid;
	}

	public function getPAuth(){
		if(empty($this->_pauth)){
			$auth = Symphony::Database()->fetchCol('pauth','SELECT pauth from tbl_email_newsletters where id = \'' . $this->getId() .'\'');
			$this->_pauth = $auth[0];
		}
		return $this->_pauth;
	}

	public function start(){
		$this->generatePAuth();
		Symphony::Database()->query("CREATE TABLE IF NOT EXISTS `tbl_email_newsletters_sent_". $this->getId() . "` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `email` varchar(255),
		  `result` varchar(255),
		  PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
	}

	public function pause(){
	}

	public function stop(){
	}

	public function sendBatch($pauth){
		if($this->getPAuth() != $pauth){
			throw new EmailNewsletterException('Incorrect Process Auth used. This usually means there is more than one process running. Aborting.');
		}
		$recipients = $this->_getRecipients($this->limit);
		if(count($recipients) == 0){
			return false;
		}
		$template = $this->getTemplate();
		foreach($recipients as $recipient){
			try{
				$template->recipients = '"'.$recipient['name'] . '" <' . $recipient['email'] . '>';
				$template->addParams(array('etm-recipient' => $recipient['email']));

				$template->reply_to_name = 'need_to_add_logic!';
				$template->addParams(array('etm-reply-to-name' => 'need_to_add_logic!'));

				$template->reply_to_email = 'TODO@need_to_add_logic.com';
				$template->addParams(array('etm-reply-to-email' => 'TODO@need_to_add_logic.com'));

				// TODO: add email sender preferences
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
					throw new EmailTemplateException("Email address invalid: ".$recipient['email']);
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

	public function getRecipientGroups($filter_complete = false){
		$gr = array();
		$groups = Symphony::Database()->fetch('SELECT recipients, completed_recipients from tbl_email_newsletters where id = \'' . $this->getId() .'\'');
		$groups_arr = array_map('trim', explode(', ', $groups[0]['recipients']));
		foreach($groups_arr as $group){
			if(!in_array($group, array_map('trim', explode(', ', $groups[0]['completed_recipients']))) || $filter_complete == false){ 
				try{
					$grp = RecipientGroupManager::create($group);
					$grp->newsletter_id = $this->getId();
					$gr[] = $grp;
				}
				catch(Exception $e){
				}
			}
		}
		return $gr;
	}

	public function getSender(){
		$sender = Symphony::Database()->fetchCol('sender','SELECT sender from tbl_email_newsletters where id = \'' . $this->getId() .'\'');
		try{
			$sndr = SenderManager::create($sender[0]);
		}
		catch(Exception $e){
		}
		return $sndr;
	}
	
	public function getTemplate(){
		$tmpl = Symphony::Database()->fetchCol('template','SELECT template from tbl_email_newsletters where id = \'' . $this->getId() .'\'');
		try{
			$template = EmailTemplateManager::load($tmpl[0]);
		}
		catch(Exception $e){
		}
		return $template;
	}

	protected function _getRecipients($limit = 10){
		$recipientGroups = $this->getRecipientGroups(true);
		$recipients = array();
		foreach($recipientGroups as $group){
			$group->dsParamLIMIT = $limit - count($recipients);
			$rcpts = $group->getSlice();
			$recipients = array_merge($recipients, (array)$rcpts['records']);
			if(count($recipients) >= $limit){
				break 2;
			}
			$this->_markRecipientGroup($group);
		}
		return $recipients;
	}

	protected function _markRecipient($recipient, $status = 'sent'){
		Symphony::Database()->query('UPDATE `tbl_email_newsletters` SET sent = sent + ' . ($status == 'sent'?1:0) . ', failed = failed + ' . ($status == 'failed'?1:0) . ', total = total + 1 WHERE id = \'' . $this->getId() . '\'');
		return Symphony::Database()->insert(array('email'=>$recipient, 'result'=>$status), 'tbl_email_newsletters_sent_' . $this->getId());
	}

	protected function _markRecipientGroup($group){
		$groups = $this->getCompletedRecipientGroups();
		//lots of complicated stuff here. Because I do not assume this function will be called a lot (1000s of times), I have used quite a lot of filters to keep the completed_recipients output clean.
		//what happens here is that the new group is merged, all empty values are cleared and all duplicates are removed. This should result in the cleanest possible value.
		$completed = array_filter(array_unique(array_merge(array_map('trim', explode(', ', $groups)), array(is_object($group)?$group->dsParamROOTELEMENT:$group))), 'strlen');
		return Symphony::Database()->update(array('completed_recipients'=>implode(', ', $completed)), 'tbl_email_newsletters', 'id = ' . $this->getId());
	}

	public function getCompletedRecipientGroups(){
		$groups = Symphony::Database()->fetchCol('completed_recipients','SELECT completed_recipients from tbl_email_newsletters where id = \'' . $this->getId() .'\'');
		return $groups[0];
	}

	public function setRecipientGroups($recipients){
		if(!array($recipients)){
			$recipients = array($recipients);
		}
		foreach($recipients as $index => $recipient){
			if(RecipientGroupManager::__getClassPath($recipient) == false){
				unset($recipients[$index]);
				throw new EmailNewsletterException('Can not add `' . $recipient . '` to the newsletter properties, because the group can not be found.');
			}
		}
		return Symphony::Database()->update(array('recipients' => implode(', ', $recipients)), 'tbl_email_newsletters', 'id = \'' . $this->getId() . '\'');
	}
	
	public function setSender($sender){
		return Symphony::Database()->update(array('sender' => $sender), 'tbl_email_newsletters', 'id = ' . $this->getId());
	}
	
	public function setTemplate($template){
		return Symphony::Database()->update(array('sender' => $template), 'tbl_email_newsletters', 'id = \'' . $this->getId() . '\'');
	}
	
	protected function generatePAuth(){
		$id = uniqid();
		return Symphony::Database()->update(array('pauth' => $id), 'tbl_email_newsletters', 'id = \'' . $this->getId() . '\'');
	}
}