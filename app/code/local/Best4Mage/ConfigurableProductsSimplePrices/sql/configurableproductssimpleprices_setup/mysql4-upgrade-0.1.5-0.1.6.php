<?php

$installer = $this;

$installer->startSetup();

$entityTypeId = $installer->getEntityTypeId('catalog_product');

foreach(array('cpsp_show_maxregular' => 'Show Maximum Price As Regulare Price') as $attrCode => $attrLabel) {
	$data= array (
		'attribute_set' =>  'Default',
		'group' => 'Simple Price Settings',
		'label'    => $attrLabel,
		'visible'     => true,
		'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
		'type'     => 'int', // multiselect uses comma-sep storage
		'input'    => 'boolean',
		'system'   => true,
		'required' => false,
		'user_defined' => 1, //defaults to false; if true, define a group
		'apply_to' => array('configurable'),
	);

	$installer->addAttribute('catalog_product', $attrCode, $data);
	
	if($attributeId = $installer->getAttributeId('catalog_product', $attrCode))
	{
		$installer->updateTableRow('catalog/eav_attribute',
			'attribute_id', $attributeId,
			'apply_to', 'configurable'
		);
	}
}
$installer->endSetup();