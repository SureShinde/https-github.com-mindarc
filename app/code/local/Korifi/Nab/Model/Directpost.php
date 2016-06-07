<?php
class Korifi_Nab_Model_Directpost extends Mage_Payment_Model_Method_Cc
{

    protected $_code  = 'nab_directpost';

    protected $_isGateway               = true;
    protected $_canAuthorize            = false;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc               = false;
    
    // Credit Card URLs
    const CC_URL_LIVE = 'https://transact.nab.com.au/live/directpostv2/authorise';
    const CC_URL_TEST = 'https://transact.nab.com.au/test/directpostv2/authorise';
    
    const STATUS_APPROVED = 'Approved';

	const PAYMENT_ACTION_AUTH_CAPTURE = 'authorize_capture';
	const PAYMENT_ACTION_AUTH = 'authorize';

	/**
	 * Returns the URL to send requests to. This changes depending on whether
	 * the extension is set to testing mode or not.
	 */
	public function getGatewayUrl()
	{
		if(Mage::getStoreConfig('payment/nab_directpost/test'))
		{
			return self::CC_URL_TEST;
		}
		else
		{
			return self::CC_URL_LIVE;
		}
	}
	
	public function getDebug()
	{
		return Mage::getStoreConfig('payment/nab_directpost/debug');
	}
	
	public function getLogPath()
	{
		return Mage::getBaseDir() . '/var/log/nab_directpost.log';
	}
	
	public function getUsername()
	{
		return Mage::getStoreConfig('payment/nab_directpost/username');
	}
	
	public function getPassword()
	{
		return Mage::getStoreConfig('payment/nab_directpost/password');
	}

	/**
	 *
	 */
	public function validate()
    {
    	if($this->getDebug())
		{
	    	$writer = new Zend_Log_Writer_Stream($this->getLogPath());
			$logger = new Zend_Log($writer);
			$logger->info("entering validate()");
		}
		
        parent::validate();
        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            $currency_code = $paymentInfo->getOrder()->getBaseCurrencyCode();
        } else {
            $currency_code = $paymentInfo->getQuote()->getBaseCurrencyCode();
        }
        return $this;
    }

	public function authorize(Varien_Object $payment, $amount)
	{
		if($this->getDebug())
		{
			$writer = new Zend_Log_Writer_Stream($this->getLogPath());
			$logger = new Zend_Log($writer);
			$logger->info("entering authorize()");
		}
	}
	
	/**
	 *
	 */
    public function capture(Varien_Object $payment, $amount)
    {
        // Ensure the transaction ID is always unique by including a time-based element
        $payment->setCcTransId($payment->getOrder()->getIncrementId() . '-' . date("His"));
        $this->setAmount($amount)->setPayment($payment);

        $result = $this->_call(self::STATUS_APPROVED, $payment);
        if($result === false) {
            $e = $this->getError();
            if (isset($e['message'])) {
                $message = Mage::helper('nab')->__('There has been an error processing your payment.') . $e['message'];
            } else {
                $message = Mage::helper('nab')->__('There has been an error processing your payment. Please try later or contact us for help.');
            }
            Mage::throwException($message);
        }
        else {
        // Check if there is a gateway error
            switch ($result['summarycode']) {
                case 1:
                    $payment->setStatus(self::STATUS_APPROVED)->setLastTransId($this->getTransactionId());
                    break;
                case 2:         /* Card declined */
                    Mage::throwException("The credit card details you provided have been declined by our credit card processor. Please review the payment details you have entered and try again. If the problem persists, please contact your card issuer.");
                    break;
                case 3:         /* 	Declined	for	any	other	reason */
                    Mage::throwException("The credit card details you provided have been Declined for any other reason");
                    break;
                default:
                    Mage::throwException("Error code " . $result['summarycode'] . ": " . urldecode($result['restext']));
                    break;
            }
        }
        return $this;
    }

	/**
	 *
	 */
    protected function _call($type, Varien_Object $payment)
    {	
		$transaction_id = 'Order #'.$payment->getOrder()->getIncrementId();
		$messageTimestamp = gmdate("YmdHis");
        $amount = number_format ($this->getAmount(),2,'.',',');
		$txn_type = 0;
        $request = array();

        $request['EPS_MERCHANT'] = htmlentities($this->getUsername());
        $request['EPS_TXNTYPE'] = $txn_type;
        $request['EPS_REFERENCEID'] = $transaction_id;
        $request['EPS_AMOUNT'] = $amount;
        $request['EPS_CURRENCY'] = 'AUD';
		$request['EPS_TIMESTAMP'] = $messageTimestamp;
		$request['EPS_FINGERPRINT'] = sha1($this->getUsername() . '|' . $this->getPassword() . '|' . $txn_type . '|' . $transaction_id . '|' . $amount . '|' . $messageTimestamp);
		$request['EPS_REDIRECT'] = 'TRUE';
		$request['EPS_CARDNUMBER'] = htmlentities($payment->getCcNumber());
		$request['EPS_EXPIRYMONTH'] = htmlentities($payment->getCcExpMonth());
		$request['EPS_EXPIRYYEAR'] = htmlentities($payment->getCcExpYear());
		$request['EPS_CCV'] = htmlentities($payment->getCcCid());				
		$request['EPS_RESULTURL'] = Mage::getUrl('nab_directpost/index/success',array('_secure' => true));
        $postRequestData = '';
        $amp = '';
        foreach($request as $key => $value) {
            //if(!empty($value)) {
			$postRequestData .= $amp . urlencode($key) . '=' . urlencode($value);
			$amp = '&';
            //}
        }

        // Send the data via HTTP POST and get the response
        $http = new Varien_Http_Adapter_Curl();
        $http->setConfig(array('timeout' => 30));
        $http->write(Zend_Http_Client::POST, $this->getGatewayUrl(), '1.1', array(), $postRequestData);

        $response = $http->read();
		$res1 = explode('Location:',$response);
		$res2 = explode('Content-Length:',$res1[1]);
		$res3 = explode('?',$res2[0]);
        // Fill out the results
        $result = array();
        $result['resulturl'] = $res3[0];
		$pieces = explode('&', $res3[1]);
        foreach($pieces as $piece) {
            $tokens = explode('=', $piece);
            $result[$tokens[0]] = $tokens[1];
        }

        return $result;
    }
}