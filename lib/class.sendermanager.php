<?php

if (!defined('ENMDIR')) define('ENMDIR', EXTENSIONS . "/email_newsletter_manager");

class sendermanager
{
    public static function __getHandleFromFilename($filename)
    {
        $result = sscanf($filename, 'sender.%[^.].php');

        return str_replace('_', '-', $result[0]);
    }

    public static function __getClassName($handle)
    {
        return sprintf('sender%s', ucfirst(str_replace('-', '_', $handle)));
    }

    public static function __getClassPath($handle, $new = false)
    {
        if (is_file(WORKSPACE . "/email-newsletters/sender.".str_replace('-', '_', $handle).".php") || $new == true) {
            return WORKSPACE . '/email-newsletters';
        }
        else {
            $extensions = Symphony::ExtensionManager()->listInstalledHandles();

            if (is_array($extensions) && !empty($extensions)) {
                foreach ($extensions as $e) {
                    if (is_file(EXTENSIONS . "/$e/email-newsletters/sender.".str_replace('-', '_', $handle).".php")) {
                        return EXTENSIONS . "/$e/email-newsletters";
                    }
                }
            }
        }

        return false;
    }

    public static function __getDriverPath($handle)
    {
        return self::__getClassPath($handle, true) . '/sender.'.str_replace('-', '_', $handle).'.php';
    }

    public static function listAll()
    {
        $result = array();

        $structure = General::listStructure(WORKSPACE . '/email-newsletters', '/sender.[\\w-]+.php/', false, 'ASC', WORKSPACE . '/email-newsletters');

        if (is_array($structure['filelist']) && !empty($structure['filelist'])) {
            foreach ($structure['filelist'] as $f) {
                $f = self::__getHandleFromFilename($f);

                if ($about = self::about($f)) {

                    $classname = self::__getClassName($f);
                    $path = self::__getDriverPath($f);

                    $can_parse = true;

                    if (method_exists($classname,'allowEditorToParse')) {
                        $can_parse = call_user_func(array($classname, 'allowEditorToParse'));
                    }

                    $about['can_parse'] = $can_parse;
                    $result[$f] = $about;
                }
            }
        }

        $extensions = Symphony::ExtensionManager()->listInstalledHandles();

        if (is_array($extensions) && !empty($extensions)) {
            foreach ($extensions as $e) {
                if (!is_dir(EXTENSIONS . "/$e/email-newsletters")) {
                    continue;
                }

                $tmp = General::listStructure(EXTENSIONS . "/$e/email-newsletters", '/sender.[\\w-]+.php/', false, 'ASC', EXTENSIONS . "/$e/email-newsletters");

                if (is_array($tmp['filelist']) && !empty($tmp['filelist'])) {
                    foreach ($tmp['filelist'] as $f) {
                        $f = self::__getHandleFromFilename($f);

                        if ($about = self::about($f)) {
                            $about['can_parse'] = false;
                            $about['type'] = null;
                            $result[$f] = $about;
                        }
                    }
                }
            }
        }

        ksort($result);

        return $result;
    }

    public static function &create($handle) {
        $classname = self::__getClassName($handle);
        $path = self::__getDriverPath($handle);

        if (!is_file($path)) {
            throw new Exception(
                __(
                    'Could not find Newsletter Sender <code>%s</code>. If the Newsletter Sender was provided by an Extension, ensure that it is installed, and enabled.',
                    array($handle)
                )
            );
        }

        if (!class_exists($classname)) {
            require_once($path);
        }

        if (class_exists($classname)) {
            $ret = new $classname(Symphony::Engine());

            return $ret;
        }
        throw new Exception(
            __(
                'The Newsletter Sender <code>%s</code> has an invalid format. Please check the documentation for details on class names.',
                array($handle)
            )
        );

    }

    public static function save($handle = null, $fields)
    {
        if (strlen(Lang::createHandle($fields['name'])) == 0) {
            return false;
        }
        if ($handle == Lang::createHandle($fields['name'], 255, '-') || (($handle == null) && (self::__getClassPath(Lang::createHandle($fields['name'], 255, '-')) == false))) {
            if (self::_writeSender(Lang::createHandle($fields['name'], 255, '-'), self::_parseTemplate($fields))) {
                Symphony::ExtensionManager()->notifyMembers(
                    'PostSenderSaved',
                    '/extension/email_newsletter_manager/',
                    array(
                        'handle'        => $handle,
                        'fields'        => $fields
                    )
                );

                return true;
            } else {
                return false;
            }
        } elseif (false == self::__getClassPath(Lang::createHandle($fields['name'], 255, '-'))) {
            if (!self::_writeSender(Lang::createHandle($fields['name'], 255, '-'), self::_parseTemplate($fields))) {
                return false;
            }
            if (!@unlink(self::__getDriverPath($handle))) {
                return false;
            }
            Symphony::ExtensionManager()->notifyMembers(
                'PostSenderSaved',
                '/extension/email_newsletter_manager/',
                array(
                    'handle'        => $handle,
                    'fields'        => $fields
                )
            );

            return true;
        } else {
            throw new Exception('Newsletter Sender ' . $fields['handle'] . ' already exists. Please choose a different name.');
        }
    }

    public static function delete($handle = null)
    {
        Symphony::ExtensionManager()->notifyMembers(
            'PreSenderDelete',
            '/extension/email_newsletter_manager/',
            array(
                'handle'        => $handle
            )
        );
        if (@unlink(self::__getDriverPath($handle))) {
            Symphony::ExtensionManager()->notifyMembers(
                'PostSenderDelete',
                '/extension/email_newsletter_manager/',
                array(
                    'handle'        => $handle
                )
            );

            return true;
        } else {
            return false;
        }
    }

    public static function about($name)
    {
        $classname = self::__getClassName($name);
        $path = self::__getDriverPath($name);

        if (!@file_exists($path)) {
            return false;
        }

        require_once($path);

        $handle = self::__getHandleFromFilename(basename($path));

        if (is_callable(array($classname, 'about'))) {
            $about = call_user_func(array(new $classname, 'about'));

            return array_merge($about, array('handle' => $handle));
        }

    }

    protected function _writeSender($handle, $contents)
    {
        $dir = self::__getClassPath($handle, true);
        if (is_dir($dir) && is_writeable($dir)) {
            if ((is_writeable(self::__getDriverPath($handle))) || !file_exists(self::__getDriverPath($handle))) {
                file_put_contents(self::__getDriverPath($handle), $contents);

                return true;
            } else {
                throw new Exception("File " . self::getDriverPath($handle) . " can not be written to. Please check permissions");

                return false;
            }
        } else {
            throw new Exception("Directory $dir does not exist, or is not writeable.");

            return false;
        }
    }

    protected function _parseTemplate($data)
    {
        $template = file_get_contents(ENMDIR . '/content/templates/tpl/sender.tpl');

        // flatten the duplicator array
        $filters = array();
        if (is_array($data['filter']) && !empty($data['filter'])) {
            foreach ($data['filter'] as $filter) {
                foreach ($filter as $key => $value) {
                    if (trim($value) == '') {
                        continue;
                    }
                    $filters[$key] = $value;
                }
            }
        }

        $template = str_replace('<!-- CLASS NAME -->' , self::__getClassName(Lang::createHandle($data['name'], 255, '_')), $template);
        $template = str_replace('<!-- NAME -->' , addcslashes($data['name'], "'"), $template);
        $template = str_replace('<!-- REPLY_TO_NAME -->' , addcslashes($data['reply-to-name'], "'"), $template);
        $template = str_replace('<!-- REPLY_TO_EMAIL -->' , addcslashes($data['reply-to-email'], "'"), $template);
        $template = str_replace('<!-- GATEWAY_SETTINGS -->' , '\''.$data['gateway'] . '\' => ' . var_export($data['email_' . $data['gateway']], true), $template);
        $template = str_replace('<!-- ADDITIONAL_HEADERS -->' , var_export($data['additional_headers'], true), $template);
        $template = str_replace('<!-- THROTTLE_EMAILS -->' , (int) addcslashes($data['throttle-emails'], "'"), $template);
        $template = str_replace('<!-- THROTTLE_TIME -->' , (int) addcslashes($data['throttle-time'], "'"), $template);

        return $template;
    }
}
