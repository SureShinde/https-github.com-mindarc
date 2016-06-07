<?php

class Biztech_Auspost_Model_Mysql4_Auspost extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the auspost_id refers to the key field in your database table.
        $this->_init('auspost/auspost', 'auspost_id');
    }
}