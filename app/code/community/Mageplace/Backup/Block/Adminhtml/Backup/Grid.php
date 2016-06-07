<?php
/**
 * Mageplace Backup
 *
 * @category       Mageplace
 * @package        Mageplace_Backup
 * @copyright      Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license        http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Block_Adminhtml_Backup_Grid
 */
class Mageplace_Backup_Block_Adminhtml_Backup_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor of Grid
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->setId('mpbackup_backup_grid');
        $this->setUseAjax(false);
        $this->setDefaultSort('backup_id');
        $this->setDefaultDir('DESC');
    }

    /**
     * Preparation of the data that is displayed by the grid.
     *
     * @return Mageplace_Backup_Block_Adminhtml_Profile_Grid
     */
    protected function _prepareCollection()
    {
        /* @var $collection Mageplace_Backup_Model_Mysql4_Backup_Collection */
        $collection = Mage::getResourceModel('mpbackup/backup_collection')->setGridView();

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
        $this->addColumn('backup_id',
            array(
                'header' => $this->__('Backup ID'),
                'index'  => 'backup_id',
                'type'   => 'number',
                'width'  => '80px',
            )
        );

        $this->addColumn('backup_name',
            array(
                'header' => $this->__('Backup Name'),
                'index'  => 'backup_name',
            )
        );

        $this->addColumn('backup_description',
            array(
                'header' => $this->__('Backup Description'),
                'index'  => 'backup_description',
            )
        );

        $this->addColumn('profile_id',
            array(
                'header'                    => $this->__('Profile'),
                'index'                     => 'profile_id',
                'type'                      => 'options',
                'width'                     => '200px',
                'options'                   => $this->_getProfiles(),
                'sortable'                  => false,
                'filter_condition_callback' => array(
                    $this,
                    '_filterProfileCondition'
                )
            )
        );

        $this->addColumn('backup_cron',
            array(
                'header'  => $this->__('Cron'),
                'index'   => 'backup_cron',
                'type'    => 'options',
                'options' => $this->_getYesNo(),
            )
        );

        $this->addColumn('backup_status',
            array(
                'header'   => $this->__('Status'),
                'index'    => 'backup_status',
                'type'     => 'options',
                'renderer' => 'mpbackup/adminhtml_backup_grid_column_renderer_status',
                'options'  => Mage::getModel('mpbackup/source_backupprocessstatus')->toOptionHash(),
            )
        );

        $this->addColumn('backup_creation_date',
            array(
                'header'    => $this->__('Created'),
                'index'     => 'backup_creation_date',
                'type'      => 'datetime',
                'gmtoffset' => true,
                'width'     => '150px',
                'default'   => ' ---- '
            )
        );

        $this->addColumn('backup_finish_date',
            array(
                'header'    => $this->__('Finished'),
                'index'     => 'backup_finish_date',
                'type'      => 'datetime',
                'gmtoffset' => true,
                'width'     => '150px',
                'default'   => ' ---- '
            )
        );

        $this->addColumn('action',
            array(
                'header'   => $this->__('Action'),
                'index'    => 'backup_id',
                'width'    => '150px',
                'type'     => 'action',
                'filter'   => false,
                'sortable' => false,
                'actions'  => array(
                    array(
                        'caption' => Mage::helper('mpbackup')->__('Delete record'),
                        'url'     => $this->getUrl('*/*/deleteRecord', array('backup_id' => '$backup_id')),
                        'confirm' => Mage::helper('adminhtml')->__('Are you sure you want to do this?')
                    ),
                    array(
                        'caption' => Mage::helper('adminhtml')->__('Delete record and files'),
                        'url'     => $this->getUrl('*/*/delete', array('backup_id' => '$backup_id')),
                        'confirm' => Mage::helper('mpbackup')->__('Are you sure you want to delete backup record and files?')
                    )
                ),
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * Helper function to load profile collection
     */
    protected function _getProfiles()
    {
        return Mage::getResourceModel('mpbackup/profile_collection')->toOptionHash();
    }

    /**
     * Helper function to load yes-no array
     */
    protected function _getYesNo()
    {
        return array(
            '1' => Mage::helper('adminhtml')->__('Yes'),
            '0' => Mage::helper('adminhtml')->__('No'),
        );
    }

    /**
     * Helper function to add page filter condition
     *
     * @param Mage_Core_Model_Mysql4_Collection_Abstract $collection Data collection
     * @param Mage_Adminhtml_Block_Widget_Grid_Column    $column     Column information to be filtered
     */
    protected function _filterProfileCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $this->getCollection()->addProfileFilter($value);
    }

    /**
     * Helper function to reveive on row click url
     *
     * @param Mageplace_Backup_Model_Profile $row Current rows dataset
     *
     * @return string URL for current row's onclick event
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('backup_id' => $row->getBackupId()));
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

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('backup_id');
        $this->getMassactionBlock()->setFormFieldName('backuptable');

        $this->getMassactionBlock()->addItem('deleteRecord',
            array(
                'label' => $this->__('Delete records'),
                'url'   => $this->getUrl('*/*/massDeleteRecord')
            )
        );

        $this->getMassactionBlock()->addItem('delete',
            array(
                'label' => $this->__('Delete records and files'),
                'url'   => $this->getUrl('*/*/massDelete')
            )
        );

        return $this;
    }
}
