<?php

class Best4Mage_ConfigurableProductsSimplePrices_Model_Observer
{
	public function modifyPrice(Varien_Event_Observer $obs)
	{
		$_coreHelper = Mage::helper('core');
		$item = $obs->getQuoteItem();
		$price = $item->getBuyRequest()->getCustomprice();
		if($price == '') return;
		$tiers = $item->getProduct()->getTierPrice();
		$item = ( $item->getParentItem() ? $item->getParentItem() : $item );
		$qty = $item->getQty();
		$info = array();
		$baseOnSimple = Mage::helper('configurableproductssimpleprices')->isTierBase();
		if($baseOnSimple == 1){
			$cart = Mage::getSingleton('checkout/cart');
			$info2 = $info = $this->_generateInfoData($cart);
			unset($info2[-1]);
			if(count($info2) > 0){
				$idQtys = $this->_calcConfigProductTierPricing();
				$qty = array_sum($idQtys[$item->getProductId()]);
			} else if($item->getBuyRequest()->hasCptpQty()){
				$qty = $item->getBuyRequest()->getCptpQty();
			}
		}
		$_SIMPLE = Mage::helper('configurableproductssimpleprices')->isEnable($item->getProduct());
		if(count($tiers)>0){
			foreach(array_reverse($tiers) as $tier) {
				if($qty >= intval($tier['price_qty'])){
					$price = (float)($baseOnSimple==true&&$_SIMPLE==false ? $item->getProduct()->getFinalPrice($tier['price_qty']) : $tier['price']);
					break;
				}
			}
		}

		if($price !== '' && $item->getProductType() == 'configurable' && $_SIMPLE)
		{
			$price = round($_coreHelper->currency($price,false,false),2);
			$customoptionprice = 1*$item->getBuyRequest()->getCustomoptionprice();
			if($customoptionprice != 0){
				$price += round($_coreHelper->currency($customoptionprice,false,false),2);	
			}
			$item->setCustomPrice($price);
			$item->setOriginalCustomPrice($price);
			$item->getProduct()->setIsSuperMode(true);
		}
		
		//if($baseOnSimple == 1 && count($info) > 0 && !$item->getBuyRequest()->hasCptpQty()) {
		if($baseOnSimple == 1 && count($info2) > 0) {	
			//Mage::dispatchEvent('checkout_cart_update_items_before', array('cart'=>$cart, 'info'=>$info));
			$this->updatePrice($obs,$cart,$info);
		}
	}
	
	public function afterRemoveUpdatePrice(Varien_Event_Observer $obs)
	{
		$cart = Mage::getSingleton('checkout/cart');
		$info = $this->_generateInfoData($cart);
		if(count($info) > 0) {
			$this->updatePrice($obs,$cart,$info);
		}
	}
	
	public function afterMergeUpdatePrice(Varien_Event_Observer $obs)
	{
		$_itemCustomNewPrice = Mage::getSingleton('checkout/session')->getAfterLoginQuotePrice();
		if(!$_itemCustomNewPrice) return $this;
		$cart = $obs->getCart();
		$info = $this->_generateInfoData($cart);
		if(count($info) > 0) {
			$this->updatePrice($obs,$cart,$info);
		}
	}
	
	public function loadCustomerCartBefore(Varien_Event_Observer $obs)
	{
		$checkout = $obs->getCheckoutSession();
		$customerQuote = Mage::getModel('sales/quote')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->loadByCustomer(Mage::getSingleton('customer/session')->getCustomerId());

        if ($customerQuote->getId() && $checkout->getQuoteId() != $customerQuote->getId()) {
			$checkout->setAfterLoginQuotePrice(true);
		}
	}
	
	public function afterCurrencyUpdatePrice(Varien_Event_Observer $obs)
	{
		$checkout = Mage::getSingleton('checkout/session');
        if ($checkout->getQuoteId()) {
			$checkout->setAfterLoginQuotePrice(true);
		}
	}
	
	public function updatePrice(Varien_Event_Observer $obs, $cart = null, $info = null)
	{
		$updated = false;
		if(is_null($cart)){
			$cart = $obs->getCart();
		}
		$quote = $cart->getQuote();
		if(is_null($info)){
			$info = $obs->getInfo();
			$updated = true;
		}
		if(count($info) == 0) return;
		
		$_coreHelper = Mage::helper('core');
		$_itemCustomNewPrice = Mage::getSingleton('checkout/session')->getAfterLoginQuotePrice();
		
		foreach($quote->getAllVisibleItems() as $item)
		{
			/*if($itemId == '-1') continue;
			$item = $quote->getItemById($itemId);*/
			$newPrice = $price = $item->getBuyRequest()->getCustomprice();
			if(!$item || $price == '') continue;

			if ($_itemCustomNewPrice && $item->getHasChildren()) {
				foreach ($item->getChildren() as $child) {
					$newPrice = $child->getProduct()->getFinalPrice();
					break;
				}
				if($newPrice) $price = $newPrice;
			}
			
			$tiers = $item->getProduct()->getTierPrice();
			$item = ( $item->getParentItem() ? $item->getParentItem() : $item );
			$_product = Mage::getModel('catalog/product')->load($item->getProductId());
			$cpspEnable = Mage::helper('configurableproductssimpleprices')->isEnable($_product);
			$baseOnTotal = Mage::helper('configurableproductssimpleprices')->isTierBase();
			
			if($cpspEnable){
				if ($option = $item->getOptionByCode('simple_product')) {
					$tiers = $option->getProduct()->getTierPrice();
				}	
			}
			
			$qty = $item->getQty();
			if(count($tiers)>0){
				if($baseOnTotal==1){
					if($tierQtyId = $this->_calcConfigProductTierPricing($info,$updated)){
						$qty = array_sum($tierQtyId[$_product->getId()]);
					}
				}
				//Mage::log($info,null,'sankhala.log');
				foreach(array_reverse($tiers) as $tier) {
					if($qty >= intval($tier['price_qty'])){
						$price = (float)($tier['price']);
						break;
					}
				}
			}
			
			if($price !== '' && $item->getProductType() == 'configurable' && $cpspEnable)
			{
				$price = round($_coreHelper->currency($price,false,false),2);
				$customoptionprice = 1*$item->getBuyRequest()->getCustomoptionprice();
				if($customoptionprice != 0){
					$price += round($_coreHelper->currency($customoptionprice,false,false),2);
				}
				$item->setCustomPrice($price);
				$item->setOriginalCustomPrice($price);
				$item->getProduct()->setIsSuperMode(true);
			}	
		}
	}
	
	private function _calcConfigProductTierPricing($info = array(), $updated = false)
    {
	    if ($items = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems()) {
            // map mapping the IDs of the parent products with the quantities of the corresponding simple products
            $idQuantities = array();
            // go through all products in the quote
            foreach ($items as $item) {
                /** @var Mage_Sales_Model_Quote_Item $item */
                if ($item->getParentItem()) {
                    continue;
                }
                // this is the product ID of the parent!
                $id = $item->getProductId();
                // map the parent ID with the quantity of the simple product
                if($updated){
					$idQuantities[$id][] = $info[$item->getId()]['qty'];
				} else {
					$idQuantities[$id][] = $item->getQty();
				}
            }
        }
        return $idQuantities;
    }
	
	private function _generateInfoData($cart)
    {
		$info = array();
		if ($items = $cart->getQuote()->getAllVisibleItems()) {
			foreach ($items as $item) {
			    if ($item->getParentItem()) {
                    continue;
                }
			    $id = $item->getId();
			    if($id != '') $info[$id]['qty'] = $item->getQty();
				else $info['-1']['qty'] = $item->getQty();
            }
        }
		return $info;
	}
}