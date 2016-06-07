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
 * Class Mageplace_Backup_Model_Temp
 *
 * @method Mageplace_Backup_Model_Mysql4_Temp _getResource()
 * @method Mageplace_Backup_Model_Mysql4_Temp getResource()
 */
class Mageplace_Backup_Model_Temp extends Mage_Core_Model_Abstract
{
    const TYPE_FILES_FOR_DELETE = 1;
    const TYPE_FILES_CLOUD      = 2;
    const TYPE_FILES_NAMES      = 3;
    const TYPE_FILES_MAIN       = 4;
    const TYPE_FILES_TEMP       = 5;

    protected $_backup = null;
    protected $_backupId = null;

    protected function _construct()
    {
        parent::_construct();

        $this->_init('mpbackup/temp');
    }

    public function setBackup($backup)
    {
        $this->_backup   = $backup;
        $this->_backupId = $backup->getId();

        return $this;
    }

    public function insertMessage($type, $message)
    {
        if (is_null($this->_backupId)) {
            return false;
        }

        return $this->_getResource()->insertMessage($this->_backupId, $type, $message);
    }

    public function getMessages($type = null)
    {
        if (is_null($this->_backupId)) {
            return false;
        }

        return $this->_getResource()->getMessages($this->_backupId, $type);
    }

    public function clearMessages()
    {
        if (is_null($this->_backupId)) {
            return false;
        }

        return $this->_getResource()->clearMessages($this->_backupId);
    }

    public function addMainBackupFile($file)
    {
        return $this->insertMessage(self::TYPE_FILES_MAIN, $file);
    }

    public function getMainBackupFile()
    {
        return $this->getMessages(self::TYPE_FILES_MAIN);
    }

    public function addBackupFileNames($file)
    {
        return $this->insertMessage(self::TYPE_FILES_NAMES, $file);
    }

    public function getBackupFileNames()
    {
        return $this->getMessages(self::TYPE_FILES_NAMES);
    }

    public function addFilesForDelete($file)
    {
        return $this->insertMessage(self::TYPE_FILES_FOR_DELETE, $file);
    }

    public function getFilesForDelete()
    {
        return $this->getMessages(self::TYPE_FILES_FOR_DELETE);
    }

    public function addTempBackupFile($file)
    {
        return $this->insertMessage(self::TYPE_FILES_TEMP, $file);
    }

    public function getTempBackupFiles()
    {
        return $this->getMessages(self::TYPE_FILES_TEMP);
    }


    public function addCloudFiles($file, $info)
    {
        $message = array(
            'files' => $file,
            'infos' => $info
        );

        $message = serialize($message);

        return $this->insertMessage(self::TYPE_FILES_CLOUD, $message);
    }


    public function getCloudFiles()
    {
        $return = array('files' => null, 'infos' => null);

        $cloudMessages = $this->getMessages(self::TYPE_FILES_CLOUD);
        if (!is_array($cloudMessages)) {
            return $return;
        }

        foreach ($cloudMessages as $cloudMessage) {
            $cloud = @unserialize($cloudMessage);
            if (!empty($cloud) && is_array($cloud)) {
                if (!empty($cloud['files'])) {
                    $return['files'][] = $cloud['files'];
                }
                if (!empty($cloud['infos'])) {
                    if (is_array($cloud['infos'])) {
                        if (!is_array($return['infos'])) {
                            $return['infos'] = array();
                        }
                        $return['infos'] = array_merge($return['infos'], $cloud['infos']);
                    } else {
                        $return['infos'][] = $cloud['infos'];
                    }
                }
            }
        }

        return $return;
    }

}