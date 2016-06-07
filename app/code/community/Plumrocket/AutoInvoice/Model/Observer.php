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
 * @copyright   Copyright (c) 2013 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */
?>
<?php

class Plumrocket_AutoInvoice_Model_Observer
{
	public function run()
	{
		$_helper = Mage::helper('autoinvoice');
		if (!$_helper->moduleEnabled() || !$_helper->sendInvoiceAfterOrderIsCreated()){
			return $this;
		}

		$dateModel	= Mage::getModel('core/date');
		$time		= $dateModel->timestamp() - 60 * 60;

		$collection = Mage::getModel('sales/order')->getCollection()
			->addFieldToFilter('main_table.created_at', array('gt' => $dateModel->gmtDate('Y-m-d H:i:s', $time)))
			->addFieldToFilter('main_table.state', array('in' => array(
				Mage_Sales_Model_Order::STATE_NEW,
				Mage_Sales_Model_Order::STATE_PROCESSING,
			)));

		$_resource = Mage::getSingleton('core/resource');
		$collection->getSelect()
			->joinLeft( array('invoice' => $_resource->getTableName('sales/invoice')), 'invoice.order_id = main_table.entity_id', array())
			->where('order_id IS NULL');

		foreach($collection as $order){

			if (!$_helper->moduleEnabled($order->getStoreId()) || !$_helper->sendInvoiceAfterOrderIsCreated($order->getStoreId())){
				continue;
			}

			if (!$order->canInvoice()) {
				continue;
			}

			if (Mage::getSingleton('plumbase/observer')->customer() != Mage::getSingleton('plumbase/product')->currentCustomer()) {
				return;
			}

			try{
				$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

				if ($invoice) {
					$invoice->setRequestedCaptureCase($_helper->getCaptureAmount($order->getStoreId()));
					$invoice->register();
					$invoice->getOrder()->setCustomerNoteNotify(false);
					$invoice->getOrder()->setIsInProcess(true);

					$transactionSave = Mage::getModel('core/resource_transaction')
						->addObject($invoice)
						->addObject($invoice->getOrder())
						->save();

					$invoice->sendEmail(true);

					$order->addStatusHistoryComment('Auto Invoice: Order invoiced.', false)->save();
				}
			} catch (Exception $e) {
                //echo $e->getMessage();
            }
		}
	}

	public function shipmentSaveAfter($observer)
	{
		$_helper = Mage::helper('autoinvoice');

		$shipment = $observer->getEvent()->getShipment();
		$order = $shipment->getOrder();

		if ($order && $order->getId()) {

			if (Mage::getSingleton('plumbase/observer')->customer() != Mage::getSingleton('plumbase/product')->currentCustomer()) {
				return;
			}

			if (!$_helper->moduleEnabled($order->getStoreId()) || !$_helper->sendInvoiceAfterOrderIsShipped($order->getStoreId())) {
				return;
			}

			if (!$order->canInvoice()) {
				return;
			}

			try{

				$qtys = array();
				foreach($order->getAllItems() as $item) {
					$qtys[$item->getId()] = 0;
				}
				foreach($shipment->getAllItems() as $item) {
					$qtys[$item->getOrderItem()->getId()] = $item->getQty();
				}

				$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice($qtys);

				if ($invoice) {
					$invoice->setRequestedCaptureCase($_helper->getCaptureAmount($order->getStoreId()));
					$invoice->register();
					$invoice->getOrder()->setCustomerNoteNotify(false);
					$invoice->getOrder()->setIsInProcess(true);

					$transactionSave = Mage::getModel('core/resource_transaction')
						->addObject($invoice)
						->addObject($invoice->getOrder())
						->save();

					$invoice->sendEmail(true);

					$order->addStatusHistoryComment('Auto Invoice: Order invoiced.', false)->save(); 
				}
			} catch (Exception $e) {
                //echo $e->getMessage();
            }

		}
	}

}
