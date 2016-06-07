<?php
class Magentothem_Bannersequence_Block_Adminhtml_Bannersequence extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_bannersequence';
    $this->_blockGroup = 'bannersequence';
    $this->_headerText = Mage::helper('bannersequence')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('bannersequence')->__('Add Item');
    parent::__construct();
  }
}