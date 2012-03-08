<?php

if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");

require_once(ENMDIR . '/lib/class.recipientgroupmanager.php');
require_once(ENMDIR . '/lib/class.sendermanager.php');
require_once(ENMDIR . '/lib/class.emailbackgroundprocess.php');

require_once(EXTENSIONS . '/email_template_manager/lib/class.emailtemplatemanager.php');

class EmailNewsletterException extends Exception{
}

class EmailNewsletter{

	public $limit = 10;
	protected $_completed = array();

	protected $_id;
	protected $_pid;
	protected $_pauth;

	protected $_template;
	protected $_sender;
	protected $_recipientgroups = array();

	public function __construct($id){
		$this->_id = $id;
		$this->_sender = $this->getSender();
		$this->_recipientgroups = $this->getRecipientGroups();
		$this->_template = $this->getTemplate();
		if(is_a($this->_sender, 'NewsletterSender')){
			$sender_about = $this->_sender->about();
			$this->limit = $sender_about['throttle-emails'];
		}
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

	public function setPId($pid){
		if(!empty($pid)){
			return Symphony::Database()->update(array('pid' => $pid), 'tbl_email_newsletters','id = \'' . $this->getId() . '\'');
		}
	}

	public function getPAuth(){
		if(empty($this->_pauth)){
			$auth = Symphony::Database()->fetchCol('pauth','SELECT pauth from tbl_email_newsletters where id = \'' . $this->getId() .'\'');
			$this->_pauth = $auth[0];
		}
		return $this->_pauth;
	}

	public function start(){
		if($this->getStatus() == 'stopped'){
			throw new EmailNewsletterException('Can not restart a stopped process. Please start a new process if you need to send again.');
		}
		$this->generatePAuth();
		Symphony::Database()->query("CREATE TABLE IF NOT EXISTS `tbl_tmp_email_newsletters_sent_". $this->getId() . "` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `email` varchar(255),
		  `result` varchar(255),
		  PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
		Symphony::Database()->query('DELETE FROM `tbl_tmp_email_newsletters_sent_'. $this->getId() . '` WHERE `result` = \'idle\'');
		$this->setStatus('sending');
		Symphony::Database()->update(array('started_on' => date('Y-m-d H:i:s', time())), 'tbl_email_newsletters', 'id = ' . $this->getId());
		EmailBackgroundProcess::spawnProcess($this->getId(), $this->getPAuth());
	}

	public function pause(){
		EmailBackgroundProcess::killProcess($this->getPId());
		$this->setStatus('paused');
		return true;
	}

	public function stop(){
		EmailBackgroundProcess::killProcess($this->getPId());
		Symphony::Database()->query('DROP TABLE IF EXISTS `tbl_tmp_email_newsletters_sent_'. $this->getId() . '`');
		$this->setStatus('stopped');
		return true;
	}

	public function sendBatch($pauth){
		if($this->getPAuth() != $pauth){
			$this->setStatus('error');
			throw new EmailNewsletterException('Incorrect Process Auth used. This usually means there is more than one process running. Aborting.');
		}
		$recipients = $this->_getRecipients($this->limit);
		
		if(count($recipients) == 0){
			Symphony::Database()->query('DROP TABLE IF EXISTS `tbl_tmp_email_newsletters_sent_'. $this->getId() . '`');
			$this->setStatus('completed');
			return 'completed';
		}

		foreach($recipients as $recipient){
			try{
				$template = $this->getTemplate();
				$sender = $this->getSender();
				$about = $sender->about();

				if(is_array($about['smtp'])){
					$email = Email::create('smtp');
					$email->setSenderName($about['smtp']['from_name']);
					$email->setSenderEmailAddress($about['smtp']['from_address']);
					$email->setHost($about['smtp']['host']);
					$email->setPort($about['smtp']['port']);
					$email->setSecure($about['smtp']['secure']);
					if($about['smtp']['auth'] == 1){
						$email->setAuth(true);
						$email->setUser($about['smtp']['username']);
						$email->setPass($about['smtp']['password']);
					}
				}
				elseif(is_array($about['amazon_ses'])){
					$email = Email::create('amazon_ses');
					$email->setSenderName($about['amazon_ses']['from_name']);
					$email->setSenderEmailAddress($about['amazon_ses']['from_address']);
					$email->setAwsKey($about['amazon_ses']['aws_key']);
					$email->setAwsSecretKey($about['amazon_ses']['aws_secret_key']);
					$email->setFallback($about['amazon_ses']['fallback']);
					$email->setReturnPath($about['amazon_ses']['return_path']);
				}
				elseif(is_array($about['sendmail'])){
					$email = Email::create('sendmail');
					$email->setSenderName($about['sendmail']['from_name']);
					$email->setSenderEmailAddress($about['sendmail']['from_address']);
				}
				else{
					Throw new EmailNewsletterException('Currently only sendmail and SMTP are supported. This will be fixed when the API supports it.');
				}

				Symphony::ExtensionManager()->notifyMembers(
					'preEmailGenerate',
					'/extension/email_newsletter_manager/',
					array(
						'newsletter'	=> &$this,
						'email'			=> &$email,
						'template' 		=> &$template,
						'recipient'		=> &$recipient
					)
				);

				require_once(TOOLKIT . '/util.validators.php');
				if(General::validateString($recipient['email'], $validators['email']) && !is_null($recipient['email'])){
					$email->setRecipients(array($recipient['name'] => $recipient['email']));
					$template->recipients = '"'.$recipient['name'] . '" <' . $recipient['email'] . '>';
					$template->addParams(array('etm-recipient' => $recipient['email']));
				}
				else{
					throw new EmailTemplateException("Email address invalid: ".$recipient['email']);
				}

				$email->setReplyToName($about['reply-to-name']);
				$template->reply_to_name = $about['reply-to-name'];
				$template->addParams(array('etm-reply-to-name' => $about['reply-to-name']));

				$email->setReplyToEmailAddress($about['reply-to-email']);
				$template->reply_to_email_address = $about['reply-to-email'];
				$template->addParams(array('etm-reply-to-email-address' => $about['reply-to-email']));
				
				$template->addParams(array('enm-newsletter-id' => $this->getId()));

				$xml = $template->processDatasources();
				$template->setXML($xml->generate());

				$content = $template->render();

				if(!empty($content['subject'])){
					$email->subject = $content['subject'];
				}
				else{
					throw new EmailTemplateException("Can not send emails without a subject");
				}

				if(isset($content['plain']))
					$email->text_plain = $content['plain'];
				if(isset($content['html']))
					$email->text_html = $content['html'];

				Symphony::ExtensionManager()->notifyMembers(
					'preEmailSend',
					'/extension/email_newsletter_manager/',
					array(
						'newsletter'	=> &$this,
						'email'			=> &$email,
						'template' 		=> &$template,
						'recipient'		=> $recipient
					)
				);

				$email->send();

				Symphony::ExtensionManager()->notifyMembers(
					'postEmailSend',
					'/extension/email_newsletter_manager/',
					array(
						'newsletter'	=> &$this,
						'email'			=> &$email,
						'template' 		=> &$template,
						'recipient'		=> $recipient
					)
				);
	
				$this->_markRecipient($recipient['email'], 'sent');
			}
			catch(EmailTemplateException $e){
				Symphony::$Log->pushToLog(__('Email Newsletter Manager: ') . $e->getMessage(), null, true);
				$this->_markRecipient($recipient['email'], 'failed');
				continue;
			}
		}
		//To prevent timing problems, the completed recipient groups should only be marked as complete when the emails are actually sent.
		Symphony::Database()->update(array('completed_recipients'=>implode(', ', $this->_completed)), 'tbl_email_newsletters', 'id = ' . $this->getId());
		return 'sent';
	}

	public function getRecipientGroups($filter_complete = false, $return_array = false){
		$gr = array();
		$groups = Symphony::Database()->fetch('SELECT recipients, completed_recipients from tbl_email_newsletters where id = \'' . $this->getId() .'\'');
		$groups_arr = array_map('trim', explode(', ', $groups[0]['recipients']));
		if($return_array == true){
			return $groups_arr;
		}
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
				break 1;
			}
			$this->_markRecipientGroup($group);
		}
		return $recipients;
	}

	public function _markRecipient($recipient, $status = 'sent'){
		Symphony::Database()->query('UPDATE `tbl_email_newsletters` SET sent = sent + ' . ($status == 'sent'?1:0) . ', failed = failed + ' . ($status == 'failed'?1:0) . ', total = total + 1 WHERE id = \'' . $this->getId() . '\'');
		if($status !== 'idle') return Symphony::Database()->query('UPDATE `tbl_tmp_email_newsletters_sent_`' . $this->getId(). ' SET status = \''.$status.'\' WHERE email = \'' . Symphony::Database()-<cleanValue($recipient) . '\'');
		else return Symphony::Database()->insert(array('email'=>$recipient, 'result'=>$status), 'tbl_tmp_email_newsletters_sent_' . $this->getId());
	}

	public function _markRecipientGroup($group){
		$groups = $this->getCompletedRecipientGroups();
		//lots of complicated stuff here. Because I do not assume this function will be called a lot (1000s of times), I have used quite a lot of filters to keep the completed_recipients output clean.
		//what happens here is that the new group is merged, all empty values are cleared and all duplicates are removed. This should result in the cleanest possible value.
		$this->_completed = array_filter(array_unique(array_merge(array_map('trim', explode(', ', $groups)), array(is_object($group)?$group->getHandle():$group))), 'strlen');
		//return Symphony::Database()->update(array('completed_recipients'=>implode(', ', $completed)), 'tbl_email_newsletters', 'id = ' . $this->getId());
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
		return Symphony::Database()->update(array('template' => $template), 'tbl_email_newsletters', 'id = \'' . $this->getId() . '\'');
	}

	public function getStats(){
		$results = Symphony::Database()->fetch('SELECT started_on, started_by, status, sent, total, failed FROM `tbl_email_newsletters` WHERE `id`=\'' . $this->getId() . '\'');
		return $results[0];
	}

	public function getStatus(){
		$status = Symphony::Database()->fetchCol('status', 'SELECT status from `tbl_email_newsletters` where id = \'' . $this->getId() .'\'');
		return $status[0];
	}

	protected function setStatus($status){
		return Symphony::Database()->update(array('status' => $status), 'tbl_email_newsletters', 'id = \'' . $this->getId() . '\'');
	}

	protected function generatePAuth(){
		$id = uniqid();
		return Symphony::Database()->update(array('pauth' => $id), 'tbl_email_newsletters', 'id = \'' . $this->getId() . '\'');
	}
}