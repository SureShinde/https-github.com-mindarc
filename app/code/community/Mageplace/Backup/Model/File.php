<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Model_File
 *
 * @method Mageplace_Backup_Model_File setProfile
 * @method Mageplace_Backup_Model_File setExtension
 * @method Mageplace_Backup_Model_File setFileParts
 * @method Mageplace_Backup_Model_File setPath
 * @method Mageplace_Backup_Model_File setIsContinuePack
 * @method Mageplace_Backup_Model_File setIsContinueCompress
 * @method Mageplace_Backup_Model_File setSkipCompress
 * @method Mageplace_Backup_Model_File setIsContinueFileChunk
 * @method int|null getSkipCompress
 * @method int|null getIsContinuePack
 * @method int|null getIsContinueCompress
 * @method int|null getIsContinueFileChunk
 * @method string getPath
 */
class Mageplace_Backup_Model_File extends Mage_Backup_Model_Backup
{
    const DEFAULT_FILE_PART_SIZE = 100; /* Mb */
    const READ_FILE_PART_SIZE = 1048576; /* = 1 Mb */
    const BACKUP_EXTENSION = 'gz';
    const DEFAULT_ARCHIVER = 'gz';
    const TAPE_ARCHIVER    = 'tar';

    const TYPE_FILES = 'files';
    const TYPE_DB    = 'db';

    const OPEN_MODE_WRITE  = 'wb';
    const OPEN_MODE_APPEND = 'ab';
    const OPEN_MODE_READ   = 'rb';

    const EXTENSION_FILELIST = 'mpfl';

    const STEP_PARAM_BYTE = 'step_gz_file_byte';

    protected $_archiver = null;
    protected $_excluded = array();
    protected $_filesForCompress = array();
    protected $_dirsForCompress = array();
    protected $_baseDir = '';

    /**
     * @var Mageplace_Backup_Helper_Data
     */
    protected $_helper = null;

    /**
     * @var Mageplace_Backup_Model_Backup
     */
    protected $_backup = null;

    protected function _construct()
    {
        $this->_baseDir = Mage::getBaseDir();
        $this->_helper  = Mage::helper('mpbackup');
    }

    static public function filesize($fp)
    {
        if (!self::is32bit()) {
            if (is_string($fp)) {
                return filesize($fp);
            } else {
                $fp = stream_get_meta_data($fp);
                if (!empty($fp['uri'])) {
                    return filesize($fp['uri']);
                }
            }
        }

        if (is_string($fp)) {
            $fp = fopen($fp, 'r');

            $fpclose = true;
        }

        $pos  = 0;
        $size = 1073741824; /* == 1024 * 1024 *1024 */
        fseek($fp, 0, SEEK_SET);
        while ($size > 1) {
            fseek($fp, $size, SEEK_CUR);

            if (fgetc($fp) === false) {
                fseek($fp, -$size, SEEK_CUR);
                $size = (int)($size / 2);
            } else {
                fseek($fp, -1, SEEK_CUR);
                $pos += $size;
            }
        }

        while (fgetc($fp) !== false) {
            ++$pos;
        }

        if (isset($fpclose)) {
            fclose($fp);
        }

        return $pos;
    }

    static public function fseek($fp, $pos, $first = true)
    {
        if (!self::is32bit()) {
            fseek($fp, $pos);

            return;
        }

        if ($first) {
            fseek($fp, 0, SEEK_SET);
        }

        $pos = floatval($pos);

        if ($pos <= PHP_INT_MAX) {
            fseek($fp, $pos, SEEK_CUR);
        } else {
            fseek($fp, PHP_INT_MAX, SEEK_CUR);
            $pos -= PHP_INT_MAX;
            self::fseek($fp, $pos, false);
        }
    }

    static public function is32bit()
    {
        return PHP_INT_SIZE === 4;
    }

    protected function _excludedDirs()
    {
        return array(
            Mage::getBaseDir('cache'),
            Mage::getBaseDir('session'),
            $this->getProfile()->getData('profile_backup_path'),
        );
    }

    public function getProfile()
    {
        if (is_null($this->_getData('profile')) && is_object($this->getBackup())) {
            $this->setData('profile', $this->getBackup()->getProfile());
        }

        if (!is_object($this->_getData('profile'))) {
            Mage::throwException($this->_helper->__('Backup profile is not specified.'));
        }

        return $this->_getData('profile');
    }

    public function setExcluded($path)
    {
        $excluded = $this->_getData('excluded');
        if (!is_array($excluded)) {
            $excluded = array();
        }

        if (is_array($path)) {
            $excluded = array_merge($excluded, $path);
        } else {
            $excluded[] = strval($path);
        }

        $this->setData('excluded', $excluded);

        return $this;
    }

    public function getExcluded()
    {
        if (is_array($this->_getData('excluded'))) {
            return $this->_getData('excluded');
        } else {
            return array();
        }
    }

    /**
     * @deprecated after version 2.0.0
     */
    public function addExcludedPath($path)
    {
        if (is_array($path)) {
            $this->_excluded = array_merge($this->_excluded, $path);
        } else {
            $this->_excluded[] = strval($path);
        }

        return $this;
    }

    public function getTime()
    {
        return Mage::app()->getLocale()->storeTimeStamp();
    }

    /**
     * @param null $ext
     * @param null $suffix
     *
     * @return string
     */
    public function getFileName($ext = null, $suffix = null)
    {
        $filename = $this->_getData('filename');
        if (!$filename) {
            $filename = $this->getBackup()->getBackupKey();
            $this->setData('filename', $filename);
        }

        if (!$ext) {
            $ext = $this->_getData('extension');
            if (!$ext) {
                $ext = self::BACKUP_EXTENSION;
                $this->setExtension($ext);
            }
        }

        return $filename . "_" . $this->getType() . "." . $ext . ($suffix ? sprintf("_part%s", $suffix) : '');
    }

    public function setFilename($filename)
    {
        $this->setData('filename', preg_replace('/[^0-9a-z\-\_]/i', '_', $filename));

        return $this;
    }


    public function getMainFileName()
    {
        return $this->_getData('filename');
    }

    /**
     * Return file location of backup file
     *
     * @param string|null $ext
     * @param string|null $suffix
     *
     * @return string
     */
    public function getFileLocation($ext = null, $suffix = null)
    {
        return $this->getPath() . DS . $this->getFileName($ext, $suffix);
    }

    public function getTarFileLocation()
    {
        if ($this->_getData('tar_file_location') === null) {
            $this->setData('tar_file_location', $this->getFileLocation(self::TAPE_ARCHIVER));
        }

        return $this->_getData('tar_file_location');
    }

    public function getFilesListFileLocation()
    {
        if ($this->_getData('files_list_file_location') === null) {
            $this->setData('files_list_file_location', $this->getFileLocation(self::EXTENSION_FILELIST));
        }

        return $this->_getData('files_list_file_location');
    }

    /**
     * Sets type of file
     *
     * @param string $value db|files
     *
     * @return Mageplace_Backup_Model_File
     */
    public function setType($value = self::TYPE_DB)
    {
        if (!in_array($value, array(self::TYPE_DB, self::TYPE_FILES))) {
            $value = self::TYPE_FILES;
        }

        $this->setData('type', $value);

        return $this;
    }

    public function getType()
    {
        return $this->_getData('type');
    }

    public function start()
    {
        if (!($profile = $this->getProfile()) || !($profile instanceof Mageplace_Backup_Model_Profile)) {
            Mage::throwException($this->_helper->__('Backup profile is not specified.'));
        }

        $this->setExtension(self::TAPE_ARCHIVER . '.' . self::DEFAULT_ARCHIVER);

        $this->_excluded = array_merge(
            $this->getExcluded(),
            $this->_excludedDirs(),
            $profile->getExcludedPath(),
            array(strtr($this->getFileLocation(), array($this->_baseDir => '')))
        );

        $this->_addBackupProcessMessage($this->_helper->__('Excluded directories and files: ') . strtr(implode(';  ', $this->_excluded), array($this->_baseDir => '')));

        foreach ($this->_excluded as &$value) {
            if (strpos($value, $this->_baseDir) !== 0) {
                $value = $this->_baseDir . (strpos($value, DS) !== 0 ? DS : '') . $value;
            }
        }

        unset($value);

        $this->_addBackupProcessMessage($this->_helper->__('Start getting files for archive'), Mageplace_Backup_Model_Backup::LOG_LEVEL_INFO);

        $fileListFileLocation = $this->getFilesListFileLocation();
        $this->getBackup()->addTempFile($fileListFileLocation);
        $this->_excluded[] = $fileListFileLocation;

        $handler = fopen($fileListFileLocation, 'w+b');
        $this->_scanDir($this->_baseDir, $handler);
        fseek($handler, 0);
        if (!fgets($handler)) {
            $this->setSkipCompress(1);
            $this->_addBackupProcessMessage($this->_helper->__('Backup archive not created (Empty file list).'), Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING);
        }
        fclose($handler);

        $this->_addBackupProcessMessage($this->_helper->__('Finish getting files for archive'), Mageplace_Backup_Model_Backup::LOG_LEVEL_INFO);

        return $this;
    }

    public function compress()
    {
        $isContinuePack     = $this->getIsContinuePack();
        $isContinueCompress = $this->getIsContinueCompress();
        if (!$isContinuePack && !$isContinueCompress) {
            $this->_addBackupProcessMessage($this->_helper->__('Start files compressing'), Mageplace_Backup_Model_Backup::LOG_LEVEL_INFO);
        }

        try {
            if (!$isContinueCompress) {
                if ($this->getBackup()->isTimeLimitMultiStep()) {
                    $archiver     = null;
                    $fileLocation = $this->getTarFileLocation();
                    $this->getBackup()->addTempFile($fileLocation);
                } else {
                    $archiver     = self::DEFAULT_ARCHIVER;
                    $fileLocation = $this->getFileLocation();
                    $this->getBackup()->addMainBackupFiles($fileLocation);
                }

                /** @var Mageplace_Backup_Model_Archive_Tar $archiveTar */
                $archiveTar = Mage::getModel('mpbackup/archive_tar', array($fileLocation, $archiver));
                $archiveTar->setBackup($this->getBackup());
                $archiveTar->setSeparator('|');
                $archiveTar->setErrorHandling(PEAR_ERROR_TRIGGER);

                $file = new SplFileObject($this->getFilesListFileLocation());
                $file->setFlags(SplFileObject::DROP_NEW_LINE | SplFileObject::SKIP_EMPTY);

                if (!$archiveTar->addModify($file, '', $this->_baseDir)) {
                    Mage::throwException($this->_helper->__('Backup archive not created.'));
                }

                if ($this->getBackup()->isTimeLimitMultiStep()) {
                    $archiverFileLocation = $this->getFileLocation(self::TAPE_ARCHIVER);
                    $this->getBackup()->addTempFile($archiverFileLocation);
                    $isContinuePack = !$archiveTar->isFinished();
                    if (!$isContinuePack) {
                        @unlink($this->getFilesListFileLocation());
                        $isContinueCompress = true;
                    }
                }
            } else {
                $isContinueCompress = false;

                $bytes = (float)$this->getRequestStepByte();
                if ($bytes > 0) {
                    $this->openGz(self::OPEN_MODE_APPEND);
                } else {
                    $this->_addBackupProcessMessage($this->_helper->__('Compressing tar file'), Mageplace_Backup_Model_Backup::LOG_LEVEL_INFO);
                    $this->getBackup()->addMainBackupFiles($this->getFileLocation());
                    $this->openGz(self::OPEN_MODE_WRITE);
                }

                $fileLocation = $this->getTarFileLocation();
                if (($tarFp = @fopen($fileLocation, "rb")) == 0) {
                    throw Mage::exception('Mageplace_Backup', $this->_helper->__('Unable to open file "%s" in binary read mode', $fileLocation));
                }

                if ($bytes > 0) {
                    self::fseek($tarFp, $bytes);
                }

                while (!feof($tarFp)) {
                    if ($this->timeIsUp()) {
                        $this->setRequestStepBytes($bytes);
                        $isContinueCompress = true;
                        break;
                    }

                    $this->write(fread($tarFp, 8192));
                    $bytes += 8192;
                }

                fclose($tarFp);
                $this->close();

                if (!$isContinueCompress) {
                    @unlink($this->getTarFileLocation());
                    $this->setRequestStepBytes(null);
                }
            }

        } catch (Exception $e) {
            Mage::logException($e);
            $this->_addBackupProcessMessage($e->getMessage(), Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING);
            $isContinuePack     = false;
            $isContinueCompress = false;
        }

        if (isset($file)) {
            $file = null;
        }

        if ($this->getBackup()->isTimeLimitMultiStep()) {
            $this->setIsContinuePack($isContinuePack);
            $this->setIsContinueCompress($isContinueCompress);
        }


        if (!$isContinuePack && !$isContinueCompress) {
            $this->_addBackupProcessMessage($this->_helper->__('Finish files compressing'), Mageplace_Backup_Model_Backup::LOG_LEVEL_INFO);
        }

        return $isContinuePack || $isContinueCompress;
    }

    protected function _scanDir($path, $res)
    {
        if (in_array($path, $this->_excluded)) {
            return false;
        }

        $check_add = true;

        $dir = scandir($path);
        foreach ($dir as $file) {
            if (($file == '.') || ($file == '..')) {
                continue;
            }

            if (is_link($path . DS . $file)) {
                fwrite($res, $path . DS . $file . PHP_EOL);
                $check_add = false;
            } elseif (is_dir($path . DS . $file)) {
                if (is_readable($path . DS . $file)) {
                    $this->_scanDir($path . DS . $file, $res);
                } else {
                    $relDir = strtr($path . DS . $file, array($this->_baseDir => ''));
                    $this->_addBackupProcessMessage($this->_helper->__('Directory "%s" not readable', $relDir), Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING);
                }
                $check_add = false;
            } elseif (is_file($path . DS . $file) && !in_array($path . DS . $file, $this->_excluded)) {
                if (is_readable($path . DS . $file)) {
                    fwrite($res, $path . DS . $file . PHP_EOL);
                    $check_add = false;
                } else {
                    $relDir = strtr($path . DS . $file, array($this->_baseDir => ''));
                    $this->_addBackupProcessMessage($this->_helper->__('File "%s" not readable', $relDir), Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING);
                }
            }
        }

        if ($check_add) {
            fwrite($res, $path . PHP_EOL);
        }

        return true;
    }

    /**
     * @deprecated after version 2.0.0
     *
     * @param $dir
     * @param $res
     *
     * @return bool
     */
    protected function _directoryIterator($dir, $res)
    {
        if (in_array($dir, $this->_excluded)) {
            return false;
        }

        $check_add = true;

        /* @var $dirItem DirectoryIterator */
        foreach (new DirectoryIterator($dir) as $dirItem) {
            if ($dirItem->isDot() || $dirItem->isLink()) {
                continue;
            }

            $pathName = $dirItem->getPathname();
            if ($dirItem->isDir()) {
                if ($dirItem->isReadable()) {
                    $this->_directoryIterator($pathName, $res);
                } else {
                    $relDir = strtr($dir, array($this->_baseDir => ''));
                    $this->_addBackupProcessMessage($this->_helper->__('Directory "%s" not readable', $relDir), Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING);
                }

                $check_add = false;

            } elseif ($dirItem->isFile() && !in_array($pathName, $this->_excluded)) {
                if ($dirItem->isReadable()) {
                    fwrite($res, $dirItem->getPathname() . PHP_EOL);
                    $check_add = false;
                } else {
                    $relDir = strtr($dirItem->getPathname(), array($this->_baseDir => ''));
                    $this->_addBackupProcessMessage($this->_helper->__('File "%s" not readable', $relDir), Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING);
                }
            }
        }

        if ($check_add) {
            fwrite($res, $dir . PHP_EOL);
        }

        return true;
    }

    public function prepareFileToUpload($size = self::DEFAULT_FILE_PART_SIZE)
    {
        if ($size < 0.001) {
            $size = 0;
        }

        $sizeByte = round($size * 1024 * 1024);
        if (!$sizeByte || (Mageplace_Backup_Model_File::filesize($this->getFileLocation()) <= $sizeByte)) {
            return array(array(
                'filename'     => $this->getFileName(),
                'filelocation' => $this->getFileLocation()
            ));
        }

        $this->_addBackupProcessMessage($this->_helper->__('Start splitting "%s" file into parts', $this->getFileName()), Mageplace_Backup_Model_Backup::LOG_LEVEL_INFO);

        $readFilePartSize = $size < self::READ_FILE_PART_SIZE ? self::READ_FILE_PART_SIZE / 1024 : self::READ_FILE_PART_SIZE;
        $counter          = 0;
        $return           = array();
        $handle           = fopen($this->getFileLocation(), 'r');
        while (!feof($handle)) {
            $counter++;

            $filename = $this->getFileName(null, $counter);
            $fileLoc  = $this->getPath() . DS . $this->getFileName(null, $counter);

            $sizeCounter = 0;
            $handle_w    = fopen($fileLoc, 'a');
            $this->getBackup()->addFilesForDelete($fileLoc);
            $wrote = true;
            do {
                $filePart = fread($handle, $readFilePartSize);
                if (fwrite($handle_w, $filePart) === false) {
                    $wrote = false;
                    break;
                }

                $sizeCounter += $readFilePartSize;
            } while ($sizeCounter < $sizeByte && !feof($handle));

            fclose($handle_w);

            if ($wrote == false) {
                $this->_addBackupProcessMessage($this->_helper->__("Write file \"%s\" error. Split process was stopped.", $fileLoc), Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING);
                break;
            } else {
                $return[] = array(
                    'filename'     => $filename,
                    'filelocation' => $fileLoc
                );
            }
        }

        fclose($handle);

        $this->setFileParts($counter);

        $this->_addBackupProcessMessage($this->_helper->__('Finish splitting "%s" file into parts', $this->getFileName()), Mageplace_Backup_Model_Backup::LOG_LEVEL_INFO);

        return $return;
    }

    /**
     * @param $backup Mageplace_Backup_Model_Backup
     *
     * @return $this
     */
    public function setBackup($backup)
    {
        $this->_backup = $backup;

        return $this;
    }

    /**
     * @return Mageplace_Backup_Model_Backup
     */
    public function getBackup()
    {
        return $this->_backup;
    }

    /**
     * Open backup file (write or read mode)
     *
     * @param string      $mode
     * @param string|null $filePath
     *
     * @throws Mageplace_Backup_Exception
     * @return Mage_Backup_Model_Backup
     */
    public function openGz($mode, $filePath = null)
    {
        if (is_null($this->getPath())) {
            Mage::exception('Mage_Backup', Mage::helper('backup')->__('Backup file path was not specified.'));
        }

        $ioAdapter = new Varien_Io_File();

        if ($filePath === null) {
            try {
                $path = $ioAdapter->getCleanPath($this->getPath());
                $ioAdapter->checkAndCreateFolder($path);
                $filePath = $path . $this->getFileName();
            } catch (Exception $e) {
                throw new Mageplace_Backup_Exception($e->getMessage());
            }
        }

        if ($mode != self::OPEN_MODE_APPEND && $mode != self::OPEN_MODE_WRITE && $mode != self::OPEN_MODE_READ) {
            $mode = self::OPEN_MODE_WRITE;
        }

        if ($mode == self::OPEN_MODE_WRITE && $ioAdapter->fileExists($filePath)) {
            $ioAdapter->rm($filePath);
        }

        if ($mode != self::OPEN_MODE_WRITE && !$ioAdapter->fileExists($filePath)) {
            throw new Mageplace_Backup_Exception(
                Mage::helper('backup')->__('Backup file "%s" does not exist.', $this->getFileName())
            );
        }

        $this->_handler = @gzopen($filePath, $mode);

        if (!$this->_handler) {
            throw new Mageplace_Backup_Exception(
                Mage::helper('backup')->__('Backup file "%s" cannot be read from or written to.', $this->getFileName())
            );
        }

        return $this;
    }

    public function getSession()
    {
        return $this->getBackup()->getSession();
    }

    protected function timeIsUp()
    {
        return !$this->getBackup()->canContinue();
    }

    protected function setSessionStepBytes($byte)
    {
        $this->getSession()->setData(self::STEP_PARAM_BYTE, $byte);
    }

    protected function setRequestStepBytes($byte)
    {
        $this->getBackup()->getStepObject()->setData(self::STEP_PARAM_BYTE, $byte);
    }

    protected function getSessionStepByte()
    {
        return $this->getSession()->getData(self::STEP_PARAM_BYTE);
    }

    protected function getRequestStepByte()
    {
        return $this->getBackup()->getStepObject()->getRequestStepBytes();
    }

    protected function _addBackupProcessMessage($message, $error = false)
    {
        $this->getBackup()->addBackupProcessMessage($message, $error);
    }

    /**
     * @deprecated after 2.0.0
     *
     * @param      $message
     * @param bool $error
     */
    protected function _addMessage($message, $error = false)
    {
        $this->_helper->addBackupProcessMessage($message, $error);
    }
}