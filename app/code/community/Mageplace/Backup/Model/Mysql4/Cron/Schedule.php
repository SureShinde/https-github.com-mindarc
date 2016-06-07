<?php
/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Model_Mysql4_Cron_Schedule extends Mage_Core_Model_Mysql4_Abstract
{
	/**
	 * Constructor
	 */
	public function _construct()
	{
		$this->_init('mpbackup/cron_schedule', 'cron_id');
	}

	public function trySetJobStatusAtomic($cronId, $newStatus, $currentStatus)
	{
		$write = $this->_getWriteAdapter();
		$result = $write->update(
			$this->getMainTable(),
			array('status' => $newStatus),
			array('cron_id = ?' => $cronId, 'status = ?' => $currentStatus)
		);

		if ($result == 1) {
			return true;
		}

		return false;
	}

	public function clean($status, $ts)
	{
		if(empty($status) || empty($ts)) {
			return $this;
		}

		$this->_getWriteAdapter()->delete(
			$this->getMainTable(),
			array(
				'status = ?' => $status,
				'executed_at > ?' => '0000-00-00 00:00:00',
				'executed_at < ?' => date('Y-m-d H:i:s', $ts),
			)
		);

		return $this;
	}
}