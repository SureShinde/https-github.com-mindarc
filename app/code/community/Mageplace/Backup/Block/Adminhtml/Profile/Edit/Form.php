<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
	/**
	 * Preperation of current form
	 *
	 * @return Mageplace_Backup_Block_Adminhtml_Profile_Edit_Form
	 */
	protected function _prepareForm()
	{
		$model = Mage::registry('mpbackup_profile');

		$form = new Varien_Data_Form();
		$form->setId('edit_form');
		$form->setAction($this->getSaveUrl());
		$form->setMethod('post');
		$form->setUseContainer(true);
		
		$form->addField('session_id',
			'hidden',
			array(
				'name' => 'session_id',
			)
		);
		
		$form->setValues($model->getData());
		
		$this->setForm($form);

		return parent::_prepareForm();
	}

	public function getSaveUrl()
	{
		return $this->getUrl('*/*/save');
	}
}