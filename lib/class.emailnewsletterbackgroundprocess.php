<?php

Class EmailNewsletterBackgroundProcessException extends BackgroundProcessException{
}

Class EmailNewsletterBackgroundProcess extends BackgroundProcess{

	protected $_newsletter_id;
	protected $_newsletter;

	public function __construct($newsletter_id){
		$this->_newsletter_id = $newsletter_id;
		$nlm = new NewsLetterManager(Administration::instance());
		$this->_newsletter = $nlm->create($this->_newsletter_id);
	}

	public function run(){
	}

	public function action(){
	}

	public function getFlag(){
	}
}