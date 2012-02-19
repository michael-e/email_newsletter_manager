<?php

if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

require_once(EXTENSIONS . '/email_newsletter_manager/lib/class.recipientsourceauthor.php');

class <!-- CLASS NAME --> extends RecipientSourceAuthor{
	
	public $dsParamROOTELEMENT = '<!-- HANDLE -->';
	public $dsParamFILTERS = <!-- FILTERS -->;

	protected $_dependencies = array();
	
	function about(){
		return array(
			'name' => '<!-- NAME -->'
		);
	}
	
	public function getSource(){
		return '<!-- SOURCE -->';
	}
}