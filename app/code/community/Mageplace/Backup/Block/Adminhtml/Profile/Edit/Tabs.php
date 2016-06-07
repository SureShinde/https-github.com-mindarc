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
 * Class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tabs
 */
class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();

        $this->setId(Mageplace_Backup_Block_Adminhtml_Profile_Edit::PAGE_TABS_BLOCK_ID);
        $this->setDestElementId('edit_form');
        $this->setTitle($this->__('Profile Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab(
            'details_section',
            array(
                'label'   => $this->__('Profile Details'),
                'title'   => $this->__('Profile Details'),
                'content' => $this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit_tab_details')->toHtml(),
            )
        );

        $this->addTab(
            'app_section',
            array(
                'label'   => $this->__('Storage Application'),
                'title'   => $this->__('Storage Application'),
                'content' => $this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit_tab_app')->toHtml(),
            )
        );

        $this->addTab(
            'multiproccess_section',
            array(
                'label'   => $this->__('Multiple Steps'),
                'title'   => $this->__('Multiple Steps'),
                'content' => $this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit_tab_multistep')->toHtml(),
            )
        );

        $this->addTab(
            'db_section',
            array(
                'label'   => $this->__('DB Tables Exclusion'),
                'title'   => $this->__('DB Tables Exclusion'),
                'content' => $this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit_tab_tables')->toHtml(),
            )
        );

        $this->addTab(
            'file_section',
            array(
                'label'   => $this->__('Files and Directories Exclusion'),
                'title'   => $this->__('Files and Directories Exclusion'),
                'content' => $this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit_tab_files')->toHtml(),
            )
        );

        $this->addTab(
            'cron_section',
            array(
                'label'   => $this->__('Cron (Scheduled Tasks)'),
                'title'   => $this->__('Cron (Scheduled Tasks)'),
                'content' => $this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit_tab_cron')->toHtml(),
            )
        );

        $this->addTab(
            'delete_section',
            array(
                'label'   => $this->__('Delete Settings'),
                'title'   => $this->__('Delete Settings'),
                'content' => $this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit_tab_delete')->toHtml(),
            )
        );

        $this->addTab(
            'log_section',
            array(
                'label'   => $this->__('Logs'),
                'title'   => $this->__('Logs'),
                'content' => $this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit_tab_logs')->toHtml(),
            )
        );

        $this->addTab(
            'auth_section',
            array(
                'label'   => $this->__('Authentication'),
                'title'   => $this->__('Authentication'),
                'content' => $this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit_tab_auth')->toHtml(),
            )
        );

        $this->_updateActiveTab();

        return parent::_beforeToHtml();
    }

    protected function _updateActiveTab()
    {
        $tabId = $this->getRequest()->getParam('tab');
        if ($tabId) {
            $tabId = preg_replace("#{$this->getId()}_#", '', $tabId);
            if ($tabId) {
                $this->setActiveTab($tabId);
            }
        }
    }
}