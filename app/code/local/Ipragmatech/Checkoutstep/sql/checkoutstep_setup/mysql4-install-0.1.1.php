<?php

$installer = $this;
 
$installer->startSetup();
 
$installer->run("
DROP TABLE IF EXISTS {$installer->getTable('checkoutstep/customerdata')};
CREATE TABLE {$installer->getTable('checkoutstep/customerdata')} (
 `id` int(11) unsigned NOT NULL auto_increment,
  `entrant_name` varchar(100),
  `entrant_email` varchar(100),
  `entrant_phone` varchar(30),
  `permanent_address`varchar(255) NULL,
  `address` varchar(255) NULL,
  `order_id` int(11) NOT NULL default '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
