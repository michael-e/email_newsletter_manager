<?php

require_once('class.recipientsource.php');

class recipientsourcesection extends RecipientSource
{
    public $emailField = null;
    public $nameFields = array();
    public $nameXslt = null;

    public $dsParamLIMIT = 10;
    public $dsParamPAGINATERESULTS = 'yes';
    public $dsParamSTARTPAGE = 1;

    /**
     * Fetch generated recipient data.
     *
     * Returns parsed recipient data. This means the xslt provided by the user
     * will be ran on the raw data, returning a name and email direcly useable
     * by the email API.
     *
     * This is the preferred way of getting recipient data.
     *
     * @todo bugtesting and error handling
     * @return array
     */
    public function getSlice()
    {
        $entries = $this->execute();
        $return['total-entries'] = (string) $entries['total-entries'];
        $return['total-pages'] = (string) $entries['total-pages'];
        $return['remaining-pages'] = (string) $entries['remaining-pages'];
        $return['remaining-entries'] = (string) $entries['remaining-entries'];
        $return['entries-per-page'] = (string) $entries['limit'];
        $return['start'] = (string) $entries['start'];
        $return['current-page'] = (string) $this->dsParamSTARTPAGE;
        $field_ids = array();
        $xsltproc = new XsltProcess();
        foreach ($this->nameFields as $nameField) {
            $field_ids[] = FieldManager::fetchFieldIDFromElementName($nameField, $this->getSource());
        }
        $email_field_id = FieldManager::fetchFieldIDFromElementName($this->emailField, $this->getSource());
        require TOOLKIT . '/util.validators.php';
        foreach ((array) $entries['records'] as $entry) {
            $entry_data = $entry->getData();
            $element = new XMLElement('entry');
            $name = '';
            $email = '';
            foreach ($entry_data as $field_id => $data) {
                if (in_array($field_id, $field_ids)) {
                    $field = FieldManager::fetch($field_id);
                    $field->appendFormattedElement($element, $data);
                }
                if ($field_id == $email_field_id) {
                    $email = $data['value'];
                }
            }
            $name = trim($xsltproc->process($element->generate(), $this->nameXslt));
            if (!empty($email)) {
                $return['records'][] = array(
                    'id'    => $entry->get('id'),
                    'email' => $email,
                    'name'  => $name,
                    'valid' => General::validateString($email, $validators['email']) ? true : false
                );
            }
        }
        if ($this->newsletter_id !== null) {
            $newsletter = EmailNewsletterManager::create($this->newsletter_id);
            if (is_a($newsletter, 'EmailNewsletter')) {
                foreach ($return['records'] as $recipient) {
                    $newsletter->_markRecipient($recipient['email'],'idle');
                }
            }
        }

        return $return;
    }

    /**
     * Fetch raw recipient data.
     *
     * Usage of the getSlice function, which also parses the XSLT for the name
     * and checks the email is recommended. This function is here mainly for
     * internal reasons.
     *
     * Be advised, this function returns an array of entry objects.
     *
     * @todo bugtesting and error handling
     * @return array
     */
    public function execute()
    {
        parent::execute();
        $where_and_joins = $this->getWhereJoinsAndGroup();

        $entries = EntryManager::fetchByPage(
            ($this->dsParamSTARTPAGE > 0 ? $this->dsParamSTARTPAGE : 1),
            $this->getSource(),
            ($this->dsParamLIMIT >= 0 ? $this->dsParamLIMIT : null),
            $where_and_joins['where'],
            $where_and_joins['joins'],
            false,
            false,
            true,
            array_merge(array($this->emailField), $this->nameFields)
        );
        // The count method of the entrymanager does not work properly, so this hack is needed :(
        $count = $this->getCount();
        $entries['total-entries'] = $count;
        $entries['total-pages'] = ceil($count / $this->dsParamLIMIT);
        $entries['remaining-pages'] = $entries['total-pages'] - $entries['current-page'];

        return $entries;
    }

    /**
     * Fetch number of recipients
     *
     * @return int
     */
    public function getCount()
    {
        parent::getCount();
        // To get the exact count for the newsletter requires a very slow query.
        // This value is not used anywhere, so for performance reasons count will not return anything.
        if ($this->newsletter_id !== null) {
            return -1;
        }
        $where_and_joins = $this->getWhereJoinsAndGroup(true);
        try {
            $count = Symphony::Database()->fetchVar('count',0, sprintf('SELECT SQL_CACHE count(DISTINCT `d`.`value`) as `count` FROM `tbl_entries` AS `e` %s %s', $where_and_joins['joins'], ' WHERE 1 ' . $where_and_joins['where']));
        } catch (DatabaseException $e) {
            // Invalid, not supported field. Instead of giving an error we should just return 0 recipients.
            // This can be improved later to recognise the type of field, and adjust the query accordingly, but for now this will do.
            $count = 0;
        }

        return $count;
    }

    /**
     * Get where and join information to build a query.
     *
     * The information returned by this function can be used in the
     * fetch() methods of the EntryManager class. If you only need
     * to fetch data the getSlice function is recommended.
     *
     * @return array
     */
    public function getWhereJoinsAndGroup($count_only = false)
    {
        $where = null;
        $joins = null;
        if (is_array($this->dsParamFILTERS) && !empty($this->dsParamFILTERS)) {
            foreach ($this->dsParamFILTERS as $field_id => $filter) {

                if ((is_array($filter) && empty($filter)) || trim($filter) == '') {
                    continue;
                }

                if (!is_array($filter)) {
                    $filter_type = $this->__determineFilterType($filter);

                    $value = preg_split('/'.($filter_type == Datasource::FILTER_AND ? '\+' : '(?<!\\\\),').'\s*/', $filter, -1, PREG_SPLIT_NO_EMPTY);
                    $value = array_map('trim', $value);

                    $value = array_map(array('Datasource', 'removeEscapedCommas'), $value);
                } else $value = $filter;

                if (!isset($fieldPool[$field_id]) || !is_object($fieldPool[$field_id])) {
                    $fieldPool[$field_id] =& FieldManager::fetch($field_id);
                }

                if ($field_id != 'id' && $field_id != 'system:date' && !($fieldPool[$field_id] instanceof Field)) {
                    throw new Exception(
                        __(
                            'Error creating field object with id %1$d, for filtering in data source "%2$s". Check this field exists.',
                            array($field_id, $this->dsParamROOTELEMENT)
                        )
                    );
                }

                if ($field_id == 'id') {
                    $where = " AND `e`.id IN ('".implode("', '", $value)."') ";
                } elseif ($field_id == 'system:date') {
                    $date = new fieldDate(Frontend::instance());

                    // Create an empty string, we don't care about the Joins, we just want the WHERE clause.
                    $empty = "";
                    $date->buildDSRetrievalSQL($value, $empty, $where, ($filter_type == Datasource::FILTER_AND ? true : false));

                    $where = preg_replace('/`t\d+`.value/', '`e`.creation_date', $where);
                } else {
                    if (!$fieldPool[$field_id]->buildDSRetrievalSQL($value, $joins, $where, ($filter_type == Datasource::FILTER_AND ? true : false))) { $this->_force_empty_result = true; return; }
                    if (!$group) {
                        $group = $fieldPool[$field_id]->requiresSQLGrouping();
                    }
                }
            }
        }

        $where .= ' AND `d`.`value` IS NOT NULL';

        $joins .= ' LEFT JOIN tbl_entries_data_'.FieldManager::fetchFieldIDFromElementName($this->emailField, $this->getSource()).' AS `d` ON `e`.`id` = `d`.`entry_id`';

        if ($this->newsletter_id !== null) {
            $joins .= ' LEFT OUTER JOIN tbl_tmp_email_newsletters_sent_'.$this->newsletter_id.' AS `n` ON `d`.`value` = `n`.`email`';
            $where .= ' AND `n`.`email` IS NULL GROUP BY `d`.`value`';
        } elseif ($count_only != true) {
            $where .= ' GROUP BY `d`.`value`';
        }

        return array(
            'where' => $where,
            'joins' => $joins
        );
    }

    public function getProperties()
    {
        $properties = array(
            'email' => $this->emailField,
            'name' => array(
                'fields' => $this->nameFields,
                'xslt'  => $this->nameXslt
            )
        );

        return array_merge(parent::getProperties(), $properties);
    }
}
