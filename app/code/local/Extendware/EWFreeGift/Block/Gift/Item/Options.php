<?php

class Extendware_EWFreeGift_Block_Gift_Item_Options extends Mage_Catalog_Block_Product_View_Options {
	public function __construct() {
		parent::__construct();
		$this->addOptionRenderer('text', 'catalog/product_view_options_type_text', 'catalog/product/view/options/type/text.phtml');
		$this->addOptionRenderer('select', 'catalog/product_view_options_type_select', 'catalog/product/view/options/type/select.phtml');
		$this->addOptionRenderer('file', 'catalog/product_view_options_type_file', 'catalog/product/view/options/type/file.phtml');
		$this->addOptionRenderer('date', 'catalog/product_view_options_type_date', 'catalog/product/view/options/type/date.phtml');
		$this->setTemplate('catalog/product/view/options.phtml');
	}
}
