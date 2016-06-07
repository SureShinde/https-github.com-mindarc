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
 * Class Mageplace_Backup_Block_Adminhtml_Backup_Js_Step
 */
class Mageplace_Backup_Block_Adminhtml_Backup_Js_Step extends Mageplace_Backup_Block_Adminhtml_Backup_Js
{
    public function __construct()
    {
        parent::__construct();

        $this->setTemplate('mpbackup/backup/js/step.phtml');
    }

    /**
     * @return Mageplace_Backup_Model_Backup_Step
     */
    public function getStepObject()
    {
        return Mage::getSingleton('mpbackup/backup_step');
    }
}