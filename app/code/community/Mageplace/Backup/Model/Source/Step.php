<?php

/**
 * Mageplace Magesocial
 *
 * @category   Mageplace
 * @package    Mageplace_Magesocial
 * @copyright  Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */
class Mageplace_Backup_Model_Source_Step extends Mageplace_Backup_Model_Source_Abstract
{
    public function toOptionArray()
    {
        return array(
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_START, 'label' => $this->_getHelper()->__('Starting backup process')),
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_FIRST, 'label' => $this->_getHelper()->__('Prepare data to start backup process')),
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_CHECK_MEMORY_LIMIT, 'label' => $this->_getHelper()->__('Check memory limit')),
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_FREE_DISK_SPACE, 'label' => $this->_getHelper()->__('Check free space')),
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_DB_BACKUP, 'label' => $this->_getHelper()->__('DB tables backup')),
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_DB_FILES_PREPARE, 'label' => $this->_getHelper()->__('Prepare DB backup file(s) to upload to cloud server')),
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_DB_CLOUD, 'label' => $this->_getHelper()->__('Upload DB backup file(s) to cloud server')),
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_DB_FINISH, 'label' => $this->_getHelper()->__('Finish DB tables backup')),
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_FILES_BACKUP, 'label' => $this->_getHelper()->__('Filesystem backup')),
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_FILES_PACK, 'label' => $this->_getHelper()->__('Compressing filesystem backup file(s)')),
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_FILES_PREPARE, 'label' => $this->_getHelper()->__('Prepare filesystem backup file(s) to upload to cloud server')),
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_FILES_CLOUD, 'label' => $this->_getHelper()->__('Upload filesystem backup file(s) to cloud server')),
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_FILES_FINISH, 'label' => $this->_getHelper()->__('Finish filesystem backup')),
            array('value' => Mageplace_Backup_Model_Backup_Step::STEP_FINISH, 'label' => $this->_getHelper()->__('Finish backup')),
        );
    }
}
