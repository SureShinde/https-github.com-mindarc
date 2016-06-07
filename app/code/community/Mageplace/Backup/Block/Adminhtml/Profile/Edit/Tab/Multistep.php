<?php

/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */
class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_Multistep extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry('mpbackup_profile');

        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('multistep_fieldset',
            array(
                'legend' => $this->__('Multiple Steps Settings'),
                'class'  => 'fieldset-wide'
            )
        );

        $isNew = !$model->getId() ? true : false;
        if ($isNew) {
            $model->setProfileMultiprocessEnable(1);
            $model->setProfileMultiprocessCronEnable(1);
            $model->setProfileMultiprocessTime(0);
            $model->setProfileMultiprocessCronTime(0);
        }

        $enable = $fieldset->addField(Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_ENABLE,
            'select',
            array(
                'name'   => Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_ENABLE,
                'label'  => $this->__('Manual Process'),
                'title'  => $this->__('Manual Process'),
                'class'  => 'input-select',
                'values' => Mage::getModel('mpbackup/source_enabledisable')->toOptionArray(),
            )
        );

        $time = $fieldset->addField(Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_TIME,
            'text',
            array(
                'name'  => Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_TIME,
                'label' => $this->__('Process Time'),
                'title' => $this->__('Process Time'),
                'class' => 'validate-number ',
                'note'  => $this->__('In seconds. Must be bigger than %s or equal to 0 to disable multi step time based process.', Mageplace_Backup_Model_Backup::MULTI_STEP_MIN_TIME),
            )
        );

        $enableCron = $fieldset->addField(Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_CRON_ENABLE,
            'select',
            array(
                'name'   => Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_CRON_ENABLE,
                'label'  => $this->__('Cron Process'),
                'title'  => $this->__('Cron Process'),
                'class'  => 'input-select',
                'values' => Mage::getModel('mpbackup/source_enabledisable')->toOptionArray(),
            )
        );

        $timeCron = $fieldset->addField(Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_CRON_TIME,
            'text',
            array(
                'name'  => Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_CRON_TIME,
                'label' => $this->__('Cron Process Time'),
                'title' => $this->__('Cron Process Time'),
                'class' => 'validate-number ',
                'note'  => $this->__('In seconds. Must be bigger than %s or equal to 0 to disable multi step time based process.', Mageplace_Backup_Model_Backup::MULTI_STEP_MIN_TIME),
            )
        );

        $form->setValues($model->getData());

        $this->setForm($form);

        $this->setChild('form_after',
            $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
                ->addFieldMap($enable->getHtmlId(), $enable->getName())
                ->addFieldMap($time->getHtmlId(), $time->getName())
                ->addFieldMap($enableCron->getHtmlId(), $enableCron->getName())
                ->addFieldMap($timeCron->getHtmlId(), $timeCron->getName())
                ->addFieldDependence(
                    $time->getName(),
                    $enable->getName(),
                    1
                )
                ->addFieldDependence(
                    $timeCron->getName(),
                    $enableCron->getName(),
                    1
                )
        );

        return parent::_prepareForm();
    }
}