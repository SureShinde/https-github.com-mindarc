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
 * Class Mageplace_Backup_Model_Backup_Finish
 *
 * @method Mageplace_Backup_Model_Backup_Finish setError
 * @method string getError
 */
class Mageplace_Backup_Model_Backup_Finish extends Mageplace_Backup_Model_Backup_Abstract
{
    const FINISH = 'finish';
    const ERROR  = 'error';

    protected static $DATA = array(
        self::FINISH => 0,
        self::ERROR  => '',
    );

    public function setFinished($value = true)
    {
        $this->setData(self::FINISH, $value);

        return $this;
    }

    public function getStaticData()
    {
        return self::$DATA;
    }
}