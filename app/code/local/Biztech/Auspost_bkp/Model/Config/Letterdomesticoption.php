<?php
    class Biztech_Auspost_Model_Config_Letterdomesticoption extends Mage_Core_Model_Config_Data
    {

        public function toOptionArray($isMultiselect)
        {
            $options = array();

            $options = array(
                array('value' => 'AUS_LETTER_REGULAR_LARGE', 'label' => Mage::helper('auspost')->__('Regular')),
                array('value' => 'AUS_LETTER_EXPRESS_SMALL', 'label' => Mage::helper('auspost')->__('Express')),
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
