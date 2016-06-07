<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_Files  extends Mage_Adminhtml_Block_Widget_Form
	implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	const PATH_AREA_ID = 'path_area';
	
	/**
	 * Get tab label
	 *
	 * @return string
	 */
	public function getTabLabel()
	{
		return Mage::helper('mpbackup')->__('Files and Directories Exclusion');
	}

	/**
	 * Get tab title
	 *
	 * @return string
	 */
	public function getTabTitle()
	{
		return $this->getTabLabel();
	}

	/**
	 * Whether tab is available
	 *
	 * @return bool
	 */
	public function canShowTab()
	{
		return true;
	}

	/**
	 * Whether tab is visible
	 *
	 * @return bool
	 */
	public function isHidden()
	{
		return false;
	}

	/**
	 * Class constructor
	 *
	 */
	public function __construct()
	{
		parent::__construct();

		$this->setTemplate('mpbackup/filesedit.phtml');
	}

	protected function _prepareLayout()
	{
		$this->setChild('files', $this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit_files'));

		return parent::_prepareLayout();
	}
	
	public function getFilesAreaHtml()
	{
		return $this->getChild('files')->toHtml();
	}
	
	public function getPathAreaId()
	{
		return self::PATH_AREA_ID;
	}
                
}