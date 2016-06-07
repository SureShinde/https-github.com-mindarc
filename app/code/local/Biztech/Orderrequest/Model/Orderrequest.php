<?php

class Biztech_Orderrequest_Model_Orderrequest extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('orderrequest/orderrequest');
    }
}