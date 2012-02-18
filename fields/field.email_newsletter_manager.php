<?php

// ini_set('display_errors', 'On');
// error_reporting(E_ALL | E_STRICT);

	if(!defined('ETMDIR')) define('ETMDIR', EXTENSIONS . "/email_template_manager");
	if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");
	require_once(ETMDIR . '/lib/class.emailtemplatemanager.php');
	require_once(ENMDIR . '/lib/class.sendermanager.php');
	require_once(ENMDIR . '/lib/class.recipientgroupmanager.php');
	require_once(ENMDIR . '/lib/class.emailnewslettermanager.php');

	/**
	 * Field: Email Newsletter Manager
	 *
	 * @package Email Newsletter Manager
	 **/
	class fieldEmail_Newsletter_Manager extends Field{

		protected $_field_id;
		protected $_entry_id;

		/**
		 * Initialize as unrequired field
		 */
		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = __('Email Newsletter Manager');
			$this->_required = false;
			$this->set('location', 'sidebar');
		}

/*-------------------------------------------------------------------------
	Section editor - set up field
-------------------------------------------------------------------------*/
		/**
		 * Displays settings panel in section editor
		 *
		 * @param XMLElement $wrapper - parent element wrapping the field
		 * @param array $errors - array with field errors, $errors['name-of-field-element']
		 */
		public function displaySettingsPanel(&$wrapper, $errors=NULL){

			// initialize field settings based on class defaults (name, placement)
			parent::displaySettingsPanel($wrapper, $errors);



			// build selector for email templates
			$all_templates = EmailTemplateManager::listAll();

			$options = array();
			if(!empty($all_templates) && is_array($all_templates)){
				$templates = $this->get('templates');
				if(is_array($templates)){
					$templates = implode(',',$templates);
				}
				foreach($all_templates as $template){
					$about = $template->about;
					$handle = $template->getHandle();
					$options[] = array(
						$handle,
						in_array($handle, explode(',', $templates)),
						$about['name']
					);
				}
			}
			$group = new XMLElement('div', NULL, array('class' => 'group'));
			$label = Widget::Label(__('Email Templates'));
			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][templates][]', $options, array('multiple'=>'multiple')));

			if(isset($errors['templates'])){
				$group->appendChild(Widget::wrapFormElementWithError($label, $errors['templates']));
			}
			else{
				$group->appendChild($label);
			}
			$wrapper->appendChild($group);

			// build selector for senders
			$all_senders = SenderManager::listAll();

			$options = array();
			if(!empty($all_senders) && is_array($all_senders)){
				$senders = $this->get('senders');
				if(is_array($senders)){
					$senders = implode(',',$senders);
				}
				foreach($all_senders as $sender){
					$options[] = array(
						$sender['handle'],
						in_array($sender['handle'], explode(',', $senders)),
						$sender['name']
					);
				}
			}
			$group = new XMLElement('div', NULL, array('class' => 'group'));
			$label = Widget::Label(__('Newsletter Senders'));
			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][senders][]', $options, array('multiple'=>'multiple')));
			if(isset($errors['senders'])){
				$group->appendChild(Widget::wrapFormElementWithError($label, $errors['senders']));
			}
			else{
				$group->appendChild($label);
			}

			// build selector for recipient groups
			$recipient_group_manager = new RecipientgroupManager(Symphony::Engine());
			$all_recipient_groups = $recipient_group_manager->listAll();

			$options = array();
			if(!empty($all_recipient_groups) && is_array($all_recipient_groups)){
				$recipient_groups = $this->get('recipient_groups');
				if(is_array($recipient_groups)){
					$recipient_groups = implode(',',$recipient_groups);
				}
				foreach($all_recipient_groups as $recipient_group){
					$options[] = array(
						$recipient_group['handle'],
						in_array($recipient_group['handle'], (array)explode(',', $recipient_groups)),
						$recipient_group['name']
					);
				}
			}
			$label = Widget::Label(__('Newsletter Recipient Groups'));
			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][recipient_groups][]', $options, array('multiple'=>'multiple')));
			if(isset($errors['recipient_groups'])){
				$group->appendChild(Widget::wrapFormElementWithError($label, $errors['recipient_groups']));
			}
			else{
				$group->appendChild($label);
			}
			$wrapper->appendChild($group);

			// append 'show column' checkbox
			$this->appendShowColumnCheckbox($wrapper);
		}

		/**
		 * Checks fields for errors in section editor.
		 *
		 * @param array $errors
		 * @param boolean $checkForDuplicates
		 */
		public function checkFields(&$errors, $checkForDuplicates=true){
			if(!is_array($errors)) $errors = array();
			$templates = $this->get('templates');
			if(empty($templates)){
				$errors['templates'] = __('This is a required field.');
			}
			$senders = $this->get('senders');
			if(empty($senders)){
				$errors['senders'] = __('This is a required field.');
			}
			$recipient_groups = $this->get('recipient_groups');
			if(empty($recipient_groups)){
				$errors['recipient_groups'] = __('This is a required field.');
			}
			parent::checkFields($errors, $checkForDuplicates);
		}

		/**
		* Save fields settings in section editor
		*/
		public function commit(){
			// prepare commit
			if(!parent::commit()) return false;
			$id = $this->get('id');
			if($id === false) return false;

			// set up fields
			$fields = array();
			$fields['field_id'] = $id;
			if($this->get('templates')){
				$fields['templates'] = implode(',', $this->get('templates'));
			}
			if($this->get('senders')){
				$fields['senders'] = implode(',', $this->get('senders'));
			}
			if($this->get('recipient_groups')){
				$fields['recipient_groups'] = implode(',', $this->get('recipient_groups'));
			}

			// delete old field settings
			Symphony::Database()->query("DELETE FROM `tbl_fields_" . $this->handle()."` WHERE `field_id` = '$id' LIMIT 1");

			// save new field settings
			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
		}

		/**
		 * Create database table for entries
		 */
		public function createTable(){
			Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_".$this->get('id')."` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `author_id` int(11) unsigned NOT NULL,
				  `newsletter_id` int(11) unsigned NOT NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);
			return true;
		}

/*-------------------------------------------------------------------------
	Publish: entries table
-------------------------------------------------------------------------*/
		/**
		 * Append newsletter status to entry table
		 */
		public function prepareTableValue($data, XMLElement $link=NULL){
			if(!is_array($data) || empty($data)) return;
			$value = null;
			if(isset($data['newsletter_id'])){
				$newsletter = EmailNewsletterManager::get($data['newsletter_id']);;
				$stats = $newsletter->getStats();
				$value = $stats['status'];
			}
			switch ($value){
				case 'sending':
					$value = __('Sending');
					break;
				case 'stopped':
					$value = __('Stopped');
					break;
				case 'error':
					$value = __('Failed');
					break;
				case 'completed':
					$value = __('Completed');
					break;
				case 'paused':
					$value = __('Paused');
					break;
				case 'idle':
					$value = __('Ready to send');
					break;
				default:
					$value = 'none';
			}
			return parent::prepareTableValue(array('value' => $value), $link);
		}

		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			if(in_array(strtolower($order), array('random', 'rand'))) {
				$sort = 'ORDER BY RAND()';
			}
			else {
				$joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) LEFT JOIN `tbl_email_newsletters` as `nl` on `ed`.`newsletter_id` = `nl`.`id`";
				$sort = 'ORDER BY `nl`.`status` ' . $order;
			}
		}

		/**
		 * Is the table column sortable?
		 *
		 * @return boolean
		 */
		public function isSortable(){
			return true;
		}

/*-------------------------------------------------------------------------
	Publish: edit
-------------------------------------------------------------------------*/
		/**
		 * Displays publish panel in content area.
		 *
		 * @param XMLElement $wrapper
		 * @param $data
		 * @param $flagWithError
		 * @param $fieldnamePrefix
		 * @param $fieldnamePostfix
		 */
		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/email_newsletter_manager/assets/email_newsletter_manager.publish.js', 1001);
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/email_newsletter_manager/assets/email_newsletter_manager.publish.css', 'screen', 1000);

			$this->_field_id = $this->get('id');
			$this->_entry_id = Administration::instance()->Page->_context['entry_id'];


			$status = NULL;

			// get newsletter properties
			$newsletter_properties = array();
			if($data['newsletter_id']){
				$newsletter = EmailNewsletterManager::get($data['newsletter_id']);
				if(is_object($newsletter->getTemplate())){
					$newsletter_properties['template'] = $newsletter->getTemplate()->getHandle();
				}
				if(is_object($newsletter->getSender())){
					$newsletter_properties['sender'] = $newsletter->getSender()->getHandle();
				}
				$newsletter_properties['recipients'] = $newsletter->getRecipientGroups(false, true);
				$stats = $newsletter->getStats();
				$status = $stats['status'];
			}

			// get configured templates
			$all_templates = EmailTemplateManager::listAll();

			$templates_options = array();
			if(!empty($all_templates) && is_array($all_templates)){
				$templates = explode(',', $this->get('templates'));
				foreach($all_templates as $template){
					$about = $template->about;
					$handle = $template->getHandle();
					if(in_array($handle, $templates)){
						$templates_options[] = array(
							$handle,
							$about['name'],
						);
					}
				}
			}

			// get configured senders
			$all_senders = SenderManager::listAll();

			$senders_options = array();
			if(!empty($all_senders) && is_array($all_senders)){
				$senders = explode(',', $this->get('senders'));
				foreach($all_senders as $sender){
					if(in_array($sender['handle'], $senders)){
						$senders_options[] = array(
							$sender['handle'],
							$sender['name'],
						);
					}
				}
			}

			// get configured recipient groups
			$all_recipient_groups = RecipientgroupManager::listAll();

			$recipient_groups_options = array();
			if(!empty($all_recipient_groups) && is_array($all_recipient_groups)){
				$recipient_groups = explode(',', $this->get('recipient_groups'));
				foreach($all_recipient_groups as $recipient_group){
					if(in_array($recipient_group['handle'], $recipient_groups)){
						$recipient_groups_options[] = array(
							$recipient_group['handle'],
							$recipient_group['name'],
						);
					}
				}
			}

			// build header
			$header = new XMLElement('label', $this->get('label'));
			$wrapper->appendChild($header);

			// build GUI element
			$gui = new XMLElement('div');
			$gui->setAttribute('class', 'email-newsletters-gui');

			// switch status
			switch ($status){
				case "sending":
					$heading = new XMLElement('p',__('Sending'), array('class'=>'status sending'));
					$gui->appendChild($heading);
					$gui->appendChild(new XMLElement('p', sprintf(__("%d emails sent"), $stats['sent']), array('class'=>'stats')));
					$gui->appendChild(new XMLElement('p', sprintf(__("%d emails failed"), $stats['failed']), array('class'=>'stats')));
					$gui->appendChild(new XMLElement('p', sprintf(__("Started %s"), DateTimeObj::get(__SYM_DATETIME_FORMAT__, strftime($stats['started_on']))), array('class'=>'stats')));
					$this->_addInfoIfApplicable($newsletter, &$gui);
					$this->_addStandardForm($newsletter, &$gui);
					$gui->appendChild(new XMLElement(
						'button',
						__('Pause'),
						array(
							'name' => 'action[save]',
							'type' => 'submit',
							'value' => 'en-pause:'.$this->_field_id.':'.$this->_entry_id,
							'class' => 'button'
						)
					));
					$gui->appendChild(new XMLElement(
						'button',
						__('Cancel'),
						array(
							'name' => 'action[save]',
							'type' => 'submit',
							'value' => 'en-stop:'.$this->_field_id.':'.$this->_entry_id,
							'class' => 'button delete confirm',
							'data-message' => __('Are you sure you want to cancel sending?')
						)
					));
					$gui->setAttribute('class', 'email-newsletters-gui reloadable');
					break;

				case "stopped":
					$heading = new XMLElement('p',__('Stopped'), array('class'=>'status stopped'));
					$gui->appendChild($heading);
					$gui->appendChild(new XMLElement('p', sprintf(__("%d emails sent"), $stats['sent']), array('class'=>'stats')));
					$gui->appendChild(new XMLElement('p', sprintf(__("%d emails failed"), $stats['failed']), array('class'=>'stats')));
					$gui->appendChild(new XMLElement('p', sprintf(__("Started %s"), DateTimeObj::get(__SYM_DATETIME_FORMAT__, strftime($stats['started_on']))), array('class'=>'stats')));
					$this->_addInfoIfApplicable($newsletter, &$gui);
					$this->_addStandardForm($newsletter, &$gui);
					$gui->appendChild(new XMLElement(
						'button',
						__('Restart'),
						array(
							'name' => 'action[save]',
							'type' => 'submit',
							'value' => 'en-restart:'.$this->_field_id.':'.$this->_entry_id,
							'class' => 'button confirm',
							'data-message' => __('Restarting will send duplicate emails. Are you sure you want to continue?')
						)
					));
					break;

				case "paused":
					$heading = new XMLElement('p',__('Paused'), array('class'=>'status paused'));
					$gui->appendChild($heading);
					$gui->appendChild(new XMLElement('p', sprintf(__("%d emails sent"), $stats['sent']), array('class'=>'stats')));
					$gui->appendChild(new XMLElement('p', sprintf(__("%d emails failed"), $stats['failed']), array('class'=>'stats')));
					$gui->appendChild(new XMLElement('p', sprintf(__("Started %s"), DateTimeObj::get(__SYM_DATETIME_FORMAT__, strftime($stats['started_on']))), array('class'=>'stats')));
					$this->_addInfoIfApplicable($newsletter, &$gui);
					$this->_addStandardForm($newsletter, &$gui);
					$gui->appendChild(new XMLElement(
						'button',
						__('Continue'),
						array(
							'name' => 'action[save]',
							'type' => 'submit',
							'value' => 'enm-send:'.$this->_field_id.':'.$this->_entry_id,
							'class' => 'button create'
						)
					));
					$gui->appendChild(new XMLElement(
						'button',
						__('Cancel'),
						array(
							'name' => 'action[save]',
							'type' => 'submit',
							'value' => 'en-stop:'.$this->_field_id.':'.$this->_entry_id,
							'class' => 'button delete confirm',
							'data-message' => __('Are you sure you want to cancel sending?')
						)
					));
					break;

				case "error":
					$heading = new XMLElement('p',__('Sending failed. Check the log for details.'), array('class'=>'status error'));
					$gui->appendChild($heading);
					$gui->appendChild(new XMLElement('p', sprintf(__("%d emails sent"), $stats['sent']), array('class'=>'stats')));
					$gui->appendChild(new XMLElement('p', sprintf(__("%d emails failed"), $stats['failed']), array('class'=>'stats')));
					$gui->appendChild(new XMLElement('p', sprintf(__("Started %s"), DateTimeObj::get(__SYM_DATETIME_FORMAT__, strftime($stats['started_on']))), array('class'=>'stats')));
					$this->_addInfoIfApplicable($newsletter, &$gui);
					$this->_addStandardForm($newsletter, &$gui);
					$gui->appendChild(new XMLElement(
						'button',
						__('Continue'),
						array(
							'name' => 'action[save]',
							'type' => 'submit',
							'value' => 'enm-send:'.$this->_field_id.':'.$this->_entry_id,
							'class' => 'button create',

						)
					));
					$gui->appendChild(new XMLElement(
						'button',
						__('Restart'),
						array(
							'name' => 'action[save]',
							'type' => 'submit',
							'value' => 'en-restart:'.$this->_field_id.':'.$this->_entry_id,
							'class' => 'button confirm',
							'data-message' => __('Restarting will send duplicate emails. Are you sure you want to continue?')
						)
					));
					break;

				case "completed":
					$heading =  new XMLElement('p',__('Completed'), array('class'=>'status completed'));
					$gui->appendChild($heading);
					$gui->appendChild(new XMLElement('p', sprintf(__("%d emails sent"), $stats['sent']), array('class'=>'stats')));
					$gui->appendChild(new XMLElement('p', sprintf(__("%d emails failed"), $stats['failed']), array('class'=>'stats')));
					$gui->appendChild(new XMLElement('p', sprintf(__("Started %s"), DateTimeObj::get(__SYM_DATETIME_FORMAT__, strftime($stats['started_on']))), array('class'=>'stats')));
					$this->_addInfoIfApplicable($newsletter, &$gui);
					$this->_addStandardForm($newsletter, &$gui);
					$gui->appendChild(new XMLElement(
						'button',
						__('Restart'),
						array(
							'name' => 'action[save]',
							'type' => 'submit',
							'value' => 'en-restart:'.$this->_field_id.':'.$this->_entry_id,
							'class' => 'button confirm',
							'data-message' => __('Restarting will send duplicate emails. Are you sure you want to continue?')
						)
					));
					break;

				default:
					if(isset($this->_entry_id)){
						$heading = new XMLElement('p',__('Ready to send'), array('class'=>'status idle'));
					}
					else{
						$heading = new XMLElement('p',__('Waiting for input'), array('class'=>'status idle'));
					}
					$gui->appendChild($heading);
					// build selector for email templates
					if(count($templates_options) > 1){
						$options = array();
						$options[] = array(NULL, NULL, __('--- please select ---'));
						foreach($templates_options as $template){
							$options[] = array(
								$template[0],
								$template[0] == $newsletter_properties['template'],
								$template[1]
							);
						}
						$gui->appendChild(
							Widget::Label(__('Email Template: '),
							Widget::Select('fields['.$this->get('element_name').'][template]', $options))
						);
					}
					elseif(count($templates_options) == 1){
						$gui->appendChild(Widget::Input(
							'fields['.$this->get('element_name').'][template]',
							$templates_options[0][0],
							'hidden')
						);
					}
					else{
						$gui->appendChild(new XMLElement('p', __('No email template has been configured.')));
					}

					// build selector for senders
					if(count($senders_options) > 1){
						$options = array();
						$options[] = array(NULL, NULL, __('--- please select ---'));
						foreach($senders_options as $sender){
							$options[] = array(
								$sender[0],
								$sender[0] == $newsletter_properties['sender'],
								$sender[1]
							);
						}
						$gui->appendChild(
							Widget::Label(__('Sender: '),
							Widget::Select('fields['.$this->get('element_name').'][sender]', $options))
						);
					}
					elseif(count($senders_options) == 1){
						$gui->appendChild(Widget::Input(
							'fields['.$this->get('element_name').'][sender]',
							$senders_options[0][0],
							'hidden')
						);
					}
					else{
						$gui->appendChild(new XMLElement('p', __('No sender has been configured.')));
					}

					// build checkboxes for recipient groups
					if(count($recipient_groups_options) > 1){
						$p = new XMLElement('p', __('Recipient Groups: '));
						$gui->appendChild($p);
						$p = new XMLElement('p', NULL, array('class' => 'recipient-groups'));
						foreach($recipient_groups_options as $recipient_group){
							$label = Widget::Label();
							$input = Widget::Input(
								'fields['.$this->get('element_name').'][recipient_groups][]',
								$recipient_group[0],
								'checkbox',
								(!empty($recipient_group[0]) && in_array($recipient_group[0], (array)$newsletter_properties['recipients']))
								? array('checked' => 'checked')
								: NULL
							);
							$label->setValue($input->generate() . $recipient_group[1]);
							$label->setAttribute('class', 'recipient-group');
							$p->appendChild($label);
						}
						$gui->appendChild($p);
					}
					elseif(count($recipient_groups_options) == 1){
						$gui->appendChild(Widget::Input(
							'fields['.$this->get('element_name').'][recipient_groups][]',
							$recipient_groups_options[0][0],
							'hidden')
						);
					}
					else{
						$gui->appendChild(new XMLElement('p', __('No recipient group has been configured.')));
					}

					// build 'save and send' button
					if(isset($this->_entry_id)){
						$gui->appendChild(new XMLElement(
							'button',
							__('Send'),
							array(
								'name' => 'action[save]',
								'type' => 'submit',
								'value' => 'enm-send:'.$this->_field_id.':'.$this->_entry_id,
								'class' => 'button create'
							)
						));
					}
					else{
						$p = new XMLElement('p', __('The entry has not been created yet. No emails can be sent.'));
						$gui->appendChild($p);
					}
			}
			$wrapper->appendChild($gui);

			// // standard:
			// if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			// else $wrapper->appendChild($label);

		}

		/**
		 * Prepares field values for database.
		 */
		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null){
			$status = self::__OK__;
			if(empty($data)) return NULL;

			/*
				TODO when saving a newsletter, we __must__ check if
				the properties are valid (i.e. configured in the
				section editor);
				otherwise it would be super-simple to send with
				unwanted or invalid properties;
			*/

			$entry_data = array();
			if($entry_id){
				// grab existing entry data
				$entry_data = Symphony::Database()->fetchRow(0, sprintf(
					"SELECT *
					 FROM `tbl_entries_data_%d`
					 WHERE `entry_id` = %d
					 LIMIT 1",
					$this->get('id'),
					$entry_id
				));
			}

			if(!is_array($data['recipient_groups'])){
				$data['recipient_groups'] = array();
			}
			$newsletter = EmailNewsletterManager::save(array(
				'id'               => $entry_data['newsletter_id'],
				'template'         => $data['template'],
				'recipients'       => implode(', ', $data['recipient_groups']),
				'sender'           => $data['sender'],
				'started_by'       => Administration::instance()->Author->get('id'),
			));

			$result = array(
				'author_id' => Administration::instance()->Author->get('id'),
				'newsletter_id' => $newsletter->getId(),
			);
			return $result;
		}

/*-------------------------------------------------------------------------
	Publish: delete
-------------------------------------------------------------------------*/

		public function entryDataCleanup($entry_id, $data=NULL){
			try{
				$newsletter_id = EmailNewsletterManager::delete($data['newsletter_id']);
				return parent::entryDataCleanup($entry_id, $data);
			}
			catch(Exception $e){
				return false;
			}
		}

/*-------------------------------------------------------------------------
	Output
-------------------------------------------------------------------------*/
		/**
		 * Allow data source filtering?
		 * @return: boolean
		 */
		public function canFilter(){
			return true;
		}

		/**
		 * Allow data source parameter output?
		 * @return: boolean
		 */
		public function allowDatasourceParamOutput(){
			return true;
		}

		/**
		 * get param pool value
		 * @return: string email newsletter sender ID
		 */
		public function getParameterPoolValue($data){
			return $data['sender_id'];
		}

		/**
		 * Fetch includable elements (DS editor)
		 * @return: array() elements
		 */
		public function fetchIncludableElements(){
			return array(
				$this->get('element_name')
			);
		}

		/**
		 * Append element to datasource output
		 */
		public function appendFormattedElement(&$wrapper, $data, $encode = false){
			$node = new XMLElement($this->get('element_name'));

			$newsletter = EmailNewsletterManager::create($data['newsletter_id']);

			$properties = $newsletter->getStats();

			$node->setAttribute('newsletter-id', $data['newsletter_id']);
			$node->setAttribute('author-id', $properties['started_by']);
			$node->setAttribute('status', $properties['status']);
			$node->setAttribute('total', $properties['total']);
			$node->setAttribute('sent', $properties['sent']);
			$node->setAttribute('failed', $properties['failed']);
			$node->appendChild(new XMLElement('subject', $newsletter->getTemplate()->subject));

			// load configuration;
			// use saved (entry) config XML if available (i.e.: if the email newsletter has been sent);
			// fallback: the field's configuration XML


			// sender
			$sender = new XMLElement('sender');
			General::array_to_xml($sender, (array)$newsletter->getSender()->about());
			$node->appendChild($sender);

			// recipients
			$recipients = new XMLElement('recipient-groups');
			foreach($newsletter->getRecipientGroups() as $group){
				$rgroup = new XMLElement('group');
				General::array_to_xml($rgroup, (array)$group->about());
				$recipients->appendChild($rgroup);
			}
			$node->appendChild($recipients);

			$template = new XMLElement('template');
			General::array_to_xml($template, (array)$newsletter->getTemplate()->about);
			$node->appendChild($template);

			$wrapper->appendChild($node);
		}

		/**
		 * Provide example form markup
		 */
		public function getExampleFormMarkup(){
			// nothing to show here
			return;
		}
/*-------------------------------------------------------------------------
	Filtering
-------------------------------------------------------------------------*/
		public function displayDatasourceFilterPanel(&$wrapper, $data=NULL, $errors=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			$wrapper->appendChild(new XMLElement('h4', $this->get('label') . ' <i>'.$this->Name().'</i>'));
			$label = Widget::Label(__('Newsletter ID'));
			$label->appendChild(Widget::Input('fields[filter]'.($fieldnamePrefix ? '['.$fieldnamePrefix.']' : '').'['.$this->get('id').']'.($fieldnamePostfix ? '['.$fieldnamePostfix.']' : ''), ($data ? General::sanitize($data) : null)));
			$wrapper->appendChild($label);
		}

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			if (!is_array($data)) $data = array($data);

			foreach ($data as &$value) {
				$value = $this->cleanValue($value);
			}

			$this->_key++;
			$data = implode("', '", $data);
			$joins .= "
				LEFT JOIN
					`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
					ON (e.id = t{$field_id}_{$this->_key}.entry_id)
			";
			$where .= "
				AND t{$field_id}_{$this->_key}.newsletter_id IN ('{$data}')
			";

			return true;
		}

/*-------------------------------------------------------------------------
	Helpers
-------------------------------------------------------------------------*/
		protected function _addStandardForm($newsletter, &$gui){
			foreach($newsletter->getRecipientGroups(false, true) as $group){
				$gui->appendChild(Widget::Input(
					'fields['.$this->get('element_name').'][recipient_groups][]',
					$group,
					'hidden')
				);
			}
			$gui->appendChild(Widget::Input(
				'fields['.$this->get('element_name').'][sender]',
				$newsletter->getSender()->getHandle(),
				'hidden')
			);
			$gui->appendChild(Widget::Input(
				'fields['.$this->get('element_name').'][template]',
				$newsletter->getTemplate()->getHandle(),
				'hidden')
			);
			$gui->appendChild(Widget::Input(
				'fields['.$this->get('element_name').'][newsletter_id]',
				$newsletter->getId(),
				'hidden')
			);
		}

		protected function _addInfoIfApplicable($newsletter, &$gui){
			$displayTemplate = count(explode(',', $this->get('templates'))) > 1;
			$displaySender = count(explode(',', $this->get('senders'))) > 1;
			$displayRecipientGroups = count(explode(',', $this->get('recipient_groups'))) > 1;

			$recipient_groups = array();
			foreach($newsletter->getRecipientGroups(false, false) as $group){
				$about = $group->about();
				$recipient_groups[] = $about['name'];
			}

			$info = null;
			if($displayTemplate){
				$info .= sprintf(__('Email Template: %s'), $newsletter->getTemplate()->about['name']);
			}
			if($displayTemplate && $displayRecipientGroups){
				$info .= '<br />';
			}
			if($displaySender){
				$info .= sprintf(__('Sender: %s'), $newsletter->getSender()->getName());
			}
			if($displayRecipientGroups && $displaySender){
				$info .= '<br />';
			}
			if($displayRecipientGroups){
				$info .= sprintf(__('Recipient groups: %s'), implode(', ', $recipient_groups));
			}

			if(!empty($info)){
				$gui->appendChild(new XMLElement('p', $info, array('class'=>'stats')));
			}
		}
	}
