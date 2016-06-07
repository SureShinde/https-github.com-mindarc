<?php

class Extendware_EWReviewReminder_Model_Observer_Public
{
	static public function beforeSend($observer){
        $reminder = $observer->getEvent()->getReminder();
        $isAuto = $observer->getEvent()->getIsAuto();
        $returnObject = $observer->getEvent()->getReturnObject();
		if ($isAuto === false) return;
		
        $returnObject->setAction('send');
    }
}
