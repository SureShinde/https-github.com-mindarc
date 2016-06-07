<?php
    class Biztech_Auspost_Model_Carrier_Auspost extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {
        protected $_code = 'auspost';

        //private $apiHttps = 'https://auspost.com.au/api/postage';
        private $apiHttps = 'https://digitalapi.auspost.com.au';
        
        protected $_numBoxes = 1;
        protected $_itemPackage = 1;
        const HANDLING_TYPE_PERCENT = 'P';
        const HANDLING_TYPE_FIXED = 'F';

        const HANDLING_ACTION_PERPACKAGE = 'P';
        const HANDLING_ACTION_PERORDER = 'O';

        public function collectRates(Mage_Shipping_Model_Rate_Request $request)
        {
            $result = Mage::getModel('shipping/rate_result');
            if(!Mage::helper('auspost')->isEnable()){
                if (!Mage::getStoreConfig('carriers/'.$this->_code.'/active')) {
                    return false;
                }
            }

            $productModel = Mage::getModel('catalog/product');
            $attr = $productModel->getResource()->getAttribute("auspost_package_type");
            $weight = 0;
            $length = 0;
            $width  = 0;
            $height = 0; 
            
            /*commented because Need to use per package or per item in cart ?*/
            //$this->_itemPackage = Mage::getModel('checkout/cart')->getQuote()->getItemsQty();
            
            $length_attr = Mage::getStoreConfig('carriers/auspost/length_attribute');
            $width_attr  = Mage::getStoreConfig('carriers/auspost/width_attribute');
            $height_attr = Mage::getStoreConfig('carriers/auspost/height_attribute');
            
            if ($request->getAllItems()) {
                foreach ($request->getAllItems() as $item) {
                     /*Do not allow configurable product to as the dimensions can be differ for its simple products*/
                    if ($item->getHasChildren() && $item->getProduct()->getTypeId()=="configurable"){
                        continue;
                    }
                    
                    if($item->getProduct()->getTypeId()=="bundle" && $item->isShipSeparately()){
                        continue;    
                    }
                    
                    /*Do not allow simple products of bundle product*/
                    if($item->getParentItem()){
                        if($item->getParentItem()->getProduct()->getTypeId()=="bundle" && !$item->isShipSeparately()){
                            continue;
                        }
                    }

                    if ($item->getHasChildren() && $item->isShipSeparately()) {
                        $productItemObj = Mage::getModel('catalog/product')->load($item->getProductId());
                        foreach ($item->getChildren() as $child) {
                            if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                                $product_id = $child->getProductId();
                                $productObj = Mage::getModel('catalog/product')->load($product_id);
                                $weight += $item->getRowWeight();
                                $item_qty = $item->getParentItem() ? $item->getParentItem()->getQty() : $item->getQty();
                                $prd_length = $productObj->getData($length_attr);
                                $prd_width = $productObj->getData($width_attr);
                                $prd_height = $productObj->getData($height_attr);
                                if($this->getConfigData('auspost_allow_default')){
                                    $prd_length = $productObj->getData($length_attr) ? $productObj->getData($length_attr) : $this->getConfigData('length_value');
                                    $prd_width = $productObj->getData($width_attr) ? $productObj->getData($width_attr) : $this->getConfigData('width_value');
                                    $prd_height = $productObj->getData($height_attr) ? $productObj->getData($height_attr) : $this->getConfigData('height_value');
                                }
                            for ($i = 1; $i <= $item_qty; $i++) {
                                    $boxes[] = array(
                                        'length' => $prd_length,
                                        'width'  => $prd_width,
                                        'height' => $prd_height
                                    );
                                }
                            }
                        }
                    } else {
                        $product_id = $item->getProductId();
                        $productObj = Mage::getModel('catalog/product')->load($product_id);
                        $weight += $item->getParentItem() ? $item->getParentItem()->getRowWeight() : $item->getRowWeight();


                        if ($attr->usesSource()) {
                            $package_type_arr[] = $attr->getSource()->getOptionText($productObj->getData('auspost_package_type')) ? $attr->getSource()->getOptionText($productObj->getData('auspost_package_type')) : 'Parcel';
                        }

                       $item_qty = $item->getParentItem() ? $item->getParentItem()->getQty() : $item->getQty();
                       $prd_length = $productObj->getData($length_attr);
                        $prd_width = $productObj->getData($width_attr);
                        $prd_height = $productObj->getData($height_attr);
                        if($this->getConfigData('auspost_allow_default')){
                            $prd_length = $productObj->getData($length_attr) ? $productObj->getData($length_attr) : $this->getConfigData('length_value');
                            $prd_width = $productObj->getData($width_attr) ? $productObj->getData($width_attr) : $this->getConfigData('width_value');
                            $prd_height = $productObj->getData($height_attr) ? $productObj->getData($height_attr) : $this->getConfigData('height_value');
                        }
                        for ($i = 1; $i <= $item_qty; $i++) {
                                $boxes[] = array(
                                    'length' => $prd_length,
                                    'width' => $prd_width,
                                    'height' => $prd_height
                                );
                            }

                    }
                }
                $lp = new Biztech_Auspost_Model_Carrier_Pack();
                $lp->pack($boxes);
                $c_size = $lp->get_container_dimensions();

                $length = $c_size['length'];
                $width  = $c_size['width'];
                $height = $c_size['height'];
            }
            
            $params = array('from'=>array('postcode'=>$this->getConfigData('auspost_from_shipping_postcode')),
                            'to'=>array('postcode'=>$request['dest_postcode']),
                            'items'=>array('length'=>$length,
                                            'width'=>$width,
                                            'height'=>$height,
                                            'weight'=>$weight
                                            )
                            );
                            
			 
            $resorce = "shipping/v1/prices/items";
            $_servicesArr = $this->apiRequest($resorce , $params);
            
			if(!$_servicesArr){
                $error = Mage::getModel('shipping/rate_result_error');
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                $result->append($error);
                return $result;
            }
            
            foreach($_servicesArr->items as $_service){
                if(count($_service->prices)){
                    foreach($_service->prices as $_serviceCost){
                        $shipping_price = $this->getFinalPriceWithHandlingFee($_serviceCost->calculated_price);
                        $method = Mage::getModel('shipping/rate_result_method');
                        $method->setCarrier($this->_code);
                        $method->setMethod($_serviceCost->product_id);
                        $method->setCarrierTitle($this->getConfigData('title'));
                        $method->setMethodTitle($_serviceCost->product_type);
                        $method->setPrice($shipping_price);
                        $method->setCost($shipping_price);
                        $result->append($method);
                    }
                }else{
                    $error = Mage::getModel('shipping/rate_result_error');
                    $error->setCarrier($this->_code);
                    $error->setCarrierTitle($this->getConfigData('title'));
                    $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                    $result->append($error);
                    return $result;
                }
            }
            return $result;
        }

        public function getAllowedMethods()
        {
            return array('auspost' => $this->getConfigData('auspost_method_name'));
        }

        protected function apiRequest ($action, $params = array (), $auth = true)
        {
            $_helper = Mage::helper('auspost');
            $url = $this->apiHttps.'/'.$action.'/';
            $request_body = $_helper->buildHttpQuery($params);
            $res = $_helper->ausPostValidation($url, $request_body, true);
            return json_decode($res);
            //return $_helper->parseXml($res);
        }

        public function getFinalPriceWithHandlingFee($cost) {
            $handlingFee = $this->getConfigData('handling_fee');
            $handlingType = $this->getConfigData('handling_type');
            if (!$handlingType) {
                $handlingType = self::HANDLING_TYPE_FIXED;
            }
            $handlingAction = $this->getConfigData('handling_action');
            if (!$handlingAction || $handlingAction=="O") {
                $handlingAction = self::HANDLING_ACTION_PERORDER;
            }
            $this->_numBoxes = self::HANDLING_ACTION_PERPACKAGE ? $this->_itemPackage : 1;
            return $handlingAction == self::HANDLING_ACTION_PERPACKAGE ? $this->_getPerpackagePrice($cost, $handlingType, $handlingFee) : $this->_getPerorderPrice($cost, $handlingType, $handlingFee);
        }
        protected function _getPerpackagePrice($cost, $handlingType, $handlingFee) {
            if ($handlingType == self::HANDLING_TYPE_PERCENT) {
                return ($cost + ($cost * $handlingFee / 100)) * $this->_numBoxes;
            }
            return ($cost + $handlingFee) * $this->_numBoxes;
        }
        protected function _getPerorderPrice($cost, $handlingType, $handlingFee) {
            if ($handlingType == self::HANDLING_TYPE_PERCENT) {
                return ($cost * $this->_numBoxes) + ($cost * $this->_numBoxes * $handlingFee / 100);
            }
            return ($cost * $this->_numBoxes) + $handlingFee;
        }

}
