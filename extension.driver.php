<?php

if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");
require_once(ENMDIR . '/lib/class.emailnewslettermanager.php');


class extension_email_newsletter_manager extends extension{

	public function about(){
		return array(
			'name' => 'Email Newsletter Manager',
			'version' => '1.0 Beta',
			'author' => array(
				array(
					'name'=>'Huib Keemink',
					'website' => 'http://www.creativedutchmen.com',
					'email' => 'huib.keemink@creativedutchmen.com',
				),
				array(
					'name' => 'Michael Eichelsdoerfer',
					'website' => 'http://www.michael-eichelsdoerfer.de',
					'email' => 'info@michael-eichelsdoerfer.de',
				)
			)
		);
	}

	public function fetchNavigation(){
		return array(
			array(
				'location'  => __('Blueprints'),
				'name'      => __('Newsletter Recipients'),
				'link'      => '/recipientgroups/'
			),
			array(
				'location'  => __('Blueprints'),
				'name'      => __('Newsletter Senders'),
				'link'      => '/senders/'
			)
		);
	}

	public function getSubscribedDelegates(){
		return array(
			array(
				'page' => '/backend/',
				'delegate' => 'InitaliseAdminPageHead',
				'callback' => 'appendStyles'
			),
			array(
				'page' => '/publish/edit/',
				'delegate' => 'EntryPreEdit',
				'callback' => 'stopRestartNewsletter'
			),
			array(
				'page' => '/publish/edit/',
				'delegate' => 'EntryPostEdit',
				'callback' => 'initEmailNewsletter'
			),
			array(
				'page' => '/extension/email_newsletter_manager/',
				'delegate' => 'PostSenderSaved',
				'callback' => 'senderSaved'
			),
			array(
				'page' => '/extension/email_newsletter_manager/',
				'delegate' => 'PostRecipientgroupSaved',
				'callback' => 'groupSaved'
			),
			array(
				'page' => '/extension/email_template_manager/',
				'delegate' => 'EmailTemplatePostSave',
				'callback' => 'templateSaved'
			),
		);
	}

	public function appendStyles($context){
		$callback = $context['parent']->getPageCallback();

		if ($callback['driver'] == 'recipientgroups' && $callback['classname'] == 'contentExtensionEmail_newsletter_managerRecipientgroups' && $callback['context'][0] == 'preview')
			$context['parent']->Page->addStylesheetToHead(URL . '/extensions/email_newsletter_manager/assets/email_newsletter_manager.recipientgroups.preview.css', 'screen', 1000);
	}

	/**
	 * Callback function to change a sender handle in each newsletter field.
	 *
	 * @param string $context
	 * @return void
	 */
	public function senderSaved($context){
		$old_handle = $context['handle'];
		$new_handle = Lang::createHandle($context['fields']['name'], 255, '_');
		EmailNewsletterManager::updateSenderHandle($old_handle, $new_handle);
	}

	/**
	 * Callback function to change a recipient group handle in each newsletter field
	 *
	 * @param string $context
	 * @return void
	 */
	public function groupSaved($context){
		$old_handle = $context['handle'];
		$new_handle = Lang::createHandle($context['fields']['name'], 255, '_');
		EmailNewsletterManager::updateRecipientsHandle($old_handle, $new_handle);
		return true;
	}

	/**
	 * Callback function to change a template handle in each newsletter field.
	 *
	 * @param string $context
	 * @return void
	 */
	public function templateSaved($context){
		$old_handle = $context['old_handle'];
		$new_handle = Lang::createHandle($context['config']['name'], 255, '_');
		EmailNewsletterManager::updateTemplateHandle($old_handle, $new_handle);
	}

	public function stopRestartNewsletter(&$context){
		if(substr($_POST['action']['save'], 0, 11) == 'en-restart:'){
			$vars = explode(":",$_POST['action']['save']);
			$field_id = $vars[1];
			$entry_id = $vars[2];
			$data = $this->_getEntryData($field_id, $entry_id);
			if(!empty($data)){
				try{
					$newsletter = EmailNewsletterManager::create($data['newsletter_id']);
					$array = array(
						'template'	=>	$newsletter->getTemplate()->getHandle(),
						'sender'	=> 	$newsletter->getSender()->getHandle(),
						'recipients'=>	implode(', ', $newsletter->getRecipientGroups(false, true)),
						'started_by'=>	Administration::instance()->Author->get('id'));
					$news = EmailNewsletterManager::save($array);
					$context['entry']->setData($field_id, array('author_id'=>Administration::instance()->Author->get('id'), 'entry_id'=>$entry_id, 'newsletter_id'=>$news->getId()));
					//$news->start();
				}
				catch(Exception $e){
					Administration::instance()->customError(__('Error restarting email newsletter'), __($e->getMessage()));
				}
			}
		}
		if(substr($_POST['action']['save'], 0, 8) == 'en-stop:'){
			$vars = explode(":",$_POST['action']['save']);
			$field_id = $vars[1];
			$entry_id = $vars[2];
			$data = $this->_getEntryData($field_id, $entry_id);
			if(!empty($data)){
				try{
					$newsletter = EmailNewsletterManager::create($data['newsletter_id']);
					$newsletter->stop();
				}
				catch(Exception $e){
					Administration::instance()->customError(__('Error stopping email newsletter'), __($e->getMessage()));
				}
			}
		}
		if(substr($_POST['action']['save'], 0, 9) == 'en-pause:'){
			$vars = explode(":",$_POST['action']['save']);
			$field_id = $vars[1];
			$entry_id = $vars[2];
			$data = $this->_getEntryData($field_id, $entry_id);
			if(!empty($data)){
				try{
					$newsletter = EmailNewsletterManager::create($data['newsletter_id']);
					$newsletter->pause();
				}
				catch(Exception $e){
					Administration::instance()->customError(__('Error pausing email newsletter'), __($e->getMessage()));
				}
			}
		}
	}
			

	public function initEmailNewsletter($context){
		// The field has a 'save and send' button. We trigger the newsletter
		// start using the 'action' string, which seems to be the only way.
		if(@array_key_exists('save', $_POST['action']) && substr($_POST['action']['save'], 0, 9) == 'enm-send:'){
			$vars = explode(":",$_POST['action']['save']);
			
			$field_id = $vars[1];
			$entry_id = $vars[2];
			$domain = $vars[3];
			
			$data = $this->_getEntryData($field_id, $entry_id);
			if(!empty($data)){
				try{
					$newsletter = EmailNewsletterManager::create($data['newsletter_id']);
					// The reason the newsletter is started here and not in the field save function is because it must only send if all other fields are completed successfully.
					$newsletter->start();
				}
				// This is the last resort. All checks should be done before saving the entry, so this error should ideally never be shown. Ever.
				// Because the delegate this function hooks into can not undo the saving, or display any warning messages, a "hard" error is the only way to communicate what is going wrong.
				catch(Exception $e){
					Administration::instance()->customError(__('Error sending email newsletter'), __($e->getMessage()));
				}
			}
		}
	}

	public function install(){
		$etm = Symphony::ExtensionManager()->getInstance('email_template_manager');
		if($etm instanceof Extension){
			try{
				if(@mkdir(WORKSPACE . '/email-newsletters') || is_dir(WORKSPACE . '/email-newsletters')){
					Symphony::Database()->query("CREATE TABLE IF NOT EXISTS `tbl_fields_email_newsletter_manager` (
						`id` int(11) unsigned NOT NULL auto_increment,
						`field_id` int(11) unsigned NOT NULL,
						`templates` text,
						`senders` text,
						`recipient_groups` text,
						PRIMARY KEY  (`id`),
						KEY `field_id` (`field_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

					Symphony::Database()->query("CREATE TABLE `tbl_email_newsletters` (
							`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
							`template` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
							`recipients` text CHARACTER SET utf8,
							`completed_recipients` text CHARACTER SET utf8,
							`sender` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
							`total` int(11) DEFAULT '0',
							`sent` int(11) DEFAULT '0',
							`failed` int(11) DEFAULT '0',
							`started_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
							`started_by` int(10) unsigned NOT NULL,
							`flag` varchar(255) CHARACTER SET utf8 DEFAULT 'idle',
							`status` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
							`pauth` varchar(23) CHARACTER SET utf8 DEFAULT NULL,
							`pid` varchar(13) CHARACTER SET utf8 DEFAULT NULL,
							PRIMARY KEY (`id`)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
					return true;
				}
			}
			catch(Exception $e){
				throw new Exception(__('Installation failed:') . ' ' . $e->getMessage());
			}
		}
		else{
			throw new Exception(__('The Email Template Manager is required for this extension to work.'));
		}
	}

	public function update($previousVersion){
		return true;
	}

	public function uninstall(){
		Symphony::Database()->query("DROP TABLE `tbl_fields_email_newsletter_manager`");

		/*
			TODO should we drop the newsletters table upon uninstallation of the extension?
		*/

		// // drop database table
		// Symphony::Database()->query("DROP TABLE `tbl_email_newsletters`");

		/*
			TODO shoud we remove the template files upon uninstallation of the extension?
		*/

		return true;
	}
	
	private function _getEntryData($field_id, $entry_id){
		return Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_entries_data_".$field_id."` WHERE `entry_id` = '".$entry_id."' LIMIT 1");
	}
}