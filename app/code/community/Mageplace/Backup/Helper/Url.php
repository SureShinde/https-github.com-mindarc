<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Helper_Url
 */
class Mageplace_Backup_Helper_Url extends Mage_Core_Helper_Abstract
{
    public function getChangeProfileUrl()
    {
        return Mage::getModel('adminhtml/url')->getUrl('*/*/create');
    }

    public function getCheckMemoryLimitUrl()
    {
        return Mage::getUrl('mpbackup/backup/checkMemoryLimit', array('_secure' => $this->_isSecure() ? 1 : 0));
    }

    public function getStartBackupUrl()
    {
        return Mage::getModel('adminhtml/url')->getUrl('*/*/start', array('ajax' => 1));
    }

    public function getStepBackupUrl($sid = null)
    {
        $params = array(
            'ajax'    => 1,
            '_secure' => $this->_isSecure() ? 1 : 0
        );

        if ($sid) {
            $params['_query'][Mage_Core_Model_Session_Abstract::SESSION_ID_QUERY_PARAM] = $sid;
        }

        return Mage::getUrl('mpbackup/backup/backup', $params);
    }

    public function getWrapperUrl($sid = null, $backupSid = null, $profileId = null)
    {
        $params['_secure'] = 1;

        if ($sid) {
            $params['_query'][Mage_Core_Model_Session_Abstract::SESSION_ID_QUERY_PARAM] = $sid;
        }

        if ($backupSid) {
            $params['_query']['backup_process_sid'] = $backupSid;
        }

        if($profileId) {
            $params['_query']['profile_id'] = $profileId;
        }

        return Mage::getUrl('mpbackup/backup/wrapper', $params);

    }

    public function getFinishBackupUrl()
    {
        return Mage::getUrl('mpbackup/backup/finish', array('ajax' => 1, '_secure' => $this->_isSecure() ? 1 : 0));
    }

    public function getCancelBackupUrl($sid = null)
    {
        $params = array(
            'ajax' => 1,
            '_secure' => $this->_isSecure() ? 1 : 0
        );

        if ($sid) {
            $params['_query'][Mage_Core_Model_Session_Abstract::SESSION_ID_QUERY_PARAM] = $sid;
        }

        return Mage::getUrl('mpbackup/backup/cancel', $params);
    }

    public function getProgressBackupUrl()
    {
        return Mage::getUrl('mpbackup/progress/stage', array('ajax' => 1, '_secure' => $this->_isSecure() ? 1 : 0));
    }

    public function getStartBackupProgressUrl()
    {
        return Mage::getUrl('mpbackup/progress/start', array('ajax' => 1, '_secure' => $this->_isSecure() ? 1 : 0));
    }

    protected function _isSecure()
    {
        static $secure;

        if (is_null($secure)) {
            $secure = Mage::app()->getFrontController()->getRequest()->isSecure();
        }

        return $secure;
    }
}