<?php

require_once(TOOLKIT . '/class.manager.php');
if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");

require_once(ENMDIR . '/lib/class.emailnewsletter.php');

class EmailNewsletterManagerException extends Exception{
}

Class EmailNewsletterManager{

	public static function listAll($start_page = 1, $limit = NULL){
		if($start_page < 1){
			$start_page = 1;
		}
		if($limit < 1){
			$limit_query = NULL;
		}
		else{
			$limit_query = ' LIMIT ' . ((int)$start_page - 1) * (int)$limit . ', ' . (int)$limit;
		}
		$newsletters = Symphony::Database()->fetch('SELECT * from `tbl_email_newsletters`' . $limit_query);
		return $newsletters;
	}

	public static function &create($id = NULL){
		$newsletter = Symphony::Database()->fetchRow(0, 'SELECT id from `tbl_email_newsletters` WHERE `id` = "' . $id . '" LIMIT 1');
		if(!empty($newsletter)){
			return new EmailNewsLetter($id);
		}
		else{
			throw new EmailNewsletterManagerException(__('Newsletter with id %s not found.', array($id)));
		}
	}

	public static function get($id = NULL){
		return self::create($id);
	}

	public static function save($data){
		// TODO: sanitize data, check for empty values, etc.
		// Also need to create the correct format from an array of recipient groups, a csv string is not the right way.
		if(Symphony::Database()->insert($data, 'tbl_email_newsletters', true)){
			if(($id = Symphony::Database()->getInsertID()) || ($id = $data['id'])){
				return self::create($id);
			}
		}
		else{
			throw new EmailNewsletterManagerException(Symphony::Database()->getLastError());
		}
	}

	public function delete($id){
		try{
			Symphony::Database()->query(sprintf('DELETE FROM `36552cdv12`.`sym_email_newsletters` WHERE `sym_email_newsletters`.`id` = \'%d\'', $id));
			Symphony::Database()->query(sprintf('DROP TABLE `sym_tmp_email_newsletters_sent_%d`', $id));
		}
		catch(Exception $e){
			return false;
		}
	}

	public static function updateTemplateHandle($old_handle, $new_handle){
		return Symphony::Database()->update(array('template' => $new_handle), 'tbl_email_newsletters', 'template = \'' . $old_handle . '\'');
	}

	public static function updateSenderHandle($old_handle, $new_handle){
		return Symphony::Database()->update(array('sender' => $new_handle), 'tbl_email_newsletters', 'sender = \'' . $old_handle . '\'');
	}

	public static function updateRecipientsHandle($old_handle, $new_handle){
		$ids = array_keys(Symphony::Database()->fetch(sprintf('SELECT id FROM tbl_email_newsletters WHERE recipients LIKE \'%%s%%\'', $old_handle), 'id'));
		foreach($ids as $id){
			$newsletter = self::create($id);
			$groups = $newsletter->getRecipientGroups($filter_complete = false, $return_array = true);
			if(($pos = array_search($old_handle, $groups)) !== FALSE){
				$groups[$pos] = $new_handle;
				$newsletter->setRecipientGroups($groups);
			}
		}
	}
}