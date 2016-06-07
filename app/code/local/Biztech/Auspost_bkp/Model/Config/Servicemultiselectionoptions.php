<?php
    class Biztech_Auspost_Model_Config_Servicemultiselectionoptions extends Mage_Core_Model_Config_Data
    {

        public function toOptionArray($isMultiselect)
        {
            $options = array();

            $options = array(
                array('value' => 'AUS_PARCEL_REGULAR', 'label' => Mage::helper('auspost')->__('Standard')),
                array('value' => 'AUS_PARCEL_EXPRESS', 'label' => Mage::helper('auspost')->__('Express')),
            );
            return $options;
        }

        public static function getAllOptions(){
            $option = array();
            $services = self::toOptionArray();
            foreach($services as $service){
                $option[$service['value']] = $service['label'];
            }
            return $option;
        }


    }
