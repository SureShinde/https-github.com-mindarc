<?php

/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */
class Mageplace_Backup_Helper_Data extends Mage_Core_Helper_Abstract
{
    const DEFAULT_PATH = 'default';
    const LOCALPATH    = 'localpath';
    const APP_PREFIX   = 'app_';
    const APP_KEY      = 'app_key';
    const APP_SECRET   = 'app_secret';

    const BACKUP_DIR = 'mpbackups';

    const SESSION_ID       = 'mpbackup';
    const SESSION_SECTION  = 'mpbackup';
    const SESSION_MESSAGES = 'messages';

    const SESSION_MODEL_CLASS = 'mpbackup/session';

    const FILE_MEMORY_LIMIT_EXT_NAME = 'mpcml';

    protected $_isEE = false;
    protected $_eeHelper;

    protected static $_backupModel = null;

    public function __construct()
    {
        if (Mage::helper('mpbackup/version')->isEE()) {
            $this->_eeHelper = Mage::helper('mpbackup/enterprise');
        }
    }

    public static function getSessionSingleton(array $arguments = array())
    {
        $registryKey = '_singleton/' . self::SESSION_MODEL_CLASS . md5(serialize($arguments));
        if (!Mage::registry($registryKey)) {
            Mage::register($registryKey, Mage::getModel(self::SESSION_MODEL_CLASS, $arguments));
        }

        return Mage::registry($registryKey);
    }

    public function getApplicationXmlPath()
    {
        return Mage::getConfig()->getModuleDir('etc', $this->_getModuleName()) . DS . Mageplace_Backup_Model_Cloud_Application::APP_PATH_NAME;
    }

    public function getLibDir()
    {
        static $libDir;

        if ($libDir === null) {
            $libDir = Mage::getConfig()->getOptions()->getDir('lib');
            $libDir .= DS . Mageplace_Backup_Helper_Const::NAME;
        }

        return $libDir;
    }

    public function getCfg($name, $path = null, $profile_id = null)
    {
        if (is_null($path)) {
            $path = self::DEFAULT_PATH;
        }

        if (is_null($profile_id)) {
            $profile_id = $this->getDefaultProfileId();
        }

        $value = Mage::getModel('mpbackup/config')->getConfigValues($profile_id, $path, $name);

        return $value;
    }

    public function getSession($params = null, $init = false)
    {
        if (!is_array($params)) {
            $profile_id = $params;
            $params     = array();
        } else {
            $init = false;
        }

        $session = self::getSessionSingleton($params);

        if ($init) {
            if (!isset($profile_id)) {
                $profile_id = $this->getDefaultProfileId();
            }
            $session->setProfileId($profile_id);
        }

        return $session;
    }

    /**
     * Enter description here ...
     *
     * @return Mageplace_Backup_Model_Profile
     */
    public function getDefaultProfile()
    {
        return Mage::getModel('mpbackup/profile')->getDefault();
    }

    /**
     * Enter description here ...
     *
     * @return int
     */
    public function getDefaultProfileId()
    {
        static $id;

        if (is_null($id)) {
            $profile = $this->getDefaultProfile();
            if (!($profile instanceof Mageplace_Backup_Model_Profile) || !($id = $profile->getId())) {
                $id = 0;
            }
        }

        return $id;
    }

    /**
     * @param mixed|null $profile_id
     *
     * @return Mageplace_Backup_Model_Profile
     */
    public function getProfile($profile_id = null)
    {
        if (!$profile_id) {
            $profile = $this->getDefaultProfile();
        } else {
            $profile = Mage::getModel('mpbackup/profile')->load($profile_id);
        }

        return $profile;
    }

    /**
     * @param Mageplace_Backup_Model_Profile|int $profile
     *
     * @return mixed|null
     */
    public function getCloudApplication($profile)
    {
        if (is_int($profile)) {
            $profile = $this->getProfile($profile);
        }

        if (!($profile instanceof Mageplace_Backup_Model_Profile) || !$profile->getId()) {
            return null;
        }

        $profile_cloud_app = $profile->getData('profile_cloud_app');
        $cloud_storage     = Mage::getModel('mpbackup/cloud')->getInstance($profile_cloud_app);
        $cloud_storage->setProfile($profile);

        return $cloud_storage;
    }

    public function getSessionKey()
    {
        return self::CONFIG_PATH;
    }

    public function getApps()
    {
        return Mage::getModel('mpbackup/cloud_application')->getAppsArray();
    }

    public function getAppConfig($app_name)
    {
        if (strtolower($app_name) == Mageplace_Backup_Model_Cloud::DEFAULT_CLOUD_APP) {
            return array();
        }

        $apps = $this->getApps();

        return (array_key_exists($app_name, $apps) ? $apps[$app_name] : null);
    }

    public function getAppsOptionArray()
    {
        static $options = array();

        if (empty($options)) {
            $options[] = array(
                'value' => '',
                'label' => $this->__('Local Storage')
            );

            foreach ($this->getApps() as $app) {
                $options[] = array(
                    'value' => $app['name'],
                    'label' => $app['label']
                );
            }
        }

        return $options;
    }

    public function getAppsArray()
    {
        static $options = array();

        if (empty($options)) {
            $options[''] = $this->__('Local Storage');

            foreach ($this->getApps() as $app) {
                $options[$app['name']] = $app['label'];
            }
        }

        return $options;
    }

    public function getLocalBackupLocation()
    {

        if (Mage::getStoreConfig('system/dropboxbackup/localdir')) {
            return Mage::getStoreConfig('system/dropboxbackup/localdir');
        } else {
            return Mage::getBaseDir() . DS . "var" . DS . "backups";
        }

    }

    public function addBackupProcessMessage($message, $error = false)
    {
        if (is_null(self::$_backupModel)) {
            if ($backup_id = Mage::getSingleton('core/session')->getBackupId()) {
                self::$_backupModel = Mage::getModel('mpbackup/backup')
                    ->load($backup_id)
                    ->initBackupData();
            }
        }

        if (self::$_backupModel instanceof Mageplace_Backup_Model_Backup) {
            self::$_backupModel->addBackupProcessMessage($message, $error);
        }
    }

    public function resetBackupProcessMessage()
    {
        self::$_backupModel = null;
    }

    public function getBytes($val)
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    public function copyDirectory($source, $destination)
    {
        if (is_dir($source)) {
            @mkdir($destination);
            $directory = dir($source);

            while (false !== ($readdirectory = $directory->read())) {
                if ($readdirectory == '.' || $readdirectory == '..') {
                    continue;
                }

                $PathDir = $source . DS . $readdirectory;
                if (is_dir($PathDir)) {
                    $this->copyDirectory($PathDir, $destination . DS . $readdirectory);
                    continue;
                }

                @copy($PathDir, $destination . DS . $readdirectory);
            }

            $directory->close();
        } else {
            @copy($source, $destination);
        }
    }

    public function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir) || is_link($dir)) {
            return @unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . "/" . $item)) {
                @chmod($dir . "/" . $item, 0777);
                if (!$this->deleteDirectory($dir . "/" . $item)) {
                    return false;
                }
            };
        }

        return @rmdir($dir);
    }

    /**
     * @param Mageplace_Backup_Model_Backup $backup
     * @param null                          $bytes
     *
     * @throws Mageplace_Backup_Exception|Mage_Core_Exception
     * @return bool|int
     */
    public function getMemoryLimit($backup, $bytes = null)
    {
        if (is_null($bytes)) {
            $mBytes = Mageplace_Backup_Model_Backup::MEMORY_LIMIT;
        } else {
            $mBytes = intval($bytes);
        }

        if (!$mBytes || !($backup instanceof Mageplace_Backup_Model_Backup)) {
            throw Mage::exception('Mageplace_Backup', $this->__('Wrong input data'));
        }

        $secret = $backup->getBackupSecret();

        $client = new Varien_Http_Client();
        $client->setUri(Mage::helper('mpbackup/url')->getCheckMemoryLimitUrl())
            ->setConfig(array('timeout' => 30))
            ->setHeaders('accept-encoding', '')
            ->setParameterPost(array('backup_secret' => $secret, 'mbytes' => $mBytes))
            ->setMethod(Zend_Http_Client::POST);

        if ($backup->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_AUTH_ENABLE)
            && ($user = $backup->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_AUTH_USER))
        ) {
            $client->setAuth($user, $backup->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_AUTH_PASSWORD));
        }

        $response = $client->request();

        $body = $response->getRawBody();

        if ($body == Mageplace_Backup_Helper_Const::MEMORY_LIMIT_OK) {
            return true;
        } elseif ($body == Mageplace_Backup_Helper_Const::MEMORY_LIMIT_FALSE) {
            throw Mage::exception('Mageplace_Backup', $this->__('Extension can\'t get memory limit'));
        } else {
            $memoryLimitFile = $this->getCheckMemoryLimitFileLocation($backup);
            if (!file_exists($memoryLimitFile)) {
                if ($body) {
                    $backup->addBackupProcessMessage($body, Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING);
                }
                throw Mage::exception('Mageplace_Backup', $this->__('Extension can\'t get memory limit'));
            }

            $steps = @file($this->getCheckMemoryLimitFileLocation($backup), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (empty($steps) || !is_array($steps)) {
                if ($body) {
                    $backup->addBackupProcessMessage($body, Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING);
                }
                throw Mage::exception('Mageplace_Backup', $this->__('Extension can\'t get memory limit'));
            }

            $last = array_pop($steps);

            @list($iterator, $mpu) = explode('-', $last);
            if ($mpu) {
                $memoryLimit = $mpu / 1024 / 1024;
            } else {
                $memoryLimit = $iterator;
            }

            $memoryLimit = (int)$memoryLimit;
            if ($memoryLimit <= Mageplace_Backup_Model_Backup::MEMORY_LIMIT_LOW) {
                throw Mage::exception('Mageplace_Backup', $this->__('Memory limit too low (%s Mb)', $memoryLimit));
            }

            return $memoryLimit;
        }
    }

    public function checkMemoryLimit($secret, $bytes)
    {
        /** @var Mageplace_Backup_Model_Backup $backup */
        $backup = Mage::getModel('mpbackup/backup')->getCurrentBackup($secret);
        if (!$backup->getId()) {
            die(Mageplace_Backup_Helper_Const::MEMORY_LIMIT_FALSE);
        }

        $mBytes = (int)$bytes;
        if ($mBytes <= 0) {
            die(Mageplace_Backup_Helper_Const::MEMORY_LIMIT_FALSE);
        }

        $memoryLimitFile = $this->getCheckMemoryLimitFileLocation($backup);
        $check           = @file_put_contents($memoryLimitFile, '', FILE_APPEND | LOCK_EX);
        if ($check === false) {
            die(Mageplace_Backup_Helper_Const::MEMORY_LIMIT_FALSE);
        }

        $backup->addTempFile($memoryLimitFile);

        $megabyte = str_repeat('==8bytes', 128 * 1024); /* == 1Mb */

        $test = '';
        $step = Mageplace_Backup_Model_Backup::MEMORY_LIMIT_CHECK_STEP;
        for ($i = $step; $i < $mBytes; $i += $step) {
            $fp = fopen($memoryLimitFile, 'ab');
            fwrite($fp, $i . '-' . @memory_get_peak_usage(1) . PHP_EOL);
            fclose($fp);

            $test .= str_repeat($megabyte, $step);
        }

        echo Mageplace_Backup_Helper_Const::MEMORY_LIMIT_OK;
        exit(1);
    }

    /**
     * @param Mageplace_Backup_Model_Backup $backup
     *
     * @return string
     */
    public function getCheckMemoryLimitFileLocation($backup)
    {
        return $backup->getProfileData('profile_backup_path') . DS . $backup->getBackupKey() . '.' . self::FILE_MEMORY_LIMIT_EXT_NAME;
    }

    public function request($url, $params, $object = null, $timeout = null)
    {
        if (!$timeout) {
            $timeout = 0;
        }

        try {
            $client = new Varien_Http_Client();
            $client->setUri($url)
                ->setConfig(array('timeout' => $timeout))
                ->setHeaders('accept-encoding', '')
                ->setParameterPost($params)
                ->setMethod(Zend_Http_Client::POST);

            if (($object instanceof Mageplace_Backup_Model_Backup)
                && $object->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_AUTH_ENABLE)
                && $object->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_AUTH_USER)
            ) {
                $client->setAuth(
                    $object->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_AUTH_USER),
                    $object->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_AUTH_PASSWORD)
                );
            } elseif (($object instanceof Mageplace_Backup_Model_Profile)
                && $object->getData(Mageplace_Backup_Model_Profile::COLUMN_AUTH_ENABLE)
                && $object->getData(Mageplace_Backup_Model_Profile::COLUMN_AUTH_USER)
            ) {
                $client->setAuth(
                    $object->getData(Mageplace_Backup_Model_Profile::COLUMN_AUTH_USER),
                    $object->getData(Mageplace_Backup_Model_Profile::COLUMN_AUTH_PASSWORD)
                );
            }

            $response = $client->request();

            return $response->getRawBody();
        } catch (Zend_Http_Client_Exception $zhce) {
            if ($zhce->getMessage() != 'Unable to read response, or response is empty') {
                Mage::logException($zhce);
                throw $zhce;
            }
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }

        return '';
    }
}