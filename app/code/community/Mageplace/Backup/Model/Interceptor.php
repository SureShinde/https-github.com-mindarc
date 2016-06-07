<?php
/**
 * Mageplace Backup
 *
 * @category       Mageplace
 * @package        Mageplace_Backup
 * @copyright      Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license        http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Model_Interceptor
 *
 * @method mixed checkMethodCall
 */
class Mageplace_Backup_Model_Interceptor
{
    const ATTEMPT_NUMBER = 10;

    static $ERRORS_RECALL = array(
        'SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction',
        'SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction',
    );

    static $ERRORS_IGNORE = array(
        'SQLSTATE[HY000]: General error: 2006 MySQL server has gone away',
        'SQLSTATE[42000]: Syntax error or access violation: 1142 SELECT command denied to user',
        'SQLSTATE[HY000]: General error: 1665 Cannot execute statement: impossible to write to binary log since BINLOG_FORMAT ='
    );

    protected $_resource;

    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $args);
        } else {
            $tries  = 0;
            $return = false;
            do {
                $retry = false;
                try {
                    if ($method == 'checkMethodCall') {
                        if (empty($args[0]) || empty($args[1])) {
                            return null;
                        }

                        $object       = array_shift($args);
                        $objectMethod = array_shift($args);
                        $return       = call_user_func_array(array($object, $objectMethod), $args);
                    } else {
                        $return = call_user_func_array(array(Mage::getResourceSingleton('mpbackup/db'), $method), $args);
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    if ($tries < self::ATTEMPT_NUMBER && in_array($message, self::$ERRORS_RECALL)) {
                        $retry = true;
                        $tries++;
                    } else {
                        $throw = true;
                        foreach (self::$ERRORS_IGNORE as $errorPart) {
                            if (stripos($message, $errorPart) !== false) {
                                $throw = false;
                                Mage::logException($e);
                                break;
                            }
                        }
                        if ($throw) {
                            throw $e;
                        }
                    }
                }
            } while ($retry);

            return $return;
        }
    }
}