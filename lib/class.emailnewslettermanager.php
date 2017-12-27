<?php

if (!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");

require_once(ENMDIR . '/lib/class.emailnewsletter.php');

class EmailNewsletterManagerException extends Exception
{
}

class EmailNewsletterManager
{
    public static function listAll($start_page = 1, $limit = null)
    {
        if ($start_page < 1) {
            $start_page = 1;
        }
        if ($limit < 1) {
            $limit_query = null;
        } else {
            $limit_query = ' LIMIT ' . ((int) $start_page - 1) * (int) $limit . ', ' . (int) $limit;
        }
        $newsletters = Symphony::Database()->fetch('SELECT * from `tbl_email_newsletters`' . $limit_query);

        return $newsletters;
    }

    public static function &create($id = null) {
        $newsletter = Symphony::Database()->fetchRow(0, 'SELECT id from `tbl_email_newsletters` WHERE `id` = "' . $id . '" LIMIT 1');
        if (!empty($newsletter)) {
            return new EmailNewsletter($id);
        } else {
            throw new EmailNewsletterManagerException(__('Newsletter with id %s not found.', array($id)));
        }
    }

    public static function get($id = null)
    {
        return self::create($id);
    }

    public static function save($data)
    {
        // TODO: sanitize data, check for empty values, etc.
        // Also need to create the correct format from an array of recipient groups, a csv string is not the right way.
        if (Symphony::Database()->insert($data, 'tbl_email_newsletters', true)) {
            if (($id = Symphony::Database()->getInsertID()) || ($id = $data['id'])) {
                return self::create($id);
            }
        }
    }

    public function delete($id)
    {
        try {
            Symphony::Database()->query(sprintf('DELETE FROM `tbl_email_newsletters` WHERE `tbl_email_newsletters`.`id` = \'%d\'', $id));
            Symphony::Database()->query(sprintf('DROP TABLE `tbl_tmp_email_newsletters_sent_%d`', $id));
        } catch (Exception $e) {
            return false;
        }
    }

    public static function updateTemplateHandle($old_handle, $new_handle)
    {
        $query = sprintf('SELECT id,templates FROM tbl_fields_email_newsletter_manager WHERE templates LIKE \'%%%s%%\'', $old_handle);
        $fields = Symphony::Database()->fetch($query);
        foreach ($fields as $field) {
            $templates = array_map('trim', explode(',',$field['templates']));
            if (($pos = array_search($old_handle, $templates)) !== FALSE) {
                $templates[$pos] = $new_handle;
                Symphony::Database()->update(array('templates'=>implode(',',$templates)),'tbl_fields_email_newsletter_manager', 'id = \'' . $field['id'] . '\'');
            }
        }

        return Symphony::Database()->update(array('template' => $new_handle), 'tbl_email_newsletters', 'template = \'' . $old_handle . '\'');
    }

    public static function updateSenderHandle($old_handle, $new_handle)
    {
        $query = sprintf('SELECT id,senders FROM tbl_fields_email_newsletter_manager WHERE senders LIKE \'%%%s%%\'', $old_handle);
        $fields = Symphony::Database()->fetch($query);
        foreach ($fields as $field) {
            $senders = array_map('trim', explode(',',$field['senders']));
            if (($pos = array_search($old_handle, $senders)) !== FALSE) {
                $senders[$pos] = $new_handle;
                Symphony::Database()->update(array('senders'=>implode(',',$senders)),'tbl_fields_email_newsletter_manager', 'id = \'' . $field['id'] . '\'');
            }
        }

        return Symphony::Database()->update(array('sender' => $new_handle), 'tbl_email_newsletters', 'sender = \'' . $old_handle . '\'');
    }

    public static function updateRecipientsHandle($old_handle, $new_handle)
    {
        $query = sprintf('SELECT id,recipient_groups FROM tbl_fields_email_newsletter_manager WHERE recipient_groups LIKE \'%%%s%%\'', $old_handle);
        $fields = Symphony::Database()->fetch($query);
        foreach ($fields as $field) {
            $recipients = array_map('trim', explode(',',$field['recipient_groups']));
            if (($pos = array_search($old_handle, $recipients)) !== FALSE) {
                $recipients[$pos] = $new_handle;
                Symphony::Database()->update(array('recipient_groups'=>implode(',',$recipients)),'tbl_fields_email_newsletter_manager', 'id = \'' . $field['id'] . '\'');
            }
        }
        $query = sprintf('SELECT id FROM tbl_email_newsletters WHERE recipients LIKE \'%%%s%%\'', $old_handle);
        $ids = array_keys(Symphony::Database()->fetch($query, 'id'));
        foreach ($ids as $id) {
            $newsletter = self::create($id);
            $groups = $newsletter->getRecipientGroups($filter_complete = false, $return_array = true);
            if (($pos = array_search($old_handle, $groups)) !== FALSE) {
                $groups[$pos] = $new_handle;
                $newsletter->setRecipientGroups($groups);
            }
        }
    }
}
