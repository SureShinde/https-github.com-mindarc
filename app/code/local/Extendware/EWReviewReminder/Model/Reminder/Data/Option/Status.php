<?php
class Extendware_EWReviewReminder_Model_Reminder_Data_Option_Status extends Extendware_EWCore_Model_Data_Option_Singleton_Abstract
{
	public function __construct()
	{
		$this->options = array(
        	'pending' => $this->__('Pending'),
            'invalid' => $this->__('Invalid'),
        );
        
        parent::__construct();
	}
}