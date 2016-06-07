<?php

class Extendware_EWReviewReminder_RecoverController extends Extendware_EWCore_Controller_Frontend_Action
{

	public function reviewAction()
	{
		$id = (int) $this->getInput('id');
		$pid = (int) $this->getInput('pid');
		$recoveryCode = (string) $this->getInput('code');
		
		$params = array('reminder_id' => $id, 'recovery_code' => $recoveryCode);
		$history = Mage::getModel('ewreviewreminder/history')->loadByData($params);
		
		if (!$history->getId() || $history->getRecoveryCode() != $recoveryCode) {
			return $this->_redirect('/');
		}
		
		$this->processVisit($history);
		
		$product = Mage::getModel('catalog/product')->setStoreId($history->getStoreId())->load($pid);
		if (!$product->getId()) {
			return $this->_redirect('/');
		}
		
		$url = $product->getProductUrl();
		$trackingParams = $this->mHelper()->getReminderConfigData('email/tracking_params', $history->getReminderNum(), $history->getStoreId());
		if ($trackingParams) {
			if (strpos($url, '?') === false) $url .= '?';
			else $url .= '&';
			$url .= $trackingParams;
		}
		
		header('Location: ' . $url);
	}
	
	protected function updateHistory($history)
	{
		if ($history and $history->getId() > 0) {
			if (is_empty_date($history->getRecoveredAt())) {
				$history->setRecoveredAt(now());
			}
			if (!$history->getRecoveredFrom()) {
				if (isset($_SERVER['REMOTE_ADDR'])) {
					$history->setRecoveredFrom($_SERVER['REMOTE_ADDR']);
				}
			}
			
			$history->save();
		}
	}
	
	protected function processVisit($history)
	{
		$this->updateHistory($history);
		if (Mage::getStoreConfig('ewrevewreminder/general/stop_after_visit')) {
			// delete all reminders with this email address
			$model = Mage::getResourceModel('ewrevewreminder/reminder');
			$model->deleteByCustomerEmail($history->getCustomerEmail());
			
			// delete all reminders with this customer id
			if ($history->getCustomerId() > 0) {
				$model = Mage::getResourceModel('ewrevewreminder/reminder');
				$model->deleteByCustomerId($history->getCustomerId());
			}
		}
	}
	public function unsubscribeAction()
	{
		$id = (int) $this->getInput('id');
		$recoveryCode = (string) $this->getInput('code');
		
		$params = array('reminder_id' => $id, 'recovery_code' => $recoveryCode);
		$history = Mage::getModel('ewreviewreminder/history')->loadByData($params);
		
		if (!$history->getId() || $history->getRecoveryCode() != $recoveryCode) {
			return $this->_redirect('/');
		}
		
		$model = Mage::getModel('ewreviewreminder/blacklist')->loadByEmail($history->getCustomerEmail());
		if (!$model->getId()) {
			$model->setEmailAddress($history->getCustomerEmail());
			$model->save();
		}
		
		Mage::getResourceModel('ewreviewreminder/reminder')->deleteByCustomerEmail($history->getCustomerEmail());
		
		if (Extendware::helper('ewpagecache')) {
    		Mage::helper('ewpagecache')->setReasonNotDefaultRequest('reminder', true);
    		Mage::helper('ewpagecache')->sendIsNotDefaultRequestCookie();
    	}
    	
		$this->_getSession()->addSuccess($this->__('You will no longer receive any more order reminder e-mails'));
		return $this->_redirect('/');
	}
}

