<?php
/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */

/**
 * @method Mageplace_Backup_Model_Mysql4_Cron_Schedule _getResource()
 * @method Mageplace_Backup_Model_Mysql4_Cron_Schedule getResource()
 * @method string getProfileId()
 * @method string getJobCode()
 * @method datetime getScheduledAt()
 */

class Mageplace_Backup_Model_Cron_Schedule extends Mage_Cron_Model_Schedule
{
	public function _construct()
	{
		parent::_construct();

		$this->_init('mpbackup/cron_schedule');
	}

	/**
	 * Sets a job to STATUS_RUNNING only if it is currently in STATUS_PENDING.
	 * Returns true if status was changed and false otherwise.
	 *
	 * This is used to implement locking for cron jobs.
	 *
	 * @return boolean
	 */
	public function tryJobLock()
	{
		return $this->_getResource()->trySetJobStatusAtomic($this->getId(), Mageplace_Backup_Helper_Const::STATUS_RUNNING, Mageplace_Backup_Helper_Const::STATUS_PENDING);
	}

	public function clean()
	{
		$historyLifetimes = array(
			Mageplace_Backup_Helper_Const::STATUS_SUCCESS => Mage::getStoreConfig(Mage_Cron_Model_Observer::XML_PATH_HISTORY_SUCCESS)*60,
			Mageplace_Backup_Helper_Const::STATUS_MISSED => Mage::getStoreConfig(Mage_Cron_Model_Observer::XML_PATH_HISTORY_FAILURE)*60,
			Mageplace_Backup_Helper_Const::STATUS_ERROR => Mage::getStoreConfig(Mage_Cron_Model_Observer::XML_PATH_HISTORY_FAILURE)*60,
#			Mageplace_Backup_Helper_Const::STATUS_RUNNING => Mageplace_Backup_Helper_Const::CRON_SCHEDULES_RUN_LIFETIME_CYCLE*60,
		);

		$ts = time();
		foreach($historyLifetimes as $status=>$lifetime) {
			$time = $ts - $lifetime;
#			Mage::log('MPBACKUP CLEAN ' . $status . ' - ' . date('Y-m-d H:i:s', $time));
			$this->_getResource()->clean($status, $time);
		}

		return $this;
	}
}