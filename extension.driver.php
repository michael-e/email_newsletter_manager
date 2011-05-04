<?php

	/**
	 * Extension driver
	 *
	 * @package Email Newsletters 2
	 * @author Michael Eichelsdoerfer
	 */
	class Extension_Email_Newsletters_2 extends Extension
	{
		// protected $_field_id;
		// protected $_entry_id;

		/**
		 * Extension information
		 */
		public function about()
		{
			return array(
				'name'			=> 'Email Newsletters 2',
				'version'		=> '0.1',
				'release-date'	=> '2011-05-04',
				'author'		=> array(
					'name'			=> 'Huib Keemink, Michael Eichelsdoerfer',
				),
				'description'	=> 'Allows you to send Email Newsletters.'
			);
		}

		/**
		 * Add callback functions to backend delegates
		 */
		public function getSubscribedDelegates()
		{
			// return array(
			// 	array(
			// 		'page' => '/system/preferences/',
			// 		'delegate' => 'AddCustomPreferenceFieldsets',
			// 		'callback' => 'appendPreferences'
			// 	),
			// 	array(
			// 		'page' => '/publish/edit/',
			// 		'delegate' => 'EntryPostEdit',
			// 		'callback' => 'initEmailNewsletter'
			// 	),
			// );
		}

		/**
		 * Function to be executed on uninstallation
		 */
		public function uninstall()
		{
			// ## drop database table
			// Symphony::Database()->query("DROP TABLE `tbl_fields_email_newsletter`");
			// 
			// ## remove configuration details from Symphony's configuration file
			// Symphony::Configuration()->remove('email-newsletters');
			// Administration::instance()->saveConfig();
		}

		/**
		 * Function to be executed if the extension has been updated
		 *
		 * @param string $previousVersion - version number of the currently installed extension build
		 * @return boolean - true on success, false otherwise
		 */
		public function update($previousVersion)
		{
			## nothing to do here today
			return true;
		}

		/**
		 * Function to be executed on installation
		 *
		 * @return boolean - true on success, false otherwise
		 */
		public function install()
		{
			// if(ini_get('safe_mode'))
			// {
			// 	Administration::instance()->Page->pageAlert(__('Email Newsletters can not be installed because PHP is running in Safe Mode.'), AdministrationPage::PAGE_ALERT_ERROR);
			// 	return false;
			// }
			// ## Create database table and fields.
			// $fields = Symphony::Database()->query(
			// 	"CREATE TABLE IF NOT EXISTS `tbl_fields_email_newsletter` (
			// 	 `id` int(11) unsigned NOT NULL auto_increment,
			// 	 `field_id` int(11) unsigned NOT NULL,
			// 		 		 `config_xml` TEXT,
			// 	  PRIMARY KEY  (`id`),
			// 	  KEY `field_id` (`field_id`)
			// 	) ENGINE=MyISAM;"
			// );
			// if($fields) return true;
			// return false;
		}

		/**
		 * Append preferences
		 *
		 * @param object $context
		 */
		public function appendPreferences($context)
		{
			// $settings = new XMLElement('fieldset');
			// $settings->setAttribute('class', 'settings');
			// $settings->appendChild(new XMLElement('legend', 'Email Newsletters'));
			// 
			// ## Just in case the website has been moved to a new server which is running in safe mode
			// if(ini_get('safe_mode'))
			// {
			// 	$settings->appendChild(new XMLElement('p', '<strong>' . __('Warning: It appears PHP is running in Safe Mode. This extension will not work. You should uninstall the extension or get rid of PHP Safe Mode.') . '</strong>'));
			// 	$context['wrapper']->appendChild($settings);
			// 	return;
			// }
			// 
			// $label = Widget::Label('SwiftMailer Location');
			// $label->appendChild(Widget::Input('settings[email-newsletters][swiftmailer-location]', General::Sanitize($context['parent']->Configuration->get('swiftmailer-location', 'email-newsletters'))));
			// $settings->appendChild($label);
			// $settings->appendChild(new XMLElement('p', __('The SwiftMailer library must be located in the "extensions" directory. Its location defaults to "email_newsletters/lib/swiftmailer", so this field may be left empty if you have not moved the library.'), array('class' => 'help')));
			// 
			// $context['wrapper']->appendChild($settings);
		}

		/**
		 * Init the Email Newsletter if conditions are met
		 *
		 * @return void
		 */
		public function initEmailNewsletter()
		{
			// if(@array_key_exists('save', $_POST['action']) && substr($_POST['action']['save'], 0, 8) == 'en-send:')
			// {
			// 	$vars = explode(":",$_POST['action']['save']);
			// 	$this->_field_id = $vars[1];
			// 	$this->_entry_id = $vars[2];
			// 	$domain          = $vars[3];
			// 
			// 	## status must be NULL to prohibit multiple CLI processes caused by page reload;
			// 	## 'fast double-click' protection is done using JavaScript (see email-newsletters.js);
			// 	$entry_data = $this->__getEntryData();
			// 	if(!empty($entry_data) && $entry_data['status'] == NULL)
			// 	{
			// 		## build the command to initiate the background mailer process
			// 		$cmd  = 'env -i php ' . EXTENSIONS . '/email_newsletters/lib/init.php' . ' ';
			// 		$cmd .= $this->_field_id . ' ';
			// 		$cmd .= $this->_entry_id . ' ';
			// 		$cmd .= $domain . ' ';
			// 		$cmd .= 'init' . ' ';
			// 		$cmd .= '> /dev/null &';
			// 		shell_exec($cmd);
			// 	}
			// }
		}

/*-------------------------------------------------------------------------
	Helpers
-------------------------------------------------------------------------*/
		/**
		 * Get entry data
		 *
		 * @return Symphony method
		 */
		private function __getEntryData()
		{
			return Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_entries_data_".$this->_field_id."` WHERE `entry_id` = $this->_entry_id LIMIT 1");
		}

		/**
		 * Update entry data
		 *
		 * @return Symphony method
		 */
		private function __updateEntryData($array)
		{
			return Symphony::Database()->update($array, 'tbl_entries_data_'.$this->_field_id, "`entry_id` = '".$this->_entry_id."'");
		}
	}
