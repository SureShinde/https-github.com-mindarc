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
 * @method Mageplace_Backup_Model_Backup setProfileId
 * @method Mageplace_Backup_Model_Backup setBackupName
 * @method Mageplace_Backup_Model_Backup setBackupSecret
 * @method Mageplace_Backup_Model_Backup setBackupKey
 * @method Mageplace_Backup_Model_Backup setBackupCron
 * @method Mageplace_Backup_Model_Backup setBackupFilename
 * @method Mageplace_Backup_Model_Backup setBackupDescription
 * @method Mageplace_Backup_Model_Backup setBackupStarted @deprecated after version 2.0.0
 * @method Mageplace_Backup_Model_Backup setBackupErrors
 * @method Mageplace_Backup_Model_Backup setBackupFinished @deprecated after version 2.0.0
 * @method Mageplace_Backup_Model_Backup setBackupFinishDate
 * @method Mageplace_Backup_Model_Backup setBackupFiles
 * @method Mageplace_Backup_Model_Backup setBackupCloudFiles
 * @method Mageplace_Backup_Model_Backup setBackupAdditional
 * @method Mageplace_Backup_Model_Backup setBackupStatus
 * @method Mageplace_Backup_Model_Backup setBackupLogFile
 * @method Mageplace_Backup_Model_Backup setRequest
 * @method Mageplace_Backup_Model_Backup getRequest
 * @method int getProfileId
 * @method string getBackupName
 * @method string getBackupSecret
 * @method string getBackupKey
 * @method int getBackupCron
 * @method string getBackupFilename
 * @method string getBackupDescription
 * @method string getBackupCloudFiles
 * @method string getBackupFiles
 * @method int getBackupStarted @deprecated after version 2.0.0
 * @method int getBackupFinished @deprecated after version 2.0.0
 * @method string getBackupErrors
 * @method datetime getBackupFinishDate
 * @method mixed getBackupAdditional
 * @method Mageplace_Backup_Model_Mysql4_Backup _getResource
 */
class Mageplace_Backup_Model_Backup extends Mage_Core_Model_Abstract
{
    const COLUMN_NAME        = 'backup_name';
    const COLUMN_SECRET      = 'backup_secret';
    const COLUMN_KEY         = 'backup_key';
    const COLUMN_STATUS      = 'backup_status';
    const COLUMN_CRON        = 'backup_cron';
    const COLUMN_FILES       = 'backup_files';
    const COLUMN_CLOUD_FILES = 'backup_cloud_files';
    const COLUMN_LOG_FILE    = 'backup_log_file';
    const COLUMN_ERRORS      = 'backup_errors';
    const COLUMN_STARTED     = 'backup_started';
    const COLUMN_FINISHED    = 'backup_finished';
    const COLUMN_FINISH_DATE = 'backup_finish_date';

    const MEMORY_LIMIT                = 500; /* Mb */
    const MEMORY_LIMIT_LOW            = 150; /* Mb */
    const MEMORY_LIMIT_CHECK_STEP     = 2; /* Mb */
    const BACKUP_SECRET_STRING_LENGTH = 20;

    const MESSAGE_TYPE_TEMPLATE        = '[%s]';
    const MESSAGE_TYPE_REGEXP_TEMPLATE = '\[%s\]';
    const MESSAGE_TEMPLATE             = '%1$s %2$s[%3$s] %4$s';
    const MESSAGE_REGEXP_TEMPLATE      = '/([^\[]+)\s\[([^\]]+)\]\[([^\]]+)\]\s([^\[]*)/is';

    const BACKUP_FILE_PREFIX   = 'mp';
    const LOG_FILENAME         = 'mpblog';
    const FILE_LOG_EXT_NAME    = 'log';
    const FILE_CANCEL_EXT_NAME = 'mpcnl';

    const LOG_LEVEL_ALL     = 'ALL';
    const LOG_LEVEL_INFO    = 'INFO';
    const LOG_LEVEL_DEBUG   = 'DEBUG';
    const LOG_LEVEL_WARNING = 'WARNING';
    const LOG_LEVEL_ERROR   = 'ERROR';
    const LOG_LEVEL_OFF     = 'OFF';

    const STATUS_UNDEFINED       = 0;
    const STATUS_STARTED         = 1;
    const STATUS_FINISHED        = 2;
    const STATUS_CANCELLED       = 3;
    const STATUS_WARNINGS        = 4;
    const STATUS_ERRORS          = 5;
    const STATUS_CRITICAL_ERRORS = 6;
    const STATUS_HARD_FINISHED   = 7;

    const MULTI_STEP_TIME_EXTRAS = 2;
    const MULTI_STEP_MIN_TIME    = 10;

    /**
     * @var Mageplace_Backup_Model_Profile $_profile
     */
    protected $_profile;
    /**
     * @var Mageplace_Backup_Model_Backup_Step
     */
    protected $_stepObject;
    /**
     * @var Mageplace_Backup_Model_Temp $_temp
     */
    protected $_temp;
    protected $_profileId;
    protected $_backupId;
    protected $_logMessageFileName = '';
    protected $_logDB              = true;
    protected $_enabledTempLogFile = false;
    protected $_deleteErrors       = array();
    protected $_controlTime;
    protected $_maxTime;

    public function __construct()
    {
        $this->_controlTime = time();

        parent::__construct();

        $this->_init('mpbackup/backup');
    }

    public function isMultiStep()
    {
        static $isMultiStep;

        if ($isMultiStep === null) {
            $isMultiStep = $this->_stepObject->isMultiStep();
        }

        return $isMultiStep;
    }

    public function canContinue()
    {
        if ($this->checkCancelBackup()) {
            return false;
        }

        if ($this->isTimeLimitMultiStep() !== true) {
            return true;
        }

        return time() - $this->_controlTime < $this->_maxTime;
    }

    public function isTimeLimitMultiStep()
    {
        static $isTimeLimitMultiStep;

        if ($isTimeLimitMultiStep === null) {
            $this->_maxTime       = $this->getTimeLimit();
            $isTimeLimitMultiStep = $this->isMultiStep() === true && $this->_maxTime > 0;
        }

        return $isTimeLimitMultiStep;

    }

    public function getTimeLimit()
    {
        static $timeLimit;

        if ($timeLimit === null) {
            if ($this->getData('backup_cron')) {
                $timeLimit = (int)$this->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_CRON_TIME);
            } else {
                $timeLimit = (int)$this->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_TIME);
            }
            $timeLimit -= (int)$this->getTimeExtras();
            if ($timeLimit < 0) {
                $timeLimit = 0;
            }
        }

        return $timeLimit;
    }

    public function getTimeExtras()
    {
        return self::MULTI_STEP_TIME_EXTRAS;
    }

    /**
     * @param $secret
     *
     * @return Mageplace_Backup_Model_Backup
     */
    public function loadBySecret($secret)
    {
        return $this->load($secret, self::COLUMN_SECRET);
    }

    public function initBackupData()
    {
        if ($this->_backupId !== null) {
            return $this;
        }

        $this->_backupId = $this->getId();

        if (!$this->_profile && $this->getProfileId()) {
            $this->setProfile($this->getProfileId());
        }

        $this->_stepObject = Mage::getModel('mpbackup/backup_step')->setBackup($this);

        $this->_temp = Mage::getModel('mpbackup/temp')->setBackup($this);

        return $this;
    }

    public function setProfile($profile)
    {
        if (!($profile instanceof Mageplace_Backup_Model_Profile)) {
            $profile = Mage::getModel('mpbackup/profile')->load(intval($profile));
            if (!$profile->getId()) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Profile ID#%s not founded.', intval($profile)));
            }
        }

        $this->_profile   = $profile;
        $this->_profileId = $profile->getId();

        $this->setProfileId($this->_profileId);

        return $this;
    }

    public function getProfile()
    {
        return $this->_profile;
    }

    /**
     * @return Mageplace_Backup_Model_Cloud | mixed
     * @throws Mage_Core_Exception
     */
    public function getCloudStorage()
    {
        if (is_null($this->_getData('cloud_storage'))) {
            if (!$this->_profile) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Select profile first'));
            }

            $this->setData('cloud_storage', $this->_helper()->getCloudApplication($this->_profile)->setBackup($this));
        }

        return $this->_getData('cloud_storage');
    }

    public function getProfileData($key)
    {
        if (is_null($this->_getData('profile_data_' . $key))) {
            if (!$this->_profile instanceof Mageplace_Backup_Model_Profile) {
                $this->initBackupData();
                if (!$this->_profile instanceof Mageplace_Backup_Model_Profile) {
                    throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Select profile first'));
                }
            }

            $this->setData('profile_data_' . $key, $this->_profile->getData($key));
        }

        return $this->_getData('profile_data_' . $key);
    }

    public function getBackupFilenameKey()
    {
        if (null === $this->_getData('backup_filename_key')) {
            $this->setData('backup_filename_key',
                ($this->getBackupFilename() ? $this->getBackupFilename() : '')
                . self::BACKUP_FILE_PREFIX
                . Mage::app()->getLocale()->storeTimeStamp()
                . $this->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_BACKUP_FILENAME_SUFFIX)
            );
        }

        return $this->_getData('backup_filename_key');
    }

    public function disableDBLog()
    {
        $this->_logDB = false;
    }

    public function getStepObject()
    {
        return $this->_stepObject;
    }

    public function setStepObjectFinished($value = true)
    {
        $this->_stepObject->setFinished($value);

        return $this;
    }

    public function setStepObjectStep($value = null)
    {
        $this->_stepObject->setStep($value);

        return $this;
    }

    public function setStepObjectBackupId($value = null)
    {
        $this->_stepObject->setBackupId($value);

        return $this;
    }

    public function setStepObjectError($value = '')
    {
        $this->_stepObject->setError($value);

        return $this;
    }

    public function setStepObjectFiles($value = array())
    {
        $this->_stepObject->setFiles($value);

        return $this;
    }

    public function setStepObjectData($value = array())
    {
        $this->_stepObject->setStepData($value);

        return $this;
    }

    public function setStepCompressData(array $value = array())
    {
        $this->_stepObject->setStepCompressData($value);

        return $this;
    }

    public function addStepCompressData(array $value = array())
    {
        $this->_stepObject->addStepCompressData($value);

        return $this;
    }

    public function getStepCompressData()
    {
        return $this->_stepObject->getRequestBackupCompressData();
    }

    public function setStepDb(array $value = array())
    {
        $this->_stepObject->setStepDb($value);

        return $this;
    }

    public function addStepDb(array $value = array())
    {
        $this->_stepObject->addStepDb($value);

        return $this;
    }

    public function getStepDb()
    {
        return $this->_stepObject->getRequestBackupDb();
    }

    public function setStepCloudData(array $value = array())
    {
        $this->_stepObject->setStepCloudData($value);

        return $this;
    }

    public function addStepCloudData(array $value = array())
    {
        $this->_stepObject->addStepCloudData($value);

        return $this;
    }

    public function getStepCloudData($param = null)
    {
        return $this->_stepObject->getRequestBackupCloudData($param);
    }

    public function setStepObjectIsNext($value = 1)
    {
        $this->_stepObject->setIsNext($value);

        return $this;
    }

    public function getRequestBackupStep()
    {
        return $this->_stepObject->getRequestBackupStep();
    }

    public function getRequestBackupFiles()
    {
        return $this->_stepObject->getRequestBackupFiles();
    }

    public function getRequestBackupData()
    {
        return $this->_stepObject->getRequestBackupData();
    }

    public function getStepNumber()
    {
        return $this->_stepObject->getStepNumber();
    }

    public function getPointNumber()
    {
        return $this->_stepObject->getPointNumber();
    }

    public function getCheckStepMethod($step)
    {
        return $this->_stepObject->getCheckStepMethod($step);
    }

    public function isFirstStep()
    {
        return $this->_stepObject->isFirstStep();
    }

    public function setCurrentStep($step)
    {
        $this->_stepObject->setCurrentStep($step);

        return $this;
    }

    public function getCurrentStep()
    {
        return $this->_stepObject->getCurrentStep();
    }

    public function setSkippedSteps($step)
    {
        $this->_stepObject->addSkipStep($step);

        return $this;
    }

    public function getSkippedSteps()
    {
        return $this->_stepObject->getSkippedSteps();
    }

    public function getNextStep()
    {
        return $this->_stepObject->getNextStep();
    }

    /**
     * @throw Mage_Core_Exception
     */
    public function stepFirst()
    {
        $backupPath = $this->getProfileData('profile_backup_path');
        if (!file_exists($backupPath) && !@mkdir($backupPath)) {
            throw Mage::exception('Mageplace_Backup', $this->_helper()->__("Can't create backup directory"));
        } elseif (file_exists($backupPath) && !is_writable($backupPath)) {
            throw Mage::exception('Mageplace_Backup', $this->_helper()->__("Backup directory is not writable"));
        }

        return $this;
    }

    public function checkMemoryLimit()
    {
        return $this->getProfileData('profile_check_memory_limit');
    }

    public function stepMemoryLimit()
    {
        $this->addBackupProcessMessage($this->_helper()->__('Start check memory limit'), self::LOG_LEVEL_INFO);

        $memory_limit_error = false;
        $memory_limit       = ini_get('memory_limit');
        if ($memory_limit >= 0) {
            if ($this->_helper()->getBytes($memory_limit) < self::MEMORY_LIMIT_LOW * 1024 * 1024) {
                ini_set('memory_limit', self::MEMORY_LIMIT . 'M');
            }

            $memory_limit = ini_get('memory_limit');
            if ($this->_helper()->getBytes($memory_limit) < self::MEMORY_LIMIT_LOW * 1024 * 1024) {
                $this->addBackupProcessMessage($this->_helper()->__('Memory limit too low (%sb)', $memory_limit), self::LOG_LEVEL_WARNING);
                $memory_limit_error = true;
            }
        } else {
            $memory_limit = self::MEMORY_LIMIT;
        }

        if (!$memory_limit_error) {
            try {
                $memoryLimit = $this->_helper()->getMemoryLimit($this, $memory_limit);
                if ($memoryLimit !== true) {
                    $this->addBackupProcessMessage($this->_helper()->__("The peak of memory usage, that's been allocated to backup process is %s Mb", $memoryLimit), self::LOG_LEVEL_INFO);
                } else {
                    $this->addBackupProcessMessage($this->_helper()->__('Memory limit test successfully passed'), self::LOG_LEVEL_INFO);
                }

            } catch (Exception $e) {
                $this->addBackupProcessMessage($e->getMessage(), self::LOG_LEVEL_WARNING);
            }
        }

        $this->addBackupProcessMessage($this->_helper()->__('Finish check memory limit'), self::LOG_LEVEL_INFO);

        return $this;
    }

    public function checkFreeDiskSpace()
    {
        return intval($this->getProfileData('profile_free_disk_space')) > 0 ? true : false;
    }

    public function stepFreeDiskSpace()
    {
        $this->addBackupProcessMessage($this->_helper()->__('Start check free space'), self::LOG_LEVEL_INFO);

        $tmp      = Mage::getBaseDir('tmp');
        $filename = $tmp . DS . uniqid('space_check_file_') . '.tmp';

        try {
            $checkSpaceValue = (int)$this->getProfileData('profile_free_disk_space');
            if ((disk_free_space($tmp) / 1024 / 1024) < $checkSpaceValue) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Total number of bytes on the corresponding filesystem or disk partition is too small'));
            }

            $megabyte = str_repeat('8 bytes ', 131072); /* = 1Mb */

            if (file_exists($filename)) {
                @unlink($filename);
            }

            $fh = fopen($filename, 'a');
            for ($i = 1; $i <= $checkSpaceValue; $i++) {
                $write = fwrite($fh, $megabyte);
                if (!$write) {
                    @fclose($fh);
                    @unlink($filename);
                    throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Insufficient free space. Total free space %s Mb.', $i));
                }
            }
            fclose($fh);

            $isDeleted = @unlink($filename);
            if (!$isDeleted) {
                $this->addBackupProcessMessage($this->_helper()->__('File "%s" has not been deleted. Please, delete it manually.', $filename), self::LOG_LEVEL_WARNING);
            }

            $this->addBackupProcessMessage($this->_helper()->__('Enough free space to proceed'), self::LOG_LEVEL_INFO);

        } catch (Exception $e) {
            if (isset($fh)) {
                fclose($fh);
            }

            if (file_exists($filename)) {
                @unlink($filename);
            }

            throw $e;
        }

        $this->addBackupProcessMessage($this->_helper()->__('Finish check free space'), self::LOG_LEVEL_INFO);

        return $this;
    }

    public function checkDatabaseBackup()
    {
        $profile_type = $this->getProfileData('profile_type');

        return $profile_type == Mageplace_Backup_Model_Profile::TYPE_DBFILES || $profile_type == Mageplace_Backup_Model_Profile::TYPE_DB ? true : false;
    }

    public function stepDatabaseBackup()
    {
        if ($this->isMultiStep()) {
            $requestData = $this->getRequestBackupData();
            if (!empty($requestData) && is_array($requestData)) {
                /** @var $backupFileDb Mageplace_Backup_Model_File */
                $backupFileDb = Mage::getModel('mpbackup/file')
                    ->setProfile($this->_profile)
                    ->setBackup($this)
                    ->setData($requestData);
            }
        }

        if (!isset($backupFileDb)) {
            $this->addBackupProcessMessage($this->_helper()->__('Start DB tables backup'), self::LOG_LEVEL_INFO);

            /** @var $backupFileDb Mageplace_Backup_Model_File */
            $backupFileDb = Mage::getModel('mpbackup/file')
                ->setBackup($this)
                ->setPath($this->getProfileData('profile_backup_path'))
                ->setType('db');

            $firstTime = true;
        }

        $this->addMainBackupFiles($backupFileDb->getFileLocation());

        /** @var $dbModel Mageplace_Backup_Model_Db */
        $finished = Mage::getModel('mpbackup/db')
            ->setBackup($this)
            ->setExcludedTables($this->_profile->getExcludedTables())
            ->start($backupFileDb);

        if (!$finished) { /* $db doesn't equal true only if time based multistep was set and step time is up */
            $this->setStepObjectIsNext(0);
        }

        if (isset($firstTime)) {
            $this->addFilesForDelete($backupFileDb->getFileLocation());

            $this->addBackupFiles($backupFileDb->getFileName());
        }


        if ($this->isMultiStep()) {
            $this->setStepObjectData($backupFileDb->getData());

            return $this;
        }

        return $backupFileDb;
    }

    public function checkDatabaseFilesPrepare()
    {
        return $this->checkDatabaseBackup() && !$this->getCloudStorage()->isLocalStorage();
    }

    /**
     * @param $backupFileDb Mageplace_Backup_Model_File
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function stepDatabaseFilesPrepare($backupFileDb = null)
    {
        if ($this->isMultiStep()) {
            $requestData = $this->getRequestBackupData();
            if (empty($requestData) || !is_array($requestData)) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Step data error'));
            }

            $backupFileDb = Mage::getModel('mpbackup/file')
                ->setData($requestData)
                ->setBackup($this);
        }

        if (!is_object($backupFileDb)) {
            throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Database files prepare step error'));
        }

        $cloudStorage = $this->getCloudStorage();

        $filesArrDb = $backupFileDb->prepareFileToUpload($cloudStorage->getMaxSize());

        if (intval($backupFileDb->getFileParts()) > 0) {
            foreach ($filesArrDb as $dbFileInfo) {
                if (empty($dbFileInfo['filename']) || empty($dbFileInfo['filelocation'])) {
                    continue;
                }

                $this->addFilesForDelete($dbFileInfo['filelocation']);
            }
        }

        $backupFileDb->setExcluded($backupFileDb->getFileLocation());


        if ($this->isMultiStep()) {
            $this->setStepObjectFiles($filesArrDb);
            $this->setStepObjectData($backupFileDb->getData());

            return $this;
        }

        $backupFileDb->setFilesArrDb($filesArrDb);
        $backupFileDb->setCloudStorage($cloudStorage);

        return $backupFileDb;
    }

    public function checkDatabaseCloud()
    {
        return $this->checkDatabaseFilesPrepare();
    }

    /**
     * @param Mageplace_Backup_Model_File|null $backupFileDb
     *
     * @return $this|null
     * @throws Mageplace_Backup_Exception|Mage_Core_Exception
     */
    public function stepDatabaseCloud($backupFileDb = null)
    {
        if ($this->isMultiStep()) {
            $requestData = $this->getRequestBackupData();
            if (empty($requestData) || !is_array($requestData)) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Step data error'));
            }

            $filesArrDb = $this->getRequestBackupFiles();
            if (empty($filesArrDb) || !is_array($filesArrDb)) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Step files error'));
            }

            $backupFileDb = Mage::getModel('mpbackup/file')
                ->setData($requestData)
                ->setBackup($this);

            $cloudStorage = $this->getCloudStorage();
        }

        if (!is_object($backupFileDb)) {
            throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Database cloud step error'));
        }

        if (!$this->isMultiStep()) {
            $filesArrDb   = $backupFileDb->getFilesArrDb();
            $cloudStorage = $backupFileDb->getCloudStorage();
        }

        if (!isset($cloudStorage) || !is_object($cloudStorage)) {
            $cloudStorage = $this->getCloudStorage();
        }

        if (empty($filesArrDb) || !is_array($filesArrDb)) {
            throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Step database cloud files error'));
        }

        $dbFileInfo = array_shift($filesArrDb);

        if (!$backupFileDb->getIsContinueFileChunk()) {
            $this->addBackupProcessMessage($this->_helper()->__('Start "%s" DB backup file upload to cloud server', $dbFileInfo['filename']), self::LOG_LEVEL_INFO);
        }

        if ($this->isTimeLimitMultiStep() && $cloudStorage->hasChunkUpload()) {
            $backupFileDb->setIsContinueFileChunk(0);
            $cloudFile = $cloudStorage->putFileChunk($dbFileInfo['filename'], $dbFileInfo['filelocation']);
            if ($cloudFile === false) {
                array_unshift($filesArrDb, $dbFileInfo);
                $backupFileDb->setIsContinueFileChunk(1);
            }
        } else {
            $cloudFile = $cloudStorage->putFile($dbFileInfo['filename'], $dbFileInfo['filelocation']);
        }

        if (!$backupFileDb->getIsContinueFileChunk()) {
            if (!$cloudFile) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('"%s" DB backup file is not uploaded to cloud server', $dbFileInfo['filename']));
            }

            $addInfo = $cloudStorage->getAdditionalInfo(true);
            $this->addCloudFiles(
                (is_string($cloudFile) ? $cloudFile : null),
                (is_array($addInfo) && !empty($addInfo) ? $addInfo : null)
            );

            $this->addBackupProcessMessage($this->_helper()->__('Finish "%s" DB backup file upload to cloud server', $dbFileInfo['filename']), self::LOG_LEVEL_INFO);

            if (intval($backupFileDb->getFileParts()) > 0) {
                $this->addBackupProcessMessage($this->_helper()->__('Deleting "%s" file from the local server', $dbFileInfo['filename']), self::LOG_LEVEL_INFO);
                if (!@unlink($dbFileInfo['filelocation'])) {
                    $this->addBackupProcessMessage($this->_helper()->__('File "%s" is not deleted', $dbFileInfo['filename']), self::LOG_LEVEL_WARNING);
                }
            }
        }

        if (!$backupFileDb->getIsContinueFileChunk()) {
            $backupFileDb->setExcluded($dbFileInfo['filelocation']);
        }

        if ($this->isMultiStep()) {
            if (!empty($filesArrDb)) {
                $this->setStepObjectFiles($filesArrDb);
                $this->setStepObjectIsNext(0);
            }

            $this->setStepObjectData($backupFileDb->getData());

            return $this;
        }

        if (!empty($filesArrDb)) {
            $backupFileDb->setFilesArrDb($filesArrDb);

            return $this->stepDatabaseCloud($backupFileDb);
        }

        return $backupFileDb;
    }

    public function checkDatabaseFinish()
    {
        return $this->checkDatabaseBackup();
    }

    public function stepDatabaseFinish($data = null)
    {
        $this->addBackupProcessMessage($this->_helper()->__('Finish DB tables backup'), self::LOG_LEVEL_INFO);

        if ($this->isMultiStep()) {
            $this->setStepObjectData($this->getRequestBackupData());

            return $this;
        } else {
            return $data;
        }
    }

    public function checkFilesBackup()
    {
        $profileType = $this->getProfileData('profile_type');

        return $profileType == Mageplace_Backup_Model_Profile::TYPE_DBFILES || $profileType == Mageplace_Backup_Model_Profile::TYPE_FILES ? true : false;
    }

    public function stepFilesBackup($backupFileDb = null)
    {
        $this->addBackupProcessMessage($this->_helper()->__('Start directories and files backup'), self::LOG_LEVEL_INFO);

        /** @var $backupFiles Mageplace_Backup_Model_File */
        $backupFiles = Mage::getModel('mpbackup/file')
            ->setProfile($this->_profile)
            ->setBackup($this)
            ->setPath($this->getProfileData('profile_backup_path'))
            ->setType(Mageplace_Backup_Model_File::TYPE_FILES)
            ->start();

        $this->addFilesForDelete($backupFiles->getFileLocation());

        $this->addBackupFiles($backupFiles->getFileName());

        if ($backupFiles->getSkipCompress()) {
            $this->setSkippedSteps(array(
                Mageplace_Backup_Model_Backup_Step::STEP_FILES_PACK,
                Mageplace_Backup_Model_Backup_Step::STEP_FILES_PREPARE,
                Mageplace_Backup_Model_Backup_Step::STEP_FILES_CLOUD,
            ));
        }

        if ($this->isMultiStep()) {
            $this->setStepObjectData($backupFiles->getData());

            return $this;
        }

        return $backupFiles;
    }

    public function checkFilesPack()
    {
        return $this->checkFilesBackup();
    }

    public function stepFilesPack($backupFiles = null)
    {
        if ($this->isMultiStep()) {
            $requestData = $this->getRequestBackupData();
            if (empty($requestData) || !is_array($requestData)) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Files pack step data error'));
            }

            /** @var Mageplace_Backup_Model_File $backupFiles */
            $backupFiles = Mage::getModel('mpbackup/file')
                ->setBackup($this)
                ->setProfile($this->_profile)
                ->setData($requestData);
        }

        if (!is_object($backupFiles)) {
            throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Files pack step error'));
        }

        $isContinuePack = $backupFiles->compress();

        if ($this->isMultiStep()) {
            if ($isContinuePack) {
                $this->setStepObjectIsNext(0);
            }

            $this->setStepObjectData($backupFiles->getData());

            return $this;
        }

        return $backupFiles;
    }

    public function checkFilesPrepare()
    {
        return $this->checkFilesBackup() && !$this->getCloudStorage()->isLocalStorage();
    }

    /**
     * @param $backupFiles Mageplace_Backup_Model_File
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function stepFilesPrepare($backupFiles = null)
    {
        if ($this->isMultiStep()) {
            $requestData = $this->getRequestBackupData();
            if (empty($requestData) || !is_array($requestData)) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Files prepare step data error'));
            }

            /** @var Mageplace_Backup_Model_File $backupFiles */
            $backupFiles = Mage::getModel('mpbackup/file')
                ->setBackup($this)
                ->setProfile($this->_profile)
                ->setData($requestData);
        }

        if (!is_object($backupFiles)) {
            throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Files prepare step error'));
        }

        $cloudStorage = $this->getCloudStorage();

        $filesArr = $backupFiles->prepareFileToUpload($cloudStorage->getMaxSize());

        if (intval($backupFiles->getFileParts()) > 0) {
            foreach ($filesArr as $fileInfo) {
                if (empty($fileInfo['filename']) || empty($fileInfo['filelocation'])) {
                    continue;
                }

                $this->addFilesForDelete($fileInfo['filelocation']);
            }
        }

        if ($this->isMultiStep()) {
            $this->setStepObjectFiles($filesArr);
            $this->setStepObjectData($backupFiles->getData());

            return $this;
        }

        $backupFiles->setFilesArr($filesArr);
        $backupFiles->setCloudStorage($cloudStorage);

        return $backupFiles;
    }

    public function checkFilesCloud()
    {
        return $this->checkFilesPrepare();
    }

    /**
     * @param Mageplace_Backup_Model_File|null $backupFiles
     *
     * @return array
     * @throws Mageplace_Backup_Exception|Mage_Core_Exception
     */
    public function stepFilesCloud($backupFiles = null)
    {
        if ($this->isMultiStep()) {
            $requestData = $this->getRequestBackupData();
            if (empty($requestData) || !is_array($requestData)) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Files step error'));
            }

            $backupFiles = Mage::getModel('mpbackup/file')
                ->setBackup($this)
                ->setProfile($this->_profile)
                ->setData($requestData);

            $filesArr = $this->getRequestBackupFiles();
        }

        if (!is_object($backupFiles)) {
            throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Files prepare step error'));
        }

        if (!$this->isMultiStep()) {
            $filesArr     = $backupFiles->getFilesArr();
            $cloudStorage = $backupFiles->getCloudStorage();
        }

        if (!isset($cloudStorage) || !is_object($cloudStorage)) {
            $cloudStorage = $this->getCloudStorage();
        }

        if (empty($filesArr) || !is_array($filesArr)) {
            throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Step cloud files error'));
        }

        $fileInfo = array_shift($filesArr);

        if (!$backupFiles->getIsContinueFileChunk()) {
            $this->addBackupProcessMessage($this->_helper()->__('Start "%s" file upload to cloud server', $fileInfo['filename']), self::LOG_LEVEL_INFO);
        }

        if ($this->isTimeLimitMultiStep() && $cloudStorage->hasChunkUpload()) {
            $backupFiles->setIsContinueFileChunk(0);
            $cloudFile = $cloudStorage->putFileChunk($fileInfo['filename'], $fileInfo['filelocation']);
            if ($cloudFile === false) {
                array_unshift($filesArr, $fileInfo);
                $backupFiles->setIsContinueFileChunk(1);
            }
        } else {
            $cloudFile = $cloudStorage->putFile($fileInfo['filename'], $fileInfo['filelocation']);
        }

        if (!$backupFiles->getIsContinueFileChunk()) {
            if (!$cloudFile) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('"%s" file is not uploaded to cloud server', $fileInfo['filename']));
            }

            $addInfo = $cloudStorage->getAdditionalInfo(true);
            $this->addCloudFiles(
                (is_string($cloudFile) ? $cloudFile : null),
                (is_array($addInfo) && !empty($addInfo) ? $addInfo : null)
            );

            $this->addBackupProcessMessage($this->_helper()->__('Finish "%s" file upload to cloud server', $fileInfo['filename']), self::LOG_LEVEL_INFO);

            if (intval($backupFiles->getFileParts()) > 0) {
                $this->addBackupProcessMessage($this->_helper()->__('Deleting "%s" file from the local server', $fileInfo['filename']), self::LOG_LEVEL_INFO);
                if (!@unlink($fileInfo['filelocation'])) {
                    $this->addBackupProcessMessage($this->_helper()->__('File "%s" is not deleted', $fileInfo['filename']), self::LOG_LEVEL_WARNING);
                }
            }
        }

        if ($this->isMultiStep()) {
            if (!empty($filesArr)) {
                $this->setStepObjectFiles($filesArr);
                $this->setStepObjectIsNext(0);
            }

            $this->setStepObjectData($backupFiles->getData());

            return $this;
        }

        if (!empty($filesArr)) {
            $backupFiles->setFilesArr($filesArr);

            return $this->stepFilesCloud($backupFiles);
        }

        return null;
    }

    public function checkFilesFinish()
    {
        return $this->checkDatabaseBackup();
    }

    public function stepFilesFinish()
    {
        $this->addBackupProcessMessage($this->_helper()->__('Finish directories and files backup'), self::LOG_LEVEL_INFO);

        return $this;
    }

    /**
     * @param Mageplace_Backup_Model_Profile|null $profile
     *
     * @return Mageplace_Backup_Model_Backup_Item
     */
    public function initialize($profile = null)
    {
        try {
            if (!is_null($profile)) {
                $this->setProfile($profile);
            }

            if (!$this->_profile || !$this->_profileId) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Select profile first'));
            }

            $backupSecret = $this->getBackupSecretString();
            $this->setBackupSecret($backupSecret)
                ->setBackupKey($this->getBackupFilenameKey())
                ->setStatusStarted()
                ->save();

            $this->initBackupData();

            $this->setBackupLogFileTemplate($this->_profileId, $this->_backupId);

            $stepNumber  = $this->getStepNumber();
            $pointNumber = $this->getPointNumber();

        } catch (Exception $e) {
            Mage::logException($e);
            $error = $e->getMessage();
        }

        $backupItem = Mage::getModel('mpbackup/backup_item')->setBackup($this);

        if (isset($backupSecret)) {
            $backupItem->setSecret($backupSecret);
        }

        if (isset($stepNumber)) {
            $backupItem->setStepNumber($stepNumber);
        }

        if (isset($pointNumber)) {
            $backupItem->setPointNumber($pointNumber);
        }

        if (isset($error)) {
            $backupItem->setError($error);
        }


        return $backupItem;
    }

    /**
     * @param Mage_Core_Controller_Request_Http|null $request
     *
     * @return Mageplace_Backup_Model_Backup|Mageplace_Backup_Model_Backup_Step
     * @throws
     */
    public function create($request = null)
    {
        try {
            $this->initBackupData();

            if (!$this->_profile || !$this->_profileId) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Select profile first'));
            }

            if (!is_null($request)) {
                $this->setRequest($request);
            }

            if ($this->isFirstStep()) {
                $this->_startBackupProcess();
                $this->_clearSession();
            }

            $error = false;

            $stepMethodNames = $this->_stepObject->getStepMethodNames();
            if ($this->isMultiStep()) {
                if ($this->isFirstStep()) {
                    $currStep = Mageplace_Backup_Model_Backup_Step::STEP_FIRST;
                } else {
                    $currStep = $this->getRequestBackupStep();
                }

                $this->setCurrentStep($currStep);

                if ($currStep != Mageplace_Backup_Model_Backup_Step::STEP_FINISH) {
                    if (array_key_exists($currStep, $stepMethodNames) && method_exists($this, 'step' . $stepMethodNames[$currStep])) {
                        if (in_array($currStep, $this->getSkippedSteps())) {
                            return $this;
                        } else {
                            return $this->{'step' . $stepMethodNames[$currStep]}();
                        }
                    } else {
                        throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Error step method: %s', 'step' . $stepMethodNames[$currStep]));
                    }
                }
            } else {
                $nextStep = Mageplace_Backup_Model_Backup_Step::STEP_FIRST;
                $transfer = null;
                do {
                    $currStep = $nextStep;

                    $this->setCurrentStep($currStep);

                    if (array_key_exists($currStep, $stepMethodNames) && method_exists($this, 'step' . $stepMethodNames[$currStep])) {
                        if (in_array($currStep, $this->getSkippedSteps())) {
                            continue;
                        } else {
                            if (!is_null($transfer)) {
                                $transfer = $this->{'step' . $stepMethodNames[$currStep]}($transfer);
                            } else {
                                $transfer = $this->{'step' . $stepMethodNames[$currStep]}();
                            }
                        }
                    } else {
                        throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Error step method: %s', 'step' . $stepMethodNames[$currStep]));
                    }

                    $nextStep = $this->getNextStep();

                } while ($nextStep != Mageplace_Backup_Model_Backup_Step::STEP_FINISH && !$this->checkCancelBackup());

                $this->setCurrentStep($nextStep);
            }

        } catch (Exception $e) {
            $error = true;
            Mage::logException($e);
            $this->addBackupProcessMessage($e->getMessage(), true);
            $this->setBackupErrors($e->getMessage());
        }

        $this->finishBackupProcess($error, $this->checkCancelBackup());

        $this->setStepObjectFinished();
        if (isset($e)) {
            $this->setStepObjectError($e->getMessage());

            return $this;
        } else {
            $this->rotationDelete();

            return $this;
        }
    }

    public function finishBackupProcess($errorCheck = false, $cancel = false)
    {
        $this->initBackupData();

        if (is_string($errorCheck)) {
            $errorCheck = strip_tags(nl2br(trim($errorCheck)));

            $this->addBackupProcessMessage($errorCheck, true);
            $this->setBackupErrors($errorCheck);
        }

        $stayLocalFilesWhenError = $errorCheck && !$this->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_BACKUP_ERROR_DELETE_LOCAL);
        if (!$stayLocalFilesWhenError) {
            $bu_files_loc = $this->getMainBackupFiles(true);
            if (!$this->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_LOCAL_COPY) && !empty($bu_files_loc)) {
                foreach ($bu_files_loc as $delfile) {
                    if (file_exists($delfile)) {
                        $this->addBackupProcessMessage($this->_helper()->__('Deleting "%s" file from the server', basename($delfile)), self::LOG_LEVEL_INFO);
                        if (!@unlink($delfile)) {
                            $this->addBackupProcessMessage($this->_helper()->__('File "%s" is not deleted', basename($delfile)), self::LOG_LEVEL_WARNING);
                        }
                    }
                }
            }
        }

        if ($errorCheck || $cancel) {
            $this->deleteUnnecessaryFiles();
        }

        if ($this->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_LOCAL_COPY) || $stayLocalFilesWhenError) {
            if ($backupFiles = $this->getBackupFiles()) {
                if (is_string($backupFiles)) {
                    $backupFiles = explode(';', $backupFiles);
                } else {
                    if (!is_array($backupFiles)) {
                        $backupFiles = array();
                    }
                }
            } else {
                $backupFiles = array();
            }

            $bu_files = $this->getBackupFileNames();
            if (!empty($bu_files) && is_array($bu_files)) {
                $backupFiles = array_merge($backupFiles, $bu_files);
            }

            if ($stayLocalFilesWhenError) {
                $mergeFiles     = array();
                $filesForDelete = $this->getFilesForDelete();
                if (!empty($filesForDelete) && is_array($filesForDelete)) {
                    $filesForDelete = array_unique($filesForDelete);
                    foreach ($filesForDelete as $fileForDelete) {
                        if (file_exists($fileForDelete)) {
                            $mergeFiles[] = basename($fileForDelete);
                        }
                    }
                }
                $backupFiles = array_merge($backupFiles, $mergeFiles);
            }

            $backupFiles = array_unique($backupFiles);
            $this->setBackupFiles(implode(';', $backupFiles));
        }


        $cloud = $this->getCloudFiles();
        if (is_array($cloud)) {
            $cloudFiles = !empty($cloud['files']) && is_array($cloud['files']) ? $cloud['files'] : array();
            $cloudInfo  = !empty($cloud['infos']) && is_array($cloud['infos']) ? $cloud['infos'] : array();

            $this->setBackupCloudFiles(implode(';', $cloudFiles));
            $this->setBackupAdditional(serialize($cloudInfo));
        }

        if ($cancel) {
            $this->setBackupCloudFiles('');
            $this->setBackupFiles('');
        }

        $logLevel = $this->getLogLevel();
        if ($logLevel != self::LOG_LEVEL_OFF) {
            $logFile = $this->getLogMessageFileName();
            $this->setBackupLogFile($logFile ? basename($logFile) : '');
        }

        if ($errorCheck) {
            if ($this->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_BACKUP_ERROR_DELETE_LOCAL)) {
                $this->setBackupFiles('');
            }
            if ($this->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_BACKUP_ERROR_DELETE_CLOUD)) {
                $this->setBackupCloudFiles('');
            }
            $this->addBackupProcessMessage($this->_helper()->__('Backup was finished with errors'), self::LOG_LEVEL_ERROR);
            $this->setStatusErrors();
        } elseif ($cancel) {
            $this->addBackupProcessMessage($this->_helper()->__('Backup was canceled'), self::LOG_LEVEL_INFO);
            $this->setStatusCancelled();
        } else {
            $warnings = false;
            if ($this->getSession()->getHasBackupWarnings()) {
                $warnings = true;
            } else {
                $file = new SplFileObject($this->getLogMessageFileName());
                while ($file->valid()) {
                    $row = trim($file->current());
                    $file->next();

                    if (!$row) {
                        continue;
                    }

                    if (stripos($row, sprintf(self::MESSAGE_TYPE_TEMPLATE, self::LOG_LEVEL_WARNING))) {
                        $warnings = true;
                        break;
                    }
                }
            }
            if ($warnings) {
                $this->addBackupProcessMessage($this->_helper()->__('Backup was saved with warnings'), self::LOG_LEVEL_WARNING);
                $this->setStatusWarnings();
            } else {
                $this->addBackupProcessMessage($this->_helper()->__('Backup was successfully saved'), self::LOG_LEVEL_INFO);
                $this->setStatusFinished();
            }
        }

        $this->setBackupFinishDate(Mage::getSingleton('core/date')->gmtDate());

        $this->deleteTempFiles();

        Mage::getModel('mpbackup/interceptor')->checkMethodCall($this, 'save');

        $this->addBackupProcessMessage($this->_helper()->__('Backup process was finished'), self::LOG_LEVEL_INFO);

        if ($this->isCron()) {
            Mage::getModel('mpbackup/cron')->finishSchedule($this, $errorCheck);
        }

        $this->getSession()->unsLogMessageFileName();

        $this->_temp->clearMessages();

        $this->_helper()->resetBackupProcessMessage();

        $this->_clearSession();

        return $this;
    }

    public function criticalSave($error, $backup_id = null)
    {
        if (is_null($backup_id)) {
            $backup_id = $this->_backupId ? $this->_backupId : $this->getId();
        }

        $data = array(
            self::COLUMN_ERRORS      => strip_tags(nl2br(trim($error))),
            self::COLUMN_STATUS      => self::STATUS_CRITICAL_ERRORS,
            self::COLUMN_FINISH_DATE => Mage::getSingleton('core/date')->gmtDate()
        );

        return $this->_getResource()->criticalSave($backup_id, $data);
    }

    /**
     * Add backup process message
     *
     * @param string $message
     * @param bool|string $error Boolean or ERROR|WARNING|INFO|DEBUG
     *
     * @return Varien_Data_Form_Element_Abstract
     */
    public function addBackupProcessMessage($message, $error = false)
    {
        $message_type = $this->getMessageType($error);
        if ($message_type === self::LOG_LEVEL_WARNING) {
            $this->getSession()->setHasBackupWarnings(true);
        }

        $logLevel = $this->getLogLevel();
        if ($logLevel === self::LOG_LEVEL_OFF) {
            return $this;
        }

        if ($logLevel !== self::LOG_LEVEL_ALL) {
            if (($logLevel === self::LOG_LEVEL_WARNING) && ($message_type !== self::LOG_LEVEL_ERROR) && ($message_type !== self::LOG_LEVEL_WARNING)) {
                return $this;
            } else {
                if (($logLevel === self::LOG_LEVEL_INFO) && ($message_type === self::LOG_LEVEL_DEBUG)) {
                    return $this;
                }
            }
        }

        $content = $this->getBackupProcessMessage($message, $message_type);
        if (!$logFile = $this->getLogMessageFileName()) {
            Mage::logException(new Exception('Message "' . $content . '" is not written to log file'));
        } else {
            $file = new SplFileObject($logFile, 'a');
            $file->fwrite($content . PHP_EOL);
            $file->fflush();
            $file = null;
        }

        return $this;
    }

    public function getLogLevel()
    {
        static $logLevel = null;

        if (is_null($logLevel)) {
            if ($this->_profile && $this->_profile->getProfileLogLevel()) {
                $logLevel = $this->_profile->getProfileLogLevel();
            } else {
                $logLevel = self::LOG_LEVEL_ALL;
            }
        }

        return $logLevel;
    }

    /**
     * @param bool|string $error
     *
     * @return string ERROR|WARNING|INFO|DEBUG
     */
    public function getMessageType($error = false)
    {
        if ($error === false) {
            return self::LOG_LEVEL_DEBUG;
        } elseif ($error === true) {
            return self::LOG_LEVEL_ERROR;
        } else {
            return strtoupper($error);
        }
    }

    public function getBackupProcessMessage($message, $type)
    {
        return sprintf(self::MESSAGE_TEMPLATE,
            Mage::app()->getLocale()->storeDate(null, null, true),
            sprintf(self::MESSAGE_TYPE_TEMPLATE, $type),
            @memory_get_peak_usage(1) / (1024 * 1024) . 'Mb',
            strip_tags($message)
        );
    }

    public function getLogMessageFileName($session = true)
    {
        if ($session && !$this->_logMessageFileName) {
            try {
                $this->_logMessageFileName = $this->getSession()->getLogMessageFileName();
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_logMessageFileName = null;
            }
        }

        if (!$this->_logMessageFileName) {
            $this->initBackupData();

            if ($this->_profile && $this->_profileId) {
                $logDir = $this->_profile->getData('profile_log_path');

                $this->_logMessageFileName = $logDir . DS . $this->getBackupLogFile();
                try {
                    $splFileInfo = new SplFileInfo($this->_logMessageFileName);
                    if (!$splFileInfo->isFile() && !$splFileInfo->isLink()) {
                        $splFileInfo->openFile('a');
                        if (!$splFileInfo->isReadable()) {
                            Mage::logException(new Exception('Log file is not readable'));
                        }
                        if (!$splFileInfo->isWritable()) {
                            Mage::logException(new Exception('Log file is not writable'));
                        }
                    }
                } catch (Exception $e) {
                    Mage::logException($e);
                }

                $this->getSession()->setLogMessageFileName($this->_logMessageFileName);
            }
        }

        return $this->_logMessageFileName;
    }

    public function isCron()
    {
        return (bool)$this->getBackupCron();
    }

    public function getBackupLogFile()
    {
        $backup_log_file = $this->_getData('backup_log_file');
        if (!is_null($backup_log_file)) {
            return $backup_log_file;
        }

        $this->initBackupData();
        $backup_log_file = $this->getBackupLogFileTemplate($this->_profileId, $this->_backupId);
        $this->setData('backup_log_file', $backup_log_file);

        return $backup_log_file;
    }

    public function getBackupLogFileTemplate($profileId, $backupId)
    {
        return self::LOG_FILENAME . '-' . $profileId . '-' . $backupId . ' [' . date('Y-m-d H-i-s') . '].' . self::FILE_LOG_EXT_NAME;
    }

    public function setBackupLogFileTemplate($profileId, $backupId)
    {
        $logFileName = $this->getBackupLogFileTemplate($profileId, $backupId);
        $this->setData('backup_log_file', $logFileName);
        $this->save();

        return $this;
    }

    public function getLogFilePath()
    {
        if (!$this->isFinished()) {
            return $this->getLogMessageFileName();
        }

        if (!$logFile = $this->_getData('backup_log_file')) {
            return null;
        }

        if (!is_object($this->_profile)) {
            return null;
        }

        $logDir = $this->_profile->getData('profile_log_path');

        return $logDir . DS . $logFile;
    }

    public function getBackupSecretString()
    {
        return Mage::helper('core')->getRandomString(Mageplace_Backup_Model_Backup::BACKUP_SECRET_STRING_LENGTH);
    }

    public function getBackupStatus()
    {
        return (int)$this->_getData(self::COLUMN_STATUS);
    }

    public function setStatusStarted()
    {
        return $this->setBackupStatus(self::STATUS_STARTED);
    }

    public function setStatusFinished()
    {
        return $this->setBackupStatus(self::STATUS_FINISHED);
    }

    public function setStatusCancelled()
    {
        return $this->setBackupStatus(self::STATUS_CANCELLED);
    }

    public function setStatusWarnings()
    {
        return $this->setBackupStatus(self::STATUS_WARNINGS);
    }

    public function setStatusErrors()
    {
        return $this->setBackupStatus(self::STATUS_ERRORS);
    }

    public function setStatusCriticalErrors()
    {
        return $this->setBackupStatus(self::STATUS_CRITICAL_ERRORS);
    }

    public function isStatusStarted($status = null)
    {
        if ($status === null) {
            return $this->getBackupStatus() === self::STATUS_STARTED;
        } else {
            return $status === self::STATUS_STARTED;
        }
    }

    public function isStatusFinished($status = null)
    {
        if ($status === null) {
            return $this->getBackupStatus() === self::STATUS_FINISHED;
        } else {
            return $status === self::STATUS_FINISHED;
        }
    }

    public function isStatusCancelled($status = null)
    {
        if ($status === null) {
            return $this->getBackupStatus() === self::STATUS_CANCELLED;
        } else {
            return $status === self::STATUS_CANCELLED;
        }
    }

    public function isStatusErrors($status = null)
    {
        if ($status === null) {
            return $this->getBackupStatus() === self::STATUS_ERRORS;
        } else {
            return $status === self::STATUS_ERRORS;
        }
    }

    public function isStatusCriticalErrors($status = null)
    {
        if ($status === null) {
            return $this->getBackupStatus() === self::STATUS_CRITICAL_ERRORS;
        } else {
            return $status === self::STATUS_CRITICAL_ERRORS;
        }
    }

    public function isStatusWarnings($status = null)
    {
        if ($status === null) {
            return $this->getBackupStatus() === self::STATUS_WARNINGS;
        } else {
            return $status === self::STATUS_WARNINGS;
        }
    }

    public function isFinished()
    {
        return $this->isStatusStarted() === false;
    }

    public function isSuccessFinished()
    {
        return $this->isStatusFinished();
    }

    public function deleteRecordAndFiles($session = true)
    {
        $this->initBackupData();

        $errors = false;

        if (!$this->_profile) {
            $this->addDeleteError($this->_helper()->__('Wrong backup profile'));

            if ($cloudFiles = $this->getBackupCloudFiles()) {
                $this->addDeleteError($this->_helper()->__('Files "%s" does not deleted from the cloud server', implode('","', explode(';', $cloudFiles))));
            }

            $errors = true;
        } else {
            /** @var Mageplace_Backup_Model_Cloud|mixed $cloudStorage */
            $cloudStorage = $this->_helper()->getCloudApplication($this->_profile);
            if (is_object($cloudStorage) && !$cloudStorage->isLocalStorage()
                && ($strCloudFiles = $this->getBackupCloudFiles())
                && ($cloudFiles = explode(';', $strCloudFiles))
            ) {
                $cloudStorage->setBackup($this);

                foreach ($cloudFiles as $file) {
                    if (!$file) {
                        continue;
                    }
                    try {
                        $delete = $cloudStorage->deleteFile($file);
                    } catch (Exception $e) {
                        if ($session) {
                            $this->_getAdminSession()->addError($e->getMessage());
                        }
                        $this->addDeleteError($e->getMessage());
                        $delete = false;
                    }

                    if (!$delete) {
                        $message = $this->_helper()->__('File "%s" does not deleted from the cloud server', $file);
                        if ($session) {
                            $this->_getAdminSession()->addWarning($message);
                        }
                        $this->addDeleteError($message);
                        $errors = true;
                    }
                }
            }
        }

        if (($strBackupFiles = $this->getBackupFiles())
            && ($backupFiles = explode(';', $strBackupFiles))
        ) {
            $backupDir = $this->_profile->getData('profile_backup_path');

            foreach ($backupFiles as $file) {
                if (!$file) {
                    continue;
                }

                $deleted    = false;
                $backupPath = $backupDir . DS . $file;
                if (file_exists($backupPath)) {
                    $deleted = @unlink($backupPath);
                } else {
                    $message = $this->_helper()->__('File "%s" does not exist on the local server', $backupPath);
                    if ($session) {
                        $this->_getAdminSession()->addWarning($message);
                    }
                    $this->addDeleteError($message);
                    $errors = true;
                    continue;
                }

                if (!$deleted) {
                    $message = $this->_helper()->__('File "%s" does not deleted from the local server', $backupPath);
                    if ($session) {
                        $this->_getAdminSession()->addWarning($message);
                    }
                    $this->addDeleteError($message);
                    $errors = true;
                }
            }
        }

        if ($logFile = $this->getBackupLogFile()) {
            $logPath = $this->_profile->getData('profile_log_path') . DS . $logFile;
            if (file_exists($logPath)) {
                if (!@unlink($logPath)) {
                    $message = $this->_helper()->__('File "%s" does not deleted from the local server', $logPath);
                    if ($session) {
                        $this->_getAdminSession()->addWarning($message);
                    }
                    $this->addDeleteError($message);
                    $errors = true;
                }
            } else {
                $message = $this->_helper()->__('File "%s" does not exist on the local server', $logPath);
                if ($session) {
                    $this->_getAdminSession()->addWarning($message);
                }
                $this->addDeleteError($message);
                $errors = true;
            }
        }

        $this->delete();

        return !$errors;
    }

    public function rotationDelete()
    {
        $this->initBackupData();

        try {
            if (!$this->_profile) {
                throw Mage::exception('Mageplace_Backup', $this->_helper()->__('Select profile first'));
            }

            if ($this->_profile->getData(Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE) != Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_ROTATION) {
                return;
            }

            $number = (int)$this->_profile->getData(Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_ROTATION_NUMBER);
            if ($number < 1) {
                return;
            }

            /** @var Mageplace_Backup_Model_Mysql4_Backup_Collection $collection */
            $collection = $this->getCollection()
                ->addFilter('profile_id', $this->_profileId)
                ->addOrder('backup_creation_date', Mageplace_Backup_Model_Mysql4_Backup_Collection::SORT_ORDER_ASC);

            $total = $collection->count();
            if ($total <= $number) {
                return;
            }

            $stat = array(
                Mageplace_Backup_Helper_Email::DELETE_STAT_DELETED => 0,
                Mageplace_Backup_Helper_Email::DELETE_STAT_BACKUPS => array(),
                Mageplace_Backup_Helper_Email::DELETE_STAT_ERRORS  => array(),
            );

            /** @var Mageplace_Backup_Model_Backup $backup */
            foreach ($collection->getIterator() as $backup) {
                if ($total <= $number) {
                    break;
                }

                $id   = $backup->getId();
                $name = $backup->getBackupName();

                $backup->deleteRecordAndFiles(false);

                if (!Mage::getModel('mpbackup/backup')->load($id)->getId()) {
                    $stat[Mageplace_Backup_Helper_Email::DELETE_STAT_DELETED]++;
                    $stat[Mageplace_Backup_Helper_Email::DELETE_STAT_BACKUPS][] = $name;
                }

                $stat[Mageplace_Backup_Helper_Email::DELETE_STAT_ERRORS] = array_merge($stat[Mageplace_Backup_Helper_Email::DELETE_STAT_ERRORS], $backup->getDeleteErrors());

                $total--;
            }

            Mage::getModel('mpbackup/cron')->sendSuccessDeleteEmail($this->_profile, $stat);
        } catch (Exception $e) {
            Mage::logException($e);
            if (isset($this->_profile)) {
                Mage::getModel('mpbackup/cron')->sendErrorDeleteEmail($this->_profile, $e->getMessage());
            }
        }
    }

    public function addDeleteError($error)
    {
        $this->_deleteErrors[] = $error;
    }

    public function getDeleteErrors()
    {
        return $this->_deleteErrors;
    }

    public function  getCurrentBackup($secret)
    {
        if (!$secret) {
            return $this;
        }

        $id = $this->_getResource()->getCurrentBackupId($secret);
        if ($id) {
            $this->load($id);
        }

        return $this;
    }

    public function getLogs($logLevel = null)
    {
        $logs = array();

        if (is_null($logLevel) || $logLevel == self::LOG_LEVEL_OFF) {
            return $logs;
        }

        $file = $this->getLogFilePath();
        if (!file_exists($file)) {
            return $logs;
        }

        $logsArr = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($logLevel == self::LOG_LEVEL_ALL || $logLevel == '') {
            return $logsArr;
        }

        $logs = preg_grep('/^(.*)(' . sprintf(self::MESSAGE_TYPE_REGEXP_TEMPLATE, self::LOG_LEVEL_DEBUG) . ')(.*)$/', $logsArr, PREG_GREP_INVERT);

        return $logs;
    }

    public function addMainBackupFiles($file)
    {
        return $this->_temp->addMainBackupFile($file);
    }

    public function addBackupFiles($file)
    {
        return $this->_temp->addBackupFileNames($file);
    }

    public function addTempFile($file)
    {
        return $this->_temp->addTempBackupFile($file);
    }

    public function addCloudFiles($file, $info)
    {
        $files = $this->_temp->addCloudFiles($file, $info);
    }

    public function addFilesForDelete($file)
    {
        return $this->_temp->addFilesForDelete($file);
    }

    public function getFilesForDelete()
    {
        return $this->_temp->getFilesForDelete();
    }

    public function getTempFiles()
    {
        return $this->_temp->getTempBackupFiles();
    }

    public function getMainBackupFiles()
    {
        return $this->_temp->getMainBackupFile();
    }

    public function getBackupFileNames()
    {
        return $this->_temp->getBackupFileNames();
    }

    public function getCloudFiles()
    {
        return $this->_temp->getCloudFiles();
    }

    public function deleteUnnecessaryFiles()
    {
        if ($this->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_BACKUP_ERROR_DELETE_LOCAL)) {
            $files = $this->getFilesForDelete();
            if (!empty($files) && is_array($files)) {
                $files = array_unique($files);
                foreach ($files as $file) {
                    if (file_exists($file)) {
                        $this->addBackupProcessMessage($this->_helper()->__('Deleting unnecessary file "%s" from the server', basename($file)), self::LOG_LEVEL_INFO);
                        if (!@unlink($file)) {
                            $this->addBackupProcessMessage($this->_helper()->__('File "%s" is not deleted', basename($file)), self::LOG_LEVEL_WARNING);
                        }
                    }
                }
            }
        }

        if ($this->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_BACKUP_ERROR_DELETE_CLOUD)) {
            $cloud = $this->getCloudFiles();
            if (is_array($cloud)) {
                $cloudFiles = !empty($cloud['files']) && is_array($cloud['files']) ? $cloud['files'] : array();
                $cloudInfo  = !empty($cloud['infos']) && is_array($cloud['infos']) ? $cloud['infos'] : array();

                $cloudStorage = $this->_helper()->getCloudApplication($this->_profile);
                if (is_object($cloudStorage) && !$cloudStorage->isLocalStorage() && is_array($cloudFiles)) {
                    $backupAdditional = $this->getBackupAdditional();
                    $this->setBackupAdditional($cloudInfo);
                    $cloudStorage->setBackup($this);
                    $this->setBackupAdditional($backupAdditional);

                    foreach ($cloudFiles as $file) {
                        try {
                            $this->addBackupProcessMessage($this->_helper()->__('Deleting unnecessary cloud file "%s"', $file), self::LOG_LEVEL_INFO);
                            $delete = $cloudStorage->deleteFile($file);
                        } catch (Exception $e) {
                            $this->addBackupProcessMessage($e->getMessage(), self::LOG_LEVEL_WARNING);
                            $delete = false;
                        }

                        if (!$delete) {
                            $this->addBackupProcessMessage($this->_helper()->__('File "%s" does not deleted from the cloud server', $file), self::LOG_LEVEL_WARNING);
                        }
                    }
                }
            }
        }
    }

    public function deleteTempFiles()
    {
        $tempFiles = $this->getTempFiles();
        if (empty($tempFiles) || !is_array($tempFiles)) {
            return false;
        }

        foreach ($tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        return true;
    }

    public function checkCancelBackup()
    {
        return file_exists($this->getCancelFileLocation());
    }

    public function cancelBackup()
    {
        file_put_contents($this->getCancelFileLocation(), '');

        $this->addTempFile($this->getCancelFileLocation());

        return $this;
    }

    public function getCancelFileLocation()
    {
        if ($this->_getData('cancel_file_location') === null) {
            $this->setData('cancel_file_location', $this->getProfileData('profile_backup_path') . DS . $this->getBackupKey() . '.' . self::FILE_CANCEL_EXT_NAME);
        }

        return $this->_getData('cancel_file_location');
    }

    /**
     * @param boolean $clear
     *
     * @return Mage_Adminhtml_Model_Session
     */
    public function getSession($clear = false)
    {
        if ($this->_profileId && $this->_backupId) {
            $session = Mage::registry('mpbackup_' . $this->_profileId . '_' . $this->_backupId);
            if (is_null($session)) {
                $session = $this->_helper()->getSession(array($this->_profileId, $this->_backupId));

                Mage::register('mpbackup_' . $this->_profileId . '_' . $this->_backupId, $session);
            }

        } else {
            $session = $this->_helper()->getSession();
        }

        if ($clear) {
            $session->clear();
        }

        return $session;
    }

    protected function _startBackupProcess()
    {
        $this->addBackupProcessMessage($this->_helper()->__('Backup process was started'), self::LOG_LEVEL_INFO);
    }

    /**
     * @return Mageplace_Backup_Helper_Data
     */
    protected function _helper()
    {
        static $helper = null;

        if (is_null($helper)) {
            $helper = Mage::helper('mpbackup');
        }

        return $helper;
    }

    protected function _clearSession()
    {
        $this->getSession(true);
    }

    /**
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }
}