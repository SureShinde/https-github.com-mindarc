<?php

/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */
class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_Logs extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Preperation of current form
     *
     * @return Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_Details
     */
    protected function _prepareForm()
    {
        $model = Mage::registry('mpbackup_profile');

        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('log_fieldset',
            array(
                'legend' => $this->__('Log Settings'),
                'class'  => 'fieldset-wide'
            )
        );

        $isNew = !$model->getProfileId() ? true : false;

        $fieldset->addField('profile_log_path',
            'text',
            array(
                'name'  => 'profile_log_path',
                'label' => $this->__('Profile Log Path'),
                'title' => $this->__('Profile Log Path'),
                'note'  => $this->__('If empty, Magento log folder (%s) will be used.', Mage::getBaseDir('log')),
            )
        );

        $fieldset->addField('profile_log_level',
            'select',
            array(
                'name'   => 'profile_log_level',
                'label'  => $this->__('Log Level'),
                'title'  => $this->__('Log Level'),
                'values' => Mage::getModel('mpbackup/source_loglevel')->toOptionArray()
            )
        );

        if ($isNew) {
            $model->setData('profile_log_level', Mageplace_Backup_Model_Backup::LOG_LEVEL_INFO);
        }

		$form->setValues($model->getData());

		$this->setForm($form);

		return parent::_prepareForm();
	}
}
