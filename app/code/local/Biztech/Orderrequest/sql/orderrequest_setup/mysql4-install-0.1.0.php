<?php

    $installer = $this;

    $installer->startSetup();

    $installer->run("

        -- DROP TABLE IF EXISTS {$this->getTable('orderrequest')};
        CREATE TABLE {$this->getTable('orderrequest')} (
        `orderrequest_id` int(11) unsigned NOT NULL auto_increment,
        `order_increment_id` varchar(50) default NULL,
        `customer_email` varchar(255) default NULL,
        `type` varchar(10) default NULL,
        `created_time` datetime default NULL,
        `update_time` datetime default NULL,
        PRIMARY KEY  (`orderrequest_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        ");

    $installer->endSetup();