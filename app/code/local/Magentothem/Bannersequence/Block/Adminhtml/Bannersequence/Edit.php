<?php

class Magentothem_Bannersequence_Block_Adminhtml_Bannersequence_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'bannersequence';
        $this->_controller = 'adminhtml_bannersequence';
        
        $this->_updateButton('save', 'label', Mage::helper('bannersequence')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('bannersequence')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('bannersequence_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'bannersequence_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'bannersequence_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('bannersequence_data') && Mage::registry('bannersequence_data')->getId() ) {
            return Mage::helper('bannersequence')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('bannersequence_data')->getTitle()));
        } else {
            return Mage::helper('bannersequence')->__('Add Item');
        }
    }
}