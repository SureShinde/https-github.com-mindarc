<?php

class Best4Mage_ConfigurableProductsSimplePrices_Model_System_Config_Source_Priceformates extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getAllOptions()
    {
		return array(
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Use Default')),
			array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('1000,00')),
			array('value' => 2, 'label'=>Mage::helper('adminhtml')->__('1000.00'))
        );
	}
	
	public function toOptionArray()
    {
        return $this->getAllOptions();
    }

}