<?php

/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */
class Mageplace_Backup_Block_Adminhtml_Backup_Create extends Mage_Adminhtml_Block_Widget_Form_Container
{
    const PROGRESS_AREA_NAME = 'progressarea';
    const START_BUTTON_ID    = 'mpbackupstartbutton';
    const BACK_BUTTON_ID     = 'mpbackupbackbutton';
    const FORM_ID            = 'mpbackupcreateform';

    protected $_objectId = 'backup_id';
    protected $_blockGroup = 'mpbackup';
    protected $_controller = 'adminhtml_backup';
    protected $_mode = 'create';

    /**
     * Constructor for the category edit form
     */
    public function __construct()
    {
        parent::__construct();

        $this->_removeButton('reset');

        if (!$backupId = Mage::registry('mpbackup_backup')->getId()) {
            $this->_removeButton('delete');
            $this->_removeButton('save');
            $this->_addButton('start',
                array(
                    'label'   => $this->__('Backup Now!'),
                    'onclick' => $this->getStartJSFunction(),
                    'class'   => 'save mpbackuploaddisable mpbackupprocessdisable',
                    'id'      => $this->getStartButtonId(),
                    'style'   => 'display:none;'
                ),
                -100
            );
        } else {
            $this->_updateButton('delete', 'label', $this->__('Delete record and files'));

            $this->_addButton('deleteRecord',
                array(
                    'label'   => $this->__('Delete record'),
                    'onclick' => 'setLocation(\'' . $this->getUrl('*/*/deleteRecord', array('backup_id' => $backupId)) . '\')',
                    'class'   => 'delete',
                )
            );
        }

        $this->_updateButton('back', 'id', $this->getBackButtonId());
        $this->_updateButton('back', 'class', 'back mpbackupprocessdisable');
        $this->_updateButton('back', 'after_html', $this->getBackButtonAfterHtml());
    }

    public function getHeaderText()
    {
        return $this->__('Create Backup');
    }

    public function getHeaderCssClass()
    {
        return '';
    }

    public function getStartJSFunction()
    {
        return 'mpbackup.start()';
    }

    public function getFormId()
    {
        return self::FORM_ID;
    }

    public function getStartButtonId()
    {
        return self::START_BUTTON_ID;
    }

    public function getBackButtonId()
    {
        return self::BACK_BUTTON_ID;
    }

    public function getProgressAreaName()
    {
        return self::PROGRESS_AREA_NAME;
    }

    /**
     * @return Mageplace_Backup_Model_Profile
     */
    public function getProfile()
    {
        return Mage::registry('mpbackup_profile');
    }

    public function getLogLevel()
    {
        if(is_null($this->_getData('log_level'))) {
            $profile = $this->getProfile();
            if ($profile && $profile->getProfileLogLevel()) {
                $logLevel = $profile->getProfileLogLevel();
            } else {
                $logLevel = 'ALL';
            }

            $this->setData('log_level', $logLevel);
        }


        return $this->_getData('log_level');
    }

    public function isLogDisable()
    {
        return ($this->getLogLevel() == 'OFF');
    }

    protected function _toHtml()
    {
        $this->_formScripts[] = $this->getChildHtml('mpbackup_backup_js_init');

        return parent::_toHtml();
    }
}