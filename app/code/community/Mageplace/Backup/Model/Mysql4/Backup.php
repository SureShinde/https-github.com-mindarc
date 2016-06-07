<?php

/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */
class Mageplace_Backup_Model_Mysql4_Backup extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * @param $backup_id
     * @param $data
     *
     * @return bool
     */
    public function criticalSave($backup_id, $data)
    {
        if (!is_array($data)) {
            return false;
        }

        $write = $this->_getWriteAdapter();

        if ($backup_id) {
            return Mage::getModel('mpbackup/interceptor')->checkMethodCall(
                $write,
                'update',
                $this->getMainTable(),
                $data,
                $write->quoteInto("{$this->_idFieldName} = ?", $backup_id)
            );
            /* the same as $return = $write->update($this->getMainTable(), $data, $condition); */
        } else {
            return Mage::getModel('mpbackup/interceptor')->checkMethodCall(
                $write,
                'insert',
                $this->getMainTable(),
                $data
            );
            /* the same as $return = $write->insert($this->getMainTable(), $data); */
        }
    }

    public function getCurrentBackupId($secret)
    {
        $read = $this->_getReadAdapter();

        $backup_id_select = $read->select()
            ->from($this->getMainTable(), array($this->getIdFieldName()))
            ->order($this->getIdFieldName() . ' ' . Varien_Db_Select::SQL_DESC)
            ->limit(1);

        $binds = array(
            Mageplace_Backup_Model_Backup::COLUMN_SECRET      => $secret,
            Mageplace_Backup_Model_Backup::COLUMN_STATUS      => Mageplace_Backup_Model_Backup::STATUS_STARTED,
            Mageplace_Backup_Model_Backup::COLUMN_FINISH_DATE => '0000-00-00 00:00:00',
        );

        foreach (array_keys($binds) as $key) {
            $backup_id_select->where($key . " = :" . $key);
        }

        return (int)$read->fetchOne($backup_id_select, $binds);
    }

    public function trySetStatusAtomic($backupId, $status, $statusValue)
    {
        $write  = $this->_getWriteAdapter();
        $result = $write->update(
            $this->getMainTable(),
            array($status => $statusValue),
            array('backup_id = ?' => $backupId)
        );

        if ($result == 1) {
            return true;
        }

        return false;
    }

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('mpbackup/backup', 'backup_id');
    }

    /**
     * Sets the creation and update timestamps
     *
     * @param    Mage_Core_Model_Abstract $object Current profile
     *
     * @return    Mageplace_Backup_Model_Mysql4_Backup
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        $id = $object->getBackupId();
        if (!$id) {
            $object->setBackupCreationDate(Mage::getSingleton('core/date')->gmtDate());
        }
        $object->setBackupUpdateDate(Mage::getSingleton('core/date')->gmtDate());

        return $this;
    }
}
