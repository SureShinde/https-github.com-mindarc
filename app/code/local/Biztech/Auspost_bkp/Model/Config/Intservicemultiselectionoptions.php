<?php
    class Biztech_Auspost_Model_Config_Intservicemultiselectionoptions extends Mage_Core_Model_Config_Data
    {

        public function toOptionArray($isMultiselect)
        {
            $options = array();

            $options = array(
                array('value' => 'INTL_SERVICE_AIR_MAIL', 'label' => Mage::helper('auspost')->__('Air Mail')),
                array('value' => 'INTL_SERVICE_SEA_MAIL', 'label' => Mage::helper('auspost')->__('Sea Mail')),
                array('value' => 'INTL_SERVICE_ECI_PLATINUM', 'label' => Mage::helper('auspost')->__('Express Courier International Platinum')),
                array('value' => 'INTL_SERVICE_EPI', 'label' => Mage::helper('auspost')->__('Express Post International')),
                array('value' => 'INTL_SERVICE_PTI', 'label' => Mage::helper('auspost')->__('Pack and Track International')),
                array('value' => 'INTL_SERVICE_RPI', 'label' => Mage::helper('auspost')->__('Registered Post International')),
                array('value' => 'INTL_SERVICE_ECI_M', 'label' => Mage::helper('auspost')->__('Express Courier International Merchandise')),
                array('value' => 'INTL_SERVICE_ECI_D', 'label' => Mage::helper('auspost')->__('Express Courier International Documents'))

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
