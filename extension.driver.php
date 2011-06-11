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
			Symphony::Database()->query(
				'CREATE TABLE IF NOT EXISTS
				`tbl_email_newsletter_manager_recipientgroups` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(255) NOT NULL,
					`recipients` text,
					PRIMARY KEY (`id`)
				)
				ENGINE=InnoDB 
				DEFAULT CHARSET=utf8
				COLLATE=utf8_unicode_ci
				AUTO_INCREMENT=4'
			);
			Symphony::Database()->query(
				'CREATE TABLE IF NOT EXISTS
				`tbl_email_newsletter_manager_recipientgroups_params` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`recipientgroup_id` int(10) unsigned NOT NULL,
					`name` varchar(255) NOT NULL,
					`value` varchar(255) NOT NULL,
					PRIMARY KEY (`id`)
				)
				ENGINE=InnoDB 
				DEFAULT CHARSET=utf8
				COLLATE=utf8_unicode_ci
				AUTO_INCREMENT=18'
			);
			Symphony::Database()->query(
				'CREATE TABLE IF NOT EXISTS
				`tbl_email_newsletter_manager_senders` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(255)  NOT NULL,
					`email` varchar(255)  NOT NULL,
					`reply-to-name` varchar(255) DEFAULT NULL,
					`reply-to-email` varchar(255) DEFAULT NULL,
					PRIMARY KEY (`id`)
				)
				ENGINE=InnoDB 
				DEFAULT CHARSET=utf8
				COLLATE=utf8_unicode_ci
				AUTO_INCREMENT=11'
			);
			return true;
		}
		else{
			throw new Exception(__('The Email Template Manager is required for this extension to work.'));
		}
	}
	
	public function uninstall(){
		Symphony::Database()->query('DROP TABLE `tbl_email_newsletter_manager_recipientgroups`');
		Symphony::Database()->query('DROP TABLE `tbl_email_newsletter_manager_recipientgroups_params`');
		Symphony::Database()->query('DROP TABLE `tbl_email_newsletter_manager_senders`');
		return true;
	}
}