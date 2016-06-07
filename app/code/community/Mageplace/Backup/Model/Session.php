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
 * Class Mageplace_Backup_Model_Session
 */
class Mageplace_Backup_Model_Session extends Mage_Core_Model_Session_Abstract
{
    protected $_sid;

    public function __construct($params = null)
    {
        if (!empty($params) && is_array($params) && count($params) > 0) {
            $name = null;
            if (array_key_exists('name', $params)) {
                $name = $params['name'];
                unset($params['name']);
            }

            if (array_key_exists('sid', $params)) {
                $this->_sid = $params['sid'] ? $params['sid'] : null;
                unset($params['sid']);
            }

            $suffix = array();
            foreach ($params as $param) {
                if ($param) {
                    $suffix[] = $param;
                }
            }

            if (count($suffix)) {
                $this->init('mpbackup_' . implode('_', $suffix), $name);

                return;
            } else if (!empty($name)) {
                $this->init('mpbackup', $name);
            }
        }

        $this->init('mpbackup');
    }

    public function setSessionId($id = null)
    {
        if (!empty($this->_sid)) {
            $id = $this->_sid;
        }

        return parent::setSessionId($id);
    }

    public function setProfileId($profile_id)
    {
        if ($profile_id != $this->_getData('profile_id')) {
            $this->clear();
        }

        $this->setData('profile_id', $profile_id);

        return $this;
    }

    public function checkCloud($data)
    {
        if (empty($data)) {
            return false;
        }

        $id = md5(serialize($data));
        if (($cloud_id = $this->getCloudId()) && ($cloud_id == $id)) {
            return true;
        }

        return false;
    }

    public function setCloudId($data)
    {
        if (empty($data)) {
            return $this;
        }

        $id = md5(serialize($data));

        $this->setData('cloud_id', $id);

        return $this;
    }

    public function initProfilePath($profile = null)
    {
        $path = array();
        if (is_null($profile)) {
            if ($profile_obj = $this->getProfile()) {
                $profile = $profile_obj;
            } else if ($profile_id = $this->getProfileId()) {
                $profile = $profile_id;
            }
        }

        if (($profile instanceof Mageplace_Backup_Model_Profile) && $profile->getId()) {
            $path = $profile->getData(Mageplace_Backup_Model_Profile::EXCLUDED_PATH);
        } else if (is_int($profile)) {
            $profile = Mage::getModel('mpbackup/profile')->load($profile);
            if ($profile->getId()) {
                $path = $profile->getData(Mageplace_Backup_Model_Profile::EXCLUDED_PATH);
            }
        }

        $this->unsProfilePath();

        $this->addProfilePath($path);
    }

    public function addProfilePath($path, $add = true)
    {
        if (!$path) {
            return $this;
        }

        if (!is_array($path)) {
            $path = (array)$path;
        }


        $profile_paths = $this->getProfilePath();

        if ($add) {
            $profile_paths = array_merge($profile_paths, $path);
        } else {
            foreach ($path as $p) {
                $key = array_search($p, $profile_paths);
                if ($key !== false) {
                    unset($profile_paths[$key]);
                }
            }
        }

        $this->setData('profile_path', $profile_paths);

        return $this;
    }

    public function getProfilePath()
    {
        $profile_paths = $this->_getData('profile_path');
        if (!$profile_paths || !is_array($profile_paths)) {
            return array();
        } else {
            return array_values($profile_paths);
        }
    }

    public function clearProfilePath()
    {
        $this->unsProfilePath();

        return $this;
    }

    public function setBackupModel($backupModel)
    {
        $backupModelSer = serialize($backupModel);
        $this->setData('backup_model', $backupModelSer);

        return $this;
    }

    public function getBackupModel()
    {
        $backupModelSer = $this->getData('backup_model');
        $backupModel    = unserialize($backupModelSer);

        return $backupModel;
    }
}