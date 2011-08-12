<?php

require_once(TOOLKIT . '/class.manager.php');
if(!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");

require_once(ENMDIR . '/lib/class.emailnewsletter.php');

class EmailNewsletterManagerException extends Exception{
}

Class EmailNewsletterManager extends Manager{

	public function listAll($start_page = 1, $limit = NULL){
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

	public function &create($id = NULL){
		$newsletter = Symphony::Database()->fetchRow(0, 'SELECT * from `tbl_email_newsletters` WHERE `id` = "' . $id . '" LIMIT 1');
		if(!empty($newsletter)){
			return new EmailNewsLetter($newsletter);
		}
		else{
			throw new EmailNewsletterManagerException(__('Newsletter with id %s not found.', array($id)));
		}
	}

	public function get($id = NULL){
		return $this->create($id);
	}

	public function save($data){
		if(Symphony::Database()->insert($data, 'tbl_email_newsletters', true)){
			if(($id = Symphony::Database()->getInsertID()) || ($id = $data['id'])){
				return $this->create($id);
			}
		}
		else{
			throw new EmailNewsletterManagerException(Symphony::Database()->getLastError());
		}
	}
}