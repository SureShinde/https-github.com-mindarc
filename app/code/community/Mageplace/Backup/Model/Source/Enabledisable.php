<?php
/**
 * Mageplace Magesocial
 *
 * @category   Mageplace
 * @package    Mageplace_Magesocial
 * @copyright  Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Model_Source_Enabledisable extends Mageplace_Backup_Model_Source_Abstract
{
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label' => $this->_getHelper()->__('Enable')),
            array('value' => 0, 'label' => $this->_getHelper()->__('Disable')),
        );
    }
}
