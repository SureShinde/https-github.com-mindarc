<?php
class Extendware_EWReviewReminder_Model_History_Data_Option_Coupon_Redeemed extends Extendware_EWCore_Model_Data_Option_Singleton_Abstract
{
	public function __construct()
	{
		$this->options = array(
			'1' => $this->__('Yes'),
			'0' => $this->__('No'),
			'disabled' => $this->__('Disabled'),
		
		);
        parent::__construct();
	}
}
