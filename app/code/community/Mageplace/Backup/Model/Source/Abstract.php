<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Model_Source_Abstract
 */
abstract class Mageplace_Backup_Model_Source_Abstract
{
    abstract public function toOptionArray();

    public function toOptionHash()
    {
        $hash = array();
        foreach ($this->toOptionArray() as $item) {
            $hash[$item['value']] = $item['label'];
        }

        return $hash;
    }

    /**
     * @return Mageplace_Backup_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('mpbackup');
    }
}
