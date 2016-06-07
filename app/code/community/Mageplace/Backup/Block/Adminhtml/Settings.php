<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Block_Adminhtml_Settings extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareLayout()
	{
		if(!$cloud_app = $this->getData('cloud_app')) {
			Mage::throwException($this->__('Local storage is selected.'));
		}

		$profile_cloud_app = strtolower(preg_replace('#[^a-z0-9]#is', '', $cloud_app));
		$block_class = 'Mageplace_Backup_Block_Adminhtml_Settings_'.ucfirst($profile_cloud_app);
		if(class_exists($block_class, false) || mageFindClassFile($block_class)) {
			$field_block = $this->getLayout()->createBlock('mpbackup/adminhtml_settings_'.$profile_cloud_app);
			$this->setChild('cloud_app_settings', $field_block);
		} 		
		
		return parent::_prepareLayout();
	}

    /**
     * Get form HTML
     *
     * @return string
     */
    public function getFormHtml()
    {
    	if (is_object($this->getChild('cloud_app_settings'))) {
            return $this->getChildHtml('cloud_app_settings');
        }
        return parent::getFormHtml();
    }
	
    
	protected function _prepareForm()
	{
		if(!$cloud_app = $this->getData('cloud_app')) {
			Mage::throwException($this->__('Storage application is not specified.')) ;
		}
		
		if(!($config = Mage::helper('mpbackup')->getAppConfig($cloud_app)) || empty($config['settings']) || !is_array($config['settings'])) {
			Mage::throwException($this->__('Wrong configuration data for cloud application "%s".', $cloud_app));
		}

		$form = new Varien_Data_Form();

		$fieldset = $form->addFieldset('app_fieldset',
			array(
				'legend'	=> $this->__('%s Settings', $config['label']),
				'class'		=> 'fieldset-wide'
			)
		);
		
		$config_values = array();
		if($profile_id = $this->getData('profile_id')) {
			$config_model = Mage::getModel('mpbackup/config');
			/* @var $config Mageplace_Backup_Model_Config */
			$config_values = $config_model->getConfigValues($profile_id, Mageplace_Backup_Model_Config::CLOUD_PATH.'/'.$cloud_app);
		}
		
		foreach($config['settings'] as $s_name=>$settings) {
			if(empty($settings['type'])) {
				continue;
			}
			
			if(empty($settings['name'])) {
				$settings['name'] = $s_name;
			}


			if(is_array($config_values) && array_key_exists($s_name, $config_values)) {
				$settings['value'] = $config_values[$s_name];
			}

			$settings['name'] = $cloud_app.$settings['name'];
			
			if(empty($settings['title']) && !empty($settings['label'])) {
				$settings['title'] = $settings['label'];
			}

			if(!empty($settings['source_model'])) {
				$settings['values'] = Mage::getModel($settings['source_model'])->toOptionArray();
				unset($settings['source_model']);
			}

			$fieldset->addField($s_name,
				$settings['type'],
				$settings
			);
		}

		$this->setForm($form);
		
		
		return parent::_prepareForm();
	}
	
}