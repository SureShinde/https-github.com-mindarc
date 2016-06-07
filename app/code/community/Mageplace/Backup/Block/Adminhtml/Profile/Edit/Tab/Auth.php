<?php

/**
 * Mageplace Magesocial
 *
 * @category   Mageplace
 * @package    Mageplace_Magesocial
 * @copyright  Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */
class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_Auth extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Preperation of current form
     *
     * @return Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_Cron
     */
    protected function _prepareForm()
    {
        $model = Mage::registry('mpbackup_profile');

        $isNew = $model->getId() ? false : true;

        $form = new Varien_Data_Form();

        /*
         * Auth settings fieldset
         */

        $ENABLE        = Mageplace_Backup_Model_Profile::COLUMN_AUTH_ENABLE;
        $AUTH_USER     = Mageplace_Backup_Model_Profile::COLUMN_AUTH_USER;
        $AUTH_PASSWORD = Mageplace_Backup_Model_Profile::COLUMN_AUTH_PASSWORD;

        $authFieldset = $form->addFieldset('auth_fieldset',
            array(
                'legend' => $this->__('Authentication settings'),
            )
        );

        $authFieldset->addField('cron_auth_note',
            'note',
            array(
                'text' => $this->__('Enable and fill in the settings below in case the site is protected with Apache authentication'),
            )
        );

        $enable = $authFieldset->addField($ENABLE,
            'select',
            array(
                'name'   => $ENABLE,
                'label'  => $this->__('Enable'),
                'title'  => $this->__('Enable'),
                'class'  => 'input-select',
                'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            )
        );

        $authUser = $authFieldset->addField($AUTH_USER,
            'text',
            array(
                'name'  => $AUTH_USER,
                'label' => $this->__('User'),
                'title' => $this->__('User'),
            )
        );

        $authPassword = $authFieldset->addField($AUTH_PASSWORD,
            'password',
            array(
                'name'  => $AUTH_PASSWORD,
                'label' => $this->__('Password'),
                'title' => $this->__('Password'),
            )
        );

        $form->setValues($model->getData());

        $this->setForm($form);

        $this->setChild('form_after',
            $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
                ->addFieldMap($enable->getHtmlId(), $enable->getName())
                ->addFieldMap($authUser->getHtmlId(), $authUser->getName())
                ->addFieldMap($authPassword->getHtmlId(), $authPassword->getName())
                ->addFieldDependence(
                    $authUser->getName(),
                    $enable->getName(),
                    1
                )
                ->addFieldDependence(
                    $authPassword->getName(),
                    $enable->getName(),
                    1
                )
        );

        return parent::_prepareForm();
    }
}
