<?php
    class Biztech_Orderrequest_Helper_Data extends Mage_Core_Helper_Abstract
    {
        public function isEnable(){
            $isenabled = Mage::getStoreConfig('orderrequest/orderrequest_general/enabled');
            if($isenabled){
                return true;
            }
            else{
                return false;
            }
        }
}