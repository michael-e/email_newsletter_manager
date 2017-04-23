<?php

/**
 * Background Process for the ENM.
 *
 **/

// Accurate timing
$start_time = microtime(true);

error_reporting(0);

register_shutdown_function('handleShutdown');
set_error_handler("handleError");

function handleShutdown()
{
    $error = error_get_last();
    if (($error !== null) && ($error['type'] <= 1)) {
        file_put_contents(DOCROOT . '/manifest/newsletter-log.txt', '['.DateTimeObj::get('Y/m/d H:i:s').'] pid: '.getmypid().' - ' . $error['message'] . ' in file: ' . $error['file'] . ' on line ' . $error['line'] . "\r\n", FILE_APPEND);
    }
}
function handleError($error_level,$error_message,$error_file,$error_line,$error_context)
{
    //echo $error_message . "\r\n";
}

$newsletter_id  = $_SERVER['argv'][1];
$process_auth = $_SERVER['argv'][2];
$_SERVER['HTTP_HOST'] = $_SERVER['argv'][3];

// Generic Symphony includes & defines
define('DOCROOT', realpath(rtrim(dirname(__FILE__) . '/../../../', '\\/')));
define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '\\/') . dirname($_SERVER['PHP_SELF']), '.\\/'));
define('HTTP_HOST', $_SERVER['HTTP_HOST']);

// Include autoloader:
require_once(DOCROOT . '/vendor/autoload.php');

// Include the boot script:
require_once(DOCROOT . '/symphony/lib/boot/bundle.php');

//Inside bundle.php, the error_reporting is set again, but we don't want to be stopped by any other than fatal errors.
error_reporting(0);

require_once(DOCROOT . '/symphony/lib/core/class.symphony.php');
require_once(DOCROOT . '/symphony/lib/core/class.administration.php');

GenericErrorHandler::$enabled = false;

// ENM Specific includes & defines
define_safe('ENM_DIR', DOCROOT . '/extensions/email_newsletter_manager');
define_safe('ETM_DIR', DOCROOT . '/extensions/email_template_manager');

require_once(ENM_DIR . '/lib/class.sendermanager.php');
require_once(ENM_DIR . '/lib/class.recipientgroupmanager.php');
require_once(ENM_DIR . '/lib/class.emailnewslettermanager.php');
require_once(ENM_DIR . '/lib/class.emailbackgroundprocess.php');

// Needed to __construct() the Symphony class.
// This in turn is needed to get the Symphony::Database() functions working.
$thing = Administration::instance();

try {
    $newsletter = EmailNewsletterManager::create($newsletter_id);
    if (is_a($newsletter, 'EmailNewsletter')) {
        $newsletter->setPId(getmypid());
        $sending_settings = $newsletter->getSender()->about();
        if ($newsletter->sendBatch($process_auth) != 'completed') {
            time_sleep_until($start_time + $sending_settings['throttle-time']);
            EmailBackgroundProcess::spawnProcess($newsletter_id, $process_auth);
        }
    } else {
        throw new Exception('Newsletter with id: ' . $newsletter_id . ' not found.');
    }
} catch (Exception $e) {
    file_put_contents(DOCROOT . '/manifest/newsletter-log.txt', '['.DateTimeObj::get('Y/m/d H:i:s').'] newsletter-id: '.$newsletter_id.' - ' . $e->getMessage() . "\r\n", FILE_APPEND);
}
