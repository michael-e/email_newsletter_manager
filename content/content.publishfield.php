<?php

if (!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");
if (!defined('ENVIEWS')) define('ENVIEWS', ENMDIR . "/content/templates");

class contentExtensionemail_newsletter_managerpublishfield extends AjaxPage
{
    public function view()
    {
        $this->addHeaderToPage('Content-Type', 'text/html');
        $field_id = $this->_context[0];
        $entry_id = $this->_context[1];
        $this->_context['entry_id'] = $entry_id;
        try {

            $entry = EntryManager::fetch($entry_id);
            $entry = $entry[0];
            if (!is_a($entry, 'Entry')) {
                $this->_status = 404;

                return;
            }

            $field = FieldManager::fetch($field_id);
            if (!is_a($field, 'Field')) {
                $this->_status = 404;

                return;
            }
            $field->set('id', $field_id);

            $entry_data = $entry->getData();

            $data = new XMLElement('field');
            $field->displayPublishPanel($data, $entry_data[$field_id]);

            echo $data->generate(true);
            exit;
            $this->_Result->appendChild($data);
        } catch (Exception $e) {
        }
    }

    public function addScriptToHead()
    {
    }

    public function addStylesheetToHead()
    {
    }
}
