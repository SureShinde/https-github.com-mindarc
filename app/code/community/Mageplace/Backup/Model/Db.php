<?php
/**
 * Mageplace Backup
 *
 * @category       Mageplace
 * @package        Mageplace_Backup
 * @copyright      Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license        http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Model_Db
 */
class Mageplace_Backup_Model_Db extends Mage_Backup_Model_Db
{
    const SESSION_PARAM_TABLE  = 'step_db_table';
    const SESSION_PARAM_ROW    = 'step_db_row';
    const SESSION_PARAM_LIMIT  = 'step_db_limit';
    const SESSION_PARAM_LENGTH = 'step_db_length';

    /* @var $_profile Mageplace_Backup_Model_Profile */
    protected $_profile;

    /* @var $_profile Mageplace_Backup_Helper_Data */
    protected $_helper;

    /* @var $_backup Mageplace_Backup_Model_Backup */
    protected $_backup = null;

    protected $_exclude_tables      = array();
    protected $_exclude_tables_rows = array();

    static protected $EXCLUDE_TABLES = array();

    static protected $EXCLUDE_TABLES_ROWS = array(
        'mpbackup/backuplog', 'mpbackup/temp', 'core_session'
    );

    function __construct()
    {
        $this->_helper = Mage::helper('mpbackup');
    }

    public function getExcludedTables()
    {
        $resource = Mage::getSingleton('core/resource');

        $exclude_tables = array();
        foreach (self::$EXCLUDE_TABLES as $table) {
            $exclude_tables[] = $resource->getTableName($table);
        }

        return array_merge(
            $exclude_tables,
            $this->_exclude_tables
        );
    }

    public function setExcludedTables($exclude_tables)
    {
        if (!is_array($exclude_tables)) {
            $exclude_tables = array();
        }

        $this->_exclude_tables = $exclude_tables;

        return $this;
    }

    public function getExcludedTablesRows()
    {
        $resource = Mage::getSingleton('core/resource');

        $exclude_tables_rows = array();
        foreach (self::$EXCLUDE_TABLES_ROWS as $table) {
            $exclude_tables_rows[] = $resource->getTableName($table);
        }

        return array_merge(
            $exclude_tables_rows,
            $this->_exclude_tables_rows,
            $this->getExcludedTables()
        );
    }

    public function setExcludedTablesRows($exclude_tables_rows)
    {
        if (!is_array($exclude_tables_rows)) {
            $exclude_tables_rows = array();
        }

        $this->_exclude_tables_rows = $exclude_tables_rows;

        return $this;
    }

    /**
     * @param Mageplace_Backup_Model_File $backupFile
     *
     * @return boolean
     */
    public function start($backupFile)
    {
        $sessionTable = $this->getStepTable();
        $sessionRow   = $this->getStepRow();
        if ($sessionTable !== null) {
            $checkContinue = false;
        } else {
            $checkContinue = true;
        }

        $resource = $this->getResource();

        $backupFile->openGz(!$checkContinue ? Mageplace_Backup_Model_File::OPEN_MODE_APPEND : Mageplace_Backup_Model_File::OPEN_MODE_WRITE);

        $resource->beginTransaction();

        $excludedTablesRows = $this->getExcludedTablesRows();

        if ($checkContinue) {
            $backupFile->write($resource->getHeader());
        }

        $checkFinish = true;
        $tables      = $resource->getTables();
        foreach ($tables as $table) {
            if (!$checkContinue) {
                if ($table != $sessionTable) {
                    continue;
                } else {
                    $checkContinue = true;
                }
            }

            if (!in_array($table, $excludedTablesRows)) {
                if ($sessionTable != $table) {
                    $this->_addBackupProcessMessage($this->_helper->__('Start "%s" table backup', $table));
                    $backupFile->write($resource->getTableHeader($table) . $resource->getTableDropSql($table) . "\n");
                    $backupFile->write($resource->getTableCreateSql($table, false) . PHP_EOL);
                }

                /** @var Varien_Object|boolean $tableStatus */
                $tableStatus = $resource->getTableStatus($table);
                if (is_object($tableStatus) && $tableStatus->getRows()) {
                    if ($sessionTable != $table) {
                        $backupFile->write($resource->getTableDataBeforeSql($table));
                    }

                    if ($sessionTable == $table && $this->getStepLimit() > 0 && $this->getStepLength() > 0) {
                        $limit           = $this->getStepLimit();
                        $multiRowsLength = $this->getStepLength();
                    } elseif ($tableStatus->getDataLength() > self::BUFFER_LENGTH) {
                        if ($tableStatus->getAvgRowLength() < self::BUFFER_LENGTH) {
                            $limit           = floor(self::BUFFER_LENGTH / $tableStatus->getAvgRowLength());
                            $multiRowsLength = ceil($tableStatus->getRows() / $limit);
                        } else {
                            $limit           = 1;
                            $multiRowsLength = $tableStatus->getRows();
                        }
                    } else {
                        $limit           = $tableStatus->getRows();
                        $multiRowsLength = 1;
                    }

                    $start = $sessionTable != $table ? 0 : $sessionRow;
                    for ($i = $start; $i < $multiRowsLength; $i++) {
                        if ($this->timeIsUp()) {
                            $this->setStepParams($table, $i, $limit, $multiRowsLength);
                            $checkFinish = false;

                            break 2;
                        }

                        $backupFile->write($resource->getTableDataSql($table, $limit, $i * $limit));
                    }

                    $backupFile->write($resource->getTableDataAfterSql($table));
                }

                $this->_addBackupProcessMessage($this->_helper->__('Finish "%s" table backup', $table));
            } else {
                $backupFile->write($resource->getTableHeader($table));
                $createSql = $resource->getTableCreateSql($table, false) . PHP_EOL;
                $createSql = preg_replace('/(CREATE TABLE)(.*+)/is', '$1 IF NOT EXISTS $2', $createSql);
                $backupFile->write($createSql);
                $this->_addBackupProcessMessage($this->_helper->__('Skip "%s" table rows backup', $table));
            }

            if ($this->timeIsUp() && ($next = next($tables)) && ($next !== false)) {
                $this->setStepParams($next, 0, 0, 0);
                $checkFinish = false;
                break;
            }
        }

        if ($checkFinish) {
            $backupFile->write($resource->getTableForeignKeysSql());
            $backupFile->write($resource->getFooter());

            $this->setStepParams(null, null, null, null);
        }

        $resource->commitTransaction();

        $backupFile->close();

        return $checkFinish;
    }

    /**
     * @return Mage_Backup_Model_Mysql4_Db|Mageplace_Backup_Model_Interceptor
     */
    public function getResource()
    {
        return Mage::getModel('mpbackup/interceptor');
    }

    /**
     * @param $backup Mageplace_Backup_Model_Backup
     *
     * @return $this
     */
    public function setBackup($backup)
    {
        $this->_backup = $backup;

        return $this;
    }

    /**
     * @return Mageplace_Backup_Model_Backup
     */
    public function getBackup()
    {
        return $this->_backup;
    }

    protected function timeIsUp()
    {
        return !$this->getBackup()->canContinue();
    }

    public function getSession()
    {
        return $this->getBackup()->getSession();
    }

    protected function setSessionStepParams($table, $row, $limit, $multiRowsLength)
    {
        $this->getSession()
            ->setData(self::SESSION_PARAM_TABLE, $table)
            ->setData(self::SESSION_PARAM_ROW, $row)
            ->setData(self::SESSION_PARAM_LIMIT, $limit)
            ->setData(self::SESSION_PARAM_LENGTH, $multiRowsLength);
    }

    protected function setStepParams($table, $row, $limit, $multiRowsLength)
    {
        $this->getBackup()->addStepDb(array(
            self::SESSION_PARAM_TABLE  => $table,
            self::SESSION_PARAM_ROW    => $row,
            self::SESSION_PARAM_LIMIT  => $limit,
            self::SESSION_PARAM_LENGTH => $multiRowsLength,
        ));
    }

    protected function getSessionStepTable()
    {
        return $this->getSession()->getData(self::SESSION_PARAM_TABLE);
    }

    protected function getStepTable()
    {
        $dbData = $this->getBackup()->getStepDb();

        return empty($dbData[self::SESSION_PARAM_TABLE]) ? null : $dbData[self::SESSION_PARAM_TABLE];
    }

    protected function getSessionStepRow()
    {
        return (int)$this->getSession()->getData(self::SESSION_PARAM_ROW);
    }

    protected function getStepRow()
    {
        $dbData = $this->getBackup()->getStepDb();

        return empty($dbData[self::SESSION_PARAM_ROW]) ? 0 : (int)$dbData[self::SESSION_PARAM_ROW];
    }

    protected function getSessionStepLimit()
    {
        return (int)$this->getSession()->getData(self::SESSION_PARAM_LIMIT);
    }

    protected function getStepLimit()
    {
        $dbData = $this->getBackup()->getStepDb();

        return empty($dbData[self::SESSION_PARAM_LIMIT]) ? 0 : (int)$dbData[self::SESSION_PARAM_LIMIT];
    }

    protected function getSessionStepLength()
    {
        return (int)$this->getSession()->getData(self::SESSION_PARAM_LENGTH);
    }

    protected function getStepLength()
    {
        $dbData = $this->getBackup()->getStepDb();

        return empty($dbData[self::SESSION_PARAM_LENGTH]) ? 0 : (int)$dbData[self::SESSION_PARAM_LENGTH];
    }

    protected function _addBackupProcessMessage($message, $error = false)
    {
        $this->getBackup()->addBackupProcessMessage($message, $error);
    }

    /**
     * @deprecated after 2.0.0
     *
     * @param      $message
     * @param bool $error
     */
    protected function _addMessage($message, $error = false)
    {
        $this->_helper->addBackupProcessMessage($message, $error);
    }
}