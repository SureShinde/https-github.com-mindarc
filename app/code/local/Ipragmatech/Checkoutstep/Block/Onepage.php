<?php

class Ipragmatech_Checkoutstep_Block_Onepage extends Mage_Checkout_Block_Onepage
{
    public function getSteps()
    {
        $steps = array();

        if (!$this->isCustomerLoggedIn()) {
            $steps['login'] = $this->getCheckout()->getStepData('login');
        }
		
        //check that module is enable or not
        if (Mage::helper('checkoutstep')->isEnabled()) {
        	$stepCodes = array('billing', 'shipping', 'shipping_method', 'payment', 'checkoutstep', 'review');
        }
        else {
        	$stepCodes = array('billing', 'shipping', 'shipping_method', 'payment', 'review');
        }
        foreach ($stepCodes as $step) {
            $steps[$step] = $this->getCheckout()->getStepData($step);
        }
        
        return $steps;
    }
}