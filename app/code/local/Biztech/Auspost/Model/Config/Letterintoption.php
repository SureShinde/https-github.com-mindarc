<?php
    class Biztech_Auspost_Model_Config_Letterintoption extends Mage_Core_Model_Config_Data
    {

        public function toOptionArray($isMultiselect)
        {
            $options = array();

            $options = array(
                array('value' => 'INTL_SERVICE_ECI_PLATINUM', 'label' => Mage::helper('auspost')->__('Express Courier International Platinum')),
                array('value' => 'INTL_SERVICE_ECI_D', 'label' => Mage::helper('auspost')->__('Express Courier International Documents')),
                array('value' => 'INTL_SERVICE_EPI_C5', 'label' => Mage::helper('auspost')->__('Express Post International C5')),
                array('value' => 'INTL_SERVICE_EPI_B4', 'label' => Mage::helper('auspost')->__('Express Post International B4')),
                array('value' => 'INTL_SERVICE_PTI', 'label' => Mage::helper('auspost')->__('Pack and Track International')),
                array('value' => 'INTL_SERVICE_RPI_DLE', 'label' => Mage::helper('auspost')->__('Registered Post International Prepaid DL Envelope')),
                array('value' => 'INTL_SERVICE_RPI_B4', 'label' => Mage::helper('auspost')->__('Registered Post International B4')),
                array('value' => 'INTL_SERVICE_AIR_MAIL', 'label' => Mage::helper('auspost')->__('Air Mail')),
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
