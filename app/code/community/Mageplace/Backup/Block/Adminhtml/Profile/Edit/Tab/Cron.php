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
 * Class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_Cron
 */
class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_Cron extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry('mpbackup_profile');

        $isNew = $model->getId() ? false : true;

        $form = new Varien_Data_Form();

        $CRON_ENABLE    = Mageplace_Backup_Model_Profile::CRON_ENABLE;
        $CRON_TIME_TYPE = Mageplace_Backup_Model_Profile::CRON_TIME_TYPE;
        $CRON_TIME      = Mageplace_Backup_Model_Profile::CRON_TIME;
        $CRON_EXPR      = Mageplace_Backup_Model_Profile::CRON_BACKUP_EXPR;

        $CRON_FAILURE_RUNNING = Mageplace_Backup_Model_Profile::CRON_FAILURE_RUNNING;

        $CRON_SUCCESS_EMAIL           = Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL;
        $CRON_SUCCESS_EMAIL_IDENTITY  = Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL_IDENTITY;
        $CRON_SUCCESS_EMAIL_TEMPLATE  = Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL_TEMPLATE;
        $CRON_SUCCESS_EMAIL_LOG_LEVEL = Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL_LOG_LEVEL;

        $CRON_ERROR_EMAIL          = Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL;
        $CRON_ERROR_EMAIL_IDENTITY = Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL_IDENTITY;
        $CRON_ERROR_EMAIL_TEMPLATE = Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL_TEMPLATE;

        /*
         * Cron backup settings fieldset
         */
        $fieldset = $form->addFieldset('base_fieldset',
            array(
                'legend' => $this->__('Backup settings'),
            )
        );

        $enable = $fieldset->addField($CRON_ENABLE,
            'select',
            array(
                'name'   => $CRON_ENABLE,
                'label'  => $this->__('Enable Cron Backup'),
                'title'  => $this->__('Enable Cron Backup'),
                'class'  => 'input-select',
                'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            )
        );

        $fieldset->addType('crontime', Mage::getConfig()->getBlockClassName('mpbackup/form_element_crontime'));

        $cronTime = $fieldset->addField($CRON_TIME,
            'crontime',
            array(
                'name'                 => $CRON_TIME,
                'cron_expression_type' => $model->getData($CRON_TIME_TYPE),
                'cron_expression'      => $model->getData($CRON_EXPR),
                'label'                => $this->__('Cron Expression'),
                'title'                => $this->__('Cron Expression'),
                'label_custom'         => $this->__('Custom'),
                'label_default'        => $this->__('Default'),
                'cron_expr_note'       => $this->__('Expression example: */5 * * * *'),
                'frequency_time_note'  => $this->__('Frequency  Hours : Minutes'),
            )
        );

        $cron_failure_running = $model->getData($CRON_FAILURE_RUNNING);
        if (is_null($cron_failure_running) || $cron_failure_running === '') {
            $cron_failure_running = Mageplace_Backup_Model_Profile::CRON_FAILURE_RUNNING_DEFAULT;
        }
        $model->setData($CRON_FAILURE_RUNNING, $cron_failure_running);

        $cronFailureRunning = $fieldset->addField($CRON_FAILURE_RUNNING,
            'text',
            array(
                'name'               => $CRON_FAILURE_RUNNING,
                'label'              => $this->__('Failure if running more then'),
                'title'              => $this->__('Failure if running more then'),
                'class'              => 'input-select cron-short-text validate-number ',
                'after_element_html' => $this->__('minutes'),
            )
        );

        /*
        * Success email settings fieldset
        */
        $emailFieldset = $form->addFieldset('cron_email_fieldset',
            array(
                'legend'                => $this->__('Success email settings'),
                'name'                  => 'cron_email_fieldset',
                'fieldset_container_id' => 'cron_email_fieldset_container',
                'class'                 => 'fieldset-wide',
            )
        );

        $emailFieldset->addField($CRON_SUCCESS_EMAIL,
            'text',
            array(
                'name'  => $CRON_SUCCESS_EMAIL,
                'label' => $this->__('Success Email Recipient'),
                'title' => $this->__('Success Email Recipient'),
                'class' => 'validate-email ',
            )
        );

        $emailFieldset->addField($CRON_SUCCESS_EMAIL_IDENTITY,
            'select',
            array(
                'name'   => $CRON_SUCCESS_EMAIL_IDENTITY,
                'label'  => $this->__('Success Email Sender'),
                'title'  => $this->__('Success Email Sender'),
                'class'  => 'input-select',
                'values' => Mage::getModel('adminhtml/system_config_source_email_identity')->toOptionArray(),
            )
        );

        $emailFieldset->addField($CRON_SUCCESS_EMAIL_TEMPLATE,
            'select',
            array(
                'name'   => $CRON_SUCCESS_EMAIL_TEMPLATE,
                'label'  => $this->__('Success Email Template'),
                'title'  => $this->__('Success Email Template'),
                'class'  => 'input-select',
                'values' => Mage::getModel('adminhtml/system_config_source_email_template')->setPath('mpbackup_success_email_template')->toOptionArray(),
            )
        );

        $emailFieldset->addField($CRON_SUCCESS_EMAIL_LOG_LEVEL,
            'select',
            array(
                'name'   => $CRON_SUCCESS_EMAIL_LOG_LEVEL,
                'label'  => $this->__('Success Email Log Level'),
                'title'  => $this->__('Success Email Log Level'),
                'class'  => 'input-select',
                'values' => Mage::getModel('mpbackup/source_loglevel')->cronOptionArray(),
            )
        );

        if ($isNew) {
            $model->setData($CRON_SUCCESS_EMAIL_LOG_LEVEL, Mageplace_Backup_Model_Backup::LOG_LEVEL_INFO);
        }

        /*
         * Error email settings fieldset
         */
        $errorEmailFieldset = $form->addFieldset('cron_error_email_fieldset',
            array(
                'legend'                => $this->__('Error notification email settings'),
                'name'                  => 'cron_error_email_fieldset',
                'fieldset_container_id' => 'cron_error_email_fieldset_container',
                'class'                 => 'fieldset-wide',
            )
        );

        $errorEmailFieldset->addField($CRON_ERROR_EMAIL,
            'text',
            array(
                'name'  => $CRON_ERROR_EMAIL,
                'label' => $this->__('Error Email Recipient'),
                'title' => $this->__('Error Email Recipient'),
                'class' => 'validate-email ',
            )
        );

        $errorEmailFieldset->addField($CRON_ERROR_EMAIL_IDENTITY,
            'select',
            array(
                'name'   => $CRON_ERROR_EMAIL_IDENTITY,
                'label'  => $this->__('Error Email Sender'),
                'title'  => $this->__('Error Email Sender'),
                'class'  => 'input-select',
                'values' => Mage::getModel('adminhtml/system_config_source_email_identity')->toOptionArray(),
            )
        );

        $errorEmailFieldset->addField($CRON_ERROR_EMAIL_TEMPLATE,
            'select',
            array(
                'name'   => $CRON_ERROR_EMAIL_TEMPLATE,
                'label'  => $this->__('Error Email Template'),
                'title'  => $this->__('Error Email Template'),
                'class'  => 'input-select',
                'values' => Mage::getModel('adminhtml/system_config_source_email_template')->setPath('mpbackup_error_email_template')->toOptionArray(),
            )
        );

        $fieldsetDependence = $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
            ->addConfigOptions(array('levels_up' => 0))
            ->addFieldMap($enable->getHtmlId(), $enable->getName())
            ->addFieldMap($emailFieldset->getHtmlId(), $emailFieldset->getName())
            ->addFieldMap($errorEmailFieldset->getHtmlId(), $errorEmailFieldset->getName())
            ->addFieldDependence(
                $emailFieldset->getName(),
                $enable->getName(),
                1
            )
            ->addFieldDependence(
                $errorEmailFieldset->getName(),
                $enable->getName(),
                1
            );

        $this->setChild('form_after',
            $this->getLayout()->createBlock('mpbackup/adminhtml_widget_form_element_dependence')
                ->setAdditionalHtml($fieldsetDependence->toHtml())
                ->addFieldMap($enable->getHtmlId(), $enable->getName())
                ->addFieldMap($cronTime->getHtmlId(), $cronTime->getName())
                ->addFieldMap($cronFailureRunning->getHtmlId(), $cronFailureRunning->getName())
                ->addFieldDependence(
                    $cronTime->getName(),
                    $enable->getName(),
                    1
                )
                ->addFieldDependence(
                    $cronFailureRunning->getName(),
                    $enable->getName(),
                    1
                )
        );

        $form->setValues($model->getData());

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
