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
 * Class Mageplace_Backup_Block_Adminhtml_Backup_Js_Init
 * @method Mageplace_Backup_Block_Adminhtml_Backup_Create getParentBlock()
 */
class Mageplace_Backup_Block_Adminhtml_Backup_Js_Init extends Mageplace_Backup_Block_Adminhtml_Backup_Js
{
    public function __construct()
    {
        parent::__construct();

        $this->setTemplate('mpbackup/backup/js/init.phtml');
    }

    public function getFormId()
    {
        return $this->getParentBlock()->getFormId();
    }

    public function getStartButtonId()
    {
        return $this->getParentBlock()->getStartButtonId();
    }

    public function getBackButtonId()
    {
        return $this->getParentBlock()->getBackButtonId();
    }

    public function getProgressAreaName()
    {
        return $this->getParentBlock()->getProgressAreaName();
    }

    public function isMultiStep()
    {
        return ($this->getProfile()->getProfileMultiprocessEnable() == 1);
    }

    public function isLogDisable()
    {
        return $this->getParentBlock()->isLogDisable();
    }

    public function getProcessRequestPeriod()
    {
        $period = $this->getProfile()->getProfileRequestPeriod();

        return intval($period ? $period : Mageplace_Backup_Helper_Const::BACKUP_PROCESS_REQUEST_PERIOD);
    }

    public function getChangeProfileUrl()
    {
        return $this->getUrlHelper()->getChangeProfileUrl();
    }

    public function getStartBackupUrl()
    {
        return $this->getUrlHelper()->getStartBackupUrl();
    }

    public function getStepBackupUrl()
    {
        return $this->getUrlHelper()->getStepBackupUrl();
    }

    public function getFinishBackupUrl()
    {
        return $this->getUrlHelper()->getFinishBackupUrl();
    }

    public function getCancelBackupUrl()
    {
        return $this->getUrlHelper()->getCancelBackupUrl();
    }

    public function getProgressBackupUrl()
    {
        return $this->getUrlHelper()->getProgressBackupUrl();
    }

    public function getStartBackupProgressUrl()
    {
        return $this->getUrlHelper()->getStartBackupProgressUrl();
    }

    public function getTranslatorScript()
    {
        return $this->getJsHelper()->getTranslatorScript();
    }
}