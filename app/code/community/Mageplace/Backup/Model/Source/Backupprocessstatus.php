<?php
/**
 * Mageplace Magesocial
 *
 * @category   Mageplace
 * @package    Mageplace_Magesocial
 * @copyright  Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Model_Source_Backupprocessstatus
 */
class Mageplace_Backup_Model_Source_Backupprocessstatus extends Mageplace_Backup_Model_Source_Abstract
{
    public function toOptionArray()
    {
        return array(
            array('value' => Mageplace_Backup_Model_Backup::STATUS_FINISHED, 'label' => $this->_getHelper()->__('Finished')),
            array('value' => Mageplace_Backup_Model_Backup::STATUS_CANCELLED, 'label' => $this->_getHelper()->__('Canceled')),
            array('value' => Mageplace_Backup_Model_Backup::STATUS_STARTED, 'label' => $this->_getHelper()->__('Running')),
            array('value' => Mageplace_Backup_Model_Backup::STATUS_WARNINGS, 'label' => $this->_getHelper()->__('Warnings')),
            array('value' => Mageplace_Backup_Model_Backup::STATUS_ERRORS, 'label' => $this->_getHelper()->__('Errors')),
            array('value' => Mageplace_Backup_Model_Backup::STATUS_CRITICAL_ERRORS, 'label' => $this->_getHelper()->__('Critical Errors')),
            array('value' => Mageplace_Backup_Model_Backup::STATUS_UNDEFINED, 'label' => $this->_getHelper()->__('Unknown')),
        );
    }
}
