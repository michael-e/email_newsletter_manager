<?php

/**
 * Background Process for the ENM.
 *
 **/

error_reporting(E_ERROR);

// Accurate timing
$start_time = microtime(true);

// Generic Symphony includes & defines
define('DOCROOT', rtrim(dirname(__FILE__) . '/../../../', '\\/'));
define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '\\/') . dirname($_SERVER['PHP_SELF']), '\\/'));

require_once(DOCROOT . '/symphony/lib/boot/bundle.php');
require_once(DOCROOT . '/symphony/lib/core/class.symphony.php');
require_once(DOCROOT . '/symphony/lib/core/class.administration.php');


// ENM Specific includes & defines
define_safe('ENM_DIR', DOCROOT . '/extensions/email_newsletter_manager');
define_safe('ETM_DIR', DOCROOT . '/extensions/email_template_manager');

require_once(ENM_DIR . '/lib/class.sendermanager.php');
require_once(ENM_DIR . '/lib/class.recipientgroupmanager.php');
require_once(ENM_DIR . '/lib/class.emailnewslettermanager.php');
require_once(ENM_DIR . '/lib/class.emailbackgroundprocess.php');

$newsletter_id  = $_SERVER['argv'][1];
$process_auth = $_SERVER['argv'][2];

// Needed to __construct() the Symphony class.
// This in term is needed to get the Symphony::Database() functions working.
$thing = Administration::instance();

try{
	$newsletter = EmailNewsletterManager::create($newsletter_id);
	if(is_a($newsletter, 'EmailNewsletter')){
		$newsletter->setPId(getmypid());
		$newsletter->setSender('test');
		$sending_settings = $newsletter->getSender()->about();
		$newsletter->sendBatch($process_auth);
		time_sleep_until($start_time + 10);
		EmailBackgroundProcess::spawnProcess($newsletter_id, $process_auth);
	}
	else{
		throw new Exception('Newsletter with id: ' . $newsletter_id . ' not found.');
	}
}
catch(Exception $e){
	file_put_contents(DOCROOT . '/manifest/newsletter-log.txt', '['.DateTimeObj::get('Y/m/d H:i:s').'] pid: '.getmypid().' - ' . $e->getMessage() . "\r\n", FILE_APPEND);
}