<?php class Biztech_Auspost_Helper_Data extends Mage_Core_Helper_Abstract
    {
        public function buildHttpQuery($query)
        {
            return json_encode( $query );
        }

        public function parseXml($xmlString)
        {
            libxml_use_internal_errors(true);
            $xmlObject = simplexml_load_string($xmlString);
            $result = array ();
            if (!empty($xmlObject))
                $this->convertXmlObjToArr($xmlObject, $result);
            return $result;
        }

        public function convertXmlObjToArr($obj, &$arr)
        {
            $children = $obj->children();
            $executed = false;
            foreach ($children as $elementName => $node)
            {
                if(is_array($arr) && array_key_exists( $elementName , $arr ) )
                {
                    if(is_array($arr[$elementName])&& array_key_exists( 0 ,$arr[$elementName] ) )
                    {
                        $i = count($arr[$elementName]);
                        $this->convertXmlObjToArr ($node, $arr[$elementName][$i]);
                    }
                    else
                    {
                        $tmp = $arr[$elementName];
                        $arr[$elementName] = array();
                        $arr[$elementName][0] = $tmp;
                        $i = count($arr[$elementName]);
                        $this->convertXmlObjToArr($node, $arr[$elementName][$i]);
                    }
                }
                else
                {
                    $arr[$elementName] = array();
                    $this->convertXmlObjToArr($node, $arr[$elementName]);   
                }
                $executed = true;
            }
            if(!$executed&&$children->getName()=="")
            {
                $arr = (String)$obj;
            }
            return ;
        }

        public function ausPostValidation($url, $params , $auth = true) 
        {
            $account_num  = Mage::getStoreConfig('carriers/auspost/auspost_account_num');
            $username = Mage::getStoreConfig('carriers/auspost/auspost_username');
            $password = Mage::getStoreConfig('carriers/auspost/auspost_pass');
            $ch = curl_init($url); 
            curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Cookie: OBBasicAuth=fromDialog",
                'Account-Number:'.$account_num
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $contents = curl_exec ($ch);
            curl_close ($ch);
            return $contents;
        }


        public function getAllStoreDomains() {
            $domains = array();
            foreach (Mage::app()->getWebsites() as $website) {
                $url = $website->getConfig('web/unsecure/base_url');
                if ($domain = trim(preg_replace('/^.*?\/\/(.*)?\//', '$1', $url))) {
                    $domains[] = $domain;
                }
                $url = $website->getConfig('web/secure/base_url');
                if ($domain = trim(preg_replace('/^.*?\/\/(.*)?\//', '$1', $url))) {
                    $domains[] = $domain;
                }
            }
            return array_unique($domains);
        }


        public function checkKey($k,$s = ''){    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, sprintf('http://store.biztechconsultancy.com/extension/licence.php'));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'key='.urlencode($k).'&domains='.urlencode(implode(',', $this->getAllStoreDomains())).'&sec=australianpost');
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $content = curl_exec($ch);
            $res = Zend_Json::decode($content);
            $modulestatus = new Mage_Core_Model_Config();
            $enc = Mage::helper('core');      
            if(empty($res)){
                $modulestatus->saveConfig('auspost/activation/key', "");
                $modulestatus->saveConfig('carriers/auspost/active', 0);
                $data = Mage::getStoreConfig('auspost/activation/data');
                $groups = array(
                    'activation'=>array(
                        'fields'=>array(
                            'data'=>array(
                                'value'=>$data
                            ),
                            'websites'=>array(
                                'value'=>''
                            )                        
                        )
                    )
                );
                Mage::getModel('adminhtml/config_data')
                ->setSection('auspost')
                ->setGroups($groups)
                ->save();
                Mage::getConfig()->reinit();
                Mage::app()->reinitStores();  
                return;
            }
            $data = '';
            $web  = '';  
            $en   = '';   

            if(isset($res['dom']) && intval($res['c']) > 0 &&  intval($res['suc']) == 1){
                $data = $enc->encrypt(base64_encode(Zend_Json::encode($res)));
                if (!$s)
                { 
                    $params =  Mage::app()->getRequest()->getParam('groups');
                    if(isset($params['activation']['fields']['websites']['value'])){
                        $s = $params['activation']['fields']['websites']['value'];
                    }else if(Mage::app()->getRequest()->getParam('website')){
                        $s = explode(',', str_replace($data, '', Mage::helper('core')->decrypt(Mage::getStoreConfig('auspost/activation/websites'))));
                    }
                }
                $en = $res['suc'];
                if(isset($s) && $s != null){
                    $web = $enc->encrypt($data.implode(',', $s).$data);
                }else{
                    $web = $enc->encrypt($data.$data);
                }
            }
            else
            {
                $modulestatus->saveConfig('auspost/activation/key', "");
                $modulestatus->saveConfig('carriers/auspost/active', 0);

            }
            $groups = array(
                'activation'=>array(
                    'fields'=>array(
                        'data'=>array(
                            'value'=>$data
                        ),
                        'websites'=>array(
                            'value'=>(string)$web
                        ),
                        'en'=>array(
                            'value'=>$en
                        ),

                        'installed'=>array(
                            'value'=>1
                        )
                    )
                )
            );
            Mage::getModel('adminhtml/config_data')
            ->setSection('auspost')
            ->setGroups($groups)
            ->save();
            Mage::getConfig()->reinit();
            Mage::app()->reinitStores();  
        }

        public function getDataInfo()
        {
            $data = Mage::getStoreConfig('auspost/activation/data');
            return Zend_Json::decode(base64_decode(Mage::helper('core')->decrypt($data)));
        }

        public function getAllWebsites(){

            if(!Mage::getStoreConfig('auspost/activation/installed'))
            {
                return array();
            }
            $data = Mage::getStoreConfig('auspost/activation/data');
            $web = Mage::getStoreConfig('auspost/activation/websites');
            $websites = explode(',', str_replace($data, '', Mage::helper('core')->decrypt($web)));
            $websites = array_diff($websites, array(""));
            return $websites;
        }

        public function getFormatUrl($url)
        {
            $input = trim($url, '/');
            if (!preg_match('#^http(s)?://#', $input)) {
                $input = 'http://' . $input;
            }
            $urlParts = parse_url($input);
            $domain   = preg_replace('/^www\./', '', $urlParts['host'].$urlParts['path']);
            return $domain;
        }

        public function isEnable()
        {                
            $websiteId = Mage::app()->getWebsite()->getId() ; 
            $isenabled = Mage::getStoreConfig('carriers/auspost/active');
            if($isenabled){
                if($websiteId){
                    $websites = $this->getAllWebsites();
                    $key      = Mage::getStoreConfig('auspost/activation/key');
                    if($key == null || $key == '')
                    {
                        return false; 
                    }else{
                        $en = Mage::getStoreConfig('auspost/activation/en');
                        if($isenabled && $en && in_array($websiteId, $websites))
                        { return true; } else { return false; }
                    }
                }
                else{
                    $en = Mage::getStoreConfig('auspost/activation/en');
                    if($isenabled && $en){return true;}
                }
            }
        }
    }