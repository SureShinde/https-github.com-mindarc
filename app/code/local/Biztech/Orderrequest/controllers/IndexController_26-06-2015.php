<?php
    class Biztech_Orderrequest_IndexController extends Mage_Core_Controller_Front_Action
    {
        /*public function indexAction()
        {
        /*
        * Load an object by id 
        * Request looking like:
        * http://site.com/orderrequest?id=15 
        *  or
        * http://site.com/orderrequest/id/15     
        */
        /* 
        $orderrequest_id = $this->getRequest()->getParam('id');

        if($orderrequest_id != null && $orderrequest_id != '')    {
        $orderrequest = Mage::getModel('orderrequest/orderrequest')->load($orderrequest_id)->getData();
        } else {
        $orderrequest = null;
        }    
        */

        /*
        * If no param we load a the last created item
        */ 
        /*
        if($orderrequest == null) {
        $resource = Mage::getSingleton('core/resource');
        $read= $resource->getConnection('core_read');
        $orderrequestTable = $resource->getTableName('orderrequest');

        $select = $read->select()
        ->from($orderrequestTable,array('orderrequest_id','title','content','status'))
        ->where('status',1)
        ->order('created_time DESC') ;

        $orderrequest = $read->fetchRow($select);
        }
        Mage::register('orderrequest', $orderrequest);
        * /

        $this->loadLayout();     
        $this->renderLayout();
        }*/

        public function saveorderrequestAction()
        {
            $order_id         = $this->getRequest()->getParam('orderid');
            $order            = Mage::getModel('sales/order')->load($order_id);

            $customer_request = strip_tags($this->getRequest()->getParam('customer_order_request'));

            if (!empty($customer_request)){
                $value = '<strong>' . Mage::helper('orderrequest')->__('Customer Order Comment') . '</strong><br/>' . $customer_request;

                $order->addStatusHistoryComment($value,$order->getStatus())
                ->setIsVisibleOnFront(true)
                ->setIsCustomerNotified(true);

                $order->save();

                $sql    = "SELECT order_increment_id FROM orderrequest WHERE order_increment_id = '".$order->getIncrementId()."'";
                $result = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);

                if(count($result) == 0){
                    $data['order_increment_id'] = $order->getIncrementId();
                    $data['customer_email']     = $order->getCustomerEmail();
                    $data['type']               = "frontend";
                    $model                      = Mage::getModel('orderrequest/orderrequest');
                    $model->setData($data);
                    $model->setCreatedTime(now())->setUpdateTime(now());
                    $model->save();
                }

                $to       = Mage::getStoreConfig('trans_email/ident_sales/email');
                $subject  = "Customer Order Comment for Order #".$order->getIncrementId();
                $message  = "<h1 style='font-size:13px; font-weight:normal; line-height:22px; margin:0 0 11px 0;'>Hello,</h1><p>You just recieved below customer comment for order #".$order->getIncrementId()."</p><strong>Customer Order Comment</strong><br/>".nl2br($customer_request);
                $header   = "MIME-Version: 1.0\r\n";
                $header  .= "Content-type: text/html; charset: utf8\r\n";
                mail($to, $subject, $message, $header);

                $this->_redirect('sales/order/view/order_id/'.$order_id.'/');
                return;
            }
        }
}