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
$installer->run("ALTER TABLE `{$this->getTable('mpbackup/cron_schedule')}` CHANGE `schedule_id` `schedule_id` int(10) unsigned NULL DEFAULT NULL ");
$installer->endSetup();