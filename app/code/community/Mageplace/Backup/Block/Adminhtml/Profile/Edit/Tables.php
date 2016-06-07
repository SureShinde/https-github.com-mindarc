<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tables extends Mage_Core_Block_Template
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->setTemplate('mpbackup/tables.phtml');
	}

	public function getDBTables()
	{
		static $tables = null;
		
		if(is_null($tables)) {
			$DBTables = Mage::getModel('mpbackup/db')->getTables();
			$excluded = $this->getExcludedTables();
			foreach($DBTables as $table) {
				$tables[] = array(
					'name'		=> $table,
					'checked'	=> in_array($table, $excluded),
				);
			}
		}
		
		return $tables;
	}
	
	public function getProfile()
	{
		return Mage::registry('mpbackup_profile');
	}
	
	public function getExcludedTables()
	{
		return $this->getProfile()->getExcludedTables();
	}
}