<?php
/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Model_Backup_Step
 *
 * @method Mageplace_Backup_Model_Backup_Step setBackupId()
 * @method Mageplace_Backup_Model_Backup_Step setStep()
 * @method Mageplace_Backup_Model_Backup_Step setFiles()
 * @method Mageplace_Backup_Model_Backup_Step setIsNext()
 * @method Mageplace_Backup_Model_Backup_Step setError()
 * @method int getBackupId()
 * @method string getStep()
 * @method array getFiles()
 * @method array getStepData()
 * @method int getIsNext()
 * @method int getFinished()
 * @method string getError()
 * @method string getCurrentStep()
 *
 */
class Mageplace_Backup_Model_Backup_Step extends Varien_Object
{
    const SESSION_PARAM_SKIP_STERS = 'skipped_steps';

    /**
     * @see Mageplace_Backup_Model_Backup_Step::$STEP_OBJECT
     */
    const SO_SECRET        = 'secret';
    const SO_STEP          = 'step';
    const SO_DB            = 'db';
    const SO_FILES         = 'files';
    const SO_DATA          = 'data';
    const SO_IS_NEXT       = 'is_next';
    const SO_FINISHED      = 'finished';
    const SO_ERROR         = 'error';
    const SO_SID           = 'session_id';
    const SO_COMPRESS_DATA = 'compress_data';
    const SO_CLOUD_DATA    = 'cloud_data';
    const STEP_PARAM_BYTE  = Mageplace_Backup_Model_File::STEP_PARAM_BYTE;

    const STEP_START = 'start';
    /**
     * @see Mageplace_Backup_Model_Backup::stepFirst()
     */
    const STEP_FIRST = 'first';
    /**
     * @see Mageplace_Backup_Model_Backup::stepMemoryLimit()
     */
    const STEP_CHECK_MEMORY_LIMIT = 'check-ml';
    /**
     * @see Mageplace_Backup_Model_Backup::stepFreeDiskSpace()
     */
    const STEP_FREE_DISK_SPACE = 'check-fds';
    /**
     * @see Mageplace_Backup_Model_Backup::stepDatabaseBackup()
     */
    const STEP_DB_BACKUP = 'db';
    /**
     * @see Mageplace_Backup_Model_Backup::stepDatabaseFilesPrepare()
     */
    const STEP_DB_FILES_PREPARE = 'db-files-prepare';
    /**
     * @see Mageplace_Backup_Model_Backup::stepDatabaseCloud()
     */
    const STEP_DB_CLOUD = 'db-cloud';
    /**
     * @see Mageplace_Backup_Model_Backup::stepDatabaseFinish()
     */
    const STEP_DB_FINISH = 'db-finish';
    /**
     * @see Mageplace_Backup_Model_Backup::stepFilesBackup()
     */
    const STEP_FILES_BACKUP = 'files';
    /**
     * @see Mageplace_Backup_Model_Backup::stepFilesBackup()
     */
    const STEP_FILES_PACK = 'files-pack';
    /**
     * @see Mageplace_Backup_Model_Backup::stepFilesPrepare()
     */
    const STEP_FILES_PREPARE = 'files-prepare';
    /**
     * @see Mageplace_Backup_Model_Backup::stepFilesCloud()
     */
    const STEP_FILES_CLOUD = 'files-cloud';
    /**
     * @see Mageplace_Backup_Model_Backup::stepFilesFinish()
     */
    const STEP_FILES_FINISH = 'files-finish';
    /**
     * @see Mageplace_Backup_Model_Backup::backupCreate()
     */
    const STEP_FINISH = 'finish';

    /**
     * @var array
     * @see Mageplace_Backup_Model_Backup::getNextStep()
     */
    protected static $STEP_BY_STEP = array(
        self::STEP_START,
        self::STEP_FIRST,
        self::STEP_CHECK_MEMORY_LIMIT,
        self::STEP_FREE_DISK_SPACE,
        self::STEP_DB_BACKUP,
        self::STEP_DB_FILES_PREPARE,
        self::STEP_DB_CLOUD,
        self::STEP_DB_FINISH,
        self::STEP_FILES_BACKUP,
        self::STEP_FILES_PACK,
        self::STEP_FILES_PREPARE,
        self::STEP_FILES_CLOUD,
        self::STEP_FILES_FINISH,
        self::STEP_FINISH
    );

    /**
     * Starting point is empty step without return $this->getStepObject() and it equal 1
     *
     * @see Mageplace_Backup_Model_Backup::getStepObjectJs()
     */
    protected static $STEP_POINTS = array(
        self::STEP_START              => 10,
        self::STEP_FIRST              => 20,
        self::STEP_CHECK_MEMORY_LIMIT => 10,
        self::STEP_FREE_DISK_SPACE    => 15,
        self::STEP_DB_BACKUP          => 50,
        self::STEP_DB_FILES_PREPARE   => 20,
        self::STEP_DB_CLOUD           => 40,
        self::STEP_DB_FINISH          => 2,
        self::STEP_FILES_BACKUP       => 10,
        self::STEP_FILES_PACK         => 100,
        self::STEP_FILES_PREPARE      => 30,
        self::STEP_FILES_CLOUD        => 70,
        self::STEP_FILES_FINISH       => 10,
    );

    /**
     * @var array
     * @see Mageplace_Backup_Model_Backup::getCheckStepMethod()
     * @see Mageplace_Backup_Model_Backup::backupCreate()
     */
    protected static $STEP_METHODS_NAMES = array(
        self::STEP_FIRST              => 'First',
        self::STEP_CHECK_MEMORY_LIMIT => 'MemoryLimit',
        self::STEP_FREE_DISK_SPACE    => 'FreeDiskSpace',
        self::STEP_DB_BACKUP          => 'DatabaseBackup',
        self::STEP_DB_FILES_PREPARE   => 'DatabaseFilesPrepare',
        self::STEP_DB_CLOUD           => 'DatabaseCloud',
        self::STEP_DB_FINISH          => 'DatabaseFinish',
        self::STEP_FILES_BACKUP       => 'FilesBackup',
        self::STEP_FILES_PACK         => 'FilesPack',
        self::STEP_FILES_PREPARE      => 'FilesPrepare',
        self::STEP_FILES_CLOUD        => 'FilesCloud',
        self::STEP_FILES_FINISH       => 'FilesFinish',
    );

    /**
     * @var array
     * @see Mageplace_Backup_Model_Backup_Step::getData()
     */
    protected static $STEP_OBJECT = array(
        self::SO_SECRET        => '',
        self::SO_STEP          => '',
        self::SO_DB            => array(),
        self::SO_FILES         => array(),
        self::SO_DATA          => array(),
        self::SO_COMPRESS_DATA => array(),
        self::SO_CLOUD_DATA    => array(),
        self::SO_IS_NEXT       => 1,
        self::SO_FINISHED      => 0,
        self::SO_ERROR         => '',
        self::SO_SID           => '',
        self::STEP_PARAM_BYTE  => 0
    );

    /**
     * @var Mageplace_Backup_Model_Backup
     */
    protected $_backup;
    /**
     * @var int
     */
    protected $_backupId;
    /**
     * @var int
     */
    protected $_backupSecret;
    /**
     * @var Mage_Core_Controller_Request_Http
     */
    protected $_request;

    protected function _construct()
    {
        $this->_data = self::$STEP_OBJECT;
    }

    /**
     * @param Mageplace_Backup_Model_Backup $backup
     *
     * @return $this
     */
    public function setBackup(Mageplace_Backup_Model_Backup $backup)
    {
        $this->_backup       = $backup;
        $this->_backupId     = $backup->getId();
        $this->_backupSecret = $backup->getBackupSecret();
        $this->_request      = $backup->getRequest();

        return $this;
    }

    public function setStepData($data)
    {
        $this->setData(self::SO_DATA, $data);

        return $this;
    }

    public function setStepCompressData($data)
    {
        $this->setData(self::SO_COMPRESS_DATA, $data);

        return $this;
    }

    public function addStepCompressData($data)
    {
        $this->setData(self::SO_COMPRESS_DATA, array_merge($this->getData(self::SO_COMPRESS_DATA), $data));

        return $this;
    }

    public function setStepDb($data)
    {
        $this->setData(self::SO_DB, $data);

        return $this;
    }

    public function addStepDb($data)
    {
        $this->setData(self::SO_DB, array_merge($this->getData(self::SO_DB), $data));

        return $this;
    }

    public function setStepCloudData($data)
    {
        $this->setData(self::SO_CLOUD_DATA, $data);

        return $this;
    }

    public function addStepCloudData($data)
    {
        $this->setData(self::SO_CLOUD_DATA, array_merge($this->getData(self::SO_CLOUD_DATA), $data));

        return $this;
    }

    public function setFinished($value = 1)
    {
        $this->setData(self::SO_FINISHED, $value);

        return $this;
    }

    public function isFinished()
    {
        return intval($this->getData(self::SO_FINISHED)) == 1;
    }

    public function isNext()
    {
        return $this->getData(self::SO_IS_NEXT);
    }

    public function getSid()
    {
        return $this->getData(self::SO_SID);
    }


    public function isMultiStep()
    {
        if (is_null($this->_getData('is_multi_step'))) {
            $manualEnable = $this->_backup->getProfileData('profile_multiprocess_enable');
            $cronEnable   = $this->_backup->getProfileData('profile_multiprocess_cron_enable');
            $isCron       = $this->_backup->getData('backup_cron');
            if ($isCron && $cronEnable) {
                $this->setData('is_multi_step', true);
            } else {
                if (!$isCron && $manualEnable) {
                    $this->setData('is_multi_step', true);
                } else {
                    $this->setData('is_multi_step', false);
                }
            }
        }

        return $this->_getData('is_multi_step');
    }

    /**
     * @param null $key
     *
     * @return Mage_Core_Controller_Request_Http | mixed
     */
    public function getRequest($key = null)
    {
        if (is_null($key)) {
            return $this->_request;
        } else {
            if (is_null($this->_getData('request_' . $key))) {
                $this->setData('request_' . $key, $this->_request->getParam($key));
            }

            return $this->_getData('request_' . $key);
        }
    }

    public function getRequestBackupStep()
    {
        return $this->getRequest(self::SO_STEP);
    }

    public function getRequestBackupFiles()
    {
        return $this->getRequest(self::SO_FILES);
    }

    public function getRequestBackupData()
    {
        return $this->getRequest(self::SO_DATA);
    }

    public function getRequestBackupCompressData()
    {
        return $this->getRequest(self::SO_COMPRESS_DATA);
    }

    public function getRequestBackupDb()
    {
        return $this->getRequest(self::SO_DB);
    }

    public function getRequestStepBytes()
    {
        return $this->getRequest(self::STEP_PARAM_BYTE);
    }

    public function getRequestBackupCloudData($param = null)
    {
        $data = $this->getRequest(self::SO_CLOUD_DATA);
        if(null === $param) {
            return $data;
        }

        return empty($data[$param]) ? null : $data[$param];
    }

    public function getStepNumber()
    {
        if (is_null($this->_getData('step_number'))) {
            $stepCounter = 0;
            for ($i = 0, $stepCount = count(self::$STEP_BY_STEP); $i < $stepCount; $i++) {
                $step = self::$STEP_BY_STEP[$i];
                if ($this->getCheckStepMethod($step)) {
                    $stepCounter++;
                }
            }

            $this->setData('step_number', $stepCounter);
        }

        return $this->_getData('step_number');
    }

    public function getPointNumber()
    {
        if (is_null($this->_getData('point_number'))) {
            $pointTotal = 0;
            for ($i = 0, $stepCount = count(self::$STEP_BY_STEP); $i < $stepCount; $i++) {
                $step = self::$STEP_BY_STEP[$i];
                if ($this->getCheckStepMethod($step) && array_key_exists($step, self::$STEP_POINTS)) {
                    $pointTotal += self::$STEP_POINTS[$step];
                }
            }

            $this->setData('point_number', $pointTotal);
        }

        return $this->_getData('point_number');
    }

    public function getCheckStepMethod($step)
    {
        if (is_null($this->_getData('check_step_method_' . $step))) {
            if (!is_object($this->_backup)) {
                throw Mage::exception('Mageplace_Backup', Mage::helper('mpbackup')->__('Error backup object'));
            }

            if (!array_key_exists($step, self::$STEP_METHODS_NAMES)
                || !method_exists($this->_backup, 'check' . self::$STEP_METHODS_NAMES[$step])
                || $this->_backup->{'check' . self::$STEP_METHODS_NAMES[$step]}()
            ) {
                $this->setData('check_step_method_' . $step, true);
            } else {
                $this->setData('check_step_method_' . $step, false);
            }
        }

        return $this->_getData('check_step_method_' . $step);
    }

    public function isFirstStep()
    {
        if (is_null($this->_getData('first_step'))) {
            $this->setData('first_step', !$this->getRequestBackupStep() || $this->getRequestBackupStep() == self::STEP_FIRST);
        }

        return $this->_getData('first_step');
    }

    public function setCurrentStep($step)
    {
        if (!in_array($step, self::$STEP_BY_STEP)) {
            throw Mage::exception('Mageplace_Backup', Mage::helper('mpbackup')->__('Error step'));
        }

        $this->setData('current_step', $step);

        return $this;
    }

    public function getNextStep()
    {
        if (!is_object($this->_backup)) {
            throw Mage::exception('Mageplace_Backup', Mage::helper('mpbackup')->__('Error backup object'));
        }

        $currStep  = $this->getCurrentStep();
        $stepCount = count(self::$STEP_BY_STEP);
        $pos       = array_search($currStep, self::$STEP_BY_STEP) + 1;
        if ($stepCount <= $pos) {
            return self::STEP_FINISH;
        }

        $nextStep = null;
        for ($i = $pos; $i < $stepCount; $i++) {
            $nextStep = self::$STEP_BY_STEP[$i];
            if ($this->_backup->getCheckStepMethod($nextStep)) {
                return $nextStep;
            }
        }

        return self::STEP_FINISH;
    }

    public function getEmptyStepObject()
    {
        return self::$STEP_OBJECT;
    }

    public function getStepNames()
    {
        return Mage::getSingleton('mpbackup/source_step')->toOptionHash();
    }

    public function getStepMethodNames()
    {
        return self::$STEP_METHODS_NAMES;
    }

    public function getPoints()
    {
        return self::$STEP_POINTS;
    }

    public function getMethodName($key)
    {
        return $this->_camelize($key);
    }

    public function clear()
    {
        $this->_data = array_merge($this->_data, self::$STEP_OBJECT);

        return $this;
    }

    public function clearAll()
    {
        $this->_data = self::$STEP_OBJECT;

        return $this;
    }

    /**
     * Add steps to skip during backup process
     *
     * @param $step
     *
     * @return $this
     */
    public function addSkipStep($step)
    {
        $skip = $this->_getSession()->getData(self::SESSION_PARAM_SKIP_STERS);
        if (!is_array($skip)) {
            $skip = array();
        }

        if (is_array($step)) {
            $skip = array_merge($skip, $step);
        } else {
            $skip[] = $step;
        }

        $this->_getSession()->setData(self::SESSION_PARAM_SKIP_STERS, $skip);

        return $this;
    }

    public function getSkippedSteps()
    {
        $skip = $this->_getSession()->getData(self::SESSION_PARAM_SKIP_STERS);
        if (!is_array($skip)) {
            $skip = array();
        }

        return $skip;
    }

    public function serialize($attributes = array(), $valueSeparator = '=', $fieldSeparator = ' ', $quote = '"')
    {
        if (empty($attributes)) {
            $attributes = array_keys(self::$STEP_OBJECT);
        }

        return parent::serialize($attributes, $valueSeparator, $fieldSeparator, $quote);
    }

    public function __toArray(array $arrAttributes = array())
    {
        if (isset($this->_backupId)) {
            if ($this->_data[self::SO_SECRET] === self::$STEP_OBJECT[self::SO_SECRET]) {
                $this->_data[self::SO_SECRET] = $this->_backupSecret;
            }

            if ($this->_data[self::SO_STEP] === self::$STEP_OBJECT[self::SO_STEP]) {
                if ($this->_data[self::SO_IS_NEXT]) {
                    $this->_data[self::SO_STEP] = $this->getNextStep();
                } else {
                    $this->_data[self::SO_STEP] = $this->getCurrentStep();
                }
            }

            if ($this->_data[self::SO_SID] === self::$STEP_OBJECT[self::SO_SID]) {
                $this->_data[self::SO_SID] = $this->_backup->getSession()->getEncryptedSessionId();
            }
        }

        $arrRes = array();
        foreach (self::$STEP_OBJECT as $key => $value) {
            if (array_key_exists($key, $this->_data)) {
                $arrRes[$key] = $this->_data[$key];
            } else {
                $arrRes[$key] = $value;
            }
        }

        return $arrRes;
    }

    public function parse($json)
    {
        try {
            $arr = Zend_Json::decode($json);
            if (!is_array($arr) || empty($arr)) {
                return $json;
            }

            foreach (self::$STEP_OBJECT as $key => $value) {
                if (array_key_exists($key, $arr)) {
                    $this->setData($key, $arr[$key]);
                } else {
                    $this->setData($key, $value);
                }
            }

        } catch (Exception $e) {
            Mage::logException($e);

            return $json;
        }

        return $this;
    }

    protected function _getSession()
    {
        return $this->_backup->getSession();
    }
}