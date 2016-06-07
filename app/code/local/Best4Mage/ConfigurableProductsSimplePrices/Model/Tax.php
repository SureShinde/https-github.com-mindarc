<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Tax
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Tax totals calculation model
 */
class Best4Mage_ConfigurableProductsSimplePrices_Model_Tax extends Mage_Tax_Model_Sales_Total_Quote_Tax
{
    /**
     * Calculate address tax amount based on one unit price and tax amount
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_Tax_Model_Sales_Total_Quote
     */
    protected function _unitBaseCalculation(Mage_Sales_Model_Quote_Address $address, $taxRateRequest)
    {
		if(version_compare(Mage::getVersion(),'1.8.1.0','>='))
		{
			$items = $this->_getAddressItems($address);
			$itemTaxGroups = array();
			$store = $address->getQuote()->getStore();
			$catalogPriceInclTax = $this->_config->priceIncludesTax($store);
	
			foreach ($items as $item) {
				if ($item->getParentItem()) {
					continue;
				}
	
				if ($item->getHasChildren() && $item->isChildrenCalculated()) {
					foreach ($item->getChildren() as $child) {
						$this->_unitBaseProcessItemTax(
							$address, $child, $taxRateRequest, $itemTaxGroups, $catalogPriceInclTax);
					}
					$this->_recalculateParent($item);
				} else {
					$_SIMPLE = Mage::helper('configurableproductssimpleprices')->isEnable($item->getProduct());
					if($item->getProductType() == 'configurable' && $_SIMPLE){
						foreach ($item->getChildren() as $child) {
							$item->getProduct()->setTaxClassId($child->getProduct()->getTaxClassId());
						}
					}
					$this->_unitBaseProcessItemTax(
						$address, $item, $taxRateRequest, $itemTaxGroups, $catalogPriceInclTax);
				}
			}
			if ($address->getQuote()->getTaxesForItems()) {
				$itemTaxGroups += $address->getQuote()->getTaxesForItems();
			}
			$address->getQuote()->setTaxesForItems($itemTaxGroups);
		} else {
			$items = $this->_getAddressItems($address);
			$itemTaxGroups  = array();
			foreach ($items as $item) {
				if ($item->getParentItem()) {
					continue;
				}
	
				if ($item->getHasChildren() && $item->isChildrenCalculated()) {
					foreach ($item->getChildren() as $child) {
						$taxRateRequest->setProductClassId($child->getProduct()->getTaxClassId());
						$rate = $this->_calculator->getRate($taxRateRequest);
						$this->_calcUnitTaxAmount($child, $rate);
						$this->_addAmount($child->getTaxAmount());
						$this->_addBaseAmount($child->getBaseTaxAmount());
						$applied = $this->_calculator->getAppliedRates($taxRateRequest);
						if ($rate > 0) {
							$itemTaxGroups[$child->getId()] = $applied;
						}
						$this->_saveAppliedTaxes(
							$address,
							$applied,
							$child->getTaxAmount(),
							$child->getBaseTaxAmount(),
							$rate
						);
						$child->setTaxRates($applied);
					}
					$this->_recalculateParent($item);
				}
				else {
					$_SIMPLE = Mage::helper('configurableproductssimpleprices')->isEnable($item->getProduct());
					if($item->getProductType() == 'configurable' && $_SIMPLE){
						foreach ($item->getChildren() as $child) {
							$item->getProduct()->setTaxClassId($child->getProduct()->getTaxClassId());
						}
					}
					$taxRateRequest->setProductClassId($item->getProduct()->getTaxClassId());
					$rate = $this->_calculator->getRate($taxRateRequest);
					$this->_calcUnitTaxAmount($item, $rate);
					$this->_addAmount($item->getTaxAmount());
					$this->_addBaseAmount($item->getBaseTaxAmount());
					$applied = $this->_calculator->getAppliedRates($taxRateRequest);
					if ($rate > 0) {
						$itemTaxGroups[$item->getId()] = $applied;
					}
					$this->_saveAppliedTaxes(
						$address,
						$applied,
						$item->getTaxAmount(),
						$item->getBaseTaxAmount(),
						$rate
					);
					$item->setTaxRates($applied);
				}
			}
			if ($address->getQuote()->getTaxesForItems()) {
				$itemTaxGroups += $address->getQuote()->getTaxesForItems();
			}
			$address->getQuote()->setTaxesForItems($itemTaxGroups);	
		}
        return $this;
    }

    /**
     * Calculate address total tax based on row total
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @param   Varien_Object $taxRateRequest
     * @return  Mage_Tax_Model_Sales_Total_Quote
     */
    protected function _rowBaseCalculation(Mage_Sales_Model_Quote_Address $address, $taxRateRequest)
    {
        if(version_compare(Mage::getVersion(),'1.8.1.0','>='))
		{
			$items = $this->_getAddressItems($address);
			$itemTaxGroups = array();
			$store = $address->getQuote()->getStore();
			$catalogPriceInclTax = $this->_config->priceIncludesTax($store);
	
			foreach ($items as $item) {
				if ($item->getParentItem()) {
					continue;
				}
				if ($item->getHasChildren() && $item->isChildrenCalculated()) {
					foreach ($item->getChildren() as $child) {
						$this->_rowBaseProcessItemTax(
							$address, $child, $taxRateRequest, $itemTaxGroups, $catalogPriceInclTax);
					}
					$this->_recalculateParent($item);
				} else {
					$_SIMPLE = Mage::helper('configurableproductssimpleprices')->isEnable($item->getProduct());
					if($item->getProductType() == 'configurable' && $_SIMPLE){
						foreach ($item->getChildren() as $child) {
							$item->getProduct()->setTaxClassId($child->getProduct()->getTaxClassId());
						}
					}
					$this->_rowBaseProcessItemTax(
						$address, $item, $taxRateRequest, $itemTaxGroups, $catalogPriceInclTax);
				}
			}
	
			if ($address->getQuote()->getTaxesForItems()) {
				$itemTaxGroups += $address->getQuote()->getTaxesForItems();
			}
			$address->getQuote()->setTaxesForItems($itemTaxGroups);
		} else {
			$items = $this->_getAddressItems($address);
			$itemTaxGroups  = array();
			foreach ($items as $item) {
				if ($item->getParentItem()) {
					continue;
				}
				if ($item->getHasChildren() && $item->isChildrenCalculated()) {
					foreach ($item->getChildren() as $child) {
						$taxRateRequest->setProductClassId($child->getProduct()->getTaxClassId());
						$rate = $this->_calculator->getRate($taxRateRequest);
						$this->_calcRowTaxAmount($child, $rate);
						$this->_addAmount($child->getTaxAmount());
						$this->_addBaseAmount($child->getBaseTaxAmount());
						$applied = $this->_calculator->getAppliedRates($taxRateRequest);
						if ($rate > 0) {
							$itemTaxGroups[$child->getId()] = $applied;
						}
						$this->_saveAppliedTaxes(
							$address,
							$applied,
							$child->getTaxAmount(),
							$child->getBaseTaxAmount(),
							$rate
						);
						$child->setTaxRates($applied);
					}
					$this->_recalculateParent($item);
				}
				else {
					$_SIMPLE = Mage::helper('configurableproductssimpleprices')->isEnable($item->getProduct());
					if($item->getProductType() == 'configurable' && $_SIMPLE){
						foreach ($item->getChildren() as $child) {
							$item->getProduct()->setTaxClassId($child->getProduct()->getTaxClassId());
						}
					}
					$taxRateRequest->setProductClassId($item->getProduct()->getTaxClassId());
					$rate = $this->_calculator->getRate($taxRateRequest);
					$this->_calcRowTaxAmount($item, $rate);
					$this->_addAmount($item->getTaxAmount());
					$this->_addBaseAmount($item->getBaseTaxAmount());
					$applied = $this->_calculator->getAppliedRates($taxRateRequest);
					if ($rate > 0) {
						$itemTaxGroups[$item->getId()] = $applied;
					}
					$this->_saveAppliedTaxes(
						$address,
						$applied,
						$item->getTaxAmount(),
						$item->getBaseTaxAmount(),
						$rate
					);
					$item->setTaxRates($applied);
				}
			}
	
			if ($address->getQuote()->getTaxesForItems()) {
				$itemTaxGroups += $address->getQuote()->getTaxesForItems();
			}
			$address->getQuote()->setTaxesForItems($itemTaxGroups);
		}
        return $this;
    }

    /**
     * Calculate address total tax based on address subtotal
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @param   Varien_Object $taxRateRequest
     * @return  Mage_Tax_Model_Sales_Total_Quote
     */
    protected function _totalBaseCalculation(Mage_Sales_Model_Quote_Address $address, $taxRateRequest)
    {
        if(version_compare(Mage::getVersion(),'1.8.1.0','>='))
		{
			$items = $this->_getAddressItems($address);
			$store = $address->getQuote()->getStore();
			$taxGroups = array();
			$itemTaxGroups = array();
			$catalogPriceInclTax = $this->_config->priceIncludesTax($store);
	
			foreach ($items as $item) {
				if ($item->getParentItem()) {
					continue;
				}
				
				if ($item->getHasChildren() && $item->isChildrenCalculated()) {
					foreach ($item->getChildren() as $child) {
						$this->_totalBaseProcessItemTax(
							$child, $taxRateRequest, $taxGroups, $itemTaxGroups, $catalogPriceInclTax);
					}
					$this->_recalculateParent($item);
				} else {
					$_SIMPLE = Mage::helper('configurableproductssimpleprices')->isEnable($item->getProduct());
					if($item->getProductType() == 'configurable' && $_SIMPLE){
						foreach ($item->getChildren() as $child) {
							$item->getProduct()->setTaxClassId($child->getProduct()->getTaxClassId());
						}
					}
					$this->_totalBaseProcessItemTax(
						$item, $taxRateRequest, $taxGroups, $itemTaxGroups, $catalogPriceInclTax);
				}
			}
			
			if ($address->getQuote()->getTaxesForItems()) {
				$itemTaxGroups += $address->getQuote()->getTaxesForItems();
			}
			$address->getQuote()->setTaxesForItems($itemTaxGroups);
	
			foreach ($taxGroups as $taxId => $data) {
				if ($catalogPriceInclTax) {
					$rate = (float)$taxId;
				} else {
					$rate = $data['applied_rates'][0]['percent'];
				}
	
				$inclTax = $data['incl_tax'];
	
				$totalTax = array_sum($data['tax']);
				$baseTotalTax = array_sum($data['base_tax']);
				$this->_addAmount($totalTax);
				$this->_addBaseAmount($baseTotalTax);
				$totalTaxRounded = $this->_calculator->round($totalTax);
				$baseTotalTaxRounded = $this->_calculator->round($totalTaxRounded);
				$this->_saveAppliedTaxes($address, $data['applied_rates'], $totalTaxRounded, $baseTotalTaxRounded, $rate);
			}
		} else {
			$items          = $this->_getAddressItems($address);
			$store          = $address->getQuote()->getStore();
			$taxGroups      = array();
			$itemTaxGroups  = array();
	
			foreach ($items as $item) {
				if ($item->getParentItem()) {
					continue;
				}
	
				if ($item->getHasChildren() && $item->isChildrenCalculated()) {
					foreach ($item->getChildren() as $child) {
						$taxRateRequest->setProductClassId($child->getProduct()->getTaxClassId());
						$rate = $this->_calculator->getRate($taxRateRequest);
						$applied_rates = $this->_calculator->getAppliedRates($taxRateRequest);
						$taxGroups[(string)$rate]['applied_rates'] = $applied_rates;
						$taxGroups[(string)$rate]['incl_tax'] = $child->getIsPriceInclTax();
						$this->_aggregateTaxPerRate($child, $rate, $taxGroups);
						if ($rate > 0) {
							$itemTaxGroups[$child->getId()] = $applied_rates;
						}
					}
					$this->_recalculateParent($item);
				} else {
					$_SIMPLE = Mage::helper('configurableproductssimpleprices')->isEnable($item->getProduct());
					if($item->getProductType() == 'configurable' && $_SIMPLE){
						foreach ($item->getChildren() as $child) {
							$item->getProduct()->setTaxClassId($child->getProduct()->getTaxClassId());
						}
					}
					$taxRateRequest->setProductClassId($item->getProduct()->getTaxClassId());
					$rate = $this->_calculator->getRate($taxRateRequest);
					$applied_rates = $this->_calculator->getAppliedRates($taxRateRequest);
					$taxGroups[(string)$rate]['applied_rates'] = $applied_rates;
					$taxGroups[(string)$rate]['incl_tax'] = $item->getIsPriceInclTax();
					$this->_aggregateTaxPerRate($item, $rate, $taxGroups);
					if ($rate > 0) {
						$itemTaxGroups[$item->getId()] = $applied_rates;
					}
				}
			}
	
			if ($address->getQuote()->getTaxesForItems()) {
				$itemTaxGroups += $address->getQuote()->getTaxesForItems();
			}
			$address->getQuote()->setTaxesForItems($itemTaxGroups);
	
			foreach ($taxGroups as $rateKey => $data) {
				$rate = (float) $rateKey;
				$inclTax = $data['incl_tax'];
				$totalTax = $this->_calculator->calcTaxAmount(array_sum($data['totals']), $rate, $inclTax);
				$baseTotalTax = $this->_calculator->calcTaxAmount(array_sum($data['base_totals']), $rate, $inclTax);
				$this->_addAmount($totalTax);
				$this->_addBaseAmount($baseTotalTax);
				$this->_saveAppliedTaxes($address, $data['applied_rates'], $totalTax, $baseTotalTax, $rate);
			}
		}
		return $this;
	}
	
}