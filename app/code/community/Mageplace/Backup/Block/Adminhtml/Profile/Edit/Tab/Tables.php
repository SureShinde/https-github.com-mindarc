<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Tab_Tables  extends Mage_Adminhtml_Block_Widget_Form
	implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	/**
	 * Get tab label
	 *
	 * @return string
	 */
	public function getTabLabel()
	{
		return Mage::helper('mpbackup')->__('DB Tables Exclusion');
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

		$this->setTemplate('mpbackup/tablesedit.phtml');
	}

	protected function _prepareLayout()
	{
		$this->setChild('DBTables', $this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit_tables'));

		return parent::_prepareLayout();
	}
	
	public function getTablesAreaHtml()
	{
		return $this->getChild('DBTables')->toHtml();
	}
}