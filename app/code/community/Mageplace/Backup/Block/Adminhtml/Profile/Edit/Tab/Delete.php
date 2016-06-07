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
 * Class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_Delete
 */
class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_Delete extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry('mpbackup_profile');

        $isNew = $model->getId() ? false : true;

        $form = new Varien_Data_Form();

        $CRON_DELETE_TYPE                          = Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE;
        $CRON_DELETE_TYPE_ROTATION_NUMBER          = Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_ROTATION_NUMBER;
        $CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS = Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS;

        $CRON_SUCCESS_DELETE_EMAIL          = Mageplace_Backup_Model_Profile::CRON_SUCCESS_DELETE_EMAIL;
        $CRON_SUCCESS_DELETE_EMAIL_IDENTITY = Mageplace_Backup_Model_Profile::CRON_SUCCESS_DELETE_EMAIL_IDENTITY;
        $CRON_SUCCESS_DELETE_EMAIL_TEMPLATE = Mageplace_Backup_Model_Profile::CRON_SUCCESS_DELETE_EMAIL_TEMPLATE;

        $CRON_ERROR_EMAIL          = Mageplace_Backup_Model_Profile::CRON_ERROR_DELETE_EMAIL;
        $CRON_ERROR_EMAIL_IDENTITY = Mageplace_Backup_Model_Profile::CRON_ERROR_DELETE_EMAIL_IDENTITY;
        $CRON_ERROR_EMAIL_TEMPLATE = Mageplace_Backup_Model_Profile::CRON_ERROR_DELETE_EMAIL_TEMPLATE;

        /*
         * Delete settings fieldset
         */
        $deleteFieldset = $form->addFieldset('delete_fieldset',
            array(
                'legend' => $this->__('Delete Settings'),
            )
        );

        $deleteType = $deleteFieldset->addField($CRON_DELETE_TYPE,
            'select',
            array(
                'name'   => $CRON_DELETE_TYPE,
                'label'  => $this->__('Backup delete type'),
                'title'  => $this->__('Backup delete type'),
                'class'  => 'input-select',
                'values' => Mage::getModel('mpbackup/source_crondeletetype')->toOptionArray(),
            )
        );

        $deleteTypeRotNum = $deleteFieldset->addField($CRON_DELETE_TYPE_ROTATION_NUMBER,
            'text',
            array(
                'name'  => $CRON_DELETE_TYPE_ROTATION_NUMBER,
                'label' => $this->__('Max number of backups'),
                'title' => $this->__('Max number of backups'),
                'class' => 'input-select validate-number cron-short-text ',
            )
        );

        $deleteTypeOld = $deleteFieldset->addField($CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS,
            'text',
            array(
                'name'  => $CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS,
                'label' => $this->__('Delete backup after'),
                'title' => $this->__('Delete backup after'),
                'class' => 'input-select validate-number cron-short-text ',
                'note'  => $this->__('days')
            )
        );

        /*
        * Success email settings fieldset
        */
        $emailFieldset = $form->addFieldset('delete_email_fieldset',
            array(
                'legend' => $this->__('Success email settings'),
                'name'                  => 'delete_email_fieldset',
                'fieldset_container_id' => 'delete_email_fieldset_container',
                'class'                 => 'fieldset-wide',
            )
        );

        $emailFieldset->addField($CRON_SUCCESS_DELETE_EMAIL,
            'text',
            array(
                'name'  => $CRON_SUCCESS_DELETE_EMAIL,
                'label' => $this->__('Success Email Recipient'),
                'title' => $this->__('Success Email Recipient'),
                'class' => 'validate-email '
            )
        );

        $emailFieldset->addField($CRON_SUCCESS_DELETE_EMAIL_IDENTITY,
            'select',
            array(
                'name'   => $CRON_SUCCESS_DELETE_EMAIL_IDENTITY,
                'label'  => $this->__('Success Email Sender'),
                'title'  => $this->__('Success Email Sender'),
                'values' => Mage::getModel('adminhtml/system_config_source_email_identity')->toOptionArray(),
            )
        );

        $emailFieldset->addField($CRON_SUCCESS_DELETE_EMAIL_TEMPLATE,
            'select',
            array(
                'name'   => $CRON_SUCCESS_DELETE_EMAIL_TEMPLATE,
                'label'  => $this->__('Success Email Template'),
                'title'  => $this->__('Success Email Template'),
                'values' => Mage::getModel('adminhtml/system_config_source_email_template')->setPath('mpbackup_success_delete_email_template')->toOptionArray(),
            )
        );

        /*
         * Error email settings fieldset
         */
        $errorEmailFieldset = $form->addFieldset('delete_error_email_fieldset',
            array(
                'legend' => $this->__('Error notification email settings'),
                'name'                  => 'delete_error_email_fieldset',
                'fieldset_container_id' => 'delete_error_email_fieldset_container',
                'class'                 => 'fieldset-wide',
            )
        );

        $errorEmailFieldset->addField($CRON_ERROR_EMAIL,
            'text',
            array(
                'name'  => $CRON_ERROR_EMAIL,
                'label' => $this->__('Error Email Recipient'),
                'title' => $this->__('Error Email Recipient'),
                'class' => 'validate-email '
            )
        );

        $errorEmailFieldset->addField($CRON_ERROR_EMAIL_IDENTITY,
            'select',
            array(
                'name'   => $CRON_ERROR_EMAIL_IDENTITY,
                'label'  => $this->__('Error Email Sender'),
                'title'  => $this->__('Error Email Sender'),
                'values' => Mage::getModel('adminhtml/system_config_source_email_identity')->toOptionArray(),
            )
        );

        $errorEmailFieldset->addField($CRON_ERROR_EMAIL_TEMPLATE,
            'select',
            array(
                'name'   => $CRON_ERROR_EMAIL_TEMPLATE,
                'label'  => $this->__('Error Email Template'),
                'title'  => $this->__('Error Email Template'),
                'values' => Mage::getModel('adminhtml/system_config_source_email_template')->setPath('mpbackup_error_delete_email_template')->toOptionArray(),
            )
        );

        $fieldsetDependence = $this->getLayout()->createBlock('mpbackup/adminhtml_widget_form_element_dependence')
            ->addConfigOptions(array('levels_up' => 0))
            ->addFieldMap($emailFieldset->getHtmlId(), $emailFieldset->getName())
            ->addFieldMap($errorEmailFieldset->getHtmlId(), $errorEmailFieldset->getName())
            ->addFieldMap($deleteType->getHtmlId(), $deleteType->getName())
            ->addFieldDependence(
                $emailFieldset->getName(),
                $deleteType->getName(),
                array(
                    Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_ROTATION,
                    Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_DELETE_OLD
                )
            )
            ->addFieldDependence(
                $errorEmailFieldset->getName(),
                $deleteType->getName(),
                array(
                    Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_ROTATION,
                    Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_DELETE_OLD
                )
            );

        $this->setChild('form_after',
            $this->getLayout()->createBlock('mpbackup/adminhtml_widget_form_element_dependence')
                ->setAdditionalHtml($fieldsetDependence->toHtml())
                ->addFieldMap($deleteType->getHtmlId(), $deleteType->getName())
                ->addFieldMap($deleteTypeRotNum->getHtmlId(), $deleteTypeRotNum->getName())
                ->addFieldMap($deleteTypeOld->getHtmlId(), $deleteTypeOld->getName())
                ->addFieldDependence(
                    $deleteTypeRotNum->getName(),
                    $deleteType->getName(),
                    Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_ROTATION
                )
                ->addFieldDependence(
                    $deleteTypeOld->getName(),
                    $deleteType->getName(),
                    Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_DELETE_OLD
                )
        );

        $form->setValues($model->getData());

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
