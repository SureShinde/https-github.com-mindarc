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
 * Class Mageplace_Backup_Block_Adminhtml_Profile_Edit
 */
class Mageplace_Backup_Block_Adminhtml_Profile_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    const PAGE_TABS_BLOCK_ID = 'page_tabs';

    protected $_objectId = 'profile_id';
    protected $_blockGroup = 'mpbackup';
    protected $_controller = 'adminhtml_profile';


    /**
     * Constructor for the category edit form
     */
    public function __construct()
    {
        parent::__construct();

        $this->_removeButton('reset');
        $this->_updateButton('save', 'label', $this->__('Save Profile'));
        $this->_updateButton('delete', 'label', $this->__('Delete Profile'));

        $model = Mage::registry('mpbackup_profile');

        $storage = Mage::registry('mpbackup_storage');

        $isConnected = is_object($storage) && $storage->checkConnection() ? true : false;

        if ($model->getId() && is_object($storage) && $storage->needAuthorize()) {
            $this->_addButton('authorize',
                array(
                    'label'   => $isConnected ? $this->__('Save and re-authorize') : $this->__('Save and authorize'),
                    'onclick' => 'saveAndAuthorize()',
                    'class'   => 'save'
                ),
                -110
            );
        }

        $this->_addButton('saveandcontinue',
            array(
                'label'   => $this->__('Save and continue edit'),
                'onclick' => 'saveAndContinueEdit(\'' . $this->getSaveAndContinueUrl() . '\')',
                'class'   => 'save'
            ),
            -100
        );

        $this->addFormScripts("
			function saveAndAuthorize(){
				" . ($isConnected ? "if(confirm(\"" . $this->__('Your application already authorized. Are you sure you want to re-authorize?') . "\")) {" : '') . "
					editForm.submit($('edit_form').action+'authorize/edit/');
				" . ($isConnected ? "}" : '') . "
			}

			function saveAndContinueEdit(urlTemplate){
				var urlTemplateSyntax = /(^|.|\\r|\\n)({{(\\w+)}})/;
                var template = new Template(urlTemplate, urlTemplateSyntax);
                var url = template.evaluate({tab_id:" . self::PAGE_TABS_BLOCK_ID . "JsTabs.activeTab.id});
        		editForm.submit(url);
			}
		");
    }

    public function getSaveAndContinueUrl()
    {
        return $this->getUrl('*/*/save', array(
            '_current'   => true,
            'back'       => 'edit',
            'tab'        => '{{tab_id}}',
            'active_tab' => null
        ));
    }

    public function addFormScripts($js)
    {
        $this->_formScripts[] = $js;
    }

    public function getHeaderText()
    {
        if (Mage::registry('mpbackup_profile')->getProfileId()) {
            return $this->__('Edit Profile');
        } else {
            return $this->__('New Profile');
        }
    }

    public function getHeaderCssClass()
    {
        return '';
    }
}