<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Model_Backuplog extends Mage_Core_Model_Abstract
{
	protected function _construct()
	{
		parent::_construct();

		$this->_init('mpbackup/backuplog');
	}
}