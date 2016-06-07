<?php
	
class Biztech_Auspost_Model_System_Config_Enabledisable{

    public function toOptionArray()
    {
        $options = array(
            array('value' => 0, 'label'=>Mage::helper('auspost')->__('No')),
        );
        $websites = Mage::helper('auspost')->getAllWebsites();
        if(!empty($websites)){
        	$options[] = array('value' => 1, 'label'=>Mage::helper('auspost')->__('Yes'));
        }
        return $options;
    }

}