<?php
class Biztech_Orderrequest_Block_Orderrequest extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getOrderrequest()     
     { 
        if (!$this->hasData('orderrequest')) {
            $this->setData('orderrequest', Mage::registry('orderrequest'));
        }
        return $this->getData('orderrequest');
        
    }
}