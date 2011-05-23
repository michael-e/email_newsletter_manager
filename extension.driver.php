<?php

class extension_email_newsletters extends extension{

	public function about(){
		return array(
			'name' => 'Email Newsletters',
			'version' => '2.0 Alpha',
			'author' => array(
				array(
					'name'=>'Huib Keemink'
				),
				array(
					'name' => 'Michael Eichelsd&ouml;rfer'
				)
			)
		);
	}
	
	public function fetchNavigation() {
		return array(
			array(
				'location'  => __('Blueprints'),
				'name'      => __('Email Recipients'),
				'link'      => '/recipientgroups/'
			),
			array(
				'location'  => __('Blueprints'),
				'name'      => __('Email Senders'),
				'link'      => '/senders/'
			)			
		);
	}
	
	public function install(){
		try{
			$etm = Symphony::ExtensionManager()->getInstance('email_template_manager');
			if($etm instanceof Extension){
				return true;
			}
			else{
				throw new Exception(__('The Email Template Manager is required for this extension to work.'));
			}
		}
		catch(Exception $e){
			throw new Exception(__('The Email Template Manager is required for this extension to work.'));
		}
	}
}