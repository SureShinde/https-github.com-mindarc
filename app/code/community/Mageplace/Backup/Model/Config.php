<?php

/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */
class Mageplace_Backup_Model_Config extends Mage_Core_Model_Abstract
{
    const CLOUD_PATH   = 'cloudapp';
    const CRON_PATH    = 'cron';
    const DEFAULT_PATH = 'default';

    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_init('mpbackup/config');
    }

    public function getConfigValues($profile = null, $path = null, $name = null, $getConfigId = false)
    {
        $values = $this->_getData('config_values_arr');
        $ids    = $this->_getData('config_values_ids');

        if (is_null($values)) {
            $config_items = $this->getCollection()->getItems();
            $values       = array();
            foreach ($config_items as $item) {
                $values[$item->getProfileId()][$item->getConfigPath()][$item->getConfigName()] = $item->getConfigValue();
                $ids[$item->getProfileId()][$item->getConfigPath()][$item->getConfigName()]    = $item->getId();
            }
            $this->setData('config_values_arr', $values);
            $this->setData('config_values_ids', $ids);
        }

        $profile = strval($profile);
        if (!$profile) {
            return $values;
        } else {
            if (!array_key_exists($profile, $values)) {
                return null;
            }
        }

        $path = strval($path);
        $name = strval($name);

        if ($getConfigId) {
            if (!empty($ids[$profile][$path][$name])) {
                return $ids[$profile][$path][$name];
            }

            return null;
        }

        if ($path) {
            if (array_key_exists($path, $values[$profile])) {
                if ($name) {
                    if (array_key_exists($name, $values[$profile][$path])) {
                        return (string)$values[$profile][$path][$name];
                    } else {
                        return null;
                    }
                } else {
                    return $values[$profile][$path];
                }
            } else {
                return null;
            }
        } else {
            return $values[$profile];
        }

        return null;
    }

    public function saveConfigValue($profile, $path, $name, $value = '')
    {
        if (empty($profile) || empty($path) || empty($name)) {
            Mage::getSingleton('adminhtml/session')->addError('Trying to save wrong config data: profile=' . $profile . '; path=' . $path . '; name=' . $name);

            return false;
        }

        if ($id = $this->getConfigValues($profile, $path, $name, true)) {
            $this->setId($id);
        }

        $this->setProfileId($profile);
        $this->setConfigPath($path);
        $this->setConfigName($name);
        $this->setConfigValue($value);

        try {
            $this->save();
        } catch (Exception $e) {
            Mage::logException($e);

            return false;
        }

        return true;
    }


    public function deleteConfigValues($profile_id, $path = null, $name = null)
    {
        if (!$profile_id) {
            return $this;
        }

        $config_coll = $this->getCollection()->addFilter('profile_id', $profile_id);

        if ($path) {
            $config_coll->addFilter('config_path', $path);

        }

        if ($name) {
            $config_coll->addFilter('config_name', $name);
        }

        $config_items = $config_coll->getItems();

        foreach ($config_items as $item) {
            $item->delete();
        }

        return $this;
    }

}
