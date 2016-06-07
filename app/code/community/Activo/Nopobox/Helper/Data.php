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
 
class Activo_Nopobox_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getBillingCss()
    {
        if (Mage::getStoreConfig('activo_nopobox/global/restrict_billing'))
        {
            return " validate-nopobox";
        }
        else
        {
            return "";
        }
    }
    
    public function getShippingCss()
    {
        if (Mage::getStoreConfig('activo_nopobox/global/restrict_shipping'))
        {
            return " validate-nopobox";
        }
        else
        {
            return "";
        }
    }
}