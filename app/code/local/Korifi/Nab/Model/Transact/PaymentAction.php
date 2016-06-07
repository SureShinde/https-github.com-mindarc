<?php
class Korifi_Nab_Model_Transact_PaymentAction
{
	public function toOptionArray()
	{
		return array(
			array(
				'value' => Korifi_Nab_Model_Transact::PAYMENT_ACTION_AUTH_CAPTURE,
				'label' => Mage::helper('nab')->__('Authorise and Capture')
			),
			array(
				'value' => Korifi_Nab_Model_Transact::PAYMENT_ACTION_AUTH,
				'label' => Mage::helper('nab')->__('Authorise')
			)
		);
	}
}

?>
