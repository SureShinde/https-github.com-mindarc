<?php

class Extendware_EWFreeGift_Block_Gift_Item_Bundle extends Mage_Bundle_Block_Catalog_Product_View_Type_Bundle {
	public function _construct() {
		parent::_construct();
		$this->addRenderer('radio', 'bundle/catalog_product_view_type_bundle_option_radio');
		$this->addRenderer('checkbox', 'bundle/catalog_product_view_type_bundle_option_checkbox');
		$this->addRenderer('select', 'bundle/catalog_product_view_type_bundle_option_select');
		$this->addRenderer('multi', 'bundle/catalog_product_view_type_bundle_option_multi');
	}
	
	public function getOptionHtml($option)
    {
        if (!isset($this->_optionRenderers[$option->getType()])) {
            return $this->__('There is no defined renderer for "%s" option type.', $option->getType());
        }
        return $this->getLayout()->createBlock($this->_optionRenderers[$option->getType()])
            ->setOption($option)->setProduct($this->getProduct())->toHtml();
    }
}
