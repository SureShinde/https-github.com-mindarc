<?php
class Extendware_EWFreeGift_Block_Gift_Selection extends Extendware_EWCore_Block_Mage_Core_Template {
	protected function _construct() {
        parent::_construct();
        $this->setProducts(Mage::helper('ewfreegift')->getPotentialGifts());
        $this->setUnclaimedGiftCount($this->mHelper()->getUnclaimedGiftCount());
    }
    
	public function getActionUrl() {
		$returnUrl = Mage::getUrl('*/*/*', array('_use_rewrite' => true, '_current' => true));
		return $this->getUrl('ewfreegift/cart/addProductAsGift', array(
			Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => Mage::helper('core')->urlEncode($returnUrl)
		));
	}
	
	protected function _toHtml() {
		$products = $this->getProducts();
		if (count($products) <= 0) return null;
        return parent::_toHtml();
    }
}
