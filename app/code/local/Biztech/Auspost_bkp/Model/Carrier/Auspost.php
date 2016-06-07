<?php
    class Biztech_Auspost_Model_Carrier_Auspost extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {
        protected $_code = 'auspost';

        private $apiHttps = 'https://auspost.com.au/api/postage';
        private $services = 'https://auspost.com.au//api/postage/parcel/international';

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
            if($request['dest_country_id'] == 'AU'){
                $length = 0;
                $width  = 0;
                $height = 0; 

                $length_attr = Mage::getStoreConfig('carriers/auspost/length_attribute');
                $width_attr  = Mage::getStoreConfig('carriers/auspost/width_attribute');
                $height_attr = Mage::getStoreConfig('carriers/auspost/height_attribute');

                if ($request->getAllItems()) {
                    foreach ($request->getAllItems() as $item) {
                         if ($item->getHasChildren()) {
                        continue;
                    }

                        if ($item->getHasChildren() && $item->isShipSeparately()) {
                            $productItemObj = Mage::getModel('catalog/product')->load($item->getProductId());
                            if ($attr->usesSource()) {
                                $package_type_arr[] = $attr->getSource()->getOptionText($productItemObj->getData('auspost_package_type')) ? $attr->getSource()->getOptionText($productItemObj->getData('auspost_package_type')) : 'Parcel';
                            }

                            foreach ($item->getChildren() as $child) {
                                if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                                    $product_id = $child->getProductId();
                                    $productObj = Mage::getModel('catalog/product')->load($product_id);
                                    $weight += $item->getRowWeight();
                                    $item_qty = $item->getParentItem() ? $item->getParentItem()->getQty() : $item->getQty();
                                for ($i = 1; $i <= $item_qty; $i++) {
                                        $boxes[] = array(
                                            'length' => 1,
                                            'width'  => 1,
                                            'height' => 1
                                        );
                                    }
                                }
                            }
                        } else {

                            $product_id = $item->getProductId();
                            $productObj = Mage::getModel('catalog/product')->load($product_id);
                            $weight += $item->getRowWeight();


                            if ($attr->usesSource()) {
                                $package_type_arr[] = $attr->getSource()->getOptionText($productObj->getData('auspost_package_type')) ? $attr->getSource()->getOptionText($productObj->getData('auspost_package_type')) : 'Parcel';
                            }

                           $item_qty = $item->getParentItem() ? $item->getParentItem()->getQty() : $item->getQty();
                        for ($i = 1; $i <= $item_qty; $i++) {
                                $boxes[] = array(
                                    'length' => 1,
                                    'width'  => 1,
                                    'height' => 1
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

                $package_type_arr = array_unique($package_type_arr);
                if(count($package_type_arr) > 1){
                    $package_type  = 'Parcel';
                }else{
                    $package_type  = $package_type_arr[0];
                }
                if($package_type != 'Letter'){
                    $resorce = 'parcel/domestic/service';
                    $params = array(
                        'from_postcode' => $this->getConfigData('auspost_from_shipping_postcode'),
                        'to_postcode'   => $request['dest_postcode'],
                        'length'        => $length,
                        'width'         => $width,
                        'height'        => $height,
                        'weight'        => $weight
                    );

                    $enable_services = explode(',', Mage::getStoreConfig('carriers/auspost/auspost_enable_services'));
                }else{
                    $resorce = 'letter/domestic/service';
                    $params = array(
                        'length'        => $length *10,
                        'width'         => $width *10,
                        'thickness'     => $height *10,
                        'weight'        => $weight * 1000
                    );
                    $enable_services = explode(',', Mage::getStoreConfig('carriers/auspost/auspost_enable_services_letter'));
                }
                $_servicesArr = $this->apiRequest($resorce , $params);



            }else
            {
                if ($request->getAllItems()) {
                    foreach ($request->getAllItems() as $item) {
                         if ($item->getHasChildren()) {
                    continue;
                }

                        if ($item->getHasChildren() && $item->isShipSeparately()) {
                            $productItemObj = Mage::getModel('catalog/product')->load($item->getProductId());
                            if ($attr->usesSource()) {
                                $package_type_arr[] = $attr->getSource()->getOptionText($productItemObj->getData('auspost_package_type')) ? $attr->getSource()->getOptionText($productItemObj->getData('auspost_package_type')) : 'Parcel';
                            }
                            foreach ($item->getChildren() as $child) {
                                if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                                    $product_id = $child->getProductId();
                                    $productObj = Mage::getModel('catalog/product')->load($product_id);
                                    $weight += $item->getRowWeight();
                                }
                            }
                        } else {
                            $product_id = $item->getProductId();
                            $productObj = Mage::getModel('catalog/product')->load($product_id);
                            $weight += $item->getRowWeight();
                            if ($attr->usesSource()) {
                                $package_type_arr[] = $attr->getSource()->getOptionText($productObj->getData('auspost_package_type')) ? $attr->getSource()->getOptionText($productObj->getData('auspost_package_type')) : 'Parcel';
                            }
                        }
                    }
                }
                
                $package_type_arr = array_unique($package_type_arr);
                if(count($package_type_arr) > 1){
                    $package_type  = 'Parcel';
                }else{
                    $package_type  = $package_type_arr[0];
                }

                if($package_type != 'Letter'){
                    $resorce = 'parcel/international/service';
                    $enable_services = explode(',', Mage::getStoreConfig('carriers/auspost/auspost_enable_int_services'));
                      $_servicesArr = $this->apiRequest($resorce, array (
                        'country_code' => $request['dest_country_id'],
                        'weight'       => $weight
                    ));  
                }else{
                    $resorce = 'letter/international/service';
                    $enable_services = explode(',', Mage::getStoreConfig('carriers/auspost/auspost_enable_int_services_letter'));
                      $_servicesArr = $this->apiRequest($resorce, array (
                        'country_code' => $request['dest_country_id'],
                        'weight'       => $weight * 1000
                    ));  
                }
              

            }

            $_services = "";
            $_services = array();



            if(isset($_servicesArr['service']['code']))
            {
                $_services[] = array(
                    'code' => $_servicesArr['service']['code'],
                    'name' => $_servicesArr['service']['name']
                );
            }
            else{
                if(isset($_servicesArr['service'])){
                    foreach($_servicesArr['service'] as $_service)
                    {
                        $options = array();
                        $_services[$_service['code']] = array(
                            'code' => $_service['code'],
                            'name' => $_service['name'],
                            'max_extra_cover' => $_service['max_extra_cover']
                        );

                        if(isset($_service['options']['option'])){
                            if($request['dest_country_id'] == 'AU'){
                                foreach($_service['options']['option'] as $_option)
                                {
                                    if(Mage::getStoreConfig('carriers/auspost/auspost_disable_signature_services')){
                                        if($_option['code'] != 'AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY'){
                                            $options[$_option['code']]  = array(
                                                'code' => $_option['code'],
                                                'name' => $_option['name'],
                                                'max_extra_cover' => $_option['suboptions']['option']['max_extra_cover']
                                            );
                                        }
                                    }else{

                                        $options[$_option['code']]  = array(
                                            'code' => $_option['code'],
                                            'name' => $_option['name'],
                                            'max_extra_cover' => $_option['suboptions']['option']['max_extra_cover']
                                        );
                                    }

                                }
                                $_services[$_service['code']]['options'] = $options;
                            }else{
                                foreach($_service['options']['option'] as $_option)
                                {
                                    if(is_array($_option)){
                                        if($_option['code']!= 'INTL_SERVICE_OPTION_EXTRA_COVER'){
                                            $options[$_option['code']]  = array(
                                                'code' => $_option['code'],
                                                'name' => $_option['name'],
                                                'max_extra_cover' => $_service['max_extra_cover']
                                            );
                                        }
                                    }else{
                                        if($_service['options']['option']['code'] != 'INTL_SERVICE_OPTION_EXTRA_COVER'){
                                            $options[$_service['options']['option']['code']]  = array(
                                                'code' => $_service['options']['option']['code'],
                                                'name' => $_service['options']['option']['name'],
                                                'max_extra_cover' => $_service['max_extra_cover']
                                            );
                                        }
                                    }
                                }
                                $_services[$_service['code']]['options'] = $options;

                            }
                        }
                    }  
                }  
            }

            if (!count($_services))
            {
                if(isset($_servicesArr['errorMessage'])){
                    Mage::log($_servicesArr['errorMessage'], Zend_Log::DEBUG, 'auspost.log');
                }
                $error = Mage::getModel('shipping/rate_result_error');
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                $result->append($error);
                return $result;
            }
           
            
            $extra_cover = '';

            foreach ($_services as  $_value) {

                if(!in_array($_value['code'],$enable_services))
                    continue;

                if(!empty($_value['options'])){
                    foreach($_value['options'] as $_val){
                        $totals   = Mage::getSingleton('checkout/session')->getQuote()->getTotals();
                        $subtotal = $totals["subtotal"]->getValue();
                        if($subtotal > $_val['max_extra_cover']){
                            $extra_cover = $_val['max_extra_cover'];
                        }else{
                            $extra_cover  = $subtotal ;
                        }
                        if($extra_cover == 0){$extra_cover = '';}
                        if($request['dest_country_id'] == 'AU'){

                            if($package_type != 'Letter'){
                                $params = array (
                                    'from_postcode'  => $this->getConfigData('auspost_from_shipping_postcode'),
                                    'to_postcode'    => $request['dest_postcode'],
                                    'length'         => $length,
                                    'width'          => $width,
                                    'height'         => $height,
                                    'weight'         => $weight,
                                    'service_code'   => $_value['code'],
                                    'option_code'    => $_val['code']
                                ); 

                                if(Mage::getStoreConfig('carriers/auspost/auspost_add_extra_cover_price')){
                                    $params['suboption_code'] = 'AUS_SERVICE_OPTION_EXTRA_COVER';
                                    $params['extra_cover'] = $extra_cover;

                                }
                                $res = $this->apiRequest('parcel/domestic/calculate', $params);
                            }else{
                                $params = array (

                                    'weight'         => $weight * 1000,
                                    'service_code'   => $_value['code'],
                                    'option_code'    => $_val['code']
                                ); 

                                $res = $this->apiRequest('letter/domestic/calculate', $params);
                            }
                        }else{
                            if ($package_type != 'Letter') {
                            $params = array(
                                'country_code' => $request['dest_country_id'],
                                'weight' => $weight,
                                'service_code' => $_value['code']
                            );
                        } else {
                            $params = array(
                                'country_code' => $request['dest_country_id'],
                                'weight' => $weight * 1000,
                                'service_code' => $_value['code']
                            );
                        }

                            if(Mage::getStoreConfig('carriers/auspost/auspost_add_extra_cover_price_int_service')){
                                $params['option_code'] = 'INTL_SERVICE_OPTION_EXTRA_COVER';
                                $params['extra_cover'] = $extra_cover;
                            }
                            if($package_type != 'Letter'){
                                $res = $this->apiRequest('parcel/international/calculate', $params);
                            }else{
                                $res = $this->apiRequest('letter/international/calculate', $params); 
                            }
                        }
                        $shipping_price = $this->getFinalPriceWithHandlingFee($res['total_cost']);

                        if($res['total_cost']){

                            $method = Mage::getModel('shipping/rate_result_method');
                            $method->setCarrier($this->_code);
                            $method->setMethod($_value['code'].'_'.$_val['code']);
                            $method->setCarrierTitle($this->getConfigData('title'));
                            if($request['dest_country_id'] == 'AU'){
                                $method->setMethodTitle($_value['name'] . ' - '. $_val['name']);
                            }else{
                                $method->setMethodTitle($_value['name']); 
                            }
                            $method->setPrice($shipping_price);
                            $method->setCost($shipping_price);
                            $result->append($method);
                        }else{
                            $error = Mage::getModel('shipping/rate_result_error');
                            $error->setCarrier($this->_code);
                            $error->setCarrierTitle($this->getConfigData('title'));
                            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                            $result->append($error);
                            return $result;
                        }
                    }
                }else{

                    $totals   = Mage::getSingleton('checkout/session')->getQuote()->getTotals();
                    $subtotal = $totals["subtotal"]->getValue();
                    if($subtotal > $_value['max_extra_cover']){
                        $extra_cover = $_value['max_extra_cover'];
                    }else{
                        $extra_cover  = $subtotal ;
                    }
                    if($extra_cover == 0){$extra_cover = '';}

                    if ($package_type != 'Letter') {
                            $params = array(
                                'country_code' => $request['dest_country_id'],
                                'weight' => $weight,
                                'service_code' => $_value['code']
                            );
                        } else {
                            $params = array(
                                'country_code' => $request['dest_country_id'],
                                'weight' => $weight * 1000,
                                'service_code' => $_value['code']
                            );
                        }

                    if(Mage::getStoreConfig('carriers/auspost/auspost_add_extra_cover_price_int_service')){
                        $params['option_code'] = 'INTL_SERVICE_OPTION_EXTRA_COVER';
                        $params['extra_cover'] = $extra_cover;
                    }

                    if($package_type != 'Letter'){
                        $res = $this->apiRequest('parcel/international/calculate', $params);
                    }else{
                        $res = $this->apiRequest('letter/international/calculate', $params); 
                    }
                    $shipping_price = $this->getFinalPriceWithHandlingFee($res['total_cost']);

                    if($res['total_cost']){
                        $method = Mage::getModel('shipping/rate_result_method');
                        $method->setCarrier($this->_code);
                        $method->setMethod($_value['code']);
                        $method->setCarrierTitle($this->getConfigData('title'));
                        $method->setMethodTitle($_value['name'] . ' ');
                        $method->setPrice($shipping_price);
                        $method->setCost($shipping_price);
                        $result->append($method);
                    }else{
                        $error = Mage::getModel('shipping/rate_result_error');
                        $error->setCarrier($this->_code);
                        $error->setCarrierTitle($this->getConfigData('title'));
                        $error->setErrorMessage($this->getConfigData('specificerrmsg'));
                        $result->append($error);
                        return $result;
                    }
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
            $url = $this->apiHttps.'/'.$action.'.xml?'.$_helper->buildHttpQuery($params); 
            $headers = array (
                "Accept: text/html,application/xhtml+xml,application/xml",
                "Cookie: OBBasicAuth=fromDialog"
            );
            $res = $_helper->ausPostValidation($url, $headers, true);
            return $_helper->parseXml($res);
        }

        public function getFinalPriceWithHandlingFee($cost)
        {
            $handlingFee = $this->getConfigData('handling_fee');
            $handlingType = $this->getConfigData('handling_type');
            if (!$handlingType) {
                $handlingType = self::HANDLING_TYPE_FIXED;
            }
            $handlingAction = $this->getConfigData('handling_action');
            if (!$handlingAction) {
                $handlingAction = self::HANDLING_ACTION_PERORDER;
            }

            return $handlingAction == self::HANDLING_ACTION_PERPACKAGE
            ? $this->_getPerpackagePrice($cost, $handlingType, $handlingFee)
            : $this->_getPerorderPrice($cost, $handlingType, $handlingFee);
        }

        protected function _getPerpackagePrice($cost, $handlingType, $handlingFee)
        {
            if ($handlingType == self::HANDLING_TYPE_PERCENT) {
                return ($cost + ($cost * $handlingFee/100)) * $this->_numBoxes;
            }

            return ($cost + $handlingFee) * $this->_numBoxes;
        }


        protected function _getPerorderPrice($cost, $handlingType, $handlingFee)
        {
            if ($handlingType == self::HANDLING_TYPE_PERCENT) {
                return ($cost * $this->_numBoxes) + ($cost * $this->_numBoxes * $handlingFee / 100);
            }

            return ($cost * $this->_numBoxes) + $handlingFee;
        }

}
