<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

$installer = $this;

$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('mpbackup/backuplog')}` (
	`backuplog_id`			int(10) unsigned NOT NULL AUTO_INCREMENT,
	`backup_id`				int(10) unsigned NOT NULL,
	`backuplog_type`		varchar(20) NOT NULL,
	`backuplog_message`		text NOT NULL,
	`backuplog_time`		datetime NOT NULL,
	PRIMARY KEY (`backuplog_id`),
	CONSTRAINT `FK_MPBACKUP_BACKUPLOG_BACKUP_ID` FOREIGN KEY (`backup_id`) REFERENCES `{$this->getTable('mpbackup/backup')}` (`backup_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8 COMMENT='MagePlace Backup Logs';
");

$installer->getConnection()->addColumn($installer->getTable('mpbackup/profile'), 'profile_log_path', 'varchar(255) NOT NULL AFTER `profile_backup_path`');
$installer->getConnection()->addColumn($installer->getTable('mpbackup/backup'), 'backup_finish_date', 'datetime NOT NULL AFTER `backup_creation_date`');

$installer->endSetup();