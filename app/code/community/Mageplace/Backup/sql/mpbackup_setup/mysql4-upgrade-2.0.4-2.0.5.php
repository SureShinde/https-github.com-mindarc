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
    Mageplace_Backup_Model_Profile::COLUMN_AUTH_ENABLE,
    'tinyint(1) NOT NULL DEFAULT "0"'
);

$installer->endSetup();