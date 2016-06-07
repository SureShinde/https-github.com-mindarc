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
 * Class Mageplace_Backup_Block_Adminhtml_Backup_Grid_Column_Renderer_Status
 */
class Mageplace_Backup_Block_Adminhtml_Backup_Grid_Column_Renderer_Status
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Renders grid column
     *
     * @param Mageplace_Backup_Model_Backup|Varien_Object $backup
     *
     * @return string
     */
    public function render(Varien_Object $backup)
    {
        if ($backup->isSuccessFinished()) {
            return '<strong style="color:rgb(60,179,113);">' . Mage::helper('mpbackup')->__('Finished') . '</strong>';
        } elseif ($backup->isStatusCriticalErrors()) {
            return '<strong style="color:#DC143C;">' . Mage::helper('mpbackup')->__('Critical Errors') . '</strong>';
        } elseif ($backup->isStatusErrors()) {
            return '<strong style="color:red;">' . Mage::helper('mpbackup')->__('Errors') . '</strong>';
        } elseif ($backup->isStatusCancelled()) {
            return '<strong style="color:rgb(127,255,0);">' . Mage::helper('mpbackup')->__('Canceled') . '</strong>';
        } elseif ($backup->isStatusWarnings()) {
            return '<strong style="color:#E2CF6A;">' . Mage::helper('mpbackup')->__('Warnings') . '</strong>';
        } elseif (!$backup->isStatusFinished()) {
            $backup_creation_date = strtotime($backup->getData('backup_creation_date'));
            $lifeCycle            = $backup_creation_date + Mageplace_Backup_Helper_Const::CRON_SCHEDULES_RUN_LIFETIME_CYCLE * 60;
            if ($lifeCycle <= time()) {
                return '<strong style="color:orange;">' . Mage::helper('mpbackup')->__('Running for too long or Interrupted Unsuccessfully') . '</strong>';
            }

            return '<strong style="color:#406A83;">' . Mage::helper('mpbackup')->__('Running') . '</strong>';

        } else {
            return '<strong>' . Mage::helper('mpbackup')->__('Unknown') . '</strong>';
        }
    }
}
