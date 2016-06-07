<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package        Mageplace_Backup
 * @copyright    Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license        http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Block_Adminhtml_Backup_Create_Form extends Mage_Adminhtml_Block_Widget_Form
{
	/**
	 * Preperation of current form
	 *
	 * @return Mageplace_Backup_Block_Adminhtml_Profile_Create_Form
	 */
	protected function _prepareForm()
	{
		$model = Mage::registry('mpbackup_backup');

		$id = $model->getId();

		$form = new Varien_Data_Form();

		$fieldset = $form->addFieldset('base_fieldset',
			array(
				'legend' => $this->__('Backup Details'),
				'class' => 'fieldset-wide'
			)
		);

		$fieldset->addField('profile_id',
			'select',
			array(
				'name' => 'profile_id',
				'label' => $this->__('Profile'),
				'title' => $this->__('Profile'),
				'required' => true,
				'disabled' => ($id ? true : false),
				'values' => $this->_getProfilesForForm(),
				'onchange' => 'mpbackup.changeProfile();'
			)
		);

		$fieldset->addField('backup_name',
			'text',
			array(
				'name' => 'backup_name',
				'label' => $this->__('Backup Name'),
				'title' => $this->__('Backup Name'),
//				'required' => true,
				'note' => $this->__("If empty, random file name will be generated."),
			)
		);

		if (!$id) {
			$fieldset->addField('backup_filename',
				'text',
				array(
					'name' => 'backup_filename',
					'label' => $this->__('Backup File Name Prefix'),
					'title' => $this->__('Backup File Name Prefix'),
//					'note' => $this->__("If empty, random file name will be generated."),
				)
			);
		}

		$fieldset->addField('backup_description',
			'textarea',
			array(
				'name' => 'backup_description',
				'label' => $this->__('Backup Description'),
				'title' => $this->__('Backup Description'),
			)
		);


		if (!$id) {
			$fieldset->addField('backup_files',
				'hidden',
				array(
					'name' => 'backup_files'
				)
			);

			if (!$this->isLogDisable()) {
				$fieldset_progress = $form->addFieldset('progress_fieldset',
					array(
						'legend' => $this->__('Progress of the backup process'),
						'class' => 'fieldset-wide'
					)
				);

				$fieldset_progress->addField('progress_area',
					'note',
					array(
						'name' => 'progress_area',
						'label' => $this->__('Backup Progress'),
						'title' => $this->__('Backup Progress'),
						'text' => '<div id="' . $this->getParentBlock()->getProgressAreaName() . '" style="width:100%; height:350px; overflow:auto;"></div>',
					)
				);
			}
		} else {
			$fieldset_progress = $form->addFieldset('bufiles_fieldset',
				array(
					'legend' => $this->__('Backup files'),
					'class' => 'fieldset-wide'
				)
			);

			if ($files = $model->getBackupFiles()) {
				$fieldset_progress->addField('files_area',
					'note',
					array(
						'name' => 'files_area',
						'label' => $this->__('Local Files'),
						'title' => $this->__('Local Files'),
						'text' => implode('<br />', explode(';', $files)),
					)
				);
			}

			if ($log_file = $model->getBackupLogFile()) {
				$fieldset_progress->addField('log_file_area',
					'note',
					array(
						'name' => 'log_file_area',
						'label' => $this->__('Log File'),
						'title' => $this->__('Log File'),
						'text' => $log_file,
					)
				);
			}

			if ($cloud_files = $model->getBackupCloudFiles()) {
				$fieldset_progress->addField('cloud_files_area',
					'note',
					array(
						'name' => 'cloud_files_area',
						'label' => $this->__('Cloud Server Files'),
						'title' => $this->__('Cloud Server Files'),
						'text' => implode('<br />', explode(';', $cloud_files)),
					)
				);
			}

			$fieldset_log = $form->addFieldset('log_fieldset',
				array(
					'legend' => $this->__('Backup logs'),
					'class' => 'fieldset-wide'
				)
			);

			if ($errors = $model->getBackupErrors()) {
				$fieldset_log->addField('errors_area',
					'note',
					array(
						'name' => 'errors_area',
						'label' => $this->__('Backup Errors'),
						'title' => $this->__('Backup Errors'),
						'text' => $errors,
					)
				);
			}

			if (($log_file_path = $model->getBackupLogFile()) && file_exists($this->getProfile()->getData('profile_log_path') . DS . $log_file_path)) {
				$fieldset_log->addField('logs_area',
					'textarea',
					array(
						'name' => 'logs_area',
						'label' => $this->__('Backup Log'),
						'title' => $this->__('Backup Log'),
						'disabled' => 1
					)
				);
				$model->setData('logs_area', @file_get_contents($this->getProfile()->getData('profile_log_path') . DS . $log_file_path));
			}


			$fieldset->addField('backup_id',
				'hidden',
				array(
					'name' => 'backup_id'
				)
			);
		}

		$form->setValues($model->getData());
		$form->setUseContainer(true);
		$form->setId($this->getParentBlock()->getFormId());
		$form->setMethod('post');

		$form->setAction($this->getSaveUrl());
		if (!$id) {
			$form->setOnsubmit($this->getParentBlock()->getStartJSFunction());
		}

		$this->setForm($form);

		return parent::_prepareForm();
	}

	public function getProfile()
	{
		return Mage::registry('mpbackup_profile');
	}

    /**
     * @see Mageplace_Backup_Block_Adminhtml_Backup_Create::isLogDisable()
     * @return boolean
     */
    public function isLogDisable()
	{
		return $this->getParentBlock()->isLogDisable();
	}

	/**
	 * Helper function to load profile collection
	 */
	protected function _getProfilesForForm()
	{
		return Mage::getResourceModel('mpbackup/profile_collection')->toOptionArray();
	}

	/**
	 * Helper function to load applications array
	 */
	protected function _getAppsForForm()
	{
		return Mage::helper('mpbackup')->getAppsOptionArray();
	}

	public function getSaveUrl()
	{
		return $this->getUrl('*/*/save');
	}
}
