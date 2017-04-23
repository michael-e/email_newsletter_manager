<?php

require_once('class.recipientsource.php');

class recipientsourcestatic extends RecipientSource
{
    public $dsParamLIMIT = 10;
    public $dsParamSTARTPAGE = 1;
    protected $_tempTable;

    /**
     * Fetch recipient data, and include useful data.
     *
     * This function is used internally to fetch the recipient data.
     * It is the preferred way of getting data out of the system,
     * because it will also return pagination and other useful data.
     *
     * @return array
     */
    public function getSlice()
    {
        $recipients = $this->execute();
        $return['total-entries'] = $this->getCount();
        $pages = ((int) $return['total-entries']/(int) $this->dsParamLIMIT);
        $return['total-pages'] = (int) ceil($pages);
        $return['remaining-pages'] = max(0, (int) $return['total-pages'] - (int) $this->dsParamSTARTPAGE);
        $return['remaining-entries'] = max(0, ((int) $return['total-entries'] - ((int) $this->dsParamSTARTPAGE * (int) $this->dsParamLIMIT)));
        $return['entries-per-page'] = $this->dsParamLIMIT;
        $return['start'] = (((int) $this->dsParamSTARTPAGE - 1) * (int) $this->dsParamLIMIT) + 1;
        $return['current-page'] = (int) $this->dsParamSTARTPAGE;
        if ($this->newsletter_id !== null) {
            $newsletter = EmailNewsletterManager::create($this->newsletter_id);
            if (is_a($newsletter, 'EmailNewsletter')) {
                foreach ($recipients as $recipient) {
                    $newsletter->_markRecipient($recipient['email'],'idle');
                }
            }
        }

        return array_merge($return, array('records'=>$recipients));
    }

    /**
     * Fetch recipient data.
     *
     * @return array
     */
    public function execute()
    {
        parent::execute();
        $this->_createTempTable();

        if ($this->newsletter_id !== null) {
            $where .= ' AND `d`.`email` IS NOT NULL GROUP BY `d`.`email`';
            $joins .= ' LEFT OUTER JOIN tbl_tmp_email_newsletters_sent_'.$this->newsletter_id.' AS `n` ON `d`.`email` = `n`.`email`
                        WHERE `n`.`email` IS NULL';
        } else {
            $joins .= 'GROUP BY `d`.`email`';
        }

        $limit = ' LIMIT ' . ($this->dsParamSTARTPAGE - 1) * $this->dsParamLIMIT . ', ' . $this->dsParamLIMIT;

        $rows = Symphony::Database()->fetch('SELECT `d`.`id`, `d`.`name`, `d`.`email`, `d`.`valid` from ' . $this->_tempTable . ' as `d` ' . $joins . $where . $limit);

        return $rows;
    }

    /**
     * Fetch number of recipients
     *
     * @return int
     */
    public function getCount()
    {
        parent::getCount();
        if ($this->newsletter_id !== null) {
            return -1;
        }
        $this->_createTempTable();
        $rows = Symphony::Database()->fetchCol('count','SELECT count(email) as count from ' . $this->_tempTable);

        return $rows[0];
    }

    protected function _parseNameAndEmail(&$string)
    {
        $string = trim($string);

        if (strstr($string, '<')) {
            $name = trim(strstr($string, '<', true), "\" \t\n\r\0\x0B");
            $email = trim(strstr($string, '<'), "<> \t\n\r\0\x0B");
        } else {
            $email = trim($string, " \t\n\r\0\x0B");
            $name = null;
        }
        if (strlen($email) == 0) {
            unset($string);
        } else {
            require TOOLKIT . '/util.validators.php';

            return array(
                'name'  => $name,
                'email' => $email,
                'valid' => General::validateString($email, $validators['email']) ? true : false
            );
        }
    }

    protected function _createTempTable()
    {
        if ($this->_tempTable == null) {
            $name = 'email_newsletters_static_recipients_' . substr(md5(microtime()), 0, 10);
            if (Symphony::Database()->query('CREATE TEMPORARY TABLE ' . $name . ' (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY ( `id` ), email varchar(255), name varchar(255),`valid` BOOL NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;')) {
                if (count($this->recipients) > 0) {
                    $rcpts = array_map(array(__CLASS__, '_parseNameAndEmail'), explode(',', $this->recipients));
                    foreach ($rcpts as $recipient) {
                        $values[] = '(\'' . Symphony::Database()->cleanValue($recipient['email']) . '\', \'' . Symphony::Database()->cleanValue($recipient['name']) . '\', '. ($recipient['valid']?1:0) . ')';
                    }
                    $value = implode(', ', $values);
                    Symphony::Database()->query("INSERT IGNORE INTO " . $name . " (email, name, valid) values " . $value);
                }
                $this->_tempTable = $name;

                return true;
            }
        }
    }
}
