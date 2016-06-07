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
 * @method Mageplace_Backup_Model_Backup_Progress setFinish
 * @method Mageplace_Backup_Model_Backup_Progress setError
 * @method Mageplace_Backup_Model_Backup_Progress setItems
 * @method Mageplace_Backup_Model_Backup_Progress setText
 * @method int getFinish
 * @method string getError
 * @method array getItems
 * @method string getText
 */
class Mageplace_Backup_Model_Backup_Progress extends Mageplace_Backup_Model_Backup_Abstract
{
    const FINISH = 'finish';
    const ERROR  = 'error';
    const ITEMS  = 'items';
    const TEXT   = 'text';

    const TEXT_TREE_POINT = '...';

    protected static $DATA = array(
        self::FINISH => 0,
        self::ERROR  => '',
        self::ITEMS  => array(),
        self::TEXT   => ''
    );

    public function setFinished($value = true)
    {
        $this->setData(self::FINISH, $value);

        return $this;
    }

    public function parseFile($logFile, &$startId, $finish = false)
    {
        if (!$logFile || !file_exists($logFile)) {
            return $this->setError('Log file error');
        }

        try {
            $file = new SplFileObject($logFile);
            $file->seek(!$startId ? 0 : $startId - 1);

            $counter = 0;

            $items = array();
            $item  = null;
            while ($file->valid()) {
                $row = trim($file->current());
                $file->next();

                if (!$row) {
                    continue;
                }

                $item = Mage::getModel('mpbackup/backup_progress_item')->parse($row, $item);
                $items[] = $item;

                $counter += $item->getLength();


                if (!$finish && $counter > Mageplace_Backup_Helper_Const::BACKUP_PROCESS_REQUEST_PERIOD * Mageplace_Backup_Helper_Const::BACKUP_PROCESS_RESPONSE_SIZE) {
                    break;
                }
            }

            $startId = $file->key();

        } catch (Exception $e) {
            Mage::logException($e);

            return $this->setError($e->getMessage());
        }

        if (empty($items)) {
            if ($finish) {
                return $this->setError('Log is empty (' . print_r($items, true) . ') and log process is finished');
            } else {
                return $this->setData(self::TEXT, self::TEXT_TREE_POINT)
                    ->setError(print_r($items, true));
            }
        }

        return $this->setData(self::ITEMS, $items);
    }

    public function hasErrors()
    {
        return $this->_getData(self::ERROR) != '';
    }

    public function getStaticData()
    {
        return self::$DATA;
    }

    public function __toArray(array $arrAttributes = array())
    {
        $items = $this->_getData(self::ITEMS);
        if (is_array($items)) {
            foreach ($items as $key => $item) {
                if ($item instanceof Mageplace_Backup_Model_Backup_Progress_Item) {
                    $items[$key] = $item->getData();
                } elseif (is_array($item)) {
                    $items[$key] = $item;
                } else {
                    $items[$key] = (array)$item;
                }
            }
            $this->setData(self::ITEMS, $items);
        } else {
            $this->setData(self::ITEMS, self::$DATA[self::ITEMS]);
        }

        $arrRes = array();
        foreach ($this->getStaticData() as $key => $value) {
            if (array_key_exists($key, $this->_data)) {
                $arrRes[$key] = $this->_data[$key];
            } else {
                $arrRes[$key] = $value;
            }
        }

        return $arrRes;
    }


}