<?php
    $installer = $this;

    $installer->startSetup();

    $installer->addAttribute('catalog_product', 'auspost_package_type', array(
            'label'         => 'Australia Post Package Type',
            'group'         => 'General',
            'input'         => 'select',
            'type'          => 'int',
            'visible'       => 1,
            'required'      => 0,
            'user_defined'  => 1,
            'source'        => 'eav/entity_attribute_source_table',
            'backend'    => 'eav/entity_attribute_backend_array',
            'option'     => array (
                'values' => array(
                    0 => 'Parcel',
                    1 => 'Letter'

                )),
            'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL

        ));

