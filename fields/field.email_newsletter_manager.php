<?php

	/**
	 * Field: Email Newsletter
	 *
	 * @package Email Newsletter Manager
	 **/
	class fieldEmail_Newsletter_Manager extends Field
	{
		protected $_field_id;
		protected $_entry_id;
		protected $_section_id;

		/**
		 * Initialize as unrequired field
		 */
		function __construct(&$parent)
		{
			parent::__construct($parent);
			$this->_name = __('Email Newsletter Manager');
			$this->_required = false;
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
		public function displaySettingsPanel(&$wrapper, $errors=NULL)
		{
			## initialize field settings based on class defaults (name, placement)
			parent::displaySettingsPanel($wrapper, $errors);

			## get current section id
			$section_id = Administration::instance()->Page->_context[1];

			## configuration (XML)
			$label = Widget::Label(__('Configuration (XML)'));
			$label->appendChild(Widget::Textarea('fields['.$this->get('sortorder').'][config_xml]', 12, 50, General::sanitize($this->get('config_xml')), array('class' => 'code')));
			if(isset($errors['config_xml']))
			{
				$wrapper->appendChild(Widget::wrapFormElementWithError($label, $errors['config_xml']));
			}
			else
			{
				$wrapper->appendChild($label);
			}
			$this->appendShowColumnCheckbox($wrapper);
		}

		/**
		 * Checks fields for errors in section editor.
		 *
		 * @param array $errors
		 * @param boolean $checkForDuplicates
		 */
		public function checkFields(&$errors, $checkForDuplicates=true)
		{
			if(!is_array($errors)) $errors = array();
			if(@simplexml_load_string($this->get('config_xml')) == false)
			{
				$errors['config_xml'] = __('XML is invalid');
			}
			parent::checkFields($errors, $checkForDuplicates);
		}

		/**
		* Save fields settings in section editor
		*/
		public function commit()
		{
			## prepare commit
			if(!parent::commit()) return false;
			$id = $this->get('id');
			if($id === false) return false;

			## set up fields
			$fields = array();
			$fields['field_id'] = $id;
			if($this->get('config_xml')) $fields['config_xml'] = $this->get('config_xml');

			## delete old field settings
			Symphony::Database()->query("DELETE FROM `tbl_fields_" . $this->handle()."` WHERE `field_id` = '$id' LIMIT 1");

			## save new field setting
			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
		}

		/**
		 * Create database table for entries
		 */
		public function createTable()
		{
			Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_".$this->get('id')."` (
				 `id` int(11) unsigned NOT NULL auto_increment,
				 `entry_id` int(11) unsigned NOT NULL,
				 `author_id` int(11) unsigned NOT NULL,
				 `sender_id` int(11) unsigned default NULL,
				 `rec_group_ids` varchar(255) default NULL,
				 `config_xml` text,
				 `status` enum('processing','cancel','error','sent') NULL default NULL,
				 `error_message` varchar(255) default NULL,
				 `log_file` varchar(255) default NULL,
				 `subject` varchar(255) default NULL,
				 `content_html` mediumtext,
				 `content_text` mediumtext,
				 `rec_mailto` mediumtext,
				 `rec_invalid` mediumtext,
				 `rec_replacements` mediumtext,
				 `stats_rec_total` int(10) unsigned default NULL,
				 `stats_rec_sent` int(10) unsigned default NULL,
				 `stats_rec_errors` int(10) unsigned default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`)
				) ENGINE=MyISAM;"
			);
			return true;
		}

/*-------------------------------------------------------------------------
	Publish: entries table
-------------------------------------------------------------------------*/
		/**
		 * Append newsletter status to entry table
		 */
		public function prepareTableValue($data, XMLElement $link=NULL)
		{
			if(!is_array($data) || empty($data)) return;
			$value = null;
			if(isset($data['status']))
			{
				$value = $data['status'];
			}
			switch ($value)
			{
				case 'processing':
					$value = __('Processing...');
					break;
				case 'cancel':
					$value = __('Cancelling...');
					break;
				case 'error':
					$value = __('ERROR');
					break;
				case 'sent':
					$value = __('Sent');
					break;
				default:
					$value = NULL;
			}
			return parent::prepareTableValue(array('value' => $value), $link);
		}

		/**
		 * Is the table column sortable?
		 *
		 * @return boolean
		 */
		public function isSortable()
		{
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
		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL)
		{
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/email_newsletters/assets/email-newsletters.js', 1001);
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/email_newsletters/assets/email-newsletters.css', 'screen', 1000);

			$this->_field_id = $this->get('id');
			$this->_entry_id = Administration::instance()->Page->_context['entry_id'];
			$section_handle = Administration::instance()->Page->_context['section_handle'];
			$this->_section_id = Symphony::Database()->fetchVar('id', 0, "SELECT id FROM `tbl_sections` WHERE `handle` = '".$section_handle."' LIMIT 1");

			## post action 'cancel';
			## status must not be 'error' (which will be the final status after cancelling) to prohibit problems caused by page reload
			$status = !empty($this->_entry_id) ? Symphony::Database()->fetchVar('status', 0, "SELECT status FROM `tbl_entries_data_".$this->_field_id."` WHERE `entry_id` = $this->_entry_id LIMIT 1") : NULL;
			if(isset($_POST['action']['en-cancel-'.$this->_field_id]) && $status != 'error')
			{
				$this->__updateEntryData(array(
					'status' => 'cancel',
				));
			}

			## post action 'retry'
			if(isset($_POST['action']['en-retry-'.$this->_field_id]))
			{
				$this->__updateEntryData(array(
					'status' => NULL,
					'error_message' => NULL,
					'config_xml' => NULL,
					'content_html' => NULL,
					'content_text' => NULL,
					'rec_mailto' => NULL,
					'rec_invalid' => NULL,
					'rec_replacements' => NULL,
					'stats_rec_total' => NULL,
					'stats_rec_sent' => NULL,
					'stats_rec_errors' => NULL,
				));
			}

			## get field configuration data
			$field_data = Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_fields_email_newsletter` WHERE `field_id` = $this->_field_id LIMIT 1");

			## get entry data
			$entry_data = !empty($this->_entry_id) ? Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_entries_data_".$this->_field_id."` WHERE `entry_id` = $this->_entry_id LIMIT 1") : NULL;

			$config                 = simplexml_load_string($field_data['config_xml']);
			$element_name           = $this->get('element_name');
			$author_id              = Administration::instance()->Author->get('id');
			$status                 = $entry_data['status'];
			$sender_id              = $entry_data['sender_id'];
			$senders                = $config->xpath('senders/item');
			$live_mode              = (string)$config->{'live-mode'} == '1' ? true : false;
			$debug_info             = (string)$config->{'debug-info'} == '1' ? true : false;
			$is_developer           = Administration::instance()->Author->isDeveloper();
			$debug                  = ($is_developer === true && $debug_info === true) ? true : false;
			$rec_groups             = $config->xpath('recipients/group');
			$recipient_group_ids    = explode(',',$entry_data['rec_group_ids']);
			$page_html_id           = (string)$config->content->{'page-html'}['page-id'];
			$page_text_id           = (string)$config->content->{'page-text'}['page-id'];
			$page_html_url_appendix = (string)$config->content->{'page-html'}['url-appendix'];
			$page_text_url_appendix = (string)$config->content->{'page-text'}['url-appendix'];
			$subject_field_label    = (string)$config->{'subject-field-label'};

			## find the newsletter subject
			$subject_field_handle = Lang::createHandle($subject_field_label);
			$subject_field_id = $subject_field_handle ? Symphony::Database()->fetchVar('id', 0, "SELECT id FROM `tbl_fields` WHERE `element_name` = '".$subject_field_handle."' AND `parent_section` = '".$this->_section_id."' LIMIT 1") : NULL;
			if($subject_field_id != $this->_field_id)
			{
				$subject = $subject_field_id ? Symphony::Database()->fetchVar('value', 0, "SELECT value FROM `tbl_entries_data_".$subject_field_id."` WHERE `entry_id` = '".$this->_entry_id."' LIMIT 1 ") : NULL;
			}

			## build header
			$header = new XMLElement('h3', $this->get('label'));
			$wrapper->appendChild($header);

			## build GUI element
			$gui = new XMLElement('div');
			$gui->setAttribute('class', 'email-newsletters-gui');

			## build the hidden fields
			$gui->appendChild(Widget::Input('fields['.$element_name.'][author_id]', $author_id, 'hidden'));
			if($status !== NULL)
			{
				$gui->appendChild(Widget::Input('fields['.$element_name.'][sender_id]', $sender_id, 'hidden'));
				foreach($rec_groups as $rec_group)
				{
					if(in_array($rec_group['id'], $recipient_group_ids))
					{
						$gui->appendChild(Widget::Input('fields['.$element_name.'][recipient_group_ids][]', $rec_group['id'], 'hidden'));
					}
				}
			}
			$gui->appendChild(Widget::Input('fields['.$element_name.'][subject]', General::sanitize($subject), 'hidden'));

			## switch status
			switch ($status)
			{
				case "processing":
					$gui->setAttribute('class', 'email-newsletters-gui reloadable');
					$gui->appendChild(new XMLElement('p', __('Processing...')));
					## calculate estimated time to run if throttling is active
					## make sure that the first slice has been sent
					## otherwise the initial value would definitely be wrong if the first slice sends all the emails
					$mailer_params['throttle_number'] = (string)$config->throttling->{"emails-per-time-period"};
					$mailer_params['throttle_period'] = (string)$config->throttling->{"time-period-in-seconds"};
					$throttling_active = intval($mailer_params['throttle_number']) && intval($mailer_params['throttle_period']) ? true : false;
					$recipients_exist = !empty($entry_data['stats_rec_total']) ? true : false;
					$first_slice_sent = (!empty($entry_data['stats_rec_sent']) || !empty($entry_data['stats_rec_errors'])) ? true : false;
					if($throttling_active && $recipients_exist && $first_slice_sent)
					{
						$time_to_run = ceil(($entry_data['stats_rec_total'] - $entry_data['stats_rec_sent'] - $entry_data['stats_rec_errors']) / intval($mailer_params['throttle_number'])) * intval($mailer_params['throttle_period']);
						$gui->appendChild(new XMLElement('p', __('Estimated time left: ') . gmdate('H:i:s', $time_to_run)));
					}
					## standards
					if(!empty($sender_id) && count($senders) > 1)
					{
						$sender = $config->xpath("senders/item[@id = $sender_id]");
						$gui->appendChild(new XMLElement('p', __('Sender: ') . (string)$sender[0]));
					}
					$rec_groups_used = array();
					foreach($rec_groups as $rec_group)
					{
						if(in_array($rec_group['id'], $recipient_group_ids))
						{
							$rec_groups_used[] = $rec_group;
						}
					}
					if(!empty($rec_groups_used) && (count($rec_groups) > 1 || $debug === true))
					{
						$gui->appendChild(new XMLElement('p', __('Recipient groups: ') . implode(', ', $rec_groups_used)));
					}
					$gui->appendChild($this->__buildStatusTable($entry_data));
					$p = new XMLElement('p');
					$p->appendChild(new XMLElement('button', __('Cancel'), array('name' => 'action[en-cancel-'.$this->_field_id.']', 'type' => 'submit', 'class' => 'send')));
					$gui->appendChild($p);
					break;

				case "cancel":
					$gui->setAttribute('class', 'email-newsletters-gui reloadable');
					$gui->appendChild(new XMLElement('p', __('Cancelling...')));
					break;

				case "error":
					$gui->appendChild(new XMLElement('p', __($entry_data['error_message'])));
					$gui->appendChild($this->__buildStatusTable($entry_data));
					$p = new XMLElement('p');
					$p->appendChild(new XMLElement('button', __('Retry'), array('name' => 'action[en-retry-'.$this->_field_id.']', 'type' => 'submit', 'class' => 'send')));
					$gui->appendChild($p);
					break;

				case "sent":
					$gui->appendChild(new XMLElement('p', '<strong>' . __('Mailing complete.') . '</strong>'));
					$senders = $config->xpath('senders/item');
					if(!empty($sender_id) && count($senders) > 1)
					{
						$sender = $config->xpath("senders/item[@id = $sender_id]");
						$gui->appendChild(new XMLElement('p', __('Sender: ') . (string)$sender[0]));
					}
					$rec_groups_used = array();
					foreach($rec_groups as $rec_group)
					{
						if(in_array($rec_group['id'], $recipient_group_ids))
						{
							$rec_groups_used[] = $rec_group;
						}
					}
					if(!empty($rec_groups_used) && (count($rec_groups) > 1 || $debug === true))
					{
						$gui->appendChild(new XMLElement('p', __('Recipient groups: ') . implode(', ', $rec_groups_used)));
					}
					$gui->appendChild($this->__buildStatusTable($entry_data));
					if($live_mode !== true)
					{
						$p = new XMLElement('p');
						$p->appendChild(new XMLElement('button', __('Retry'), array('name' => 'action[en-retry-'.$this->_field_id.']', 'type' => 'submit', 'class' => 'send')));
						$gui->appendChild($p);
					}
					break;

				default:
					$senders = $config->xpath('senders/item');
					if(count($senders) > 1)
					{
						$p = new XMLElement('p');
						$options[] = array(NULL, NULL, __('--- please select ---'));
						foreach($senders as $sender)
						{
							$sender_name = (string)$sender;
							$sender_id = $sender['id'];
							$options[] = array($sender_id, $entry_data['sender_id'] == $sender_id, $sender_name);
						}
						$p->appendChild(Widget::label(__('Sender: '), Widget::Select('fields['.$element_name.'][sender_id]', $options)));
						$gui->appendChild($p);
					}
					else
					{
						$sender_id = $senders[0]['id'];
						$gui->appendChild(Widget::Input('fields['.$element_name.'][sender_id]', $sender_id, 'hidden'));
					}
					if(count($rec_groups) > 1 || $debug === true)
					{
						$p = new XMLElement('p', __('Recipient groups: '));
						$gui->appendChild($p);
						$p = new XMLElement('p', NULL, array('class' => 'recipient-groups'));
						foreach($rec_groups as $rec_group)
						{
							$label = Widget::Label();
							$recipients = Widget::Input('fields['.$element_name.'][recipient_group_ids][]', $rec_group['id'], 'checkbox', (!empty($rec_group['id']) && (in_array($rec_group['id'], $recipient_group_ids)) ? array('checked' => 'checked') : NULL));
							if($debug === true)
							{
								$rec_group_id = (string)$rec_group['id'];
								$recipients_page_id = (string)$rec_group['page-id'];
								$recipients_page_path = Administration::instance()->resolvePagePath($recipients_page_id);
								$recipients_page_url = URL . '/' . $recipients_page_path . '/' . $this->__replaceParamsInString($rec_group['url-appendix']);
								$developer_info = $recipients_page_path ? ' '.'<a href="'.$recipients_page_url.'" target="_blank">'.'XML'.'</a>' : '';
								$rec_group = $rec_group . $developer_info;
							}
							$label->setValue($recipients->generate() . $rec_group);
							$label->setAttribute('class', 'recipient-group');
							$p->appendChild($label);
						}
						$gui->appendChild($p);
					}
					else
					{
						$gui->appendChild(Widget::Input('fields['.$element_name.'][recipient_group_ids][]', $rec_groups[0]['id'], 'hidden'));
					}
					if(isset($this->_entry_id))
					{
						## content links
						$page_html_url = URL . '/' . Administration::instance()->resolvePagePath($page_html_id) . '/' . ltrim($this->__replaceParamsInString($page_html_url_appendix), '/');
						$page_text_url = URL . '/' . Administration::instance()->resolvePagePath($page_text_id) . '/' . ltrim($this->__replaceParamsInString($page_text_url_appendix), '/');
						$content_html_link = $page_html_id ? '<a href="'.$page_html_url.'" target="_blank">HTML</a>' : NULL;
						$content_text_link = $page_text_id ? '<a href="'.$page_text_url.'" target="_blank">TEXT</a>' : NULL;
						$content_links = new XMLElement('p', __('Content: ') . $content_html_link . (($page_html_id && $page_text_id) ? ' | ' : '') . $content_text_link);
						$gui->appendChild($content_links);
						$p = new XMLElement('p');
						$p->appendChild(new XMLElement('button', __('Send'), array('name' => 'action[save]', 'type' => 'submit', 'value' => 'en-send:'.$this->_field_id.':'.$this->_entry_id.':'.DOMAIN.':'.$live_mode, 'class' => 'send', 'id' => 'savesend')));
						$gui->appendChild($p);
						## append 'no live mode' information
						if($live_mode !== true)
						{
							$gui->appendChild(new XMLElement('p', __('Live Mode has not been set. No emails will be sent.')));
						}
					}
					else
					{
						$p = new XMLElement('p', __('The entry has not been created yet. No emails can be sent.'));
						$gui->appendChild($p);
					}
			}
			$wrapper->appendChild($gui);
		}

		/**
		 * Prepares field values for database.
		 */
		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null)
		{
			$status = self::__OK__;
			if(empty($data)) return NULL;

			if($entry_id) $entry_data = Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_entries_data_".$this->get('id')."` WHERE `entry_id` = $entry_id LIMIT 1");

			$result = array(
				'author_id'        => $data['author_id'],
				'sender_id'        => $data['sender_id'],
				'rec_group_ids'    => $data['recipient_group_ids'] ? implode(',', $data['recipient_group_ids']) : NULL,
				// Subject: strip excess whitespace
				'subject'          => preg_replace('/\s\s+/', ' ', trim($data['subject'])),
				'status'           => $entry_data['status'],
				'error_message'    => $entry_data['error_message'],
				'config_xml'       => $entry_data['config_xml'],
				'log_file'         => $entry_data['log_file'],
				'content_html'     => $entry_data['content_html'],
				'content_text'     => $entry_data['content_text'],
				'rec_mailto'       => $entry_data['rec_mailto'],
				'rec_invalid'      => $entry_data['rec_invalid'],
				'rec_replacements' => $entry_data['rec_replacements'],
				'stats_rec_total'  => $entry_data['stats_rec_total'],
				'stats_rec_sent'   => $entry_data['stats_rec_sent'],
				'stats_rec_errors' => $entry_data['stats_rec_errors'],
			);
			return $result;
		}

/*-------------------------------------------------------------------------
	Output
-------------------------------------------------------------------------*/
		/**
		 * Allow data source filtering?
		 * @return: boolean
		 */
		public function canFilter()
		{
			return false;
		}

		/**
		 * Allow data source parameter output?
		 * @return: boolean
		 */
		public function allowDatasourceParamOutput()
		{
			return true;
		}

		/**
		 * get param pool value
		 * @return: string email newsletter sender ID
		 */
		public function getParameterPoolValue($data)
		{
			return $data['sender_id'];
		}

		/**
		 * Fetch includable elements (DS editor)
		 * @return: array() elements
		 */
		public function fetchIncludableElements()
		{
			return array(
				$this->get('element_name')
			);
		}

		/**
		 * Append element to datasource output
		 */
		public function appendFormattedElement(&$wrapper, $data, $encode = false)
		{
			$node = new XMLElement($this->get('element_name'));
			$node->setAttribute('author-id', $data['author_id']);
			$node->setAttribute('status', $data['status']);
			$node->setAttribute('total', $data['stats_rec_total']);
			$node->setAttribute('sent', $data['stats_rec_sent']);
			$node->setAttribute('errors', $data['stats_rec_errors']);
			$node->appendChild(new XMLElement('subject', $data['subject']));

			## load configuration;
			## use saved (entry) config XML if available (i.e.: if the email newsletter has been sent);
			## fallback: the field's configuration XML
			if(!empty($data['config_xml']))
			{
				$config = simplexml_load_string($data['config_xml']);
			}
			else
			{
				$field_data = Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_fields_email_newsletter` WHERE `field_id` = ".$this->get('id')." LIMIT 1");
				$config = simplexml_load_string($field_data['config_xml']);
			}

			## sender
			$sender = new XMLElement('sender');
			$sender_id = $data['sender_id'];
			if(!empty($sender_id))
			{
				$sender_data = $config->xpath("senders/item[@id = $sender_id]");
				$sender->setValue((string)$sender_data[0]);
				$sender->setAttribute('id', $data['sender_id']);
			}
			$node->appendChild($sender);

			## recipients
			$rec_groups = $config->xpath('recipients/group');
			$recipient_group_ids = explode(',', $data['rec_group_ids']);
			$recipients = new XMLElement('recipients');
			foreach($rec_groups as $rec_group)
			{
				if(in_array($rec_group['id'], $recipient_group_ids))
				{
					$group = new XMLElement('group', $rec_group);
					$group->setAttribute('id', $rec_group['id']);
					$recipients->appendChild($group);
				}
			}
			$node->appendChild($recipients);

			$wrapper->appendChild($node);
		}

		/**
		 * Provide example form markup
		 */
		public function getExampleFormMarkup()
		{
			## nothing to show here
			return;
		}

/*-------------------------------------------------------------------------
	Helpers
-------------------------------------------------------------------------*/
		/**
		 * Update entry data
		 *
		 * @return Symphony method
		 **/
		private function __updateEntryData($array)
		{
			return Symphony::Database()->update($array, 'tbl_entries_data_'.$this->_field_id, "`entry_id` = '".$this->_entry_id."'");
		}

		/**
		 * Replace parameters in string
		 *
		 * @param string $string
		 * @return string $string
		 */
		private function __replaceParamsInString($string)
		{
			$params = $this->__findParamsInString($string);
			if(is_array($params) && !empty($params))
			{
				foreach($params as $value)
				{
					if($value == 'id')
					{
						$string = str_replace('{$'.$value.'}', $this->_entry_id, $string);
					}
					else if($field_id = Symphony::Database()->fetchVar('id', 0, "SELECT id FROM `tbl_fields` WHERE `element_name` = '".$value."' AND `parent_section` = '".$this->_section_id."' LIMIT 1"))
					{
						$field_handle = Symphony::Database()->fetchVar('handle', 0, "SELECT handle FROM `tbl_entries_data_".$field_id."` WHERE `entry_id` = '".$this->_entry_id."' LIMIT 1");
						$string = str_replace('{$'.$value.'}', $field_handle, $string);
					}
				}
			}
			return $string;
		}

		/**
		 * Find parameters in string
		 *
		 * @param string $string
		 * @return array $params
		 */
		private function __findParamsInString($string)
		{
			preg_match_all('/{\$([^:}]+)(::handle)?}/', $string, $matches);
			$params = array_unique($matches[1]);
			if(!is_array($params) || empty($params)) return array();
			return $params;
		}

		/**
		 * Build the email newsletter status table (counts)
		 *
		 * @param array $entry_data
		 * @return string HTML table
		 */
		private function __buildStatusTable($entry_data)
		{
			$aTableHead = array(
				array(__('Total'), 'col'),
				array(__('Sent'), 'col'),
				array(__('Errors'), 'col'),
			);
			$td1 = Widget::TableData($entry_data['stats_rec_total'] ? $entry_data['stats_rec_total'] : '-');
			$td2 = Widget::TableData($entry_data['stats_rec_sent'] ? $entry_data['stats_rec_sent'] : '0');
			$td3 = Widget::TableData($entry_data['stats_rec_errors'] ? $entry_data['stats_rec_errors'] : '0');
			$aTableBody = array();
			$aTableBody[] = Widget::TableRow(array($td1, $td2, $td3));
			$table = Widget::Table(
				Widget::TableHead($aTableHead),
				NULL,
				Widget::TableBody($aTableBody),
				NULL
			);
			$table->setAttributeArray(array('class' => 'status'));
			return $table;
		}
	}
