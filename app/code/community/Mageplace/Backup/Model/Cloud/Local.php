<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Model_Cloud_Local extends Mageplace_Backup_Model_Cloud
{
	const FILE_PART_MAX_SIZE	= 0;
	
	protected function _construct()
	{
		parent::_construct();
	}
	
	public function needAuthorize()
	{
		return false;
	}
}
