<?php

/* Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future.
 *****************************************************
 * @category   Tb
 * @package    Tb_Extratabs
 * @copyright  Copyright (c) 2014
 * @license    http://opensource.org/licenses/OSL-3.0
 */

class Tb_Extratabs_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function descriptionTab(){
        return Mage::getStoreConfig('extratabsset/general_settings/description');
    }

    public function upsellTab(){
        return Mage::getStoreConfig('extratabsset/general_settings/upsell');
    }

    public function reviewTab(){
        return Mage::getStoreConfig('extratabsset/general_settings/review');
    }

    public function relatedTab(){
        return Mage::getStoreConfig('extratabsset/general_settings/related');
    }

    public function additionalTab(){
        return Mage::getStoreConfig('extratabsset/general_settings/additional');
    }

    public function tagsTab(){
        return Mage::getStoreConfig('extratabsset/general_settings/tags');
    }

    public function staticBlockTab(){

        return Mage::getStoreConfig('extratabsset/general_settings/blocks');
    }

    public function attributeTab1(){
        if(Mage::getStoreConfig('extratabsset/custom_attributes/active_tab1')){
            $arr = array(
                    'title' => Mage::getStoreConfig('extratabsset/custom_attributes/tab1_title'),
                    'code' => Mage::getStoreConfig('extratabsset/custom_attributes/tab1_id'),
                    'template' => 'extratabs/product/view/attribute1.phtml'
                     );
            return $arr;
        }else{
            return false;
        }
    }

    public function attributeTab2(){
        if(Mage::getStoreConfig('extratabsset/custom_attributes/active_tab2')){
            $arr = array(
                'title' => Mage::getStoreConfig('extratabsset/custom_attributes/tab2_title'),
                'code' => Mage::getStoreConfig('extratabsset/custom_attributes/tab1_id'),
                'template' => 'extratabs/product/view/attribute2.phtml'
            );
            return $arr;
        }else{
            return false;
        }
    }

    public function attributeTab3(){
        if(Mage::getStoreConfig('extratabsset/custom_attributes/active_tab3')){
            $arr = array(
                'title' => Mage::getStoreConfig('extratabsset/custom_attributes/tab3_title'),
                'code' => Mage::getStoreConfig('extratabsset/custom_attributes/tab3_id'),
                'template' => 'extratabs/product/view/attribute3.phtml'
            );
            return $arr;
        }else{
            return false;
        }
    }

}