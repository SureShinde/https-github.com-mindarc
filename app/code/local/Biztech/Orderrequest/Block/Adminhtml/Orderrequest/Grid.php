<?php

class Biztech_Orderrequest_Block_Adminhtml_Orderrequest_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('orderrequestGrid');
      $this->setDefaultSort('order_increment_id');
      $this->setDefaultDir('DESC');
      $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      $collection = Mage::getModel('orderrequest/orderrequest')->getCollection();
      //$collection->getSelect()->group('order_increment_id');
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
      /*$this->addColumn('orderrequest_id', array(
          'header'    => Mage::helper('orderrequest')->__('ID'),
          'align'     =>'right',
          'width'     => '50px',
          'index'     => 'orderrequest_id',
      ));*/

      $this->addColumn('order_increment_id', array(
          'header'    => Mage::helper('orderrequest')->__('Order #'),
          'align'     =>'left',
          'index'     => 'order_increment_id',
      ));

	  /*
      $this->addColumn('content', array(
			'header'    => Mage::helper('orderrequest')->__('Item Content'),
			'width'     => '150px',
			'index'     => 'content',
      ));
	  */

      /*$this->addColumn('status', array(
          'header'    => Mage::helper('orderrequest')->__('Status'),
          'align'     => 'left',
          'width'     => '80px',
          'index'     => 'status',
          'type'      => 'options',
          'options'   => array(
              1 => 'Enabled',
              2 => 'Disabled',
          ),
      ));*/
	  
        /*$this->addColumn('action',
            array(
                'header'    =>  Mage::helper('orderrequest')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('orderrequest')->__('Edit'),
                        'url'       => array('base'=> '* /* /edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));*/
		
		//$this->addExportType('*/*/exportCsv', Mage::helper('orderrequest')->__('CSV'));
		//$this->addExportType('*/*/exportXml', Mage::helper('orderrequest')->__('XML'));
	  
      return parent::_prepareColumns();
  }

    /*protected function _prepareMassaction()
    {
        $this->setMassactionIdField('orderrequest_id');
        $this->getMassactionBlock()->setFormFieldName('orderrequest');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('orderrequest')->__('Delete'),
             'url'      => $this->getUrl('* /* /massDelete'),
             'confirm'  => Mage::helper('orderrequest')->__('Are you sure?')
        ));

        $statuses = Mage::getSingleton('orderrequest/status')->getOptionArray();

        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('orderrequest')->__('Change status'),
             'url'  => $this->getUrl('* /* /massStatus', array('_current'=>true)),
             'additional' => array(
                    'visibility' => array(
                         'name' => 'status',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => Mage::helper('orderrequest')->__('Status'),
                         'values' => $statuses
                     )
             )
        ));
        return $this;
    }*/

  public function getRowUrl($row)
  {
      //return $this->getUrl('*/*/edit', array('id' => $row->getId()));
      return $this->getUrl('*/*/edit', array('id' => $row->getId(),'oid' => $row->getOrderIncrementId()));
  }

}