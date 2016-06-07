<?php

class Extendware_EWReviewReminder_Model_Observer_Example
{
	static function afterGetTemplateVariablesEvent(Varien_Object $observer) {
		$reminder = $observer->getReminder(); // in case you need the reminder for any reason
		
		// get the current variables from the transport
		$variables = $observer->getTransport()->getTemplateVariables();
		
		// change any variables you want here
		$variables['customer_name'] = ucwords(strtolower($variables['customer_name']));
		
		// set the modified template variables in the transport so that it will be utilized
		$observer->getTransport()->setTemplateVariables($variables);
	}
}
