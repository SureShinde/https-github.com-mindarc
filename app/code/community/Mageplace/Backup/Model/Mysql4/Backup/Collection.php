<?php
/**
 * Mageplace Magesocial
 *
 * @category   Mageplace
 * @package    Mageplace_Magesocial
 * @copyright  Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Model_Mysql4_Backup_Collection
 */
class Mageplace_Backup_Model_Mysql4_Backup_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected $_gridView = false;


    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('mpbackup/backup');
    }

    /**
     * Creates an options array for grid filter functionality
     *
     * @return array Options array
     */
    public function toOptionHash()
    {
        return $this->_toOptionHash('backup_id', 'backup_name');
    }

    /**
     * Creates an options array for edit functionality
     *
     * @return array Options array
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('backup_id', 'backup_name');
    }

    public function setGridView($grid_view = true)
    {
        $this->_gridView = $grid_view;

        return $this;
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();

        if (!$this->_gridView) {
            return $this;
        }

        /** @var Mageplace_Backup_Model_Backup $item */
        foreach ($this->_items as $item) {
            $finishDate = $item->getData(Mageplace_Backup_Model_Backup::COLUMN_FINISH_DATE);
            if (!$finishDate || $finishDate == '0000-00-00 00:00:00') {
                $item->setData(Mageplace_Backup_Model_Backup::COLUMN_FINISH_DATE, '');
            }
        }

        return $this;
    }

    /**
     * Add Filter by profile
     *
     * @param int|Mageplace_Backup_Model_Profile|Mageplace_Backup_Model_Backup $profile Profile to be filtered
     *
     * @return Mageplace_Backup_Model_Mysql4_Backup_Collection
     */
    public function addProfileFilter($profile)
    {
        if ($profile instanceof Mageplace_Backup_Model_Profile) {
            $profile = $profile->getId();
        } else {
            if ($profile instanceof Mageplace_Backup_Model_Backup) {
                $profile = $profile->getProfileId();
            }
        }

        $profile = (int)$profile;
        if (!$profile) {
            return $this;
        }

        $select = $this->getSelect();

        $select->join(
            array(
                'profile_table' => $this->getTable('mpbackup/profile')
            ),
            'main_table.profile_id = profile_table.profile_id',
            array()
        );

        $select->where(
            'profile_table.profile_id IN (?)',
            array(
                0,
                $profile
            )
        )->group(
                'main_table.backup_id'
            );

        return $this;
    }

    /**
     * Add Filter by errors
     *
     * @param int
     *
     * @return Mageplace_Backup_Model_Mysql4_Backup_Collection
     */
    public function addStatusFilter($value)
    {
        if ($value) {
            $this->addFieldToFilter('backup_errors', array('neq' => ""));
        } else {
            $this->addFilter('backup_errors', '');
        }

        return $this;
    }
}
