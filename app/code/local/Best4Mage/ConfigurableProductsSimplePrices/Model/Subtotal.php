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
 * Calculate items and address amounts including/excluding tax
 */
class Best4Mage_ConfigurableProductsSimplePrices_Model_Subtotal extends Mage_Tax_Model_Sales_Total_Quote_Subtotal
{
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $this->_store = $address->getQuote()->getStore();
        $this->_address = $address;

        $this->_subtotalInclTax = 0;
        $this->_baseSubtotalInclTax = 0;
        $this->_subtotal = 0;
        $this->_baseSubtotal = 0;
        $this->_roundingDeltas = array();

        $address->setSubtotalInclTax(0);
        $address->setBaseSubtotalInclTax(0);
        $address->setTotalAmount('subtotal', 0);
        $address->setBaseTotalAmount('subtotal', 0);

        $items = $this->_getAddressItems($address);
        if (!$items) {
            return $this;
        }

        $addressRequest = $this->_getAddressTaxRequest($address);
        $storeRequest = $this->_getStoreTaxRequest($address);
        $this->_calculator->setCustomer($address->getQuote()->getCustomer());
        if ($this->_config->priceIncludesTax($this->_store)) {
            $classIds = array();
            foreach ($items as $item) {
                $classIds[] = $item->getProduct()->getTaxClassId();
                if ($item->getHasChildren()) {
                    foreach ($item->getChildren() as $child) {
                        $classIds[] = $child->getProduct()->getTaxClassId();
                    }
                }
            }
            $classIds = array_unique($classIds);
            $storeRequest->setProductClassId($classIds);
            $addressRequest->setProductClassId($classIds);
            $this->_areTaxRequestsSimilar = $this->_calculator->compareRequests($storeRequest, $addressRequest);
        }

        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                foreach ($item->getChildren() as $child) {
                    $this->_processItem($child, $addressRequest);
                }
                $this->_recalculateParent($item);
            } else {
                $_SIMPLE = Mage::helper('configurableproductssimpleprices')->isEnable($item->getProduct());
                if($item->getProductType() == 'configurable' && $_SIMPLE){
					foreach ($item->getChildren() as $child) {
						$item->getProduct()->setTaxClassId($child->getProduct()->getTaxClassId());
					}
				}
				$this->_processItem($item, $addressRequest);
            }
            $this->_addSubtotalAmount($address, $item);
        }
        $address->setRoundingDeltas($this->_roundingDeltas);
        return $this;
    }
}

