<?php


class Biztech_Auspost_Model_Config_Source_Attributes extends Varien_Data_Collection{

    public function toOptionArray(){
        $attributes = Mage::getSingleton('eav/config')
            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection();


        $allProductAttributes = array();

        foreach($attributes as $attribute){
            if(($attribute['frontend_input'] == 'text'|| $attribute['frontend_input'] == 'select')){
                $allProductAttributes[] = array('label'=> $attribute['attribute_code'], 'value'=>$attribute['attribute_code']);
            }
        }

        return $allProductAttributes;
    }

    
}
