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
 * Class Mageplace_Backup_Model_Backup_Abstract
 */
abstract class Mageplace_Backup_Model_Backup_Abstract extends Varien_Object
{
    /**
     * @var Mageplace_Backup_Model_Backup
     */
    protected $_backup;

    abstract public function getStaticData();

    protected function _construct()
    {
        $this->_data = $this->getStaticData();
    }

    public function setBackup(Mageplace_Backup_Model_Backup $backup)
    {
        $this->_backup = $backup;

        return $this;
    }

    public function getBackup()
    {
        return $this->_backup;
    }

    /**
     * @param array|string $key
     * @param null         $value
     *
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function setData($key, $value = null)
    {
        if (is_string($key) && !array_key_exists($key, $this->getStaticData()) && !in_array($key, $this->getApprovedKeys())) {
            throw Mage::exception('Mageplace_Backup', Mage::helper('mpbackup')->__('Wrong object key: %s', $key));
        }

        return parent::setData($key, $value);
    }

    /**
     * @return array
     */
    protected function getApprovedKeys()
    {
        return array();
    }

    public function getMethodName($key)
    {
        return $this->_camelize($key);
    }

    public function parse($json)
    {
        try {
            $arr = Zend_Json::decode($json);
            if (!is_array($arr) || empty($arr)) {
                return $json;
            }

            foreach ($this->getStaticData() as $key => $value) {
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

    public function __toArray(array $arrAttributes = array())
    {
        $dataArray = $this->getStaticData();
        if (!empty($arrAttributes)) {
            $dataArray = array_intersect_key($dataArray, array_flip($arrAttributes));
        }

        $arrRes = array();
        foreach ($dataArray as $key => $value) {
            if (array_key_exists($key, $this->_data)) {
                $arrRes[$key] = $this->_data[$key];
            } else {
                $arrRes[$key] = $value;
            }
        }

        return $arrRes;
    }
}