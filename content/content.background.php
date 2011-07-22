<?php

require_once(TOOLKIT . '/class.ajaxpage.php');

Class contentExtensionemail_newsletter_managerbackground extends AjaxPage{
	
	public function view(){
		echo 'This should become the place to initiate a new background process.';
		die();
	}
	
	public function handleFailedAuthorisation(){
		$this->view();
	}
}