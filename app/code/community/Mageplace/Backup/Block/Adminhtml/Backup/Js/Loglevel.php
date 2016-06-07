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
 * Class Mageplace_Backup_Block_Adminhtml_Backup_Js_Loglevel
 */
class Mageplace_Backup_Block_Adminhtml_Backup_Js_Loglevel extends Mageplace_Backup_Block_Adminhtml_Backup_Js
{
    const JS_LOG_LEVEL_DEBUG   = 'DEBUG';
    const JS_LOG_LEVEL_INFO    = 'INFO';
    const JS_LOG_LEVEL_WARNING = 'WARNING';
    const JS_LOG_LEVEL_ERROR   = 'ERROR';

    static private $LOG_LEVELS = array(
        self::JS_LOG_LEVEL_DEBUG   => Mageplace_Backup_Model_Backup::LOG_LEVEL_DEBUG,
        self::JS_LOG_LEVEL_INFO    => Mageplace_Backup_Model_Backup::LOG_LEVEL_INFO,
        self::JS_LOG_LEVEL_WARNING => Mageplace_Backup_Model_Backup::LOG_LEVEL_WARNING,
        self::JS_LOG_LEVEL_ERROR   => Mageplace_Backup_Model_Backup::LOG_LEVEL_ERROR,
    );

    /**
     * @var ArrayIterator
     */
    private $_logLevelIterator;

    public function __construct()
    {
        parent::__construct();

        $this->setTemplate('mpbackup/backup/js/loglevel.phtml');

        $this->_logLevelIterator = new ArrayObject(self::$LOG_LEVELS);
        $this->_logLevelIterator = $this->_logLevelIterator->getIterator();
    }

    public function getLogLevels()
    {
        return $this->_logLevelIterator;
    }
}