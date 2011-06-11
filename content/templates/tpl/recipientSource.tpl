<?php

if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

require_once(EXTENSIONS . '/email_newsletter_manager/lib/class.recipientsource.php');

class <!-- CLASS NAME --> extends RecipientSource{
	
	public $dsParamROOTELEMENT = '<!-- HANDLE -->';
	public $dsParamFILTERS = <!-- FILTERS -->;
	public $dsParamREQUIREDPARAM = '<!-- REQUIRED_PARAM -->';
	public $emailField = '<!-- EMAIL_FIELD -->';
	public $nameFields = <!-- NAME_FIELDS -->;
	public $nameXslt = '<!-- NAME_XSLT -->';
	
	function about(){
		return array(
			'name' => '<!-- NAME -->'
		);
	}
	
	public function getSource(){
		return '<!-- SOURCE -->';
	}
}