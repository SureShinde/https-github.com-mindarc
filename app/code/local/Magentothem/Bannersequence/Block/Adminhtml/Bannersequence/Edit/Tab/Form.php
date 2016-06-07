<?php

class Magentothem_Bannersequence_Block_Adminhtml_Bannersequence_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('bannersequence_form', array('legend'=>Mage::helper('bannersequence')->__('Item information')));
     
          
        //if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('store_id', 'multiselect', array(
                'name' => 'store_id[]',
                'label' => $this->__('Store View'),
                'required' => TRUE,
                'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(FALSE, TRUE),
            ));
       // }
        
      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('bannersequence')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));
      
    $fieldset->addField('animate', 'select', array(
          'label'     => Mage::helper('bannersequence')->__('Animation'),
          'name'      => 'animate',
          'values'    => array(
              array(
                  'value'     => 'animate-in',
                  'label'     => Mage::helper('bannersequence')->__('Animate-in'),
              ),

              array(
                  'value'     => 'animate-out',
                  'label'     => Mage::helper('bannersequence')->__('Animate-out'),
              ),
          ),
      ));
      
      
      $fieldset->addField('title2', 'text', array(
          'label'     => Mage::helper('bannersequence')->__('Title2'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title2',
      ));
      
      
       $fieldset->addField('link', 'text', array(
          'label'     => Mage::helper('bannersequence')->__('Link'),
          'required'  => false,
          'name'      => 'link',
      ));

      $fieldset->addField('filename', 'file', array(
          'label'     => Mage::helper('bannersequence')->__('Background Image'),
          'required'  => false,
          'name'      => 'filename',
     ));
      
     $fieldset->addField('image', 'file', array(
      'label'     => Mage::helper('bannersequence')->__('Image'),
      'required'  => false,
      'name'      => 'image',
      ));
		
      //$fieldset->addField('image2', 'file', array(
      //    'label'     => Mage::helper('bannersequence')->__('Image2'),
      //    'required'  => false,
      //    'name'      => 'image2',
      //));
      
       $fieldset->addField('description', 'editor', array(
          'name'      => 'description',
          'label'     => Mage::helper('bannersequence')->__('Description'),
          'title'     => Mage::helper('bannersequence')->__('Description'),
          'style'     => 'width:700px; height:400px;',
          'wysiwyg'   => false,
          'required'  => false,
      ));
      
       $fieldset->addField('kind_effect', 'select', array(
          'label'     => Mage::helper('bannersequence')->__('Kind of effec'),
          'name'      => 'kind_effect',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('bannersequence')->__('Effec1'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('bannersequence')->__('Effect2'),
              ),
                array(
                  'value'     => 3,
                  'label'     => Mage::helper('bannersequence')->__('Effect3'),
              ),
                array(
                  'value'     => 4,
                  'label'     => Mage::helper('bannersequence')->__('Effect4'),
              ),
          ),
      ));
		
   
     
     
      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('bannersequence')->__('Status'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('bannersequence')->__('Enabled'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('bannersequence')->__('Disabled'),
              ),
          ),

      ));
      
       $fieldset->addField('order', 'text', array(
          'label'     => Mage::helper('bannersequence')->__('Order'),
          'required'  => false,
          'name'      => 'order',
      ));
     
      if ( Mage::getSingleton('adminhtml/session')->getBannersequenceData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getBannersequenceData());
          Mage::getSingleton('adminhtml/session')->setBannersequenceData(null);
      } elseif ( Mage::registry('bannersequence_data') ) {
          $form->setValues(Mage::registry('bannersequence_data')->getData());
      }
      return parent::_prepareForm();
  }
}