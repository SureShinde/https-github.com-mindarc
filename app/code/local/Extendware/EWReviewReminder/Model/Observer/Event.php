<?php

class Extendware_EWReviewReminder_Model_Observer_Event
{
	static public function reviewSaveCommitAfter($observer) {
        $review = $observer->getEvent()->getObject();
		// delete all reminders with this customer id
		if ($review->getCustomerId() > 0) {
			Mage::getResourceModel('ewreviewreminder/reminder')->deleteByCustomerId($review->getCustomerId());
			
			$customer = Mage::getModel('customer/customer')->load($review->getCustomerId());
			if ($customer->getId() > 0) {
				Mage::getResourceModel('ewreviewreminder/reminder')->deleteByCustomerEmail(trim($customer->getEmail()));
			}
		}
    }
}
