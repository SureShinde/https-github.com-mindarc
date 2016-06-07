<?php
/**
 * Mageplace Backup
 *
 * @category      Mageplace
 * @package       Mageplace_Backup
 * @copyright     Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license       http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Helper_Js
 */
class Mageplace_Backup_Helper_Js extends Mage_Core_Helper_Js
{

    public function __construct()
    {
        $this->_translateData = array(
            'Cancel'                                                 => Mage::helper('adminhtml')->__('Cancel'),
            'WARNING!'                                               => $this->__('WARNING!'),
            'Do not reload or close the page during backup process.' => $this->__('Do not reload or close the page during backup process.'),
            'Backup - %s'                                            => $this->__('Backup - %s', Mage::app()->getLocale()->storeDate(null, null, true)),
            'It\'s not enough time to run backup'                    => $this->__('It\'s not enough time to run backup. To eliminate this error try to split the content (files and DB tables) of this backup profile into smaller parts (profiles which will include the half or less from the original profile content).'),
            'Backup model error'                                     => $this->__('Backup model error'),
            'Backup ID error'                                        => $this->__('Backup ID error'),
            'Empty response body'                                    => $this->__('Empty response body'),
            'Error %d: %s'                                           => $this->__('Error %d: %s'),
            'Backup Errors'                                          => $this->__('Backup Errors'),
            'Step %1$d'                                              => $this->__('Step %d'),
            'Step %1$d from %2$d'                                    => $this->__('Step %1$d from %2$d'),
            'Backup code id error'                                   => $this->__('Backup code id error'),
            'Unknown response status'                                => $this->__('Unknown response status'),
        );
    }

    /**
     * Retrieve JS translator initialization javascript
     *
     * @return string
     */
    public function getTranslatorScript()
    {
        return 'if (typeof(Translator) == \'undefined\') {' . "\n"
        . '    var Translator = new Translate(' . $this->getTranslateJson() . ');' . "\n"
        . '} else {' . "\n"
        . '    Translator.add(' . $this->getTranslateJson() . ');' . "\n"
        . '}' . "\n";
    }

    public function getStepObject()
    {
        return Mageplace_Backup_Model_Backup::getStepObjectJs();
    }
}