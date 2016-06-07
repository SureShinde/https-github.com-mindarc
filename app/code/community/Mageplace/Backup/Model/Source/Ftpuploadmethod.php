<?php
/**
 * Mageplace Magesocial
 *
 * @category   Mageplace
 * @package    Mageplace_Magesocial
 * @copyright  Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */
class Mageplace_Backup_Model_Source_Ftpuploadmethod extends Mageplace_Backup_Model_Source_Abstract
{
    public function toOptionArray()
    {
        return array(
            array('value' => Mageplace_Backup_Model_Cloud_Ftp::UPLOADMETHOD_CURL, 'label' => $this->_getHelper()->__('CURL')),
            array('value' => Mageplace_Backup_Model_Cloud_Ftp::UPLOADMETHOD_PUT, 'label' => $this->_getHelper()->__('PUT')),
            array('value' => Mageplace_Backup_Model_Cloud_Ftp::UPLOADMETHOD_FTP, 'label' => $this->_getHelper()->__('FTP')),
        );
    }
}
