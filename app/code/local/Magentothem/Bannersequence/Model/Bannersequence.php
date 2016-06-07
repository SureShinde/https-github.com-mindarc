<?php

class Magentothem_Bannersequence_Model_Bannersequence extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('bannersequence/bannersequence');
    }
}