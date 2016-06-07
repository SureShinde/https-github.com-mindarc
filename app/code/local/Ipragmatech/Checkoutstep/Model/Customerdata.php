<?php

class Ipragmatech_Checkoutstep_Model_Customerdata extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('checkoutstep/customerdata');
    }
}