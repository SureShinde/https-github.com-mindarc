<?php
/**
 * Activo Extensions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Activo Commercial License
 * that is available through the world-wide-web at this URL:
 * http://extensions.activo.com/license_professional
 *
 * @copyright   Copyright (c) 2014 Activo Extensions (http://extensions.activo.com)
 * @license     Commercial
 * @thanks      Several updates were committed by Aydus/Matthew Valenti
 */

class Activo_Nopobox_Helper_Directory extends Mage_Directory_Helper_Data
{
    
    /**
     * Retrieve regions data json
     *
     * @return string
     */
    public function getRegionJson()
    {

        Varien_Profiler::start('TEST: '.__METHOD__);
        
        $excludeRegions = explode(',', Mage::getStoreConfig('activo_nopobox/exclusions/regions'));
                
        if (!$this->_regionJson) {
            $cacheKey = 'DIRECTORY_REGIONS_JSON_STORE'.Mage::app()->getStore()->getId();
            if (Mage::app()->useCache('config')) {
                $json = Mage::app()->loadCache($cacheKey);
            }
            
            $version_info = Mage::getVersionInfo();
            if ((int)$version_info['major'] == 1 && (int)$version_info['minor'] < 7)
            {
                if (empty($json)) {
                    $countryIds = array();
                    foreach ($this->getCountryCollection() as $country) {
                        $countryIds[] = $country->getCountryId();
                    }
                    $collection = Mage::getModel('directory/region')->getResourceCollection()
                        ->addCountryFilter($countryIds)
                        ->load();
                    $regions = array();
                    foreach ($collection as $region) {
                        if (!$region->getRegionId()) {
                            continue;
                        }
                        
                        //[Acivo] Skip excluded regions
                        if (in_array($region->getRegionId(), $excludeRegions)) {
                            continue;
                        }
                        
                        $regions[$region->getCountryId()][$region->getRegionId()] = array(
                            'code' => $region->getCode(),
                            'name' => $this->__($region->getName())
                        );
                    }
                    $json = Mage::helper('core')->jsonEncode($regions);

                    if (Mage::app()->useCache('config')) {
                        Mage::app()->saveCache($json, $cacheKey, array('config'));
                    }
                }
            }
            else
            {
                
                if (empty($json)) {
                    $countryIds = array();
                    foreach ($this->getCountryCollection() as $country) {
                        $countryIds[] = $country->getCountryId();
                    }
                    $collection = Mage::getModel('directory/region')->getResourceCollection()
                        ->addCountryFilter($countryIds)
                        ->load();
                    $regions = array(
                        'config' => array(
                            'show_all_regions' => $this->getShowNonRequiredState(),
                            'regions_required' => $this->getCountriesWithStatesRequired()
                        )
                    );
                    foreach ($collection as $region) {
                        if (!$region->getRegionId()) {
                            continue;
                        }
                        
                        //[Acivo] Skip excluded regions
                        if (in_array($region->getRegionId(), $excludeRegions)) {
                            continue;
                        }

                        $regions[$region->getCountryId()][$region->getRegionId()] = array(
                            'code' => $region->getCode(),
                            'name' => $this->__($region->getName())
                        );
                    }
                    $json = Mage::helper('core')->jsonEncode($regions);

                    if (Mage::app()->useCache('config')) {
                        Mage::app()->saveCache($json, $cacheKey, array('config'));
                    }
                }
            }
            
            
            $this->_regionJson = $json;
        }

        Varien_Profiler::stop('TEST: '.__METHOD__);
        return $this->_regionJson;
    }
}