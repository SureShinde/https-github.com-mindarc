<?php
class Extendware_EWReviewReminder_Model_History extends Extendware_EWCore_Model_Mage_Core_Abstract
{
	protected $salesRule;
	
    public function _construct()
    {
        parent::_construct();
        $this->_init('ewreviewreminder/history');
        $this->allowAllPermissionsFor('guest');
        
        $this->addModelMethod('customer', 'customer/customer');
        $this->addModelMethod('quote', 'sales/quote');
    }
    
    protected function _beforeSave()
	{
		if ($this->isDataEmptyFor(array('store_id', 'reminder_id', 'reminder_num', 'customer_name', 'customer_email', 'email_type', 'email_subject', 'email_text', 'recovery_code'))) {
			Mage::throwException($this->__('Missing data for item'));
		}
		
		if ($this->isDataEmptyFor(array('sent_at'), 'date')) {
			Mage::throwException($this->__('Missing data for item'));
		}
		
		if ($this->getCouponCodeExists() === null) {
			if ($this->getCouponCode()) {
				$this->setCouponCodeExists(1);
			}
		}
		
		if ($this->getCouponRedeemed() === null) {
			if ($this->getCouponCode()) {
				$this->setCouponRedeemed(0);
			}
		}
		
		$this->setUpdatedAt(now());
		if (is_empty_date($this->getCreatedAt())) {
			$this->setCreatedAt(now());
		}
		
		return parent::_beforeSave();
	}
	
	protected function _beforeDelete()
    {
    	$this->_protectFromNonAdmin();
        return parent::_beforeDelete();
    }
    
	protected function _afterDelete()
	{
		// delete any associated coupons
		if ($this->getData('coupon_code')) {
			$salesRule = $this->getSalesRule();
			if ($salesRule) {
				$salesRule->delete();
			}
		}
		
		return parent::_afterDelete();
	}
	
	public function getSalesRule()
	{
		if (!$this->salesRule and $this->getData('coupon_code')) {
			$collection = Mage::getResourceModel('salesrule/coupon_collection');
			$collection->addFieldToFilter('code', $this->getData('coupon_code'));
			if ($collection->getSize() == 1) {
				$coupon = $collection->getFirstItem();
				$this->salesRule = Mage::getModel('salesrule/rule')->load($coupon->getRuleId());
			}
		}
		
		return $this->salesRule;
	}
}