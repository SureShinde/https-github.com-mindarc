<?php
class Extendware_EWReviewReminder_Block_Js_General extends Extendware_EWCore_Block_Generated_Js
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('extendware/ewreviewreminder/js/general.phtml');
    }

    public function getCacheKey() {
        $key = parent::getCacheKey();
        return md5($key);
	}
}

