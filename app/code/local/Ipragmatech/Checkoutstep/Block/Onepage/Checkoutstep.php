<?php

class Ipragmatech_Checkoutstep_Block_Onepage_Checkoutstep extends Mage_Checkout_Block_Onepage_Abstract
{
    protected function _construct()
    {    	
        $this->getCheckout()->setStepData('checkoutstep', array(
            'label'     => Mage::helper('checkout')->__('Invitation to participation'),
            'is_show'   => true
        ));
        
        parent::_construct();
    }
}