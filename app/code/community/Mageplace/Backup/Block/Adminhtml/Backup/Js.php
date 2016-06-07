<?php

/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */
class Mageplace_Backup_Block_Adminhtml_Backup_Js extends Mage_Core_Block_Template
{
    /**
     * @return Mageplace_Backup_Model_Profile
     */
    public function getProfile()
    {
        return Mage::registry('mpbackup_profile');
    }

    /**
     * @return Mageplace_Backup_Helper_Js
     */
    public function getJsHelper()
    {
        return Mage::helper('mpbackup/js');
    }

    /**
     * @return Mageplace_Backup_Helper_Url
     */
    public function getUrlHelper()
    {
        return Mage::helper('mpbackup/url');
    }

    protected function _toHtml()
    {
        return preg_replace(array('#\<script[^\>]*\>#si', '#\<\/script\>#si'), '', parent::_toHtml());
    }
}
