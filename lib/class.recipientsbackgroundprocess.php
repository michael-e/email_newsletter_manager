<?php

require_once('class.backgroundprocess.php');

class RecipientsBackgroundProcessException extends BackgroundProcessException{
}

class RecipientsBackgroundProcess extends BackgroundProcess{

	protected $_id;
	protected $_batchSize = 10;
	protected $_batchTime = 10;

	public function __construct($process_id = null){
	}

	public function action(){
		// 1) load next n entries to parse.
		// 2) parse entries & update value, status
		// 3) if entries < batchSize then return self::STATUS_COMPLETED
	}

	public function stop(){
		// 1) delete all parsed entry values
		// 2) update process status (stopped)
		// 3) exit cleanly
	}

	public function pause(){
		// 1) update process status (paused)
		// 2) exit cleanly		
	}

	public function getFlag(){
		// 1) load flag
		// 2) return flag
	}
}