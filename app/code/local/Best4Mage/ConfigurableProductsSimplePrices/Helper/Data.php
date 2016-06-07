<?php

class Best4Mage_ConfigurableProductsSimplePrices_Helper_Data extends Mage_Core_Helper_Abstract
{
	private function useProduct()
	{
		return $this->getConfig('product_level');
	}
	
	public function isEnable($product)
	{
		if($this->useProduct() == 1) {
			if(Mage::app()->getRequest()->getRouteName() != 'catalog') $product = Mage::getModel('catalog/product')->load($product->getId());
			return ($product->getCpspEnable() == 1);
		} else return ($this->getConfig('enable') == 1);
	}
	
	public function isShowPrices($product)
	{
		if($this->useProduct() == 1) return ($product->getCpspExpandPrices() == 1);
		else return ($this->getConfig('expand_prices') == 1);
	}
	
	public function isShowLowestPrice($product)
	{
		if($this->useProduct() == 1) return ($product->getCpspShowLowest() == 1);
		else return ($this->getConfig('show_lowest') == 1);
	}
	
	public function isShowMaxRegularPrice($product)
	{
		if($this->useProduct() == 1) return ($product->getCpspShowMaxregular() == 1);
		else return ($this->getConfig('show_maxregular') == 1);
	}
	
	public function isTierBase()
	{
		return ($this->getConfig('tier_base','cptp') == 1);
	}
	
	public function getCpspPriceFormate()
	{
		return $this->generatedPriceFormatArray($this->getConfig('price_format'));
	}
	
	public function isRemoveDecimalPoint()
	{
		return ($this->getConfig('choose_formate') == 1);
	}
	
	static $websiteId = null;
	static $storeId = null;
	static $groupId = null;
	static $statusId = null;
	static $taxClassId = null;
	static $minPriceArrey = array();
	static $maxPriceArrey = array();
	
	public function setUpStaticData($_product)
	{
		$resource = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');
		
		if(self::$websiteId === null) self::$websiteId = Mage::app()->getWebsite()->getId();
		
		if(self::$storeId === null) self::$storeId = Mage::app()->getStore()->getStoreId();
		
		if(self::$groupId === null) self::$groupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
		
		if(self::$statusId === null) {
			self::$statusId = $readConnection->fetchOne("SELECT `attribute_id` FROM ".$resource->getTableName('eav_attribute')." WHERE `attribute_code` LIKE 'status' AND `entity_type_id` = ".$_product->getEntityTypeId());
		}
		
		if(self::$taxClassId === null) {
			self::$taxClassId = $readConnection->fetchOne("SELECT `attribute_id` FROM ".$resource->getTableName('eav_attribute')." WHERE `attribute_code` LIKE 'tax_class_id' AND `entity_type_id` = ".$_product->getEntityTypeId());
		}
	}
	
	public function getMinimalProductPrice($_productId)
	{
		$this->getProductPrices($_productId);
		$minPriceArrey = self::$minPriceArrey[$_productId];
		return array(array_keys($minPriceArrey, min($minPriceArrey)),min($minPriceArrey));
	}
	
	public function getMaximumProductPrice($_productId)
	{
		$this->getProductPrices($_productId);
		$maxPriceArrey = self::$maxPriceArrey[$_productId];
		return array(array_keys($maxPriceArrey, max($maxPriceArrey)),max($maxPriceArrey));
	}
	
	protected function getProductPrices($productId)
	{
		if(!array_key_exists($productId, self::$minPriceArrey) || !array_key_exists($productId, self::$maxPriceArrey))
		{
			$resource = Mage::getSingleton('core/resource');
			$readConnection = $resource->getConnection('core_read');
		
			$query = "SELECT `cpip`.`entity_id`,IF(`cpip`.`tier_price` IS NOT NULL, LEAST(`cpip`.`min_price`, `cpip`.`tier_price`), `cpip`.`min_price`) AS `minimal_price`,`cpip`.`max_price` FROM ".$resource->getTableName('catalog_product_index_price')." `cpip` INNER JOIN ".$resource->getTableName('catalog_product_entity_int')." AS `at_status_default` ON ((`at_status_default`.`entity_id` = `cpip`.`entity_id`) AND (`at_status_default`.`attribute_id` = '".self::$statusId."') AND `at_status_default`.`store_id` = 0) LEFT JOIN ".$resource->getTableName('catalog_product_entity_int')." AS `at_status` ON ((`at_status`.`entity_id` = `cpip`.`entity_id`) AND (`at_status`.`attribute_id` = '".self::$statusId."') AND (`at_status`.`store_id` = ".self::$storeId.")) INNER JOIN ".$resource->getTableName('catalog_product_entity_int')." AS `at_tax_class_id_default` ON ((`at_tax_class_id_default`.`entity_id` = `cpip`.`entity_id`) AND (`at_tax_class_id_default`.`attribute_id` = '".self::$taxClassId."') AND `at_tax_class_id_default`.`store_id` = 0) LEFT JOIN ".$resource->getTableName('catalog_product_entity_int')." AS `at_tax_class_id` ON ((`at_tax_class_id`.`entity_id` = `cpip`.`entity_id`) AND (`at_tax_class_id`.`attribute_id` = '".self::$taxClassId."') AND (`at_tax_class_id`.`store_id` = ".self::$storeId.")) INNER JOIN ".$resource->getTableName('cataloginventory_stock_status')." AS `css` ON ((`css`.`product_id` = `cpip`.`entity_id`) AND (`css`.`stock_id` = 1) AND (`css`.`stock_status` = 1) AND `css`.`website_id` = `cpip`.`website_id`) WHERE `cpip`.`entity_id` IN ( SELECT `cpsl`.`product_id` FROM ".$resource->getTableName('catalog_product_super_link')." `cpsl` WHERE `cpsl`.`parent_id` = ".$productId.") AND (IF(`at_status`.`value_id` > 0, `at_status`.`value`, `at_status_default`.`value`) = '1') AND (IF(`at_tax_class_id`.`value_id` > 0, `at_tax_class_id`.`value`, `at_tax_class_id_default`.`value`) = `cpip`.`tax_class_id`) AND `cpip`.`website_id` = ".self::$websiteId." AND `cpip`.`customer_group_id` = ".self::$groupId;
			
			$priceCollection = $readConnection->fetchAll($query);
			//echo '<pre>';print_r($priceCollection);die;
			foreach($priceCollection as $price)
			{
				self::$minPriceArrey[$productId][$price['entity_id']] = $price['minimal_price'];
				self::$maxPriceArrey[$productId][$price['entity_id']] = $price['max_price'];
			}
		}
	}
	
	private function getConfig($fieldName, $fcpm = 'cpsp', $basic_options = 'settings')
	{
		return Mage::getStoreConfig($fcpm.'/'.$basic_options.'/'.$fieldName, Mage::app()->getStore());
	}
	
	public function getWishlistItemByProduct($product)
	{
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		if($customer->getId())
		{
			$wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($customer, true);
			$wishListItemCollection = $wishlist->getItemCollection();
			foreach ($wishListItemCollection as $item)
			{
				if($item->representProduct($product))
				{
					return $item;	
				}
			}
		} else {
			return false;	
		}
	}
	
	private function generatedPriceFormatArray($key = 0)
	{
		if($key == null) $key = 0;
		$precision = 2;
		if($this->isRemoveDecimalPoint()) $precision = 0;
		$arrOfPriceFormat = array(
			'0' => array(
				'precision' => $precision,
				'requiredPrecision' => $precision,
				'decimalSymbol' => '.',
				'groupSymbol' => ',',
				'groupLength' => 3	
			),
			'1' => array(
				'precision' => $precision,
				'requiredPrecision' => $precision,
				'decimalSymbol' => ',',
				'groupSymbol' => '',
				'groupLength' => 3
			),
			'2' => array(
				'precision' => $precision,
				'requiredPrecision' => $precision,
				'decimalSymbol' => '.',
				'groupSymbol' => '',
				'groupLength' => 3
			)
		);
		
		return (array_key_exists($key,$arrOfPriceFormat) ? $arrOfPriceFormat[$key] : $arrOfPriceFormat[0]);
	}
}