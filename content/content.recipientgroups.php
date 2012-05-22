<?php

if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");
if(!defined('ENVIEWS')) define('ENVIEWS', ENMDIR . "/content/templates");

if(!class_exists('ExtensionPage')){
	require_once(ENMDIR . '/lib/class.extensionpage.php');
}

require_once(TOOLKIT . '/class.xsltprocess.php');
require_once(TOOLKIT . '/class.sectionmanager.php');
require_once(ENMDIR . '/lib/class.recipientgroupmanager.php');

Class contentExtensionemail_newsletter_managerrecipientgroups extends ExtensionPage{

	function __construct(){
		$this->_XSLTProc = new XsltProcess();
		$this->_XML = new XMLElement("data");
		$this->viewDir = ENVIEWS . '/recipientgroups';
		parent::__construct(Symphony::Engine());
	}

	function __viewIndex(){
		$this->setPageType('index');
		$this->setTitle(__("Symphony - Newsletter Recipient Groups"));
		$groups = RecipientgroupManager::listAll();
		$recipientgroups = new XMLElement('recipientgroups');
		foreach($groups as $group){
			$entry = new XMLElement('entry');
			General::array_to_xml($entry, $group);
			$count = new XMLElement('count', RecipientgroupManager::create($group['handle'])->getCount());
			$entry->appendChild($count);
			$recipientgroups->appendChild($entry);
		}
		$this->_XML->appendChild($recipientgroups);
	}

	function __actionIndex(){
		if($_POST['with-selected'] == 'delete'){
			foreach((array)$_POST['items'] as $item=>$status){
				RecipientgroupManager::delete($item);
			}
		}
	}

	function __viewEdit($new = false){
		$this->setPageType('form');
		$this->setTitle(sprintf(__("Symphony - Newsletter Recipient Groups - %s", Array(), false), ucfirst($this->_context[1])));

		$errors = new XMLElement('errors');

		$context = new XMLElement('context');
		General::array_to_xml($context, $this->_context);
		$this->_XML->appendChild($context);

		$section_xml = new XMLElement('sections');
		$sectionManager = new SectionManager($this);
		$sections = $sectionManager->fetch();
		foreach($sections as $section){
			$entry = new XMLElement('entry');
			General::array_to_xml($entry, $section->get());
			foreach($section->fetchFields() as $field){
				$field_xml = new XMLElement('field');
				General::array_to_xml($field_xml,$field->get());

				$filter_html = new XMLElement('filter_html');
				$field->displayDatasourceFilterPanel($filter_html, NULL, $errors, $section->get('id'));
				$field_xml->appendChild($filter_html);

				$field_elements = new XMLElement('elements');
				General::array_to_xml($field_elements, $field->fetchIncludableElements());
				$field_xml->appendChild($field_elements);
				$entry->appendChild($field_xml);
			}
			$section_xml->appendChild($entry);
		}
		$this->_XML->appendChild($section_xml);

		$recipientgroups = new XMLElement('recipientgroups');

		if($this->_context[2] == 'saved' || $this->_context[3] == 'saved'){
			$this->pageAlert(
				__(
					__('Email Recipient updated at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Recipient Groups</a>'),
					array(
						DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
						SYMPHONY_URL . '/extension/email_newsletter_manager/recipientgroups/new/',
						SYMPHONY_URL . '/extension/email_newsletter_manager/recipientgroups/',
					)
				),
				Alert::SUCCESS
			);
		}

		if($new == false){
			/*
				TODO add POST values to XML
			*/
			$group = RecipientgroupManager::create($this->_context[1]);
			if(is_object($group)){
				$entry = new XMLElement('entry');
				$properties = $group->getProperties();
				General::array_to_xml($entry, $group->about());

				$source = new XMLElement('source', $properties['source']);
				$entry->appendChild($source);

				// Section Only
				if(is_numeric($properties['source'])){

					$fields = new XMLElement('fields');

					$email = new XMLElement('email', $properties['email']);
					$fields->appendChild($email);

					$name = new XMLElement('name');
					General::array_to_xml($name, $properties['name']);
					$fields->appendChild($name);

					$entry->appendChild($fields);
				}

				// Hack to make sure filter data is preserved in the UI when there is an error in the form.
				// For next versions: always do the local/user differentiation in php, rather than xslt.
				// This will make the xslt cleaner and easier to understand and debug.
				if(!empty($_POST['fields'])){
					$properties['filters'] = $_POST['fields']['filter'][0];
				}
				if(!empty($properties['filters'])){
					$filters = new XMLElement('filters');
					$fieldManager = new FieldManager($this);
					foreach($properties['filters'] as $filter=>$val){
						// Section and Author
						if($filter == 'id'){
							$title = new XMLElement('h4', 'System ID');
							$label = Widget::Label(__('Value'));
							$label->appendChild(Widget::Input('fields[filter]['.$properties['source'].'][id]', General::sanitize($val)));
							$filter_entry = new XMLElement('entry', NULL, array('id'=>'id', 'data-type'=>'id'));
							$filter_entry->appendChild($title);
							$filter_entry->appendChild($label);
							$filters->appendChild($filter_entry);
						}
						if($filter == 'system:date'){
							$title = new XMLElement('h4', 'System Date');
							$label = Widget::Label(__('Value'));
							$label->appendChild(Widget::Input('fields[filter]['.$properties['source'].'][system:date]', General::sanitize($val)));
							$filter_entry = new XMLElement('entry', NULL, array('id'=>'id', 'data-type'=>'system:date'));
							$filter_entry->appendChild($title);
							$filter_entry->appendChild($label);
							$filters->appendChild($filter_entry);
						}
						// Section Only
						if(is_numeric($properties['source'])){
							$section = $sectionManager->fetch($properties['source']);
							if(is_object($section)){
								$section_fields = $section->fetchFields();
								foreach ($section_fields as $field) {
									$field_ids[] = $field->get('id');
								}
								// only add filters to the duplicator if the field id
								// belongs to the current section
								if(is_numeric($filter) && in_array($filter, $field_ids)){
									$filter_obj = $fieldManager->fetch($filter);
									if(is_object($filter_obj)){
										$filter_entry = new XMLElement('entry', NULL, array('id'=>$filter, 'data-type'=>$fieldManager->fetchHandleFromID($filter)));
										$filter_obj->displayDatasourceFilterPanel($filter_entry, $val, $errors, is_numeric($properties['source'])?$properties['source']:1);
										$filters->appendChild($filter_entry);
									}
								}
							}
						}
						// Author only
						if($properties['source'] == 'authors'){
							$filter_names = array('username'=>'Username', 'first_name'=>'First Name', 'last_name'=>'Last Name', 'email'=>'Email Address', 'user_type'=>'User Type');
							if(in_array($filter, array_keys($filter_names))){
								$title = new XMLElement('h4', $filter_names[$filter]);
								$label = Widget::Label(__('Value'));
								$label->appendChild(Widget::Input('fields[filter]['.$properties['source'].'][username]', General::sanitize($val)));
								$filter_entry = new XMLElement('entry', NULL, array('id'=>'id', 'data-type'=>'username'));
								$filter_entry->appendChild($title);
								$filter_entry->appendChild($label);
								$filters->appendChild($filter_entry);
							}
						}
					}
					$entry->appendChild($filters);
				}

				if($properties['source'] == 'static_recipients'){
					$entry->appendChild(new XMLElement('static_recipients', '<![CDATA[' . $group->recipients . ']]>'));
				}

				$recipientgroups->appendChild($entry);
				$this->_XML->appendChild($recipientgroups);
			}
			else{
				Administration::instance()->errorPageNotFound();
			}
		}
	}

	function __actionEdit($new = false){
		$fields = $_POST['fields'];

		$fields['dependencies'] = array();
		try{
			$result = RecipientGroupManager::create($this->_context[1]);
			$fields['dependencies'] = $result->_dependencies;
		}
		catch(Exception $e){
		}
		if(isset($_POST['action']['delete'])){
			if(RecipientgroupManager::delete($this->_context[1])){
				redirect(SYMPHONY_URL . '/extension/email_newsletter_manager/recipientgroups');
			}
			else{
				$this->pageAlert(
					__('Could not delete, please check file permissions'),
					Alert::ERROR
				);
				return true;
			}
		}

		$post_fields = new XMLElement('post-fields');
		General::array_to_xml($post_fields, $fields);
		$this->_XML->appendChild($post_fields);

		$errors = new XMLElement('errors');
		if(!empty($fields['name']) && !empty($fields['name-xslt']) && (General::validateXML($fields['name-xslt'], $error, false) == true)){
			try{
				if(RecipientGroupManager::save(str_replace('_', '-', $this->_context[1]), $fields)){
					redirect(SYMPHONY_URL . '/extension/email_newsletter_manager/recipientgroups/edit/' . Lang::createHandle($fields['name'], 225, '_') . '/saved');
					return true;
				}
			}
			catch(Exception $e){
				$this->pageAlert(__('Could not save: ' . $e->getMessage()),Alert::ERROR);
			}
		}
		if(empty($fields['name'])){
			$errors->appendChild(new XMLElement('name', __('This field can not be empty.')));
		}
		if(strlen(Lang::createHandle($fields['name'])) == 0){
			$errors->appendChild(new XMLElement('name', __('This field must at least contain a number or a letter')));
		}
		if(empty($fields['name-xslt'])){
			$errors->appendChild(new XMLElement('name-xslt', __('This field can not be empty.')));
		}
		if(!General::validateXML($fields['name-xslt'], $error, false)){
			$errors->appendChild(new XMLElement('name-xslt', __('XML is invalid')));
		}
		$this->_XML->appendChild($errors);
		$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
	}

	function __actionNew(){
		$this->__actionEdit(true);
	}

	function __viewNew(){
		$this->_context[1] = 'New';
		$this->_useTemplate = 'viewEdit';
		$this->__viewEdit(true);
	}

	function __viewTest(){
		require_once(ENMDIR . '/lib/class.emailnewslettermanager.php');
		EmailNewsletterManager::updateRecipientsHandle('test', 'huib');
		//$newsletter = EmailNewsletterManager::create(1);
		//var_dump($newsletter->getStats());
		//$newsletter->start();
		exit();
	}

	function __viewPreview(){
		$this->setPageType('index');
		$this->setTitle(__("Symphony - Newsletter Recipient Groups Preview"));
		$sectionManager = new SectionManager($this);
		try{
			$source = RecipientgroupManager::create($this->_context[1]);
		}
		catch(Exception $e){
			Administration::instance()->errorPageNotFound();
		}
		if($_GET['pg']){
			$source->dsParamSTARTPAGE = (int)$_GET['pg'];
		}
		$source->dsParamLIMIT = 17;
		$elements = $source->getSlice();
		$recipients = new XMLElement('recipients');
		General::array_to_xml($recipients, $source->about());
		General::array_to_xml($recipients, array('source' => is_numeric($section = $source->getSource())?$sectionManager->fetch($source->getSource())->get('handle'):'system:'.$source->getSource()));
		General::array_to_xml($recipients, $elements);
		$context = new XMLElement('context');
		General::array_to_xml($context, $this->_context);
		$this->_XML->appendChild($context);
		$this->_XML->appendChild($recipients);
	}
}