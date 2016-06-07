<?php
    class Biztech_Orderrequest_Block_Adminhtml_Orderrequest extends Mage_Adminhtml_Block_Widget_Grid_Container
    {
        public function __construct()
        {
            $this->_controller = 'adminhtml_orderrequest';
            $this->_blockGroup = 'orderrequest';
            $this->_headerText = Mage::helper('orderrequest')->__('Customer Order Comments');
            $this->_addButtonLabel = Mage::helper('orderrequest')->__('Add Item');

            $flag = Mage::helper('orderrequest')->isEnable();
            if($flag){
                parent::__construct();
            }
            else{
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('orderrequest')->__('Customer Order Comment Management extension is not enabled. Please enable it from System > Configuration.'));
            }

            $this->_removeButton('add');
        }
}