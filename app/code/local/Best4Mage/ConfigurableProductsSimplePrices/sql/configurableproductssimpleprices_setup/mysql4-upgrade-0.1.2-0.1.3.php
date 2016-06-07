<?php

$installer = $this;

$installer->startSetup();

$data= array (
	'attribute_set' =>  'Default',
	'group' => 'General',
	'label'    => 'Expand with show prices',
	'visible'     => true,
	'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'type'     => 'int', // multiselect uses comma-sep storage
	'input'    => 'boolean',
	'system'   => true,
	'required' => false,
	'user_defined' => 1, //defaults to false; if true, define a group
	'apply_to' => array('configurable'),
);

$installer->addAttribute('catalog_product','cpsp_expand_prices',$data);

if($attributeId = $installer->getAttributeId('catalog_product', 'cpsp_expand_prices'))
{
	$installer->updateTableRow('catalog/eav_attribute',
		'attribute_id', $attributeId,
		'apply_to', 'configurable'
	);
}

$installer->endSetup();