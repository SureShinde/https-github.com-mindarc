<?php
    require_once 'app/Mage.php';
    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
    Mage::app();
   
    /*$client       = new SoapClient("http://localhost.magento1900.com/index.php/api/soap/?wsdl");
    $session      = $client->login('ecrstudent','123456');

    try{       

    $pro   = $client->call($session,'sales_order.info',array('100000002'));
    echo "<pre>";
    print_r($pro);
    echo "</pre>";
    } catch(Exception $ex) {
    echo $ex->getMessage();
    }
    exit;
*/
    
    
       /* $url         = 'http://localhost.magentotest.com/index.php/api/v2_soap/index/?wsdl'; 

        $user = 'developer';
        $api_key = '123456';
        try{  
           // DebugBreak();
            $soap       = new SoapClient($url);

            $session_id = $soap->login($user,$api_key);


            $pro = $soap->salesOrderInfo($session_id,'100000004');
            //$pro   = $client->call($session,'sales_order.info',array('100000741'));
            echo "<pre>";
            print_r($pro);
            echo "</pre>";
        } catch(Exception $ex) {
            echo $ex->getMessage();
        }
        exit;
    */
    
    
    $url = 'https://www.nettopet.com.au/index.php/api/xmlrpc/'; 
    
    $user = 'developer';
    $api_key = '123456';
    $shipment_id = $_REQUEST['shipment_id'];
    try{  
        //DebugBreak(); 
        $client       = new Zend_XmlRpc_Client($url);
          //$client->setConfig();
        $session = $client->call('login', array('developer', '123456'));
        
        try {
            //$$order = $client->salesOrderInfo($session,'100000004');
            
            /*$request = new Zend_XmlRpc_Request();
            $request->setMethod('sales_order.info');
            $request->setParams(array('100000004'));
             
            $orders = $client->doRequest($request);*/
            
            $orders = $client->call('call', array($session, 'sales_order_shipment.info', array($shipment_id)));
            echo "<pre>";
            print_r($orders);
            echo "</pre>";
        } catch (Exception $fault) {
            echo $fault->getMessage();
        }
    } catch(Exception $ex) {
        echo $ex->getMessage();
    }
   // exit;
?>