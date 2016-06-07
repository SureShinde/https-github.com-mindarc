<?php
Mage::helper('ewcore/cache')->clean();
$installer = $this;
$installer->startSetup();

$command  = "
  DROP TABLE IF EXISTS `ewreviewreminder_blacklist`;
	CREATE TABLE `ewreviewreminder_blacklist` (
	  `blacklist_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `email_address` varchar(255) NOT NULL,
	  `updated_at` datetime NOT NULL,
	  `created_at` datetime NOT NULL,
	  PRIMARY KEY (`blacklist_id`),
	  KEY `idx_email_address` (`email_address`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		
	DROP TABLE IF EXISTS `ewreviewreminder_history`;
	CREATE TABLE `ewreviewreminder_history` (
	  `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `store_id` smallint(5) unsigned NOT NULL,
	  `order_id` int(10) unsigned DEFAULT NULL,
	  `customer_id` int(10) unsigned DEFAULT NULL,
	  `reminder_id` int(10) unsigned NOT NULL,
	  `customer_name` varchar(255) NOT NULL,
	  `customer_email` varchar(255) NOT NULL,
	  `email_type` enum('plain','html') NOT NULL DEFAULT 'plain',
	  `email_subject` text NOT NULL,
	  `email_text` text NOT NULL,
	  `coupon_expires_at` date DEFAULT NULL,
	  `coupon_redeemed` tinyint(4) unsigned DEFAULT NULL,
	  `coupon_code_exists` tinyint(4) DEFAULT NULL,
	  `recovery_code` varchar(255) NOT NULL,
	  `coupon_code` varchar(255) DEFAULT NULL,
	  `reminder_num` int(11) unsigned NOT NULL,
	  `recovered_from` varchar(32) NOT NULL,
	  `recovered_at` datetime DEFAULT NULL,
	  `sent_at` datetime NOT NULL,
	  `last_ordered_at` datetime DEFAULT NULL,
	  PRIMARY KEY (`history_id`),
	  UNIQUE KEY `idx_recovery_code` (`recovery_code`),
	  KEY `idx_customer_id` (`customer_id`),
	  KEY `idx_store_id` (`store_id`),
	  KEY `idx_order_id` (`order_id`),
	  CONSTRAINT `fk_bb9vyx7u9l1l7k4` FOREIGN KEY (`store_id`) REFERENCES `core_store` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE,
	  CONSTRAINT `fk_bblv2zz9amq5ild` FOREIGN KEY (`order_id`) REFERENCES `sales_flat_order` (`entity_id`) ON DELETE SET NULL ON UPDATE CASCADE,
	  CONSTRAINT `fk_bbtv2thut8ynoyn` FOREIGN KEY (`customer_id`) REFERENCES `customer_entity` (`entity_id`) ON DELETE SET NULL ON UPDATE CASCADE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		
	DROP TABLE IF EXISTS `ewreviewreminder_reminder`;
	CREATE TABLE `ewreviewreminder_reminder` (
	  `reminder_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `store_id` smallint(5) unsigned NOT NULL,
	  `customer_id` int(10) unsigned DEFAULT NULL,
	  `order_id` int(10) unsigned DEFAULT NULL,
	  `status` enum('pending','invalid') NOT NULL DEFAULT 'pending',
	  `customer_email` varchar(255) NOT NULL,
	  `customer_firstname` varchar(255) NOT NULL,
	  `customer_lastname` varchar(255) NOT NULL,
	  `email_subject` text NOT NULL,
	  `email_text` text NOT NULL,
	  `product_list` text NOT NULL,
	  `product_ids` text NOT NULL,
	  `coupon_code` varchar(255) DEFAULT NULL,
	  `recovery_code` varchar(255) NOT NULL,
	  `reminder_num` int(10) unsigned NOT NULL,
	  `scheduled_at` datetime NOT NULL,
	  `last_ordered_at` datetime DEFAULT NULL,
	  `last_reminded_at` datetime DEFAULT NULL,
	  `invalid_at` datetime DEFAULT NULL,
	  PRIMARY KEY (`reminder_id`),
	  UNIQUE KEY `idx_recovery_code` (`recovery_code`),
	  KEY `idx_status` (`status`),
	  KEY `idx_customer_id` (`customer_id`),
	  KEY `idx_store_id` (`store_id`),
	  KEY `idx_order_id` (`order_id`),
	  CONSTRAINT `fk_bfprjcez5a7nd3v` FOREIGN KEY (`order_id`) REFERENCES `sales_flat_order` (`entity_id`) ON DELETE SET NULL ON UPDATE CASCADE,
	  CONSTRAINT `fk_bgzj80k2ho33r2y` FOREIGN KEY (`store_id`) REFERENCES `core_store` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE,
	  CONSTRAINT `fk_bz0ywu1p6vioif4` FOREIGN KEY (`customer_id`) REFERENCES `customer_entity` (`entity_id`) ON DELETE SET NULL ON UPDATE CASCADE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";


$command = @preg_replace('/(EXISTS\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);
$command = @preg_replace('/(REFERENCES\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);
$command = @preg_replace('/(TABLE\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);

$installer->run($command);
$installer->endSetup();