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
 * Class Mageplace_Backup_Model_Backup_Item
 *
 * @method Mageplace_Backup_Model_Backup_Item setSecret
 * @method Mageplace_Backup_Model_Backup_Item setStepNumber
 * @method Mageplace_Backup_Model_Backup_Item setPointNumber
 * @method Mageplace_Backup_Model_Backup_Item setError
 * @method string getSecret
 * @method int getStepNumber
 * @method int getPointNumber
 * @method string getError
 */
class Mageplace_Backup_Model_Backup_Item extends Mageplace_Backup_Model_Backup_Abstract
{
    const SECRET       = 'secret';
    const STEP_NUMBER  = 'step_number';
    const POINT_NUMBER = 'point_number';
    const ERROR        = 'error';

    protected static $DATA = array(
        self::SECRET       => '',
        self::STEP_NUMBER  => 0,
        self::POINT_NUMBER => 0,
        self::ERROR        => '',
    );

    public function getStaticData()
    {
        return self::$DATA;
    }
}