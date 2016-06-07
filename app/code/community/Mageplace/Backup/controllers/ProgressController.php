<?php

/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */
class Mageplace_Backup_ProgressController extends Mage_Core_Controller_Front_Action
{
    public function getSessionNamespace()
    {
        return $this->_sessionNamespace;
    }

    public function startAction()
    {
        try {
            $backup = Mage::getModel('mpbackup/backup')->getCurrentBackup($this->getRequest()->getPost('secret'));
            if (!$backup_id = $backup->getId()) {
                throw new Mageplace_Backup_Exception($this->__("Error data to start progress process"));
            }

            Mage::helper('mpbackup')
                ->getSession()
                ->clear()
                ->setData('backup_id', $backup_id)
                ->setData('start_id', 0)
                ->setData('log_file', $backup->getLogMessageFileName(false));

        } catch (Exception $e) {
            Mage::logException($e);

            $this->_finish($e->getMessage());
        }

        echo Mage::getSingleton('mpbackup/backup_progress')
            ->toJson();
        exit(1);
    }

    public function stageAction()
    {
        $finish = $this->getRequest()->getPost('finish') == 1;

        $session = Mage::helper('mpbackup')->getSession();

        $backup_id = (int)$session->getData('backup_id');
        $start_id  = (int)$session->getData('start_id');
        $log_file  = $session->getData('log_file');

        if (!$backup_id || !$log_file) {
            $this->_finish('Wrong backup id or log file: ' . $backup_id . ' - ' . $log_file);
        }

        $logs = Mage::getSingleton('mpbackup/backup_progress')
            ->parseFile($log_file, $start_id, $finish);
        if ($logs->hasErrors()) {
            if ($logs->getText()) {
                echo $logs->toJson();
                exit(1);
            } else {
                $this->_finish($logs);
            }
        }

        $session->setData('start_id', $start_id);

        if ($finish) {
            $this->_finish('finished' /*$logs*/);
        }

        echo $logs->toJson();
        exit(1);
    }

    protected function _finish($logs = null)
    {
        Mage::helper('mpbackup')->getSession()->clear();

        if (is_string($logs)) {
            $logs = Mage::getSingleton('mpbackup/backup_progress')->setError($logs);
        } elseif (!$logs instanceof Mageplace_Backup_Model_Backup_Progress) {
            $logs = Mage::getSingleton('mpbackup/backup_progress');
        }

        echo $logs->setFinished()->toJson();
        exit(1);
    }

    protected function getProgressSession()
    {
        return Mage::helper('mpbackup')->getSession();
    }
}