<?php

class Extendware_EWReviewReminder_Model_Observer_Cronjob
{
    static public function generateReminders($force = false)
    {
    	if ($force === false and Mage::helper('ewreviewreminder/config')->isMagentoCronEnabled() === false) {
    		return;
    	}
    	
    	$maxTime = 60*60*30;
    	if (ini_get('max_execution_time') > 0 and ini_get('max_execution_time') <= $maxTime) {
    		ini_set('max_execution_time', $maxTime);
    	}
    	
        Mage::getResourceModel('ewreviewreminder/reminder')->generateReminders(false);
	}

	static public function sendReminders($force = false)
    {
    	if ($force === false and Mage::helper('ewreviewreminder/config')->isMagentoCronEnabled() === false) {
    		return;
    	}
    	
    	$stores = Mage::app()->getStores();
    	foreach ($stores as $store) {
    		if (!Mage::getStoreConfig('ewreviewreminder/general/status', $store->getId())) {
    			$collection = Mage::getModel('ewreviewreminder/reminder')->getCollection();
		        $collection->addFieldToFilter('store_id', $store->getId());
		        $collection->addDateToFilter('scheduled_at', 'lteq', 'now()');
		        $collection->delete();
    			continue;
    		}
    		
    		if (Mage::getStoreConfig('ewreviewreminder/email/status', $store->getId())) {
	    		$collection = Mage::getModel('ewreviewreminder/reminder')->getCollection();
		        $collection->addFieldToFilter('status', 'pending');
		        $collection->addFieldToFilter('store_id', $store->getId());
				$collection->addDateToFilter('scheduled_at', 'lteq', 'now()');
		        $collection->load();
	    		
	    		foreach ($collection as $model) {
		        	try {
		            	$model->send(true);
		        	} catch (Exception $e) {
		        		Mage::logException($e);
		        	}
		        }	    		
    		}
    	}
    }
    
	static public function updateCouponStats()
    {
        $collection = Mage::getModel('ewreviewreminder/history')->getCollection();
        $collection->addFieldToFilter('coupon_redeemed', array('neq' => 1));
		$collection->addFieldToFilter('coupon_code_exists', 1);
        $ids = $collection->getIds();
		
        foreach ($ids as $id) {
           $model = Mage::getModel('ewreviewreminder/history')->loadById($id);
           if (!$model->getId()) continue;
           
           $salesRule = $model->getSalesRule();
           if ($salesRule) {
           		$coupons = $salesRule->getCoupons();
           		foreach ($coupons as $coupon) {
           			if ($coupon->getTimesUsed() >= 1) {
           				$model->setCouponRedeemed(1);
           				$model->save();
           				break;
           			}
           		}
           }
        }
    }
    
	static public function cleanupSalesRules()
    {
    	// must be called first or else stats will become messed up
    	self::updateCouponStats();
    	
    	// delete any coupons that have expired or become used
        $collection = Mage::getModel('ewreviewreminder/history')->getCollection();
		$collection->addDateToFilter('coupon_expires_at', 'lteq', 'now()', -10, 'day');
		$collection->addFieldToFilter('coupon_code_exists', 1);
        $ids = $collection->getIds();
        
        // delete any coupons that have already been used
        $collection = Mage::getModel('ewreviewreminder/history')->getCollection();
		$collection->addFieldToFilter('coupon_redeemed', 1);
		$collection->addFieldToFilter('coupon_code_exists', 1);
        $ids = array_merge($ids, $collection->getIds());
        $ids = array_flip(array_flip($ids)); // get rid of duplicates

        
        foreach ($ids as $id) {
			$model = $collection->getNewEmptyItem()->loadById($id);
			if (!$model->getId()) continue;
           
            $salesRule = $model->getSalesRule();
            if ($salesRule) {
            	try {
            		$salesRule->delete();
            	} catch (Exception $e) { }
            }
        }
        
        // find any coupons that no longer exist in database and mark them
        $collection = Mage::getModel('ewreviewreminder/history')->getCollection();
		$collection->addFieldToFilter('coupon_code', array('neq' => ''));
		$collection->addFieldToFilter('coupon_code_exists', 1);
		$collection->load();
		
         foreach ($collection as $model) {
        	if ($model->getSalesRule() === null) {
        		$model->setCouponCodeExists(0);
        		$model->save();
        	}
        }
    }
}
