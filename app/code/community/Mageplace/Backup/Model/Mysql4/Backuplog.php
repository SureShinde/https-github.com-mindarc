<?php
/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Model_Mysql4_Backuplog extends Mage_Core_Model_Mysql4_Abstract
{
	/**
	 * Constructor
	 */
	protected function _construct()
	{
		$this->_init('mpbackup/backuplog', 'backuplog_id');
	}
	
	public function insertLogMessage($backup_id, $type, $message, $date=null)
	{
		$bind = array(
			'backup_id'			=> $backup_id,
			'backuplog_type'	=> $type,
			'backuplog_message'	=> $message,
			'backuplog_time'	=> ($date ? $date : date('Y-m-d H:i:s'))
		);
		
		$ret = $this->_getWriteAdapter()->insert($this->getMainTable(), $bind);
	}
	
	public function getLogMessages($backup_id=null, $start_id=null)
	{
		$read = $this->_getReadAdapter();
		$write = $this->_getWriteAdapter();
		
		$select = $read->select()
			->from($this->getMainTable());
		
		if(!$backup_id) {
			$backup_id = Mage::getResourceModel('mpbackup/backup')->getCurrentBackupId();
			if(!$backup_id) {
				return array(); 
			}
		}	
		
		$select->where($write->quoteInto('backup_id = ?', $backup_id));
		
		if($start_id) {
			$select->where($write->quoteInto($this->getIdFieldName().' > ?', $start_id));
		}
		
		$select->order($this->getIdFieldName().' DESC');		
		$data = $read->fetchAll($select);
		
		return $data;
	}
		
	public function clearLogMessages($backup_id)
	{
		$this->_getWriteAdapter()->delete(
			$this->getMainTable(),
			$this->_getWriteAdapter()->quoteInto('backup_id = ?', $backup_id)
		);
	}

	public function clearAllLogMessages()
	{
		$this->_getWriteAdapter()->truncate($this->getMainTable()); 
	}
}
