<?php

$installer = $this;

$installer->startSetup();

$data= array (
	'attribute_set' =>  'Default',
	'group' => 'General',
	'label'    => 'Configurable Products Simple Prices Enable',
	'visible'     => true,
	'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'type'     => 'int', // multiselect uses comma-sep storage
	'input'    => 'boolean',
	'system'   => true,
	'required' => false,
	'user_defined' => 1, //defaults to false; if true, define a group
	'apply_to' => array('configurable'),
);

$installer->addAttribute('catalog_product','cpsp_enable',$data);

$installer->endSetup();