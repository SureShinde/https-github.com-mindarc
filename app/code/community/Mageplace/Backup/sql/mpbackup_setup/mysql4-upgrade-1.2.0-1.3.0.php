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

$installer->getConnection()->addColumn($installer->getTable('mpbackup/profile'), 'profile_log_level', 'varchar(20) NOT NULL AFTER `profile_log_path`');

$installer->endSetup();