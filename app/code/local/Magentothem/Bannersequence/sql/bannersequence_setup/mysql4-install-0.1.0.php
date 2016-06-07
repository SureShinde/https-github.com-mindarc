<?php

$installer = $this;

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('bannersequence')};
CREATE TABLE {$this->getTable('bannersequence')} (
  `bannersequence_id` int(11) unsigned NOT NULL auto_increment,
  `store_id` varchar(255) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `title2` varchar(255) NOT NULL default '',
  `link` varchar(255) NOT NULL default '',
  `filename` varchar(255) NOT NULL default '',
  `image` varchar(255) NOT NULL default '',
  `image2` varchar(255) NOT NULL default '',
  `description` text NOT NULL default '',
  `status` smallint(6) NOT NULL default '0',
  `order` smallint(6) NOT NULL default '0',
  `kind_effect` varchar(255) NOT NULL default '',
  `animate` varchar(255) NOT NULL default '',
  `created_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`bannersequence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup(); 