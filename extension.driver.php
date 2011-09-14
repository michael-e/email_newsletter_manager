<?php

class extension_email_newsletter_manager extends extension{

	public function about(){
		return array(
			'name' => 'Email Newsletter Manager',
			'version' => '1.0 Alpha',
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
		$this->_updateHandle('senders', $old_handle, $new_handle);
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
		$this->_updateHandle('recipient_groups', $old_handle, $new_handle);
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
		$this->_updateHandle('templates', $old_handle, $new_handle);
	}

	/**
	 * Update handle in field data
	 *
	 * @param string $old_handle
	 * @param string $new_handle
	 * @param string $column
	 * @return void
	 */
	protected function _updateHandle($column, $old_handle, $new_handle){
		// Select all - we don't expect any memory problems here...
		$field_data = Symphony::Database()->fetch("SELECT * FROM `tbl_fields_email_newsletter_manager`");
		foreach($field_data as $row){
			// It should be safe to parse the comma-separated string using word boundaries
			$c = preg_replace('/\b'.$old_handle.'\b/', $new_handle, $row[$column]);
			// Only update if anything has changed
			if($c !== $row[$column]){
				Symphony::Database()->update(
					array($column => $c),
					'tbl_fields_email_newsletter_manager',
					'`id` = '.$row['id']
				);
			}
		}
	}

	public function initEmailNewsletter(){
		// The field has a 'save and send' button. We trigger the newsletter
		// start using the 'action' string, which seems to be the only way.
		if(@array_key_exists('save', $_POST['action']) && substr($_POST['action']['save'], 0, 9) == 'enm-send:'){

			// save the newsletter

			// start the newsletter
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
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

					Symphony::Database()->query("CREATE TABLE IF NOT EXISTS `tbl_email_newsletters` (
					  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					  `pid` varchar(13),
					  `pauth` varchar (23),
					  `template` varchar(255),
					  `recipients` text,
					  `completed_recipients` text,
					  `sender` varchar(255),
					  `total` int(11) DEFAULT '0',
					  `sent` int(11) DEFAULT '0',
					  `failed` int(11) DEFAULT '0',
					  `started_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					  `started_by` int(10) unsigned NOT NULL,
					  `status` varchar(255) DEFAULT 'idle',
					  PRIMARY KEY (`id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
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
}