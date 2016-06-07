<?php

class Magentothem_Bannersequence_Model_Mysql4_Bannersequence_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('bannersequence/bannersequence');
    }
}