<?php

Class BackgroundProcessException extends Exception{
}

Class BackgroundProcess{
	
	protected $_batchURL;

	const FLAG_RUN		= 'run';
	const FLAG_PAUSE	= 'pause';
	const FLAG_START	= 'start';
	const FLAG_STOP		= 'stop';

	public function __construct(){
	}

	public function run(){
	}

	public function action(){
	}

	public function getFlag(){
	}

	public function nextBatch($parameters = array()){
		if(!empty($this->_batchURL) && (($url = parse_url($this->_batchURL)) !== FALSE)){
			$socket = fsockopen($url['host'], 80, $errno, $errstr);
			if(!$socket){
				throw new BackgroundProcessException('Could not open socket: (' . $errno . ') ' . $errstr);
			}
			else{
				if(!empty($parameters)){
					$str = array();
					foreach((array)$parameters as $param => $value){
						$str[] = $param . '=' . $value;
					}
					$url['query'] .= implode('&', $str);
				}
				if(strlen($url['query']) > 0){
					$url['query'] = '?' . $url['query'];
				}
				$out = 	'GET ' . $url['path'] . $url['query'] . " HTTP/1.1\r\n";
				$out .=	'HOST: ' . $url['host'] . "\r\n";
				$out .= "Connection: Close\r\n\r\n";
				fwrite($socket, $out);
				sleep(0.5);
				fclose($socket);
				return true;
			}
		}
		else{
			throw new BackgroundProcessException('URL invalid: ' . $this->_batchURL);
		}
	}

	public function setBatchURL($url){	
		$this->_batchURL = $url;
	}
}