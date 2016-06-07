<?php
/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */


/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$profileTable = $installer->getTable('mpbackup/profile');

$installer->startSetup();

$installer->getConnection()->addColumn(
    $profileTable,
    Mageplace_Backup_Model_Profile::COLUMN_AUTH_USER,
    'varchar(200) NOT NULL DEFAULT ""'
);

$installer->getConnection()->addColumn(
    $profileTable,
    Mageplace_Backup_Model_Profile::COLUMN_AUTH_PASSWORD,
    'varchar(200) NOT NULL DEFAULT ""'
);

$installer->getConnection()->addColumn(
    $profileTable,
    Mageplace_Backup_Model_Profile::COLUMN_DISABLE_CACHE,
    'tinyint(1) NOT NULL DEFAULT "1"'
);

$installer->endSetup();