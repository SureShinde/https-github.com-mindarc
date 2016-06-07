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
 * Class Mageplace_Backup_BackupController
 */
class Mageplace_Backup_BackupController extends Mage_Core_Controller_Front_Action
{
    const WRAPPER_MAX_STEP = 1000;

    public function getSessionNamespace()
    {
        return $this->_sessionNamespace;
    }

    public function backupAction($return = false)
    {
        @ignore_user_abort(true);
        @ini_set('max_execution_time', '10800');
        @set_time_limit(10800);

        $backupSecret = $this->getRequest()->getPost('secret');
        if (!$backupSecret) {
            $stepObject = $this->getStepObject()
                ->setFinished()
                ->setError($this->__("Wrong backup id code"));
				
			if($return) {
				return $stepObject;
			}
			
			echo $stepObject->toJson();

            die();
        }

        /** @var Mageplace_Backup_Model_Backup $backupModel */
        $backupModel = Mage::getModel('mpbackup/backup')
            ->loadBySecret($backupSecret)
            ->setRequest($this->getRequest());
        if (!$backupModel->getId()) {
            $stepObject = $this->getStepObject()
                ->setFinished()
                ->setError($this->__('Error backup model'));

 			if($return) {
				return $stepObject;
			}
			
			echo $stepObject->toJson();

			die();
        }

        if ($backupModel->isFinished()) {
            $stepObject = $this->getStepObject()
                ->setFinished()
                ->setError($this->__('Backup already finished'));

 			if($return) {
				return $stepObject;
			}
			
			echo $stepObject->toJson();

			die();
        }

        if ($backupModel->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_DISABLE_CACHE)) {
            $allTypes = Mage::app()->useCache();
            if (is_array($allTypes)) {
                foreach ($allTypes as $type => $value) {
                    Mage::app()->getCacheInstance()->banUse($type);
                }
            }
        }

        $stepObject = $backupModel
            ->setRequest($this->getRequest())
            ->create()
            ->getStepObject();

 		if($return) {
			return $stepObject;
		}
		
		echo $stepObject->toJson();

        exit(1);
    }

    public function finishAction()
    {
        @ignore_user_abort(true);
        @ini_set('max_execution_time', '10800');
        @set_time_limit(10800);

        $secret = $this->getRequest()->getPost('secret');

        $criticalError = $this->getRequest()->getParam('critical');
        if ($criticalError) {
            $this->_finishCriticalBackup($secret, $criticalError);
        } else {
            $error  = $this->getRequest()->getParam('error');
            $cancel = (bool)intval($this->getRequest()->getParam('cancel'));
            $this->_finishBackup($secret, $error, $cancel);
        }
    }

    protected function _finishCriticalBackup($secret, $criticalError)
    {
        Mage::getModel('mpbackup/backup')
            ->loadBySecret($secret)
            ->criticalSave($criticalError);

        exit(1);
    }

    protected function _finishBackup($secret, $error, $cancel = false)
    {
        if (!$secret) {
            echo $this->getFinishObject()
                ->setError($this->__("Can't finish backup with errors: %s", $this->__("Wrong backup id code")))
                ->toJson();

            die();
        }

        try {
            /** @var Mageplace_Backup_Model_Backup $backupModel */
            $backupModel = Mage::getModel('mpbackup/backup')
                ->loadBySecret($secret)
                ->setRequest($this->getRequest());
            if (!$backuId = $backupModel->getId()) {
                echo $this->getFinishObject()
                    ->setError($this->__("Can't finish backup with errors: %s", $this->__("Error backup model")))
                    ->toJson();

                die();
            }

            if ($backupModel->isFinished()) {
                echo $this->getFinishObject()
                    ->setFinished()
                    ->toJson();

                exit(1);
            }

            if ($backupModel->getProfileData(Mageplace_Backup_Model_Profile::COLUMN_DISABLE_CACHE)) {
                $allTypes = Mage::app()->useCache();
                if (is_array($allTypes)) {
                    foreach ($allTypes as $type => $value) {
                        Mage::app()->getCacheInstance()->banUse($type);
                    }
                }
            }

            $backupModel->finishBackupProcess($error, $cancel);
            $this->_getSession()->unsBackupId();

        } catch (Exception $e) {
            Mage::logException($e);

            $finish = false;
            if (isset($backupModel) && is_object($backupModel)) {
                if (!$backupModel->isFinished()) {
                    $finish = $backupModel->criticalSave($error);
                }
            } else {
                $finish = Mage::getModel('mpbackup/backup')->criticalSave($error);
            }

            echo $this->getFinishObject()
                ->setError($e->getMessage())
                ->setFinished($finish)
                ->toJson();

            die();
        }

        echo $this->getFinishObject()
            ->setFinished()
            ->toJson();

        exit(1);
    }

    public function cancelAction()
    {
        ignore_user_abort(true);

        $backupModel = Mage::getModel('mpbackup/backup')->loadBySecret($this->getRequest()->getPost('secret'));
        if ($backupModel->getId()) {
            $backupModel->cancelBackup();
        }
    }

    public function checkMemoryLimitAction()
    {
        ignore_user_abort(true);

        Mage::helper('mpbackup')->checkMemoryLimit(
            $this->getRequest()->getPost('backup_secret'),
            $this->getRequest()->getParam('mbytes')
        );
    }

    public function wrapperAction()
    {
        ignore_user_abort(true);

        $params      = $this->getRequest()->getPost();
        $sid         = $this->getRequest()->getParam(Mage_Core_Model_Session_Abstract::SESSION_ID_QUERY_PARAM);
        $profileId   = (int)$this->getRequest()->getParam('profile_id');
        $backupSid   = $this->getRequest()->getParam('backup_process_sid');
        $timeout     = $this->getRequest()->getParam('timeout');
        $stepCounter = $this->getRequest()->getParam('step_counter');

        $profile = Mage::getModel('mpbackup/profile')->load($profileId);
        if (!$profileId = $profile->getId()) {
            Mage::logException(new Mageplace_Backup_Exception('Wrong profile'));
        }

        if (++$stepCounter > self::WRAPPER_MAX_STEP) {
            Mage::log('Max steps count is reached');
            Mage::logException(new Mageplace_Backup_Exception('Max steps count is reached'));
            $params = array(
                'secret'   => $this->getRequest()->getParam(Mageplace_Backup_Model_Backup_Step::SO_SECRET),
                'critical' => 'Max steps count is reached'
            );
            Mage::helper('mpbackup')->request(Mage::helper('mpbackup/url')->getFinishBackupUrl(), $params, $profile, $timeout);
            /*Mage::helper('mpbackup')->request(Mage::helper('mpbackup/url')->getCancelBackupUrl($backupSid), $params, $profile, $timeout);*/
            exit;
        }

        try {
            $step = Mage::getModel('mpbackup/backup_step')->parse(
                Mage::helper('mpbackup')->request(Mage::helper('mpbackup/url')->getStepBackupUrl($backupSid), $params, $profile)
            );

            if (!$step instanceof Mageplace_Backup_Model_Backup_Step) {
                throw Mage::exception('Mageplace_Backup', strval($step));
            }

            if (!$backupSid) {
                $backupSid = $step->getSid();
            }

            if (!$step->isFinished()) {
                $params                 = $step->toArray();
                $params['step_counter'] = $stepCounter;
                if (!$sid) {
                    $sid = $this->_getSession()->getEncryptedSessionId();
                }

                try {
                    Mage::helper('mpbackup')->request(Mage::helper('mpbackup/url')->getWrapperUrl($sid, $backupSid, $profileId), $params, $profile, 1);
                } catch (Exception $e) {
                }
                exit;
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $error = $e->getMessage();
            Mage::log($error);
        }

        if (isset($error)) {
            if (empty($error)) {
                $error = Mage::helper('mpbackup')->__('Empty error body');
            }

            if (!$secret = $this->getRequest()->getParam(Mageplace_Backup_Model_Backup_Step::SO_SECRET)) {
                Mage::logException(Mage::exception('Mageplace_Backup', Mage::helper('mpbackup')->__('Backup secret code is wrong.')));
            }

            try {
                $params = array(
                    'secret' => $secret,
                    'error'  => $error
                );

                $finishObject = $this->getFinishObject()->parse(
                    Mage::helper('mpbackup')->request(Mage::helper('mpbackup/url')->getFinishBackupUrl(), $params, $profile, $timeout)
                );

                if (!$finishObject instanceof Mageplace_Backup_Model_Backup_Finish) {
                    throw Mage::exception('Mageplace_Backup', strval($finishObject));
                }

                if ($finishObject->getError()) {
                    throw Mage::exception('Mageplace_Backup', strval($finishObject->getError()));
                }
            } catch (Exception $e) {
                Mage::logException($e);
                $error .= PHP_EOL . $e->getMessage();
                $params = array(
                    'secret'   => $secret,
                    'critical' => $error
                );
                Mage::helper('mpbackup')->request(Mage::helper('mpbackup/url')->getFinishBackupUrl(), $params, $profile, $timeout);
            }
        }
    }

    protected function getStepObject()
    {
        return Mage::getModel('mpbackup/backup_step');
    }

    protected function getFinishObject()
    {
        return Mage::getModel('mpbackup/backup_finish');
    }

    protected function _getSession()
    {
        return Mage::getSingleton('core/session');
    }
}