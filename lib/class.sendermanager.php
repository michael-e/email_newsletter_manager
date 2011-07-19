<?php

require_once(TOOLKIT . '/class.manager.php');
if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");

Class SenderManager extends Manager{

	public function __getHandleFromFilename($filename){
		$result = sscanf($filename, 'sender.%[^.].php');
		return $result[0];
	}

	public function __getClassName($handle){
		return sprintf('sender%s', ucfirst($handle));
	}

	public function __getClassPath($handle, $new = false){
		if(is_file(WORKSPACE . "/email-newsletters/sender.$handle.php") || $new == true) return WORKSPACE . '/email-newsletters';
		else{
			$extensions = Symphony::ExtensionManager()->listInstalledHandles();

			if(is_array($extensions) && !empty($extensions)){
				foreach($extensions as $e){
					if(is_file(EXTENSIONS . "/$e/email-newsletters/sender.$handle.php")) return EXTENSIONS . "/$e/email-newsletters";
				}
			}
		}

		return false;
	}

	public function __getDriverPath($handle){
		return self::__getClassPath($handle, true) . "/sender.$handle.php";
	}

	public function listAll(){
		$result = array();

		$structure = General::listStructure(WORKSPACE . '/email-newsletters', '/sender.[\\w-]+.php/', false, 'ASC', WORKSPACE . '/email-newsletters');

		if(is_array($structure['filelist']) && !empty($structure['filelist'])){
			foreach($structure['filelist'] as $f){
				$f = self::__getHandleFromFilename($f);

				if($about = $this->about($f)){

					$classname = $this->__getClassName($f);
					$path = $this->__getDriverPath($f);

					$can_parse = true;

					if(method_exists($classname,'allowEditorToParse')) {
						$can_parse = call_user_func(array($classname, 'allowEditorToParse'));
					}

					$about['can_parse'] = $can_parse;
					$result[$f] = $about;
				}
			}
		}

		$extensions = Symphony::ExtensionManager()->listInstalledHandles();

		if(is_array($extensions) && !empty($extensions)){
			foreach($extensions as $e){
				if(!is_dir(EXTENSIONS . "/$e/email-newsletters")) continue;

				$tmp = General::listStructure(EXTENSIONS . "/$e/email-newsletters", '/sender.[\\w-]+.php/', false, 'ASC', EXTENSIONS . "/$e/email-newsletters");

				if(is_array($tmp['filelist']) && !empty($tmp['filelist'])){
					foreach($tmp['filelist'] as $f){
						$f = self::__getHandleFromFilename($f);

						if($about = $this->about($f)){
							$about['can_parse'] = false;
							$about['type'] = null;
							$result[$f] = $about;
						}
					}
				}
			}
		}

		ksort($result);
		return $result;
	}

	public function &create($handle){
		$classname = $this->__getClassName($handle);
		$path = $this->__getDriverPath($handle);

		if(!is_file($path)){
			throw new Exception(
				__(
					'Could not find Newsletter Sender <code>%s</code>. If the Newsletter Sender was provided by an Extension, ensure that it is installed, and enabled.',
					array($handle)
				)
			);
		}

		if(!class_exists($classname)) require_once($path);

		if(class_exists($classname)){
			return new $classname($this->_Parent);
		}
		throw new Exception(
			__(
				'The Newsletter Sender <code>%s</code> has an invalid format. Please check the documentation for details on class names.',
				array($handle)
			)
		);

	}

	public function save($handle = null, $fields){
		if($handle == Lang::createHandle($fields['name'], 255, '_') || $handle == null){
			return self::_writeSender(Lang::createHandle($fields['name'], 255, '_'), self::_parseTemplate($fields));
		}
		elseif(false == self::__getClassPath(Lang::createHandle($fields['name'], 255, '_'))){
			if(!self::_writeSender(Lang::createHandle($fields['name'], 255, '_'), self::_parseTemplate($fields))) return false;
			if(!@unlink(self::__getDriverPath($handle))) return false;
			return true;
		}
		else{
			throw new Exception('Newsletter Sender ' . $fields['handle'] . ' already exists. Please choose another name.');
		}
	}
	
	public function delete($handle = null){
		return @unlink(self::__getDriverPath($handle));
	}

	protected function _writeSender($handle, $contents){
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

	protected function _parseTemplate($data){
		$template = file_get_contents(ENMDIR . '/content/templates/tpl/sender.tpl');

		// flatten the duplicator array
		$filters = array();
		if(is_array($data['filter']) && !empty($data['filter'])){
			foreach($data['filter'] as $filter){
				foreach($filter as $key => $value){
					if(trim($value) == '') continue;
					$filters[$key] = $value;
				}
			}
		}

		$template = str_replace('<!-- CLASS NAME -->' , self::__getClassName(Lang::createHandle($data['name'], 255, '_')), $template);
		$template = str_replace('<!-- NAME -->' , addcslashes($data['name'], "'"), $template);
		$template = str_replace('<!-- REPLY_TO_NAME -->' , addcslashes($data['reply-to-name'], "'"), $template);
		$template = str_replace('<!-- REPLY_TO_EMAIL -->' , addcslashes($data['reply-to-email'], "'"), $template);	
		$template = str_replace('<!-- GATEWAY_SETTINGS -->' , '\''.$data['gateway'] . '\' => ' . var_export($data['email_' . $data['gateway']], true), $template);	
		$template = str_replace('<!-- ADDITIONAL_HEADERS -->' , var_export(array(), true), $template);	
		$template = str_replace('<!-- THROTTLE_EMAILS -->' , (int)addcslashes($data['throttle-emails'], "'"), $template);	
		$template = str_replace('<!-- THROTTLE_TIME -->' , (int)addcslashes($data['throttle-time'], "'"), $template);	

		return $template;
	}
}