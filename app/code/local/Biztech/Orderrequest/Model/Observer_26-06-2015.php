<?php

    class Biztech_Orderrequest_Model_Observer {

        /**
        * Check if customer request should be added to order.
        *
        * @param Varien_Event_Observer $observer
        * @return $this
        */
        private static $_handleCounter = 0;

        public function addCustomerRequestToOrder(Varien_Event_Observer $observer) {
            /** @var Mage_Sales_Model_Order $order */
            $order = $observer->getEvent()->getOrder();
            /** @var Mage_Core_Controller_Request_Http $request */
            $request = Mage::app()->getRequest();

            if (!is_object($order) || !$request)
                return $this;

            $comment = strip_tags($request->getParam('customer_order_request'));

            if (!empty($comment) && trim($comment) != "") {
                $value = '<strong>' . Mage::helper('orderrequest')->__('Customer Order Comment') . '</strong><br/>' . $comment;

                $order->addStatusHistoryComment($value, $order->getStatus())
                ->setIsVisibleOnFront(true)
                ->setIsCustomerNotified(true);

                $prefix = Mage::getConfig()->getTablePrefix();
                $sql = "SELECT order_increment_id FROM " . $prefix . "orderrequest WHERE order_increment_id = '" . $order->getIncrementId() . "'";
                $result = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);

                if (count($result) == 0) {
                    $data['order_increment_id'] = $order->getIncrementId();
                    $data['customer_email'] = $order->getCustomerEmail();
                    $data['type'] = "frontend";
                    $model = Mage::getModel('orderrequest/orderrequest');
                    $model->setData($data);
                    $model->setCreatedTime(now())->setUpdateTime(now());
                    $model->save();
                }
            }

            return $this;
        }

        public function saveOrderCommentAdmin(Varien_Event_Observer $observer) {
            $order = $observer->getEvent()->getOrder();
            $prefix = Mage::getConfig()->getTablePrefix();
            $sql = "SELECT order_increment_id FROM " . $prefix . "orderrequest WHERE order_increment_id = '" . $order->getIncrementId() . "'";
            $result = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);

            if (count($result) == 0) {
                $data['order_increment_id'] = $order->getIncrementId();
                $data['customer_email'] = $order->getCustomerEmail();
                $data['type'] = "admin";
                $model = Mage::getModel('orderrequest/orderrequest');
                $model->setData($data);
                $model->setCreatedTime(now())->setUpdateTime(now());
                $model->save();
            }
        }

        public function multishipOrderCustomerRequest(Varien_Event_Observer $observer) {
            if (Mage::app()->getRequest()->getControllerName() == 'multishipping') {
                $data = $observer->getData();
                $order = $observer->getEvent()->getOrder();
                $request = Mage::app()->getRequest();

                if (!is_object($order) || !$request)
                    return $this;

                $addresses = Mage::getSingleton('checkout/session')->getQuote()->getAllShippingAddresses();
                foreach ($addresses as $address) {
                    $address_array[] = $address->getId();
                }

                $comment = $request->getParam('customer_order_request_' . $address_array[self::$_handleCounter]);
                if ($comment != NULL) {
                    $comment = strip_tags($comment);

                    if (!empty($comment) && trim($comment) != "") {
                        $value = '<strong>' . Mage::helper('orderrequest')->__('Customer Order Comment') . '</strong><br/>' . $comment;

                        $order->addStatusHistoryComment($value, $order->getStatus())
                        ->setIsVisibleOnFront(true)
                        ->setIsCustomerNotified(true);

                        $prefix = Mage::getConfig()->getTablePrefix();
                        $sql = "SELECT order_increment_id FROM " . $prefix . "orderrequest WHERE order_increment_id = '" . $order->getIncrementId() . "'";
                        $result = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);

                        if (count($result) == 0) {
                            $data['order_increment_id'] = $order->getIncrementId();
                            $data['customer_email'] = $order->getCustomerEmail();
                            $data['type'] = "frontend";
                            $model = Mage::getModel('orderrequest/orderrequest');
                            $model->setData($data);
                            $model->setCreatedTime(now())->setUpdateTime(now());
                            $model->save();
                        }
                    }
                }self::$_handleCounter++;
            }
        }

    }
