<?php
    class Biztech_Orderrequest_Adminhtml_OrderrequestController extends Mage_Adminhtml_Controller_action
    {
        protected function _initAction() {
            $this->loadLayout()
            ->_setActiveMenu('orderrequest/items')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Customer Order Comments'), Mage::helper('adminhtml')->__('Customer Order Comments'));

            return $this;
        }   

        public function indexAction() {
            $this->_initAction()
            ->renderLayout();
        }

        public function editAction() {
            $this->_initAction()
            ->renderLayout();
        }
}