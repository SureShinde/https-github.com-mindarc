<?php

class Magentothem_Bannersequence_Block_Adminhtml_Bannersequence_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('bannersequence_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('bannersequence')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('bannersequence')->__('Item Information'),
          'title'     => Mage::helper('bannersequence')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('bannersequence/adminhtml_bannersequence_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}