<?php

if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");

require_once(ENMDIR . '/lib/class.recipientgroupmanager.php');
require_once(ENMDIR . '/lib/class.sendermanager.php');

require_once(EXTENSIONS . '/email_template_manager/lib/class.emailtemplatemanager.php');

class EmailNewsletterException extends Exception{
}

class EmailNewsletter{
	
	const FLAG_START = 'start';
	const FLAG_STOP = 'stop';
	const FLAG_PAUSE = 'pause';

	// signals all emails from the batch list have been sent (limited by the limit given by the sender).
	const BATCH_DONE = 'batch_done';
	// signals all emails have been sent. After this signal the background process should quit.
	const PROC_DONE = 'process_done';
	// signals the current email has been sent, but there are more emails in line. 
	const OK = 'ok';

	protected $_properties;
	
	protected $_template;
	protected $_recipient_groups;
	protected $_sender;
	
	protected $_batch = array();
	
	protected $_sent = 0;
	protected $_limit_emails = 0;

	public function __construct($properties){
		$this->_properties = $properties;

		$tplm = new EmailTemplateManager($this->_Parent);
		$this->_template = $tplm->load($properties['template']);

		$sndrm = new SenderManager($this->_Parent);
		$this->_sender = $sndrm->create($properties['sender']);

		$rcptm = new RecipientgroupManager($this->_Parent);
		$groups = array_map('trim', (array)explode(',', $properties['recipients']));
		$sender_about = $this->_sender->about();
		$this->_limit_emails = $sender_about['throttle-emails'];
		foreach($groups as $group){
			$grp = $rcptm->create($group);
			$grp->dsParamLIMIT = max(1, $this->_limit_emails);
			// Due to the way the recipientgroups fetch their data, the first page will always contain fresh data.
			$grp->dsParamSTARTPAGE = 1;
			$grp->newsletter_id = $this->_properties['id'];
			$this->_recipient_groups[] = $grp;
		}
	}

	public function start(){
		if($this->getFlag() != 'start'){
			$this->setFlag('start');
			if(Symphony::Database()->query('CREATE TABLE IF NOT EXISTS `tbl_email_newsletters_sent_'.Symphony::Database()->cleanValue($this->_properties['id']).'` (
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`email` VARCHAR( 255 ) NOT NULL ,
				`result` VARCHAR( 255 ) NULL ,
				`date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
			)')){
				return true;
			}
			else{
				throw new EmailNewsletterException(Symphony::Database()->getLastError());
			}
		}
		else{
			throw new EmailNewsletterException(__('Can not start an already running process.'));
		}
		
	}

	public function stop(){
		$this->setFlag('stop');
		if(Symphony::Database()->query('DROP TABLE `tbl_email_newsletters_sent_'.Symphony::Database()->cleanValue($this->_properties['id']))){
			return true;
		}
		else{
			throw new EmailNewsletterException(Symphony::Database()->getLastError());
		}			
	}

	public function pause(){
		$this->setFlag('pause');
	}

	public function sendNextEmail(){
		if(empty($this->_batch)){
			if($group = $this->getNextRecipientsGroup()){
				$batch = $group->getSlice($this->_properties['id']);
				if(count($batch['records']) == 0){
					$about = $group->about();
					$this->_markGroupAsCompleted($about['name']);
					return $this->sendNextEmail();
				}
				else{
					$this->_batch = $batch['records'];
				}
			}
		}
		if($recipient = @array_pop($this->_batch)){
			if($recipient['valid'] != true){
				$this->_markAsFailed($recipient['email']);
			}
			else{
				$this->_markAsSent($recipient['email']);
			}
			$this->_sent++;
			if($this->_sent >= $this->_limit_emails){
				return self::BATCH_DONE;
			}
			else{
				return self::OK;
			}
		}
		return self::PROC_DONE;
	}

	public function getFlag(){
		if($flag = Symphony::Database()->fetchCol('flag','SELECT flag from tbl_email_newsletters where `id` = ' . Symphony::Database()->cleanValue($this->_properties['id']))){
			return $flag[0];
		}
		else{
			throw new EmailNewsletterException(Symphony::Database()->getLastError());
		}
	}

	public function getProperties(){
		return $this->_properties;
	}

	public function getFinishedRecipientGroups(){
		if($recipients = Symphony::Database()->fetch('SELECT completed_recipients  from tbl_email_newsletters where `id` = ' . Symphony::Database()->cleanValue($this->_properties['id']))){
			return array_map('trim', explode(',',$recipients[0]['completed_recipients']));
		}
		else{
			throw new EmailNewsletterException(Symphony::Database()->getLastError());
		}
	}

	public function getNextRecipientsGroup(){
		$finished = $this->getFinishedRecipientGroups();
		$properties = $this->getproperties();
		$groups = $this->_recipient_groups;
		
		foreach($groups as $group){
			$about = $group->about();
			$handle = Lang::createHandle($about['name'], 225, '_');
			if(!in_array($handle, (array)$finished)){
				return $group;
			}
		}
		return false;
	}

	protected function _markAsSent($email){
		$this->_addToProcessedList($email, 'sent');
		return Symphony::Database()->query("UPDATE `tbl_email_newsletters` set total = total+1, sent = sent+1 where id = " . Symphony::Database()->cleanValue($this->_properties['id']));
	}

	protected function _markAsFailed($email){
		$this->_addToProcessedList($email, 'failed');
		return Symphony::Database()->query("UPDATE `tbl_email_newsletters` set total = total+1, failed = failed+1 where id = " . Symphony::Database()->cleanValue($this->_properties['id']));
	}

	protected function _markGroupAsCompleted($group){
		return Symphony::Database()->query("UPDATE `tbl_email_newsletters` set completed_recipients = CONCAT(completed_recipients, ', ".Symphony::Database()->cleanValue(Lang::createHandle($group,'_', 255))."') where id = " . Symphony::Database()->cleanValue($this->_properties['id']));
	}

	protected function _addToProcessedList($email, $result){
		return Symphony::Database()->insert(array('email'=>Symphony::Database()->cleanValue($email), 'result'=>Symphony::Database()->cleanValue($result)), 'tbl_email_newsletters_sent_' . Symphony::Database()->cleanValue($this->_properties['id']));
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