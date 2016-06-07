<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Block_Adminhtml_Profile_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	/**
	 * Constructor of Grid
	 *
	 */
	public function __construct()
	{
		parent::__construct();

		$this->setId('mpbackup_profile_grid');
		$this->setUseAjax(false);
		$this->setDefaultSort('profile_id');
		$this->setDefaultDir('ASC');
	}

	/**
	 * Preparation of the data that is displayed by the grid.
	 *
	 * @return Mageplace_Backup_Block_Adminhtml_Profile_Grid
	 */
	protected function _prepareCollection()
	{
		/* @var $collection Mageplace_Backup_Model_Mysql4_Profile_Collection */
		$collection = Mage::getResourceModel('mpbackup/profile_collection');
		$this->setCollection($collection);

		parent::_prepareCollection();

		return $this;
	}

	/**
	 * Preparation of the requested columns of the grid
	 *
	 * @return Mageplace_Backup_Block_Adminhtml_Profile_Grid
	 */
	protected function _prepareColumns()
	{
		$this->addColumn('profile_id',
			array(
				'header'	=> $this->__('Profile ID'),
				'index'		=> 'profile_id',
				'type'		=> 'number',
				'width'		=> '80px',
			)
		);

		$this->addColumn('profile_name',
			array(
				'header'	=> $this->__('Profile Name'),
				'index'		=> 'profile_name',
			)
		);
		
		$this->addColumn('profile_cloud_app',
			array(
				'header'	=> $this->__('Storage Application'),
				'index'		=> 'profile_cloud_app',
				'type'		=> 'options',
				'options'	=> $this->_getCloudApp(),
			)
		);
		
		$this->addColumn('profile_default',
			array(
				'header'	=> $this->__('Default'),
				'index'		=> 'profile_default',
				'type'		=> 'options',
				'options'	=> $this->_getYesNo(),
			)
		);
		
		$this->addColumn('action',
			array(
				'header'	=> $this->__('Action'),
				'index'		=> 'profile_id',
				'width'		=> '100px',
				'type'		=> 'action',
				'filter'	=> false,
				'sortable'	=> false,
				'actions'	=> array(
					array(
						'caption'	=> Mage::helper('adminhtml')->__('Edit'),
						'url'       => $this->getUrl('*/*/edit', array('profile_id' => '$profile_id')),
					),
            		array(
						'caption'   => Mage::helper('adminhtml')->__('Delete'),
						'url'       => $this->getUrl('*/*/delete', array('profile_id' => '$profile_id')),
						'confirm'   => Mage::helper('adminhtml')->__('Are you sure you want to do this?')           
            		)
            	),
			)
		);

		return parent::_prepareColumns();
	}
	
	/**
	 * Helper function to load yes-no array
	 */
	protected function _getYesNo()
	{
		return array(
			'0' => Mage::helper('adminhtml')->__('No'),
			'1' => Mage::helper('adminhtml')->__('Yes'),
		);
	}
	
	/**
	 * Helper function to load applications array
	 */
	protected function _getCloudApp()
	{
		return Mage::helper('mpbackup')->getAppsArray();
	}
	
	/**
	 * Helper function to reveive on row click url
	 *
	 * @param Mageplace_Backup_Model_Profile $row Current rows dataset
	 * @return string URL for current row's onclick event
	 */
	public function getRowUrl($row)
	{
		return $this->getUrl('*/*/edit', array('profile_id' => $row->getProfileId()));
	}

	/**
	 * Helper function to receive grid functionality urls for current grid
	 *
	 * @return string Requested URL
	 */
	public function getGridUrl()
	{
		return $this->getUrl('*/*/index', array('_current' => true));
	}
}
