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
 * Class Mageplace_Backup_Block_Adminhtml_Backup_Js_Item
 */
class Mageplace_Backup_Block_Adminhtml_Backup_Js_Item extends Mageplace_Backup_Block_Adminhtml_Backup_Js
{
    public function __construct()
    {
        parent::__construct();

        $this->setTemplate('mpbackup/backup/js/item.phtml');
    }

    public function getBackupItem()
    {
        return $this->getBackupItemObject()->getStaticData();
    }

    public function getBackupItemObject()
    {
        return Mage::getSingleton('mpbackup/backup_item');
    }
}