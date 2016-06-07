<?php
class Extendware_EWReviewReminder_Model_Reminder extends Extendware_EWCore_Model_Mage_Core_Abstract
{
	protected $template;
	protected $salesRule;
	
    public function _construct()
    {
        parent::_construct();
        $this->_init('ewreviewreminder/reminder');
        $this->allowAllPermissionsFor('guest');
        
        $this->addModelMethod('customer', 'customer/customer');
        $this->addModelMethod('order', 'sales/order');
        
        $this->addOptionModelMethod('status');
    }
    
	protected function _beforeSave()
	{
		$this->fieldWalk(array('customer_email'), array('trim'), self::FIELD_WALK_SCALAR);
		
		if ($this->isDataEmptyFor(array('store_id', 'reminder_num', 'customer_email', 'order_id'))) {
			Mage::throwException($this->__('Missing data for item'));
		}
		
		if ($this->getCustomerId() != $this->getCustomer()->getId()) {
			$this->setCustomerId(null);
		}
		
		if (!$this->getScheduledAt()) {
			$time = strtotime($this->getDelayReferenceDate());
			$delay = $this->mHelper()->getSecondsByData($this->getReminderConfigData('email/delay_magnitude'), $this->getReminderConfigData('email/delay_period'));
			
			$this->setScheduledAt(date('Y-m-d H:i:s', $time + $delay));
		}
		
		if (!$this->getRecoveryCode()) {
			$key = $this->getQuoteId() . '-' . $this->getCustomerEmail();
			$this->setRecoveryCode(strtoupper(sha1(uniqid($key . '-D&#$*SDFkajf', true))));
		}
		
		if (!$this->getStatus()) {
			$this->setStatus('pending');
		}

		if ($this->isObjectNew() === true) {
		    $this->createCoupon();
		}
		
		$this->setUpdatedAt(now());
		if (is_empty_date($this->getCreatedAt())) {
			$this->setCreatedAt(now());
		}
		
		return parent::_beforeSave();
	}

 	protected function _afterLoad()
	{
		if ($this->isObjectNew() === false) {
		    $this->updateCoupon();
            if ($this->dataHasChangedFor('coupon_code')) {
                $this->save();
            }
	    }
		return parent::_afterLoad();
	}
	
    protected function _afterSave() {
	    if ($this->isObjectNew() === true) {
		    $this->updateCoupon();
	    }
	    return parent::_afterSave();
	}
	
	protected function _afterDelete()
	{
		// we need to check to ensure this has not been sent before deleting coupon
		/*if ($this->getData('coupon_code')) {
			$salesRule = $this->getSalesRule();
			if ($salesRule) {
				$salesRule->delete();
			}
		}*/
		
		return parent::_afterDelete();
	}
	
	protected function getSalesRule()
	{
		if (!$this->salesRule) {
		    $coupon = $this->getCouponObject();
			if ($coupon) {
				$this->salesRule = Mage::getModel('salesrule/rule')->load($coupon->getRuleId());
			}
		}

		return $this->salesRule;
	}
	
	protected function getDelayReferenceDate()
	{
		$date = $this->getData($this->getReminderConfigData('email/delay_reference'));
		if (is_empty_date($date)) $date =  $this->getLastOrderedAt();
		if (is_empty_date($date)) $date = now();
		
		return $date;
	}

    protected function getCouponObject() {
        if ($this->getCouponCode()) {
    		$coupons = Mage::getModel('salesrule/coupon')->getCollection();
    		$coupons->addFieldToFilter('code', $this->getCouponCode());
    		if ($coupons->count() == 1) {
    			return  $coupons->getFirstItem();
    		}
        }
		return null;
	}
	
	protected function createCoupon()
	{
	    if ($this->getCouponIsEnabled() === false) {
		    return false;
		}
		
	    if ($this->getData('coupon_code') and $this->getCouponObject()) {
	        return true;
	    }

	    if ($this->getCouponGenerationMode() == 'copy') {
	    	try {
	    		$old = Mage::getModel('salesrule/rule')->load($this->getCouponBaseSalesRuleId());
	    		if (!$old->getId()) return false;

	    		$new = Mage::getModel('salesrule/rule');
				$new->setData($old->getData());
				$new->setId(null);
				$new->setData('is_active', 1);
				$new->setData('times_used', 0);
				$new->setData('is_rss', 0);
				$new->setData('use_auto_generation', 0);
				$new->setData('name', 'Review Reminder');
				$new->setData('coupon_type', 2);
				$new->setData('coupon_code', $this->getCouponCode() ? $this->getCouponCode() : strtoupper('RR-' . $this->getId() . 'N' . substr(md5(uniqid('', true)), 0, 7)));
				$new->setData('sort_order', $this->getCouponPriority());
		    	$new->setData('from_date', '');
				$new->setData('to_date', '');
				
				// support old versions of magento
				try {
					$new->_resetActions();
		    		$actionsArr = unserialize($new->getActionsSerialized());
			        if (!empty($actionsArr) && is_array($actionsArr)) {
			            $new->getActions()->loadArray($actionsArr);
			        }
			        
					$new->_resetConditions();
		    		$conditionsArr = unserialize($new->getConditionsSerialized());
			        if (!empty($actionsArr) && is_array($conditionsArr)) {
			            $new->getConditions()->loadArray($conditionsArr);
			        }
				} catch (Exception $e) {}

				if ($this->getCouponExpiryMagnitude() > 0) {
		        	$new->setData('to_date', strftime('%Y-%m-%d', time() + 60*60*24*$this->getCouponExpiryMagnitude()));
		        }
				$new->save();
				$this->setCouponCode($new->getData('coupon_code'));
	    	} catch (Exception $e) {
	    		Mage::logException($e);
	    		return false;
	    	}
	    } elseif ($this->getCouponGenerationMode() == 'create') {
		    $store = Mage::app()->getStore($this->getStoreId());
	
			$couponData = array();
	        $couponData['name']      = 'Review Reminder';
	        $couponData['is_active'] = 1;
	        $couponData['sort_order'] = $this->getCouponPriority();
	        $couponData['website_ids'] = array($store->getWebsiteId());
	        $couponData['coupon_type'] = 2;
	        $couponData['coupon_code'] = $this->getCouponCode() ? $this->getCouponCode() : strtoupper('RR-' . $this->getId() . 'N' . substr(md5(uniqid('', true)), 0, 7));
	        $couponData['uses_per_coupon'] = 1;
	        $couponData['uses_per_customer'] = 1;
	        $couponData['from_date'] = ''; //current date
	        $couponData['to_date'] = '';
	        $couponData['stop_rules_processing'] = $this->getCouponStopRulesProcessing();
	        if ($this->getCouponExpiryMagnitude() > 0) {
	        	$couponData['to_date'] = strftime('%Y-%m-%d', time() + 60*60*24*$this->getCouponExpiryMagnitude());;
	        }
	        $couponData['simple_action']   = $this->getCouponType();
	        $couponData['coupon_amount'] = $this->getCouponAmount(); // < 1.5
	        $couponData['discount_amount'] = $this->getCouponAmount(); // >= 1.5
	        $couponData['conditions'] = array(
	            1 => array(
	                'type'       => 'salesrule/rule_condition_combine',
	                'aggregator' => 'all',
	                'value'      => 1,
	                'new_child'  => '',
	            )
	        );
	        
	        $couponData['conditions']['1--1'] = array(
	        	'type' => 'salesrule/rule_condition_address',
	        	'attribute'	=> 'base_subtotal',
				'operator' => '>=',
				'value'      => $this->getCouponMinBaseSubtotal(),
	        );
	        
	        $couponData['actions'] = array(
	            1 => array(
	                'type'       => 'salesrule/rule_condition_product_combine',
	                'aggregator' => 'all',
	                'value'      => 1,
	                'new_child'  =>'',
	            )
	        );
	
	        //create for all customer groups
	        $couponData['customer_group_ids'] = array();
	        
	        $found = false;
	        $customerGroups = Mage::getResourceModel('customer/group_collection')->load();
	        foreach ($customerGroups as $group) {
	            if ($group->getId() == 0) {
	                $found = true;
	            }
	            $couponData['customer_group_ids'][] = $group->getId();
	        }
	        if (!$found) {
	            $couponData['customer_group_ids'][] = 0;
	        }
	        
	        try {
	            Mage::getModel('salesrule/rule')
	                ->loadPost($couponData)
	                ->save();
		        $this->setCouponCode($couponData['coupon_code']);
	        } catch (Exception $e) {
	            $couponData['coupon_code'] = '';
	            Mage::logException($e);
	            return false;
	        }
	    } else return false;
	    
        return true;
	}

    protected function updateCoupon() {
	    $this->createCoupon();
	    
	    $coupon = $this->getCouponObject();
	    if ($coupon) {
		    $salesRule = $this->getSalesRule();
			if ($salesRule) {
				$coupon->setExpirationDate(date('Y-m-d', time() + $this->getCouponExpiryMagnitude()*24*60*60));
				$coupon->save();
				
			    $salesRule->setName('Review Reminder #' . $this->getId());
				$salesRule->setToDate(date('Y-m-d', time() + $this->getCouponExpiryMagnitude()*24*60*60));
				$salesRule->save();
			}
	    }
	    
		return $this;
	}
	
	public function getReminderConfigData($path)
	{
		return $this->mHelper()->getReminderConfigData($path, $this->getReminderNum(), $this->getStoreId());
	}
	
	public function getConfigNumReminders()
	{
		return (int) Mage::getStoreConfig('ewreviewreminder/general/num_reminders', $this->getStoreId());
	}
	
	public function getCouponType()
	{
		return $this->getReminderConfigData('coupon/type');
	}
	
	public function getCouponAmount()
	{
		return $this->getReminderConfigData('coupon/amount');
	}
	
	public function getCouponGenerationMode()
	{
		return $this->getReminderConfigData('coupon/generation_mode');
	}
	
	public function getCouponMinBaseSubtotal()
	{
		return (float)$this->getReminderConfigData('coupon/min_base_subtotal');
	}
	
	public function getCouponIsEnabled()
	{
		return (bool)$this->getReminderConfigData('coupon/status');
	}
	
	public function getCouponExpiryMagnitude()
	{
		return $this->getReminderConfigData('coupon/expiry_magnitude');
	}
	
	public function getCouponBaseSalesRuleId()
	{
		return intval($this->getReminderConfigData('coupon/base_sales_rule_id'));
	}
	
	public function getCouponStopRulesProcessing()
	{
		return intval($this->getReminderConfigData('coupon/stop_rules_processing'));
	}
	
	public function getCouponExpiryPeriod()
	{
		return $this->__('day(s)');
	}
	
	public function getCouponPriority() {
		return intval($this->getReminderConfigData('coupon/priority'));
	}
	
	public function getTemplate()
	{
		if (!$this->template) {
			$this->template = Mage::getModel('core/email_template');
			$this->template->setTemplateFilter(Mage::getModel('ewreviewreminder/email_template_filter'));
			$this->template->getTemplateFilter()->setStoreId($this->getStoreId());
	        $this->template->setUseAbsoluteLinks(true);
	        $this->template->setDesignConfig(array('area'=>'frontend', 'store'=>$this->getStoreId()));
	        $this->template->loadDefault($this->getTemplateId(), Mage::getStoreConfig('general/locale/code', $this->getStoreId()));
	        if (is_null($this->template->getId())) {
				$this->template->load($this->getTemplateId());
	        }
		}
		
		return $this->template;
	}
	
	public function getTemplateId()
	{
		return $this->getReminderConfigData('email/template');
	}
	
	public function getTemplateType()
	{
		return $this->getTemplate()->isPlain() === true ? 'plain' : 'html';
	}
	
	public function getEmailType()
	{
		return $this->getTemplateType();
	}
	
	public function getTemplateVariables()
	{
		$store = Mage::app()->getStore($this->getStoreId());
		$oldStore = Mage::app()->getStore();
		Mage::app()->setCurrentStore($store->getId());
		$baseUrl = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
		
		$productIds = array();
		$order = Mage::getModel('sales/order')->load($this->getOrderId());
		$collection = Mage::getModel('sales/order_item')->getCollection()->setOrderFilter($order);
		$collection->addFieldToFilter('parent_item_id', array('null' => true));
		foreach ($collection as $orderItem) {
			$productIds[] = $orderItem->getProductId();
		}
		
		$products = new Varien_Data_Collection();
		foreach ($productIds as $productId) {
			$product = Mage::getModel('ewreviewreminder/product')->setStoreId($store->getId());
			$product->load($productId);
			if ($product->isVisibleInCatalog() === false) continue;
			if ($product->getStatus() != 1) continue;
			if ($products->getItemById($productId)) continue;
			
			$reviewUrl = $baseUrl . (string)Mage::app()->getConfig()->getNode('frontend/routers/ewreviewreminder/args/frontName') . '/recover/review/id/' . $this->getId() . '/code/'.$this->getRecoveryCode().'/pid/' . $productId . '/';
			$product->setReviewUrl($reviewUrl);
			$product->setFormattedPrice(Mage::helper('core')->currency($product->getPrice(), true, false));
			$product->setFormattedFinalPrice(Mage::helper('core')->currency($product->getFinalPrice(), true, false));
			$products->addItem($product);
		}
		
		$variables = array(
			'products' => $products,
            'order' => $order,
			'product_count' => $products->count(),
            'customer_name' => $this->getCustomerName(),
            'customer_firstname' => $this->getCustomerFirstname(),
            'customer_lastname' => $this->getCustomerLastname(),
			'customer_name_capitalized' => @uc_words(strtolower($this->getCustomerName())),
            'customer_firstname_capitalized' => @uc_words(strtolower($this->getCustomerFirstname())),
            'customer_lastname_capitalized' => @uc_words(strtolower($this->getCustomerLastname())),
			'reminder_num' => $this->getReminderNum(),
			'unsubscribe_url' => $baseUrl . (string)Mage::app()->getConfig()->getNode('frontend/routers/ewreviewreminder/args/frontName') . '/recover/unsubscribe/id/' . $this->getId() . '/code/'.$this->getRecoveryCode(),
			'tracking_params' => $this->getReminderConfigData('email/tracking_params'),
            'quote_id' => $this->getQuoteId(),
			'store' => $store
		);
		if ($this->getCouponIsEnabled() === true) {
			$variables['coupon_code'] = $this->getCouponCode();
			$variables['coupon_expiry_magnitude'] = $this->getCouponExpiryMagnitude();
			$variables['coupon_expiry_period'] = $this->getCouponExpiryPeriod();
		}
		
		$object = new Varien_Object();
        $object->setTemplateVariables($variables);
		Mage::dispatchEvent('ewreviewreminder_after_get_template_variables', array(
			'reminder' => clone $this,
			'transport' => $object,
		));
		
		Mage::app()->setCurrentStore($oldStore);
		
		return (array)$object->getTemplateVariables();
	}
	
	public function getEmailSubject()
	{
		if ($this->getData('email_subject')) {
			return $this->getData('email_subject');
		}
		
		else return $this->getDefaultEmailSubject();
	}
	
	public function getEmailText()
	{
		if ($this->getData('email_text')) {
			return $this->getData('email_text');
		} else return $this->getDefaultEmailText();
	}
	
	public function getDefaultEmailSubject()
	{
		// done just in case
		$oldStore = Mage::app()->getStore();
		Mage::app()->setCurrentStore($this->getStoreId());
			$variables = $this->getTemplateVariables();
			$text = $this->getTemplate()->getProcessedTemplateSubject($variables);
		Mage::app()->setCurrentStore($oldStore);
		return $text;
	}
	
	public function getDefaultEmailText()
	{
		// done just in case
		$oldStore = Mage::app()->getStore();
		Mage::app()->setCurrentStore($this->getStoreId());
			$variables = $this->getTemplateVariables();
			$text = $this->getTemplate()->getProcessedTemplate($variables);
		Mage::app()->setCurrentStore($oldStore);
		return $text;
	}
    
    protected function getSenderId()
    {
    	return Mage::getStoreConfig('ewreviewreminder/email/sender_identity', $this->getStoreId());
    }
    
	protected function getSenderName()
    {
		return Mage::getStoreConfig('trans_email/ident_' . $this->getSenderId() . '/name', $this->getStoreId());
    }
    
    protected function getSenderEmail()
    {
		return Mage::getStoreConfig('trans_email/ident_' . $this->getSenderId() . '/email', $this->getStoreId());
    }

    public function send($isAuto = false)
    {
    	if (Mage::helper('ewcore/environment')->isDevelopmentServer()) {
    		Mage::throwException($this->__('Sending is disabled on the development server.'));
    		return false;
    	}
    	
    	if ($this->getReminderNum() > Mage::helper('ewreviewreminder/adminhtml_config')->setStoreScope($this->getStoreId())->getNumReminders()) {
    		$this->delete();
        	Mage::throwException($this->__('Reminder followup number allowed based on store configuration. Reminder has been deleted'));
        	return false;
        }
        
        $returnObject = new Varien_Object(array('action' => 'send'));
        Mage::dispatchEvent('ewreviewreminder_before_send', array(
			'return_object' => $returnObject,
			'reminder' => clone $this,
        	'is_auto' => $isAuto,
		));

		if ($returnObject->getAction() == 'delete') {
			$this->delete();
			return false;
		} elseif ($returnObject->getAction() != 'send') {
			return false;
		}
		
		$isSent = false;
		$history = null;
        try {
        	$this->updateCoupon();
        	$salesRule = $this->getSalesRule();
        	
        	$email = Mage::getModel('ewreviewreminder/networking_client_email');
        	$email->setFromEmail($this->getSenderEmail());
			$email->setFromName($this->getSenderName());
			$email->setStoreId($this->getStoreId());
			
			$isSent = true;
			if (Mage::helper('ewcore/environment')->isDevelopmentServer() === false) {
				$isSent = $email->send(
	        		$this->getTemplateType(),
	        		$this->getCustomerName(),
	        		$this->getCustomerEmail(),
	        		$this->getEmailSubject(),
	        		$this->getEmailText()
	        	);
			}
			
        	if ($isSent === false) {
        		Mage::throwException($this->__('Could not successfully send reminder email'));
        	}
        	
        	try {
        		if (Mage::helper('ewcore/environment')->isDevelopmentServer() === false) {
		        	foreach ($this->mHelper()->getBccEmails()  as $bccEmail) {
						$email = Mage::getModel('ewreviewreminder/networking_client_email');
						$email->setFromEmail($this->getSenderEmail());
						$email->setFromName($this->getSenderName());
						$email->setStoreId($this->getStoreId());
						$email->setReplyTo($this->getCustomerEmail());
						
						$email->send(
							$this->getTemplateType(),
							$this->getCustomerName(),
							$bccEmail,
							$this->getEmailSubject(),
							$this->getEmailText()
						);
					}
        		}
        	} catch (Exception $e) {
        		Mage::logException($e);
        	}
        	
			$history = Mage::getModel('ewreviewreminder/history');
			$history->setStoreId($this->getStoreId());
			$history->setOrderId($this->getOrderId());
			$history->setCustomerId($this->getCustomerId());
			$history->setReminderId($this->getId());
			$history->setSentAt(now());
			$history->setReminderNum($this->getReminderNum());
			$history->setCustomerName($this->getCustomerName());
			$history->setCustomerEmail($this->getCustomerEmail());
			$history->setEmailSubject($this->getEmailSubject());
			$history->setEmailType($this->getEmailType());
			$history->setEmailText($this->getEmailText());
			$history->setRecoveryCode($this->getRecoveryCode());
			$history->setCouponCode($this->getCouponCode());
			$history->setLastOrderedAt($this->getLastOrderedAt());

        	if ($salesRule) {
				$history->setCouponExpiresAt(date('Y-m-d', strtotime($salesRule->getToDate()) + 60*60*24));
			}
			$history->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        if ($isSent === true) {
        	$this->delete();
        	if ($this->getReminderNum() < $this->getConfigNumReminders()) {
        		$reminder = Mage::getModel('ewreviewreminder/reminder');
	        	try {
	        		$reminder->setData($this->getData());
	        		$reminder->setId(null);
	        		$reminder->setReminderNum($this->getReminderNum() + 1);
	        		$reminder->setLastRemindedAt(now());
	        		$reminder->setStatus(null);
	        		$reminder->setEmailText(null);
	        		$reminder->setEmailSubject(null);
	        		$reminder->setCouponCode(null);
	        		$reminder->setRecoveryCode(null);
	        		$reminder->setInvalidAt(null);
	        		$reminder->setScheduledAt(null);
	        		$this->setUpdatedAt(null);
					$this->setCreatedAt(null);
	        		$reminder->save();
	        	} catch (Exception $e) {
		            $reminder->delete();
		            Mage::logException($e);
		        }
        	}
        } else {
            $this->setStatus('invalid');
            if (is_empty_date($this->getInvalidAt())) {
            	$this->setInvalidAt(now());
            }
            $this->save();
            if ($history and $history->getId() > 0) {
            	$history->delete();
            }
        }
        
        return $isSent;
    }
    
    protected function getCustomerName(){
        if (!$this->getCustomerFirstname() && !$this->getCustomerFirstname()) {
        	if ($this->getStoreId()) {
        		return Mage::getStoreConfig('ewreviewreminder/email/default_customer_name', $this->getStoreId());
        	} else {
        		return $this->__('Valued Customer');
        	}
        } else {
        	return $this->getCustomerFirstname() . ' ' . $this->getCustomerLastname();
        }
    }
       
}