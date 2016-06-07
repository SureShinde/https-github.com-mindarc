<?php

class Magentothem_Bannersequence_Block_Bannersequence extends Mage_Core_Block_Template {

    public function _prepareLayout() {
        return parent::_prepareLayout();
    }

    public function getBannersequence() {

        $collection = Mage::getResourceModel('bannersequence/bannersequence_collection');
        $collection->getSelect()
                ->where('find_in_set(0, store_id) OR find_in_set(?, store_id)', (int) (Mage::app()->getStore()->getId()))
                ->where('status=1')
				->order('order','ASC');
        if (count($collection) > 0)
            return $collection;
        return array();
    }

    function sorting_array($array, $mode='tang') {
        if ($mode == 'tang') {
            $length = count($array);
            for ($i = 0; $i < $length - 1; $i++) {
                $k = $i;
                for ($j = $i + 1; $j < $length; $j++)
                    if ($array[$j]['order'] < $array[$k]['order'])
                        $k = $j;
                $t = $array[$k];
                $array[$k] = $array[$i];
                $array[$i] = $t;
            }
        }
        if ($mode == 'giam') {
            $length = count($array);
            for ($i = 0; $i < $length - 1; $i++) {
                $k = $i;
                for ($j = $i + 1; $j < $length; $j++)
                    if ($array[$j]['order'] > $array[$k]['order'])
                        $k = $j;
                $t = $array[$k];
                $array[$k] = $array[$i];
                $array[$i] = $t;
            }
        }
        return $array;
    }

    public function getConfig($att) {
        $config = Mage::getStoreConfig('bannersequence');
        if (isset($config['bannersequence_config'])) {
            $value = $config['bannersequence_config'][$att];
            return $value;
        } else {
            throw new Exception($att . ' value not set');
        }
    }

}