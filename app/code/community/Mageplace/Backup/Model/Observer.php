<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */
 
class Mageplace_Backup_Model_Observer
{
	function processControllerActionPredispatch($observer)
	{
		if((session_id() == 'mpbackup') && ($oldSessionId = Mage::helper('mpbackup')->getOldSessionId()) && ($oldSessionId != 'mpbackup')) {
			$module_name = Mage::app()->getRequest()->getModuleName();
			if($module_name != 'mpbackup') {
				session_write_close();				
				session_id($oldSessionId);
				@session_start();				
			}
		}
		
		
		return $this;
	}
	
	
}
