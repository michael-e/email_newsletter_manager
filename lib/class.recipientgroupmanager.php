<?php

require_once(TOOLKIT . '/class.manager.php');
if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");

class RecipientgroupManager{

	public static function __getHandleFromFilename($filename){
		$result = sscanf($filename, 'recipient_group.%[^.].php');
		return str_replace('_', '-', $result[0]);
	}

	public static function __getClassName($handle){
		return sprintf('recipientgroup%s', ucfirst(str_replace('-', '_', $handle)));
	}

	public static function __getClassPath($handle, $new = false){
		if(is_file(WORKSPACE . "/email-newsletters/recipient_group.".str_replace('-', '_', $handle).".php") || $new == true) return WORKSPACE . '/email-newsletters';
		else{
			$extensions = Symphony::ExtensionManager()->listInstalledHandles();

			if(is_array($extensions) && !empty($extensions)){
				foreach($extensions as $e){
					if(is_file(EXTENSIONS . "/$e/email-newsletters/recipient_group.".str_replace('-', '_', $handle).".php")) return EXTENSIONS . "/$e/email-newsletters";
				}
			}
		}

		return false;
	}

	public static function __getDriverPath($handle){
		return self::__getClassPath($handle, true) . '/recipient_group.'.str_replace('-', '_', $handle).'.php';
	}

	public static function listAll(){
		$result = array();

		$structure = General::listStructure(WORKSPACE . '/email-newsletters', '/recipient_group.[\\w-]+.php/', false, 'ASC', WORKSPACE . '/email-newsletters');

		if(is_array($structure['filelist']) && !empty($structure['filelist'])){
			foreach($structure['filelist'] as $f){
				$f = self::__getHandleFromFilename($f);

				if($about = self::about($f)){

					$classname = self::__getClassName($f);
					$path = self::__getDriverPath($f);

					$can_parse = false;
					$type = NULL;

					if(method_exists($classname,'allowEditorToParse')) {
						$can_parse = call_user_func(array($classname, 'allowEditorToParse'));
					}

					if(method_exists($classname,'getSource')) {
						$type = call_user_func(array($classname, 'getSource'));
					}

					$about['can_parse'] = $can_parse;
					$about['type'] = $type;
					$result[$f] = $about;
				}
			}
		}

		$extensions = Symphony::ExtensionManager()->listInstalledHandles();

		if(is_array($extensions) && !empty($extensions)){
			foreach($extensions as $e){
				if(!is_dir(EXTENSIONS . "/$e/email-newsletters")) continue;

				$tmp = General::listStructure(EXTENSIONS . "/$e/email-newsletters", '/recipient_group.[\\w-]+.php/', false, 'ASC', EXTENSIONS . "/$e/email-newsletters");

				if(is_array($tmp['filelist']) && !empty($tmp['filelist'])){
					foreach($tmp['filelist'] as $f){
						$f = self::__getHandleFromFilename($f);

						if($about = self::about($f)){
							$about['can_parse'] = false;
							$about['type'] = NULL;
							$result[$f] = $about;
						}
					}
				}
			}
		}

		ksort($result);
		return $result;
	}

	public static function &create($handle){
		$env =  array(
			'today' => DateTimeObj::get('Y-m-d'),
			'current-time' => DateTimeObj::get('H:i'),
			'this-year' => DateTimeObj::get('Y'),
			'this-month' => DateTimeObj::get('m'),
			'this-day' => DateTimeObj::get('d'),
			'timezone' => DateTimeObj::get('P'),
			'website-name' => Symphony::Configuration()->get('sitename', 'general'),
			'root' => URL,
			'workspace' => URL . '/workspace',
			'upload-limit' => min($upload_size_php, $upload_size_sym),
			'symphony-version' => Symphony::Configuration()->get('version', 'symphony'),
		);

		$classname = self::__getClassName($handle);
		$path = self::__getDriverPath($handle);

		if(!is_file($path)){
			throw new Exception(
				__(
					'Could not find Recipient Group <code>%s</code>. If the Recipient Group was provided by an Extension, ensure that it is installed, and enabled.',
					array($handle)
				)
			);
		}

		if(!class_exists($classname)) require_once($path);

		return new $classname(Symphony::Engine(), $env, $process_params);

	}

	public static function save($handle = NULL, $fields){
		if(strlen(Lang::createHandle($fields['name'])) == 0){
			return false;
		}
		if($handle == Lang::createHandle($fields['name'], 255, '-') || (($handle == NULL) && (self::__getClassPath(Lang::createHandle($fields['name'], 255, '-')) == false))){
			if(self::_writeRecipientSource(Lang::createHandle($fields['name'], 255, '_'), self::_parseTemplate($fields))){
				Symphony::ExtensionManager()->notifyMembers(
					'PostRecipientgroupSaved',
					'/extension/email_newsletter_manager/',
					array(
						'handle'		=> $handle,
						'fields' 		=> $fields
					)
				);
				return true;
			}
			else{
				return false;
			}
		}
		elseif(false == self::__getClassPath(Lang::createHandle($fields['name'], 255, '-'))){
			if(!self::_writeRecipientSource(Lang::createHandle($fields['name'], 255, '_'), self::_parseTemplate($fields))) return false;
			if(!@unlink(self::__getDriverPath($handle))) return false;
			Symphony::ExtensionManager()->notifyMembers(
				'PostRecipientgroupSaved',
				'/extension/email_newsletter_manager/',
				array(
					'handle'		=> $handle,
					'fields' 		=> $fields
				)
			);
			return true;
		}
		else{
			throw new Exception('Recipientsource ' . $fields['handle'] . ' already exists. Please choose a different name.');
		}
	}

	public static function delete($handle = NULL){
		Symphony::ExtensionManager()->notifyMembers(
			'PreRecipientgroupDelete',
			'/extension/email_newsletter_manager/',
			array(
				'handle'		=> $handle
			)
		);
		if(@unlink(self::__getDriverPath($handle))){
			Symphony::ExtensionManager()->notifyMembers(
				'PostRecipientgroupDelete',
				'/extension/email_newsletter_manager/',
				array(
					'handle'		=> $handle
				)
			);
			return true;
		}
		else{
			return false;
		}
	}

	protected function _writeRecipientSource($handle, $contents){
		$dir = self::__getClassPath($handle, true);
		if(is_dir($dir) && is_writeable($dir)){
			if((is_writeable(self::__getDriverPath($handle))) || !file_exists(self::__getDriverPath($handle))){
				file_put_contents(self::__getDriverPath($handle), $contents);
				return true;
			}
			else{
				throw new Exception("File " . self::getDriverPath($handle) . " can not be written to. Please check permissions");
				return false;
			}
		}
		else{
			throw new Exception("Directory $dir does not exist, or is not writeable.");
			return false;
		}
	}

	public static function about($name){

		$classname = self::__getClassName($name);
		$path = self::__getDriverPath($name);

		if(!@file_exists($path)) return false;

		require_once($path);

		$handle = self::__getHandleFromFilename(basename($path));

		if(is_callable(array($classname, 'about'))){
			$about = call_user_func(array($classname, 'about'));
			return array_merge($about, array('handle' => $handle));
		}

	}

	protected function _parseTemplate($data){
		if(is_numeric($data['source'])){
			$template = file_get_contents(ENMDIR . '/content/templates/tpl/recipientSourceSection.tpl');
		}
		elseif($data['source'] == 'authors'){
			$template = file_get_contents(ENMDIR . '/content/templates/tpl/recipientSourceAuthor.tpl');
		}
		elseif($data['source'] == 'static_recipients'){
			$template = file_get_contents(ENMDIR . '/content/templates/tpl/recipientSourceStatic.tpl');
		}

		// flatten the duplicator array
		$filters = array();
		if(is_array($data['filter']) && !empty($data['filter'])){
			foreach($data['filter'] as $filter){
				foreach((array)$filter as $key => $value){
					if(trim($value) == '') continue;
					$filters[$key] = $value;
				}
			}
		}

		// Section and Author sources
		$template = str_replace('<!-- CLASS NAME -->' , self::__getClassName(Lang::createHandle($data['name'], 255, '_')), $template);
		$template = str_replace('<!-- NAME -->' , addcslashes($data['name'], "'"), $template);
		$template = str_replace('<!-- HANDLE -->' , Lang::createHandle($data['name'], 255, '-'), $template);
		$template = str_replace('<!-- SOURCE -->' , addcslashes($data['source'], "'"), $template);
		$template = str_replace('<!-- FILTERS -->' , var_export($filters, true), $template);

		// Dependencies
		$template = str_replace('<!-- DEPENDENCIES -->' , var_export($data['dependencies'], true), $template);

		// Section Source
		$template = str_replace('<!-- NAME_FIELDS -->' , var_export((array)$data['name-fields'], true), $template);
		$template = str_replace('<!-- EMAIL_FIELD -->' , addcslashes($data['email-field'], "'"), $template);
		$template = str_replace('<!-- NAME_XSLT -->' , addcslashes($data['name-xslt'], "'"), $template);

		// Static Recipients
		$template = str_replace('<!-- STATIC_RECIPIENTS -->' , var_export((string)$data['static_recipients'], true), $template);

		return $template;
	}
}