<?php

class Extendware_EWReviewReminder_Model_Blacklist extends Extendware_EWCore_Model_Mage_Core_Abstract
{
	protected $gotSubject = false;
	protected $gotBody = false;
	
    public function _construct()
    {
        parent::_construct();
        $this->_init('ewreviewreminder/blacklist');
    }
    
	protected function _beforeSave()
	{
		if ($this->isDataEmptyFor(array('email_address'))) {
			Mage::throwException($this->__('Missing data for item'));
		}
		
		$this->setUpdatedAt(now());
		if (is_empty_date($this->getCreatedAt())) {
			$this->setCreatedAt(now());
		}
		
		return parent::_beforeSave();
	}
	
	public function loadByEmail($value) {
		return $this->load((string)$value, 'email_address');
    }
}