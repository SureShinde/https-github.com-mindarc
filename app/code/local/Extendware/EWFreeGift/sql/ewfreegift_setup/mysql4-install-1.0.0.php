<?php
Mage::helper('ewcore/cache')->clean();
$installer = $this;
$installer->startSetup();

$sql = sprintf('SHOW COLUMNS FROM `%s`', $this->getTable('salesrule/rule'));
$columns = $this->getConnection()->fetchCol($sql);

$command = '';
if (in_array('extendware_product_skus', $columns) === false) {
	$command .= 'ALTER TABLE `salesrule` ADD `extendware_product_skus` TEXT NOT NULL;';
}

if (in_array('extendware_category_ids', $columns) === false) {
	$command .= 'ALTER TABLE `salesrule` ADD `extendware_category_ids` TEXT NOT NULL;';
}

if (in_array('extendware_max_applications', $columns) === false) {
	$command .= 'ALTER TABLE `salesrule` ADD `extendware_max_applications` INTEGER UNSIGNED NOT NULL;';
}

if (in_array('extendware_stop_trigger_exceptions', $columns) === false) {
	$command .= 'ALTER TABLE `salesrule` ADD `extendware_stop_trigger_exceptions` TEXT NOT NULL;';
}

if (in_array('extendware_stop_exceptions', $columns) === false) {
	$command .= 'ALTER TABLE `salesrule` ADD `extendware_stop_exceptions` TEXT NOT NULL;';
}
if (in_array('extendware_product_add_type', $columns) === false) {
	$command .= 'ALTER TABLE `salesrule` ADD `extendware_product_add_type` VARCHAR(255) NOT NULL;';
}

if (Mage::helper('ewcore/environment')->isDemoServer() === true) {
	$command .= "
		INSERT INTO `salesrule` (`rule_id`, `name`, `description`, `from_date`, `to_date`, `uses_per_customer`, `is_active`, `conditions_serialized`, `actions_serialized`, `stop_rules_processing`, `is_advanced`, `product_ids`, `sort_order`, `simple_action`, `discount_amount`, `discount_qty`, `discount_step`, `simple_free_shipping`, `apply_to_shipping`, `times_used`, `is_rss`, `coupon_type`, `use_auto_generation`, `uses_per_coupon`, `extendware_product_skus`, `extendware_category_ids`, `extendware_max_applications`, `extendware_stop_trigger_exceptions`, `extendware_stop_exceptions`, `extendware_product_add_type`) VALUES (100,'Receive a free gift when adding computer / laptop to cart','Add any product to the cart and you will have the option to select a free gift.\r\n\r\nIf multiple computers are purchased then up to 5 gifts may be selected based on the configuration.',NULL,NULL,0,1,'a:6:{s:4:\"type\";s:32:\"salesrule/rule_condition_combine\";s:9:\"attribute\";N;s:8:\"operator\";N;s:5:\"value\";s:1:\"1\";s:18:\"is_value_processed\";N;s:10:\"aggregator\";s:3:\"all\";}','a:7:{s:4:\"type\";s:40:\"salesrule/rule_condition_product_combine\";s:9:\"attribute\";N;s:8:\"operator\";N;s:5:\"value\";s:1:\"1\";s:18:\"is_value_processed\";N;s:10:\"aggregator\";s:3:\"all\";s:10:\"conditions\";a:1:{i:0;a:5:{s:4:\"type\";s:32:\"salesrule/rule_condition_product\";s:9:\"attribute\";s:12:\"category_ids\";s:8:\"operator\";s:2:\"==\";s:5:\"value\";s:6:\"27, 28\";s:18:\"is_value_processed\";b:0;}}}',0,1,NULL,0,'ewfg_conditional',1.0000,5.0000,1,0,0,1,1,1,0,0,'micronmouse5000,logitechcord,microsoftnatural','4,8,30',1,'','','any_product');
		INSERT INTO `salesrule` (`rule_id`, `name`, `description`, `from_date`, `to_date`, `uses_per_customer`, `is_active`, `conditions_serialized`, `actions_serialized`, `stop_rules_processing`, `is_advanced`, `product_ids`, `sort_order`, `simple_action`, `discount_amount`, `discount_qty`, `discount_step`, `simple_free_shipping`, `apply_to_shipping`, `times_used`, `is_rss`, `coupon_type`, `use_auto_generation`, `uses_per_coupon`, `extendware_product_skus`, `extendware_category_ids`, `extendware_max_applications`, `extendware_stop_trigger_exceptions`, `extendware_stop_exceptions`, `extendware_product_add_type`) VALUES (101,'Buy 1 cell phone and receive 1 cell phone free (buy 1 get 1 free)','Buy 1 cell phone and get another one for free. Up to two cell phones can be given free per order with this rule.',NULL,NULL,0,1,'a:6:{s:4:\"type\";s:32:\"salesrule/rule_condition_combine\";s:9:\"attribute\";N;s:8:\"operator\";N;s:5:\"value\";s:1:\"1\";s:18:\"is_value_processed\";N;s:10:\"aggregator\";s:3:\"all\";}','a:7:{s:4:\"type\";s:40:\"salesrule/rule_condition_product_combine\";s:9:\"attribute\";N;s:8:\"operator\";N;s:5:\"value\";s:1:\"1\";s:18:\"is_value_processed\";N;s:10:\"aggregator\";s:3:\"all\";s:10:\"conditions\";a:1:{i:0;a:5:{s:4:\"type\";s:32:\"salesrule/rule_condition_product\";s:9:\"attribute\";s:12:\"category_ids\";s:8:\"operator\";s:2:\"{}\";s:5:\"value\";s:1:\"8\";s:18:\"is_value_processed\";b:0;}}}',0,1,NULL,0,'ewfg_same',1.0000,2.0000,1,0,0,0,1,1,0,0,'','',1,'','','all_product');
		INSERT INTO `salesrule` (`rule_id`, `name`, `description`, `from_date`, `to_date`, `uses_per_customer`, `is_active`, `conditions_serialized`, `actions_serialized`, `stop_rules_processing`, `is_advanced`, `product_ids`, `sort_order`, `simple_action`, `discount_amount`, `discount_qty`, `discount_step`, `simple_free_shipping`, `apply_to_shipping`, `times_used`, `is_rss`, `coupon_type`, `use_auto_generation`, `uses_per_coupon`, `extendware_product_skus`, `extendware_category_ids`, `extendware_max_applications`, `extendware_stop_trigger_exceptions`, `extendware_stop_exceptions`, `extendware_product_add_type`) VALUES (102,'Add any 5 products to cart and receive gift','Add 5 products to the cart and a free t-shirt will be given. You may select the t-shirt of your choice.',NULL,NULL,0,1,'a:6:{s:4:\"type\";s:32:\"salesrule/rule_condition_combine\";s:9:\"attribute\";N;s:8:\"operator\";N;s:5:\"value\";s:1:\"1\";s:18:\"is_value_processed\";N;s:10:\"aggregator\";s:3:\"all\";}','a:6:{s:4:\"type\";s:40:\"salesrule/rule_condition_product_combine\";s:9:\"attribute\";N;s:8:\"operator\";N;s:5:\"value\";s:1:\"1\";s:18:\"is_value_processed\";N;s:10:\"aggregator\";s:3:\"all\";}',0,1,NULL,0,'ewfg_always',1.0000,1.0000,5,0,0,0,1,1,0,0,'','4',1,'','','any_category');
					
		INSERT INTO `salesrule_website` (`rule_id`, `website_id`) VALUES (100,1);
		INSERT INTO `salesrule_website` (`rule_id`, `website_id`) VALUES (101,1);
		INSERT INTO `salesrule_website` (`rule_id`, `website_id`) VALUES (102,1);
			
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (100,0);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (100,1);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (100,2);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (100,3);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (100,4);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (101,0);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (101,1);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (101,2);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (101,3);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (101,4);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (102,0);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (102,1);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (102,2);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (102,3);
		INSERT INTO `salesrule_customer_group` (`rule_id`, `customer_group_id`) VALUES (102,4);
	";
}

$command = @preg_replace('/(EXISTS\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);
$command = @preg_replace('/(ON\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);
$command = @preg_replace('/(REFERENCES\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);
$command = @preg_replace('/(TABLE\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);
$command = @preg_replace('/(INTO\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);
$command = @preg_replace('/(FROM\s+`)([a-z0-9\_]+?)(`)/ie', '"\\1" . $this->getTable("\\2") . "\\3"', $command);

if ($command) $installer->run($command);
$installer->endSetup(); 