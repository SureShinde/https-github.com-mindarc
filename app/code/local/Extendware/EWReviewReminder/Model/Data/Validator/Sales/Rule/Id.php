<?php
class Extendware_EWReviewReminder_Model_Data_Validator_Sales_Rule_Id extends Extendware_EWCore_Model_Data_Validator_Abstract
{
	public function isValidInput($input)
	{
		if (strlen($input) == 0) {
			return true;
		}
		
		$model = Mage::getModel('salesrule/rule')->load($input);
		return ($model->getId() ? true : false);
	}
}