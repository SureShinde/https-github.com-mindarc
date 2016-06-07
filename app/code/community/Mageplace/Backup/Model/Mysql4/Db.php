<?php
/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */


if(Mage::helper('mpbackup/version')->isOld()) {
	class Mageplace_Backup_Model_Mysql4_Db extends Mage_Backup_Model_Mysql4_Db
	{
	}	
} else {
	class Mageplace_Backup_Model_Mysql4_Db extends Mage_Backup_Model_Resource_Db
	{
	}
}