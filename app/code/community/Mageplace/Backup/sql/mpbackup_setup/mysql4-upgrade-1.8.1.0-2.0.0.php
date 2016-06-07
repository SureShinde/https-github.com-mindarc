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

$profileTable = $installer->getTable('mpbackup/profile');
$backupTable  = $installer->getTable('mpbackup/backup');

$installer->startSetup();

$installer->getConnection()->addColumn(
    $profileTable,
    Mageplace_Backup_Model_Profile::COLUMN_BACKUP_FILENAME_SUFFIX,
    'varchar(10) NOT NULL DEFAULT "" AFTER `' . Mageplace_Backup_Model_Profile::COLUMN_NAME . '`'
);
$installer->getConnection()->dropColumn(
    $profileTable,
    Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_TIME
);
$installer->getConnection()->addColumn(
    $profileTable,
    Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_TIME,
    'int(5) NOT NULL DEFAULT "0" AFTER `' . Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_ENABLE . '`'
);
$installer->getConnection()->addColumn(
    $profileTable,
    Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_CRON_ENABLE,
    'tinyint(1) NOT NULL AFTER `' . Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_TIME . '`'
);
$installer->getConnection()->addColumn(
    $profileTable,
    Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_CRON_TIME,
    'int(5) NOT NULL DEFAULT "0" AFTER `' . Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_CRON_ENABLE . '`'
);

$installer->getConnection()->addColumn(
    $backupTable,
    Mageplace_Backup_Model_Backup::COLUMN_SECRET,
    'varchar(20) NOT NULL AFTER `' . Mageplace_Backup_Model_Backup::COLUMN_NAME . '`'
);
$installer->getConnection()->addColumn(
    $backupTable,
    Mageplace_Backup_Model_Backup::COLUMN_KEY,
    'varchar(50) NOT NULL AFTER `' . Mageplace_Backup_Model_Backup::COLUMN_SECRET . '`'
);
$installer->getConnection()->addColumn(
    $backupTable,
    Mageplace_Backup_Model_Backup::COLUMN_CRON,
    'tinyint(1) NOT NULL DEFAULT "0" AFTER `' . Mageplace_Backup_Model_Backup::COLUMN_KEY . '`'
);
$installer->getConnection()->addColumn(
    $backupTable,
    Mageplace_Backup_Model_Backup::COLUMN_STATUS,
    'tinyint(1) NOT NULL DEFAULT "0" AFTER `' . Mageplace_Backup_Model_Backup::COLUMN_CRON . '`'
);

$installer->run("
    UPDATE `$backupTable` SET " . Mageplace_Backup_Model_Backup::COLUMN_SECRET . " = md5(`" . Mage::getResourceModel('mpbackup/backup')->getIdFieldName() . "` + RAND()+CURRENT_TIMESTAMP());

    UPDATE `$backupTable`
    SET `" . Mageplace_Backup_Model_Backup::COLUMN_STATUS . "` =
        CASE
            WHEN `" . Mageplace_Backup_Model_Backup::COLUMN_ERRORS . "` <> ''
             THEN 5
            WHEN `" . Mageplace_Backup_Model_Backup::COLUMN_FINISHED . "` = '1'
                AND (`" . Mageplace_Backup_Model_Backup::COLUMN_FILES . "` <> '' OR `" . Mageplace_Backup_Model_Backup::COLUMN_CLOUD_FILES . "` <> '')
                AND `" . Mageplace_Backup_Model_Backup::COLUMN_LOG_FILE . "` <> ''
             THEN 2
            WHEN `" . Mageplace_Backup_Model_Backup::COLUMN_FINISHED . "` = '0' AND `" . Mageplace_Backup_Model_Backup::COLUMN_STARTED . "` = '1'
             THEN 1
        end
    WHERE `" . Mageplace_Backup_Model_Backup::COLUMN_STATUS . "` = 0;
");

$installer->getConnection()->addKey(
    $backupTable,
    'UNQ_MPBACKUP_BACKUP_BACKUP_SECRET',
    array(Mageplace_Backup_Model_Backup::COLUMN_SECRET),
    'unique'
);

$installer->endSetup();