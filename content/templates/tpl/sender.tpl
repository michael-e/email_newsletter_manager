<?php

if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

require_once(EXTENSIONS . '/email_newsletter_manager/lib/class.newslettersender.php');

class <!-- CLASS NAME --> extends NewsletterSender
{
    // You can set custom headers here.
    // Saving the sender in the GUI will not overwrite this value.
    public $additional_headers = <!-- ADDITIONAL_HEADERS -->;

    function about()
    {
        return array(
            'name' => '<!-- NAME -->',

            <!-- GATEWAY_SETTINGS -->,

            'reply-to-name' => '<!-- REPLY_TO_NAME -->',
            'reply-to-email' => '<!-- REPLY_TO_EMAIL -->',
            'throttle-emails' => <!-- THROTTLE_EMAILS -->,
            'throttle-time' => <!-- THROTTLE_TIME -->
        );
    }
}
