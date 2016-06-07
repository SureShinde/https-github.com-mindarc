<?php

/**
 * Amasty Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Magesocial
 * @copyright  Copyright (c) 2015 Amasty. (http://www.amasty.com)
 */
class Mageplace_Backup_Model_Source_AmazonS3Regions extends Mageplace_Backup_Model_Source_Abstract
{
    public function toOptionArray()
    {
        $regions = array();

        foreach(Mageplace_Backup_Model_Cloud_Amazons3::$REGIONS as $regionCode => $regionName) {
            $regions[] = array('value' => $regionCode, 'label' => $this->_getHelper()->__($regionName));
        }

        return $regions;
    }
}
