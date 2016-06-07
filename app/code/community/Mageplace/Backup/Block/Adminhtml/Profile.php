<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Block_Adminhtml_Profile extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	protected $_blockGroup = 'mpbackup';
	protected $_controller = 'adminhtml_profile';
    
	/**
	 * Constructor for Adminhtml Profile Block
	 */
	public function __construct()
	{
		$this->_addButtonLabel = $this->__('Add New Profile');
		
		parent::__construct();
	}

	public function getHeaderText()
	{
		return $this->__('Manage Profiles');
	}
	
    /**
     * Returns the CSS class for the header
     * 
     * Usually 'icon-head' and a more precise class is returned. We return
     * only an empty string to avoid spacing on the left of the header as we
     * don't have an icon.
     * 
     * @return string
     */
	public function getHeaderCssClass()
	{
		return '';
	}
}
