<?php

Class BackgroundProcessException extends Exception{
}

Class BackgroundProcess{
	
	protected $_status;
	protected $_batchURL;

	const STATUS_IDLE 		= 'idle';
	const STATUS_RUNNING 	= 'running';
	const STATUS_COMPLETED 	= 'completed';
	const STATUS_STOPPED	= 'stopped';
	const STATUS_PAUSED		= 'paused';
	
	const FLAG_PAUSE	= 'pause';
	const FLAG_START	= 'start';
	const FLAG_STOP		= 'stop';

	public function __construct(){
		$this->_status = self::STATUS_IDLE;
	}

	public function action(){
		return self::STATUS_COMPLETED;
	}

	public function start(){
		$this->_status = self::STATUS_RUNNING;
		$status = $this->action();
		if($status == self::STATUS_RUNNING){
			$this->nextBatch();
			return true;
		}
		elseif($status == self::STATUS_COMPLETED){
			$this->_status = self::STATUS_COMPLETED;
			return true;
		}
		else{
			$this->_status = self::STATUS_STOPPED;
			return false;
		}
	}

	public function stop(){
		$this->_status = self::STATUS_STOPPED;
		exit();
	}

	public function pause(){
		$this->_status = self::STATUS_PAUSED;
	}

	public function getStatus(){
		return $this->_status;
	}

	public function updateStatus(){
		$flag = $this->getFlag();
		if($flag == self::FLAG_PAUSE && $this->_status != self::STATUS_PAUSED){
			$this->pause();
		}
		elseif($flag == self::FLAG_START && $this->_status != self::STATUS_RUNNING){
			$this->start();
		}
		elseif($flag == self::FLAG_STOP && $this->_status != self::STATUS_STOPPED){
			$this->stop();
		}
		return true;
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