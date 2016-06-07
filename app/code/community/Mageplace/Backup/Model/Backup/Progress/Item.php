<?php
/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Model_Backup_Process
 *
 * @method Mageplace_Backup_Model_Backup_Progress_Item setDate
 * @method Mageplace_Backup_Model_Backup_Progress_Item setType
 * @method Mageplace_Backup_Model_Backup_Progress_Item setMpu
 * @method Mageplace_Backup_Model_Backup_Progress_Item setLog
 * @method string getDate
 * @method string getType
 * @method string getMpu
 * @method string getLog
 */
class Mageplace_Backup_Model_Backup_Progress_Item extends Mageplace_Backup_Model_Backup_Abstract
{
    const DATE = 'date';
    const TYPE = 'type';
    const MPU  = 'mpu';
    const LOG  = 'log';

    const LENGTH = 'length';

    protected static $DATA = array(
        self::DATE => '',
        self::TYPE => '',
        self::MPU  => '',
        self::LOG  => ''
    );

    protected static $APPROVED = array(
        self::LENGTH
    );

    public function getStaticData()
    {
        return self::$DATA;
    }

    public function getApprovedKeys()
    {
        return self::$APPROVED;
    }

    public function parse($row, $prevRow = null)
    {
        $length   = 0;
        $logItem  = $this->getStaticData();
        $isParsed = preg_match(Mageplace_Backup_Model_Backup::MESSAGE_REGEXP_TEMPLATE, $row, $matches);
        array_shift($matches);
        if ($isParsed && !empty($matches) && count($matches) == count($logItem)) {
            foreach ($logItem as $key => $value) {
                $log = array_shift($matches);
                $this->setData($key, $log);
                $length += strlen($log);
            }

        } else {
            $length = strlen($row);
            $this->setData(self::LOG, $row);
        }

        $this->setData('length', $length);

        return $this;
    }

    public function getLength()
    {
        if ($this->_getData('length') === null) {
            $this->setData('length', strlen(implode('', $this->getData())));
        }

        return $this->_getData('length');
    }
}