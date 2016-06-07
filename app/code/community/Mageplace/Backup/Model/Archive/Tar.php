<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

$get_include_path = get_include_path();
$paths            = explode(PATH_SEPARATOR, $get_include_path);
if (is_array($paths)) {
    $include_paths = array();
    $exclude_paths = array();
    foreach ($paths as $path) {
        if (stripos($path, 'pear') === false) {
            $include_paths[] = $path;
        } else {
            $exclude_paths[] = $path;
        }
    }
    $include_path = implode(PATH_SEPARATOR, $include_paths);
    $suffix       = implode(PATH_SEPARATOR, $exclude_paths);
} else {
    $include_path = $get_include_path;
    $suffix       = '';
}

set_include_path(
    $include_path
    . PATH_SEPARATOR . Mage::getBaseDir('lib') . DS . 'PEAR'
    . PATH_SEPARATOR . Mage::getBaseDir('lib') . DS . 'PEAR' . DS . 'Archive'
    . ($suffix ? PATH_SEPARATOR . $suffix : '')
);

include_once "MPTar.php";

if (!class_exists('MP_Archive_Tar')) {
    throw new Mageplace_Backup_Exception(Mage::helper('mpbackup')->__('Class MP_Archive_Tar not exists.'));
}

/**
 * Class Mageplace_Backup_Model_Archive_Tar
 */
class Mageplace_Backup_Model_Archive_Tar extends MP_Archive_Tar
{
    const SESSION_PARAM_INDEX = 'step_tar_file_index';
    const SESSION_PARAM_FILE  = 'step_tar_file_path';
    const SESSION_PARAM_BYTE  = 'step_tar_file_byte';

    /**
     * @var Mageplace_Backup_Helper_Data
     */
    protected $_helper = null;

    /**
     * @var Mageplace_Backup_Model_Backup
     */
    protected $_backup   = null;
    protected $_timeIsUp = false;
    protected $_baseDir;

    function __construct($params = array())
    {
        if (!is_array($params) || !($p_tarname = array_shift($params))) {
            throw new Mageplace_Backup_Exception(Mage::helper('mpbackup')->__('Tar archive file name was not specified.'));
        }

        $this->_baseDir = Mage::getBaseDir();
        $this->_helper  = Mage::helper('mpbackup');

        parent::MP_Archive_Tar($p_tarname, array_shift($params));
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

    public function setSeparator($separator)
    {
        $this->_separator = $separator;

        return $this;
    }

    /**
     * @param SplFileObject $filesListObject
     * @param string $p_add_dir
     * @param string $p_remove_dir
     *
     * @return bool|true
     */
    function addModify($filesListObject, $p_add_dir, $p_remove_dir = '')
    {
        if (!$this->_isArchive()) {
            return $this->createModify($filesListObject, $p_add_dir, $p_remove_dir);
        }

        if (!$filesListObject instanceof SplFileObject) {
            $this->_addBackupProcessMessage('Invalid file list object', true);

            return false;
        }

        return $this->_append($filesListObject, $p_add_dir, $p_remove_dir);
    }

    /**
     * @param SplFileObject $filesListObject
     * @param string $p_add_dir
     * @param string $p_remove_dir
     *
     * @return bool
     */
    function createModify($filesListObject, $p_add_dir, $p_remove_dir = '')
    {
        if (!$this->_openWrite()) {
            return false;
        }

        if (!$filesListObject instanceof SplFileObject) {
            $this->_addBackupProcessMessage('Invalid file list object', true);

            return false;
        }

        if (!$this->_addList($filesListObject, $p_add_dir, $p_remove_dir)) {
            $this->_cleanFile();

            return false;
        }

        $this->_writeFooter();
        $this->_close();

        return true;
    }

    function _writeFooter()
    {
        if (!$this->_timeIsUp && is_resource($this->_file)) {
            $v_binary_data = pack('a1024', '');
            $this->_writeBlock($v_binary_data);
        }

        return true;
    }

    /**
     * @param SplFileObject $filesListObject
     * @param               $p_add_dir
     * @param               $p_remove_dir
     *
     * @return bool
     */
    function _addList($filesListObject, $p_add_dir, $p_remove_dir)
    {
        $v_header = array();

        if (!$this->_file) {
            $this->_addBackupProcessMessage('Invalid file descriptor', true);

            return false;
        }

        $filesListObject->seek((int)$this->getStepIndex());

        while ($filesListObject->valid()) {
            $this->setStepIndex($filesListObject->key());
            if ($this->timeIsUp()) {
                return true;
            }

            $filename = $filesListObject->current();

            if (trim($filename) === '') {
                $filesListObject->next();
                continue;
            }

            if ($filename == $this->_tarname) {
                $filesListObject->next();
                continue;
            }

            if (!file_exists($filename) && !is_link($filename)) {
                $this->_addBackupProcessMessage('File "' . $filename . '" does not exist', Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING);
                $filesListObject->next();
                continue;
            }

            if (!$this->_addFile($filename, $v_header, $p_add_dir, $p_remove_dir)) {
                return false;
            }

            if ($this->_timeIsUp) {
                return true;
            }

            $filesListObject->next();
        }

        $this->setStepIndex(null);

        return true;
    }

    function _addFile($p_filename, &$p_header, $p_add_dir, $p_remove_dir)
    {
        if (!$this->_file) {
            $this->_addBackupProcessMessage('Invalid file descriptor', true);

            return false;
        }

        if ($p_filename == '') {
            $this->_addBackupProcessMessage('Invalid file name', true);

            return false;
        }

        if ($this->getStepFile() == $p_filename) {
            $this->_packFile($this->getStepFile(), $this->getStepByte());

            return true;
        }

        $p_filename        = $this->_translateWinPath($p_filename, false);
        $v_stored_filename = $p_filename;
        $p_remove_dir      = $this->_translateWinPath($p_remove_dir, false);
        if ($p_remove_dir != '') {
            if (substr($p_remove_dir, -1) != '/') {
                $p_remove_dir .= '/';
            }

            if (substr($p_filename, 0, strlen($p_remove_dir)) == $p_remove_dir) {
                $v_stored_filename = substr($p_filename, strlen($p_remove_dir));
            }
        }

        $v_stored_filename = $this->_translateWinPath($v_stored_filename);
        $v_stored_filename = $this->_pathReduction($v_stored_filename);
        if ($this->_isArchive($p_filename)) {
            if (($v_file = @fopen($p_filename, "rb")) == 0) {
                $this->_addBackupProcessMessage('Unable to open file "' . $p_filename . '" in binary read mode', Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING);

                return true;
            }

            if (!$this->_writeHeader($p_filename, $v_stored_filename)) {
                return false;
            }

            if ($this->_packFile($v_file) === false) {
                return false;
            }

        } else {
            if (!$this->_writeHeader($p_filename, $v_stored_filename)) {
                return false;
            }

            $relDir = strtr($p_filename, array($this->_baseDir => ''));
            if (@is_link($p_filename)) {
                $this->_addBackupProcessMessage($this->_helper->__('Adding "%s" link to archive', $relDir));
            } else {
                $this->_addBackupProcessMessage($this->_helper->__('Adding "%s" directory to archive', $relDir));
            }
        }

        return true;
    }

    protected function _packFile($file, $bytes = null)
    {
        if (is_string($file)) {
            if (($v_file = @fopen($file, "rb")) == 0) {
                $this->_addBackupProcessMessage('Unable to open file "' . $file . '" in binary read mode', Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING);

                return true;
            }
        } elseif (is_resource($file)) {
            $v_file = $file;
            $file   = stream_get_meta_data($v_file);
            $file   = $file['uri'];
        } else {
            $this->_addBackupProcessMessage('Error input data', Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING);

            return false;
        }

        $bytes = (float)$bytes;
        if ($bytes > 0) {
            $this->fseek($v_file, $bytes);
        } else {
            $relDir = strtr($file, array($this->_baseDir => ''));
            $this->_addBackupProcessMessage($this->_helper->__('Adding "%s" file to archive', $relDir));
        }

        while (($v_buffer = fread($v_file, 512)) != '') {
            /*if ($bytes > 0 && !isset($first)) {
              Mage::log('#');Mage::log($v_buffer);Mage::log('#');
                $first = true;
            }*/
            $this->_writeBlock(pack("a512", "$v_buffer"));
            $bytes += 512;

            if ($this->timeIsUp()) {
                $this->_timeIsUp = true;
                $this->setStepParams($file, $bytes);
                /*Mage::log('#');Mage::log($v_buffer);Mage::log('#');*/
                break;
            }
        }

        fclose($v_file);

        if (!$this->_timeIsUp) {
            $this->setStepParams(null, null);
        }

        return true;
    }

    protected function timeIsUp()
    {
        return !$this->getBackup()->canContinue();
    }

    public function getSession()
    {
        return $this->getBackup()->getSession();
    }

    protected function setSessionStepIndex($index)
    {
        $this->getSession()->setData(self::SESSION_PARAM_INDEX, $index);
    }

    protected function setStepIndex($index)
    {
        $this->getBackup()->addStepCompressData(array(self::SESSION_PARAM_INDEX => $index));
    }

    protected function setSessionStepParams($file, $byte)
    {
        $this->getSession()
            ->setData(self::SESSION_PARAM_FILE, $file)
            ->setData(self::SESSION_PARAM_BYTE, $byte);
    }

    protected function setStepParams($file, $byte)
    {
        $this->getBackup()->addStepCompressData(array(
            self::SESSION_PARAM_FILE => $file,
            self::SESSION_PARAM_BYTE => $byte
        ));
    }

    protected function getSessionStepIndex()
    {
        return $this->getSession()->getData(self::SESSION_PARAM_INDEX);
    }

    protected function getStepIndex()
    {
        $compressData = $this->getBackup()->getStepCompressData();

        return empty($compressData[self::SESSION_PARAM_INDEX]) ? '' : $compressData[self::SESSION_PARAM_INDEX];
    }

    protected function getSessionStepFile()
    {
        return $this->getSession()->getData(self::SESSION_PARAM_FILE);
    }

    protected function getStepFile()
    {
        $compressData = $this->getBackup()->getStepCompressData();

        return empty($compressData[self::SESSION_PARAM_FILE]) ? '' : $compressData[self::SESSION_PARAM_FILE];
    }

    protected function getSessionStepByte()
    {
        return $this->getSession()->getData(self::SESSION_PARAM_BYTE);
    }

    protected function getStepByte()
    {
        $compressData = $this->getBackup()->getStepCompressData();

        return empty($compressData[self::SESSION_PARAM_BYTE]) ? '' : $compressData[self::SESSION_PARAM_BYTE];
    }

    public function isFinished()
    {
        return $this->_timeIsUp === false;
    }

    protected function _addBackupProcessMessage($message, $error = false)
    {
        $this->getBackup()->addBackupProcessMessage($message, $error);
    }

    function filesize($path)
    {
        return Mageplace_Backup_Model_File::filesize($path);
    }

    function fseek($fp, $pos)
    {
        Mageplace_Backup_Model_File::fseek($fp, $pos);
    }
}