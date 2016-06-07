<?php
class Extendware_EWReviewReminder_Model_Reminder_Data_Option_Reminder_Num extends Extendware_EWCore_Model_Data_Option_Singleton_Abstract
{
	public function __construct()
	{
		$this->options = array();
		$maxReminders = Mage::helper('ewreviewreminder/adminhtml_config')->getMaxNumReminders();
		for ($i = 1; $i <= $maxReminders; $i++) {
			$this->options[$i] = $this->__('Reminder #%s', $i);
		}
		
        parent::__construct();
	}
}