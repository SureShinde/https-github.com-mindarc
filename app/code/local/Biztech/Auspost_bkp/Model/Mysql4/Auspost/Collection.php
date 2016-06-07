<?php

class Biztech_Auspost_Model_Mysql4_Auspost_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('auspost/auspost');
    }
}