<?php

class Magentothem_Bannersequence_Model_Mysql4_Bannersequence extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the bannersequence_id refers to the key field in your database table.
        $this->_init('bannersequence/bannersequence', 'bannersequence_id');
    }
}