<?php

require_once 'Mage/Checkout/controllers/OnepageController.php';

class Ipragmatech_Checkoutstep_OnepageController extends Mage_Checkout_OnepageController
{
    public function doSomestuffAction()
    {
		if(true) {
			$result['update_section'] = array(
            	'name' => 'payment-method',
                'html' => $this->_getPaymentMethodsHtml()
			);					
		}
    	else {
			$result['goto_section'] = 'shipping';
		}		
    }    

    public function savePaymentAction()
    {
    	$this->_expireAjax();
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('payment', array());

             try {
                $result = $this->getOnepage()->savePayment($data);
            }
            catch (Mage_Payment_Exception $e) {
                if ($e->getFields()) {
                    $result['fields'] = $e->getFields();
                }
                $result['error'] = $e->getMessage();
            }
            catch (Exception $e) {
                $result['error'] = $e->getMessage();
            }
            $redirectUrl = $this->getOnePage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if (empty($result['error']) && !$redirectUrl) {
            	
            	//check the module is enable or not
            	if (Mage::helper('checkoutstep')->isEnabled()) {
					$this->loadLayout('checkout_onepage_checkoutstep');
					$result['goto_section'] = 'checkoutstep';
            	}
            	else {
            		$this->loadLayout('checkout_onepage_review');
            		
            		$result['goto_section'] = 'review';
            		$result['update_section'] = array(
            				'name' => 'review',
            				'html' => $this->_getReviewHtml()
            		);
            	}
            }

            if ($redirectUrl) {
                $result['redirect'] = $redirectUrl;
            }

            $this->getResponse()->setBody(Zend_Json::encode($result));
        }
    }

    public function saveCheckoutstepAction()
    {
    	$this->_expireAjax();
        if ($this->getRequest()->isPost()) {
            
        	//Grab the submited value 
        	$_entrant_name = $this->getRequest()->getPost('entrant_name',"");
        	$_entrant_phone = $this->getRequest()->getPost('entrant_phone',"");
        	$_entrant_email = $this->getRequest()->getPost('entrant_email',"");
        	$_permanent_address = $this->getRequest()->getPost('permanent_address',"");
        	$_address = $this->getRequest()->getPost('local_address',"");
        		
        	Mage::getSingleton('core/session')->setIpragmatechCheckoutstep(serialize(array(
				
			'entrant_name' =>$_entrant_name,
			'entrant_phone' =>$_entrant_phone,
			'entrant_email' =>$_entrant_email,
			'permanent_address' =>$_permanent_address,
			'address' =>$_address
			)));

			$result = array();
            
            $redirectUrl = $this->getOnePage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if (!$redirectUrl) {
                $this->loadLayout('checkout_onepage_review');

                $result['goto_section'] = 'review';
                $result['update_section'] = array(
                    'name' => 'review',
                    'html' => $this->_getReviewHtml()
                );

            }

            if ($redirectUrl) {
                $result['redirect'] = $redirectUrl;
            }

            $this->getResponse()->setBody(Zend_Json::encode($result));
        }
    }    
}
