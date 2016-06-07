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

$date = Mage::getSingleton('core/date')->gmtDate();

$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('mpbackup/profile')}` (
	`profile_id`			int(10) unsigned NOT NULL AUTO_INCREMENT,
	`profile_name`			varchar(255) NOT NULL,
	`profile_default`		tinyint(1) NOT NULL DEFAULT '0',
	`profile_local_copy`	tinyint(1) NOT NULL DEFAULT '1',
	`profile_backup_path`	varchar(255) NOT NULL,
	`profile_cloud_app`		varchar(255) NOT NULL,
	`profile_creation_date`	datetime NOT NULL,
	`profile_update_date`	datetime NOT NULL,
	PRIMARY KEY (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8 COMMENT='MagePlace Backup Profiles';

INSERT IGNORE INTO `{$this->getTable('mpbackup/profile')}`
	(`profile_name`, `profile_default`, `profile_creation_date`, `profile_update_date`)
VALUES
	('Default Profile', 1, '".$date."', '".$date."');

CREATE TABLE IF NOT EXISTS `{$this->getTable('mpbackup/backup')}` (
	`backup_id`				int(10) unsigned NOT NULL AUTO_INCREMENT,
	`profile_id`			int(10) unsigned NOT NULL,
	`backup_name`			varchar(255) NOT NULL,
	`backup_files`			text NOT NULL,
	`backup_cloud_files`	text NOT NULL,
	`backup_description`	text NOT NULL,
	`backup_creation_date`	datetime NOT NULL,
	`backup_update_date`	datetime NOT NULL,
	PRIMARY KEY (`backup_id`),
	CONSTRAINT `FK_MPBACKUP_BACKUP_PROFILE_ID` FOREIGN KEY (`profile_id`) REFERENCES `{$this->getTable('mpbackup/profile')}` (`profile_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8 COMMENT='MagePlace Backup Backups';

CREATE TABLE IF NOT EXISTS `{$this->getTable('mpbackup/config')}` (
	`config_id`				int(10) unsigned NOT NULL AUTO_INCREMENT,
	`profile_id`			int(10) unsigned NOT NULL,
	`config_path`			varchar(255) NOT NULL DEFAULT 'general',
	`config_name`			varchar(255) NOT NULL,
	`config_value`			text NOT NULL,
	PRIMARY KEY (`config_id`),
	CONSTRAINT `FK_MPBACKUP_SETTIINGS_PROFILE_ID` FOREIGN KEY (`profile_id`) REFERENCES `{$this->getTable('mpbackup/profile')}` (`profile_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8 COMMENT='MagePlace Backup Profile Config';

");

$installer->endSetup();
