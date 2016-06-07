<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_App extends Mage_Adminhtml_Block_Widget_Form
{
	const APP_SETTINGS_AREA_ID = 'profile_cloud_app_settings';
	
	/**
	 * Preperation of current form
	 *
	 * @return Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_App
	 */
	protected function _prepareForm()
	{
		$model = Mage::registry('mpbackup_profile');

		$isNew = !$model->getProfileId() ? true : false;

		$form = new Varien_Data_Form();

		$fieldset = $form->addFieldset('app_fieldset',
			array(
				'legend'	=> $this->__('Storage Application'),
				'class'		=> 'fieldset-wide'
			)
		);
				
		$fieldset->addField('profile_cloud_app',
			'select',
			array(
				'name'		=> $this->getProfileCloudAppId(),
				'label'		=> $this->__('Storage Application'),
				'title'		=> $this->__('Storage Application'),
				'values'	=> $this->_getAppsForForm(),
			)
		);

		$fieldset->addField('profile_local_copy',
			'select',
			array(
				'name'		=> 'profile_local_copy',
				'label'		=> $this->__('Save local copy'),
				'title'		=> $this->__('Save local copy'),
				'class' 	=> 'input-select',
				'values'	=> Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
				'disabled'	=> ($isNew || !$model->getData('profile_cloud_app') ? true : false)
			)
		);

		$fieldset->addField('profile_cloud_app_settings',
			'note',
			array(
				'label'		=> $this->__('Storage Application Settings'),
				'text'		=> '<div id="'.$this->getAppSettingsAreaId().'" style="width:600px;">'
							.(!$model->getProfileId() || !$model->getData('profile_cloud_app') ? $this->__('Local storage is selected.') : '')
							.'</div>',
			)
		);
		
		$form->setHtmlIdPrefix('cloud_app_');
		$form->setValues($model->getData());

		$this->setForm($form);
		
		$this->getLayout()->getBlock('mpbackup_adminhtml_profile_edit')->addFormScripts("
			var appSettings = function() {
				return {
					updateSettings: function() {
						" . ($model->getProfileId()
							? "var elements = [$('".$form->getHtmlIdPrefix().$this->getProfileCloudAppId()."'), $('profile_id')].flatten();"
							: "var elements = [$('".$form->getHtmlIdPrefix().$this->getProfileCloudAppId()."')].flatten();")
						. "
						
						if($('cloud_app_profile_cloud_app').selectedIndex == 0) {
							$('cloud_app_profile_local_copy').disabled = true;
							$('cloud_app_profile_local_copy').selectedIndex = 0;
						} else {
							$('cloud_app_profile_local_copy').disabled = false;
						}
						
						new Ajax.Request('{$this->getUrl('*/*/loadSettings')}',
							{
								parameters: Form.serializeElements(elements),
								evalScripts: true,
								onComplete: function(transport) {
                                    try {
                                        if(transport.responseText.isJSON()) {
                                            var response = transport.responseText.evalJSON();
                                            if (!Object.isUndefined(response.message) && response.message) {
                                                $('".$this->getAppSettingsAreaId()."').innerHTML = '<div style=\"color:red; font-weight:bold;\">' + response.message + '</div>';
                                                return;
                                            }
                                        }
                                    } catch (e) {
                                        console.log(e);
                                    }

								    $('".$this->getAppSettingsAreaId()."').innerHTML = transport.responseText;
								}
							}
						);
					},
				}
			}();
			
			Event.observe(window, 'load', function() {
				". ($model->getProfileId() && $model->getData('profile_cloud_app') ? "appSettings.updateSettings();" : '') . "
				if ($('".$form->getHtmlIdPrefix().$this->getProfileCloudAppId()."')) {
					Event.observe($('".$form->getHtmlIdPrefix().$this->getProfileCloudAppId()."'), 'change', appSettings.updateSettings);
				}
			});
		");
		
		return parent::_prepareForm();
	}
	
	/**
	 * Helper function to get cloud app settings area id
	 *
	 * @return string Returns area id.
	 */
	public function getAppSettingsAreaId() 
	{
		return self::APP_SETTINGS_AREA_ID;
	}
	
	/**
	 * Helper function to get cloud app settings area id
	 *
	 * @return string Returns area id.
	 */
	public function getProfileCloudAppId() 
	{
		return 'profile_cloud_app';
	}
	
	/**
	 * Helper function to load applications array
	 */
	protected function _getAppsForForm()
	{
		return Mage::helper('mpbackup')->getAppsOptionArray();
	}  
}