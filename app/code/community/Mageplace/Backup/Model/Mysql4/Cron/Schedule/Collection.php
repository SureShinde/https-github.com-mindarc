<?php
/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Model_Mysql4_Cron_Schedule_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	/**
	 * Constructor
	 */
	public function _construct()
	{
		$this->_init('mpbackup/cron_schedule');
	}


	/**
	 * @return Mageplace_Backup_Model_Mysql4_Cron_Schedule_Collection
	 */
	public function addPendingFilter()
	{
		$this->addFieldToFilter('status', Mageplace_Backup_Helper_Const::STATUS_PENDING);
		return $this;
	}

	/**
	 * @return Mageplace_Backup_Model_Mysql4_Cron_Schedule_Collection
	 */
	public function addDirectionOrder()
	{
		$this->addOrder('scheduled_at', self::SORT_ORDER_ASC);
		return $this;
	}
}
