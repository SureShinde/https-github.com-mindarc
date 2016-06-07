<?php
    class Biztech_Auspost_Model_Observer {


        public function checkKey($observer)
        {
            $key = Mage::getStoreConfig('auspost/activation/key');
            Mage::helper('auspost')->checkKey($key);
        }

}