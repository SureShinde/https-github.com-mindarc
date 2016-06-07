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

$installer->getConnection()->addColumn($installer->getTable('mpbackup/backup'), 'backup_log_file', 'text NOT NULL AFTER `backup_files`');
$installer->getConnection()->addColumn($installer->getTable('mpbackup/backup'), 'backup_additional', 'text NOT NULL AFTER `backup_update_date`');
$installer->getConnection()->addColumn($installer->getTable('mpbackup/backup'), 'backup_errors', 'text NOT NULL AFTER `backup_update_date`');

$installer->run("
CREATE TABLE IF NOT EXISTS `{$installer->getTable('mpbackup/cron_schedule')}` (
	`cron_id`		int(10) unsigned NOT NULL AUTO_INCREMENT,
	`schedule_id`	int(10) unsigned DEFAULT NULL,
	`profile_id`	int(10) unsigned NOT NULL,
	`job_code`		varchar(20) NOT NULL DEFAULT '',
	`status`		enum('pending','running','success','missed','error') NOT NULL DEFAULT 'pending',
	`messages`		text,
	`created_at`	datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`scheduled_at`	datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`executed_at`	datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`finished_at`	datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (`cron_id`),
	CONSTRAINT `FK_MPBACKUP_CRON_SCHEDULE_ID` FOREIGN KEY (`schedule_id`) REFERENCES `{$installer->getTable('cron/schedule')}` (`schedule_id`) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT `FK_MPBACKUP_CRON_PROFILE_ID` FOREIGN KEY (`profile_id`) REFERENCES `{$installer->getTable('mpbackup/profile')}` (`profile_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=UTF8 COMMENT='MagePlace Backup Cron Schedules';
");

$installer->endSetup();

try {
	$pendingSchedules = Mage::getModel('cron/schedule')->getCollection()
		->addFieldToFilter('job_code', array('like' => 'mpbackup%'))
		->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING)
		->load();

	/* @var $schedule Mage_Cron_Model_Schedule */
	foreach ($pendingSchedules->getIterator() as $schedule) {
		$schedule->delete();
	}
} catch(Exception $e) {
	Mage::logException($e);
}

try {
	$profileIds = Mage::getModel('mpbackup/profile')->getCollection()->getAllIds();
	/* @var $schedule Mage_Cron_Model_Schedule */
	foreach ($profileIds as $profileId) {
		Mage::getModel('core/config_data')
			->load('crontab/jobs/mpbackup'.$profileId.'/schedule/cron_expr', 'path')
			->delete();
	}

	Mage::app()->getCacheInstance()->cleanType('config');
} catch(Exception $e) {
	Mage::logException($e);
}
