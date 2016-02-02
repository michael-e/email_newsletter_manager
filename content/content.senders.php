<?php

if (!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");
if (!defined('ENVIEWS')) define('ENVIEWS', ENMDIR . "/content/templates");

if (!class_exists('ExtensionPage')) {
    require_once(ENMDIR . '/lib/class.extensionpage.php');
}

require_once(ENMDIR . '/lib/class.sendermanager.php');
require_once(ENMDIR . '/lib/class.emailnewslettermanager.php');

class contentExtensionemail_newsletter_managersenders extends ExtensionPage
{
    protected $_type;
    protected $_function;

    protected $_XSLTProc;
    protected $_XML;

    public function __construct()
    {
        $this->_XSLTProc = new XsltProcess();
        $this->_XML = new XMLElement("data");
        $this->viewDir = ENVIEWS . '/senders';
        parent::__construct(Symphony::Engine());

    }

    public function __viewIndex()
    {
        $this->setPageType('index');
        $this->setTitle(__("Symphony - Email Senders"));
        $this->appendSubheading(__('Email Newsletter Senders'), Widget::Anchor(
            __('Create New'), SYMPHONY_URL . '/extension/email_newsletter_manager/senders/new/',
            __('Create a new sender'), 'create button'
        ));
        $results = SenderManager::listAll();
        $senders = new XMLElement('senders');
        foreach ($results as $result) {
            $entry = new XMLElement('entry');
            General::array_to_xml($entry, $result);
            $senders->appendChild($entry);
        }
        $this->_XML->appendChild($senders);
    }

    public function __viewNew()
    {
        $this->_context[1] = 'New';
        $this->_useTemplate = 'viewEdit';
        $this->__viewEdit(true);
    }

    public function __viewEdit($new = false)
    {
        $this->setPageType('form');
        if ($this->_context[2] == 'saved' || $this->_context[3] == 'saved') {
            $this->pageAlert(
                __(
                    __('Email Sender updated at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Senders</a>'),
                    array(
                        Widget::Time()->generate(),
                        SYMPHONY_URL . '/extension/email_newsletter_manager/senders/new/',
                        SYMPHONY_URL . '/extension/email_newsletter_manager/senders/',
                    )
                ),
                Alert::SUCCESS
            );
        }

        $senders = new XMLElement('senders');
        $title = __('New Sender');
        $breadcrumbs = array(
            Widget::Anchor(__('Email Newsletter Senders'), SYMPHONY_URL . '/extension/email_newsletter_manager/senders/')
        );

        // Fix for 2.4 and XSRF
        if ((Symphony::Configuration()->get("enable_xsrf", "symphony") == "yes") &&
            (class_exists('XSRF'))) {
            $xsrf_input = new XMLElement('xsrf_input');
            $xsrf_input->appendChild(XSRF::formToken());
            $this->_XML->appendChild(
                $xsrf_input
            );
        }

        if (!$new) {
            $sender = SenderManager::create($this->_context[1]);

            // Make sure the POSTED values are always shown when present.
            // This will make sure the form is always up-to-date, even where there are errors.
            if (!empty($_POST['fields']) && !empty($_POST['settings'])) {
                $posted_array = $_POST['fields'];
                $posted_array[$_POST['settings']['gateway']] = $_POST['settings']['email_' . $_POST['settings']['gateway']];
            }
            $about = (empty($_POST['fields']) && empty($_POST['settings']))?(array) $sender->about():$posted_array;
            $about['handle'] = Lang::createHandle($about['name'], 225, '-');
            $entry = new XMLElement('entry');
            General::array_to_xml($entry, $about);
            $senders->appendChild($entry);
            $title = $about['name'];
            //$breadcrumbs[] = Widget::Anchor('hi', SYMPHONY_URL . '/extension/email_newsletter_manager/senders/edit/' . $sender->getHandle());
        }

        $el_gateways = new XMLElement('gateways');
        $gateways = EmailGatewayManager::listAll();
        foreach ($gateways as $gateway) {
            // to be removed in later versions. Right now only smtp and sendmail are supported.
            if (in_array($gateway['handle'], array('smtp', 'sendmail', 'amazon_ses'))) {
                $gw = EmailGatewayManager::create($gateway['handle']);
                if (!empty($about[$gateway['handle']])) {
                    $config = $about[$gateway['handle']];
                    if ($gateway['handle'] == 'smtp') {
                        $gw->setHeloHostname($config['helo_hostname']);
                        $gw->setFrom($config['from_address'], $config['from_name']);
                        $gw->setHost($config['host']);
                        $gw->setPort($config['port']);
                        $gw->setSecure($config['secure']);

                        if ($config['auth'] == 1) {
                            $gw->setAuth(true);
                            $gw->setUser($config['username']);
                            $gw->setPass($config['password']);
                        } else {
                            $gw->setAuth(false);
                            $gw->setUser('');
                            $gw->setPass('');
                        }
                    }
                    if ($gateway['handle'] == 'amazon_ses') {
                        $gw->setFrom($config['from_address'], $config['from_name']);
                        $gw->setAwsKey($config['aws_key']);
                        $gw->setAwsSecretKey($config['aws_secret_key']);
                        $gw->setFallback($config['fallback']);
                        $gw->setReturnPath($config['return_path']);
                    }
                    if ($gateway['handle'] == 'sendmail') {
                        $gw->setFrom($config['from_address'], $config['from_name']);
                    }
                }
                $entry = new XMLElement('entry');
                General::array_to_xml($entry, $gateway);
                $config_panel = new XMLElement('config_panel');
                $config_panel->appendChild($gw->getPreferencesPane());
                $entry->appendChild($config_panel);
                $el_gateways->appendChild($entry);
            }
        }
        $senders->appendChild($el_gateways);
        $this->insertBreadcrumbs($breadcrumbs);
        $this->appendSubheading($title);
        $this->_XML->appendChild($senders);
    }

    public function __actionIndex()
    {
        if ($_POST['with-selected'] == 'delete') {
            foreach ((array) $_POST['items'] as $item=>$status) {
                SenderManager::delete($item);
            }
        }
    }

    public function __actionEdit($new = false)
    {
        $fields = array_merge($_POST['fields'], $_POST['settings']);

        try {
            $result = SenderManager::create($this->_context[1]);
            $fields['additional_headers'] = $result->additional_headers;
        } catch (Exception $e) {
        }
        if (empty($result) && !$new) {
            redirect(SYMPHONY_URL . '/extension/email_newsletter_manager/senders/');

            return false;
        }

        if (isset($_POST['action']['delete'])) {
            if (SenderManager::delete($this->_context[1])) {
                redirect(SYMPHONY_URL . '/extension/email_newsletter_manager/senders/');

                return;
            } else {
                $this->pageAlert(
                    __('Could not delete. Database error.'),
                    Alert::ERROR
                );

                return true;
            }
        }

        $errors = new XMLElement('errors');
        if (empty($fields['name'])) {
            $errors->appendChild(new XMLElement('name', __('This field can not be empty.')));
        } elseif (strlen(Lang::createHandle($fields['name'])) == 0) {
            $errors->appendChild(new XMLElement('name', __('This field must at least contain a number or a letter')));
        } else {
            try {
                if (SenderManager::save(str_replace('_', '-', $this->_context[1]), $fields)) {
                    redirect(SYMPHONY_URL . '/extension/email_newsletter_manager/senders/edit/' . Lang::createHandle($fields['name'], 225, '_') . '/saved');

                    return true;
                }
            } catch (Exception $e) {
                $this->pageAlert(__('Could not save: ' . $e->getMessage()),Alert::ERROR);
            }
        }
        $this->_XML->appendChild($errors);
    }

    public function __actionNew()
    {
        return $this->__actionEdit(true);
    }

    public function view()
    {
        $context = new XMLElement('context');
        General::array_to_xml($context, $this->_context);
        $this->_XML->appendChild($context);
        parent::view();
    }

    public function action()
    {
        if ($this->_context[2] == 'saved') {
            $this->_context[2] = NULL;
        }
        $fields = new XMLElement('fields');
        General::array_to_xml($fields, (array) $_POST['fields']);
        $this->_XML->appendChild($fields);
        parent::action();
    }
}
