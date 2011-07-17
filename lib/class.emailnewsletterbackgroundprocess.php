<?php

Class EmailNewsletterBackgroundProcessException extends BackgroundProcessException{
}

Class EmailNewsletterBackgroundProcess extends BackgroundProcess{

	protected $batch_id;

	public function __construct($batch_id){
		$this->batch_id = $batch_id;
	}

	public function run(){
	}

	public function action(){
	}

	public function getFlag(){
	}
}