<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

$source = Mage::getBaseDir('tmp') . DS . 'lib' . DS . 'Zend';
$dest = Mage::getBaseDir('lib') . DS . 'Zend';


try {
	$classExists = class_exists('Zend_Oauth');
} catch(Exception $e) {
	$classExists = false;
}

if(!$classExists && (!file_exists($dest . DS . 'Oauth.php') || !file_exists($dest . DS . 'Oauth')) && file_exists($source)) {
	Mage::helper('mpbackup')->copyDirectory($source, $dest);
}

if(file_exists($source)) {
	Mage::helper('mpbackup')->deleteDirectory($source);
}