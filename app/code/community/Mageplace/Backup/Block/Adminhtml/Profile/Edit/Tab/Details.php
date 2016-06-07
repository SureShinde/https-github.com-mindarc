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
 * Class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_Details
 */
class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_Details extends Mage_Adminhtml_Block_Widget_Form
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

        $fieldset = $form->addFieldset('base_fieldset',
            array(
                'legend' => $this->__('Profile Details'),
                'class'  => 'fieldset-wide'
            )
        );

        $isNew = !$model->getProfileId() ? true : false;

        $fieldset->addField('profile_name',
            'text',
            array(
                'name'     => 'profile_name',
                'label'    => $this->__('Profile Name'),
                'title'    => $this->__('Profile Name'),
                'required' => true,
            )
        );

        $fieldset->addField('profile_backup_filename_suffix',
            'text',
            array(
                'name'  => 'profile_backup_filename_suffix',
                'label' => $this->__('Profile Backup Filename Suffix'),
                'title' => $this->__('Profile Backup Filename Suffix'),
                'note'  => $this->__('Max 10 characters. Only letters, digits and characters \'-\' and \'_\'.'),
            )
        );

        $fieldset->addField('profile_default',
            'select',
            array(
                'name'   => 'profile_default',
                'label'  => $this->__('Defaut Profile'),
                'title'  => $this->__('Defaut Profile'),
                'class'  => 'input-select',
                'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            )
        );

        $fieldset->addField('profile_type',
            'select',
            array(
                'name'   => 'profile_type',
                'label'  => $this->__('Profile Type'),
                'title'  => $this->__('Profile Type'),
                'class'  => 'input-select',
                'values' => Mage::getModel('mpbackup/source_profiletype')->toOptionArray(),
            )
        );

        $fieldset->addField('profile_backup_path',
            'text',
            array(
                'name'  => 'profile_backup_path',
                'label' => $this->__('Profile Backup Path'),
                'title' => $this->__('Profile Backup Path'),
                'note'  => $this->__('If empty, folder \'%s\' will be used.', Mage::getBaseDir('var') . DS . Mageplace_Backup_Helper_Data::BACKUP_DIR)
                    . '<br /><b style="color:red;">'
                    . $this->__('Attention: in order to avoid possible interference with native Magento Backup, don\'t use native Magento Backup folder (%s)!', Mage::getBaseDir('var') . DS . 'backups')
                    . '</b>',
            )
        );

        $fieldset->addField('profile_check_memory_limit',
            'select',
            array(
                'name'   => 'profile_check_memory_limit',
                'label'  => $this->__('Check Memory Limit'),
                'title'  => $this->__('Check Memory Limit'),
                'class'  => 'input-select',
                'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            )
        );

        $fieldset->addField('profile_free_disk_space',
            'text',
            array(
                'name'  => 'profile_free_disk_space',
                'label' => $this->__('Total Free Space Before Backup Start'),
                'title' => $this->__('Total Free Space Before Backup Start'),
                'class' => 'validate-number ',
                'note'  => $this->__('In Mb. If empty, free space will not be checked.'),
            )
        );

        $fieldset->addField('profile_backup_error_delete_local',
            'select',
            array(
                'name'   => 'profile_backup_error_delete_local',
                'label'  => $this->__('Delete unnesessary files from local server'),
                'title'  => $this->__('Delete unnesessary files from local server'),
                'class'  => 'input-select',
                'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
                'note'   => $this->__('Delete unnesessary backup files from local server if backup process has errors'),
            )
        );

        $fieldset->addField('profile_backup_error_delete_cloud',
            'select',
            array(
                'name'   => 'profile_backup_error_delete_cloud',
                'label'  => $this->__('Delete unnesessary files from cloud server'),
                'title'  => $this->__('Delete unnesessary files from cloud server'),
                'class'  => 'input-select',
                'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
                'note'   => $this->__('Delete unnesessary backup files from cloud server if backup process has errors'),
            )
        );

        $fieldset->addField(Mageplace_Backup_Model_Profile::COLUMN_DISABLE_CACHE,
            'select',
            array(
                'name'   => Mageplace_Backup_Model_Profile::COLUMN_DISABLE_CACHE,
                'label'  => $this->__('Disable cache during backup process'),
                'title'  => $this->__('Disable cache during backup process'),
                'class'  => 'input-select',
                'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            )
        );

        if ($isNew) {
            $model->setData('profile_local_copy', 1);
        } else {
            $fieldset->addField('profile_id',
                'hidden',
                array(
                    'name' => 'profile_id',
                    'id'   => 'profile_id'
                )
            );
        }

        $form->setValues($model->getData());

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
