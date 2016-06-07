<?php
class Ipragmatech_Checkoutstep_Model_Observer {
	
	const ORDER_ATTRIBUTE_FHC_ID = 'checkoutstep';
	
	public function hookToOrderSaveEvent() {
		if (Mage::helper('checkoutstep')->isEnabled()) {
			$order = new Mage_Sales_Model_Order ();
			$incrementId = Mage::getSingleton ( 'checkout/session' )->getLastRealOrderId ();
			$order->loadByIncrementId ( $incrementId );
			
			// Fetch the data 
			$_checkoutstep_data = null;
			$_checkoutstep_data = Mage::getSingleton ( 'core/session' )->getIpragmatechCheckoutstep ();
			$model = Mage::getModel ( 'checkoutstep/customerdata' )->setData ( unserialize ( $_checkoutstep_data ) );
			$model->setData ( "order_id",$order["entity_id"] );
			try {
				$insertId = $model->save ()->getId ();
				Mage::log ( "Data successfully inserted. Insert ID: " . $insertId, null, 'mylog.log');
			} catch ( Exception $e ) {
				Mage::log ( "EXCEPTION " . $e->getMessage (), null, 'mylog.log' );
			}
		}
	}
}