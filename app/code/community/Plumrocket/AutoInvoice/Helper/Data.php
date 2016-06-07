<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please 
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file
 *
 * @package     Plumrocket_Auto_Invoice
 * @copyright   Copyright (c) 2015 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */


class Plumrocket_AutoInvoice_Helper_Data extends Plumrocket_AutoInvoice_Helper_Main
{
	private $_configPrefix = 'autoinvoice/';
	
	public function moduleEnabled($storeId = null){
		return (bool)Mage::getStoreConfig($this->_configPrefix.'general/enabled', $storeId);
	}

	public function sendInvoiceAfterOrderIsCreated($storeId = null){
		return !Mage::getStoreConfig($this->_configPrefix.'general/send_invoice', $storeId);
	}

	public function sendInvoiceAfterOrderIsShipped($storeId = null){
		return Mage::getStoreConfig($this->_configPrefix.'general/send_invoice', $storeId) == 1;
	}
	
	public function getCaptureAmount($storeId = null){
		$value = Mage::getStoreConfig($this->_configPrefix.'general/capture', $storeId);
		if (!$value){
			$value = Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE;
		}
		return $value;
	}
}
	 
