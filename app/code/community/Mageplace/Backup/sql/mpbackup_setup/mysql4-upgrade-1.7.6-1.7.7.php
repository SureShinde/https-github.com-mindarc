<?php
/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */


/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('mpbackup/profile'), 'profile_backup_error_delete_local', 'tinyint(1) NOT NULL DEFAULT 0');
$installer->getConnection()->addColumn($installer->getTable('mpbackup/profile'), 'profile_backup_error_delete_cloud', 'tinyint(1) NOT NULL DEFAULT 0');

$installer->getConnection()->addColumn($installer->getTable('mpbackup/backup'), 'backup_started', 'tinyint(1) unsigned NOT NULL DEFAULT 0');
$installer->getConnection()->addColumn($installer->getTable('mpbackup/backup'), 'backup_finished', 'tinyint(1) unsigned NOT NULL DEFAULT 0');


$installer->run("
CREATE TABLE IF NOT EXISTS `{$installer->getTable('mpbackup/temp')}` (
	`temp_id`		int(10) unsigned NOT NULL AUTO_INCREMENT,
	`backup_id`		int(10) unsigned NOT NULL,
	`type`			varchar(20) NOT NULL,
	`message`		text,
	`created`		datetime NOT NULL,
	PRIMARY KEY (`temp_id`),
	CONSTRAINT `FK_MPBACKUP_TEMP_BACKUP_ID` FOREIGN KEY (`backup_id`) REFERENCES `{$installer->getTable('mpbackup/backup')}` (`backup_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8 COMMENT='MagePlace Backup Temp Messages Table';
");

$installer->endSetup();