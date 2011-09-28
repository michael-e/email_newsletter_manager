<?php

class NewsletterSender{

	public function getName(){
		$about = $this->about();
		return $about['name'];
	}

	public function getHandle(){
		$about = $this->about();
		return strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', str_replace(' ', '-', $this->getName())));
	}
}