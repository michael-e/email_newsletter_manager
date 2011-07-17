<?php

class extension_email_newsletter_manager extends extension{

	public function about(){
		return array(
			'name' => 'Email Newsletter Manager',
			'version' => '1.0 Alpha',
			'author' => array(
				array(
					'name'=>'Huib Keemink',
					'website' => 'http://www.creativedutchmen.com',
					'email' => 'huib.keemink@creativedutchmen.com',
				),
				array(
					'name' => 'Michael Eichelsdoerfer',
					'website' => 'http://www.michael-eichelsdoerfer.de',
					'email' => 'info@michael-eichelsdoerfer.de',
				)
			)
		);
	}
	
	public function fetchNavigation() {
		return array(
			array(
				'location'  => __('Blueprints'),
				'name'      => __('Newsletter Recipients'),
				'link'      => '/recipientgroups/'
			),
			array(
				'location'  => __('Blueprints'),
				'name'      => __('Newsletter Senders'),
				'link'      => '/senders/'
			)			
		);
	}
	
	public function install(){
		$etm = Symphony::ExtensionManager()->getInstance('email_template_manager');
		if($etm instanceof Extension){
			return true;
		}
		else{
			throw new Exception(__('The Email Template Manager is required for this extension to work.'));
		}
	}
	
	public function uninstall(){
		return true;
	}
}