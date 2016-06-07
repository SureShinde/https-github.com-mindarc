<?php
/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Model_Mysql4_Config extends Mage_Core_Model_Mysql4_Abstract
{
	/**
	 * Constructor
	 */
	protected function _construct()
	{
		$this->_init('mpbackup/config', 'config_id');
	}
}
