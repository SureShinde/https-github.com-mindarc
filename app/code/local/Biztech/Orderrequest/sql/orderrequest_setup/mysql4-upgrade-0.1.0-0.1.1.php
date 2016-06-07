<?php
    $installer = $this;

    $installer->startSetup();

    $this->_conn->addColumn($this->getTable('sales_flat_order_status_history'), 'type', 'VARCHAR(10)');

    $installer->endSetup(); 