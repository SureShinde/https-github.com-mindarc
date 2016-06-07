<?php

/**
 * Tb Extratabs tabs
 *
 * @category   Tb
 * @package    Tb_Extratabs
 * @author     Muhammad Rehan Mobin <m.rehan.mobin@gmail.com>
 */
class Tb_Extratabs_Block_Product_View_Tabs extends Mage_Core_Block_Template
{
    protected $_tabs = array();


    /**
     * Add tab to the container
     *
     * @param string $title
     * @param string $block
     * @param string $template
     */
    function addTab($alias, $title, $block, $template)
    {

        if (!$title || !$block || !$template) {
            return false;
        }

        $this->_tabs[] = array(
            'alias' => $alias,
            'title' => $title
        );

        $this->setChild($alias,
            $this->getLayout()->createBlock($block, $alias)
                ->setTemplate($template)
            );
    }

    /**
     * Add cms block tab to the container
     *
     * @param string $title
     * @param string $block
     * @param string $template
     */
    function addCmsTab($alias, $title, $block, $id)
    {

        if (!$title || !$block || !$id) {
            return false;
        }

        $this->_tabs[] = array(
            'alias' => $alias,
            'title' => $title
        );

        $this->setChild($alias,
            $this->getLayout()->createBlock('cms/block')->setBlockId($id)
        );
    }

    function getTabs()
    {
        if(Mage::helper('extratabs')->descriptionTab()){
            $this->addTab('description',
                $this->__('Description'),
                'catalog/product_view_description',
                'extratabs/product/view/description.phtml');
        }
        if(Mage::helper('extratabs')->additionalTab()){
            $this->addTab('additional',
                $this->__('Additional Information'),
                'catalog/product_view_attributes',
                'extratabs/product/view/attributes.phtml');
        }

        // add product attribute tabs selected by admin

        // tab1
        if($data = Mage::helper('extratabs')->attributeTab1()){
            $this->addTab('att1_'.$data['code'],
                $data['title'],
                'catalog/product_view',
                'extratabs/product/view/attributetab1.phtml'
            );
        }

        // tab2
        if($data = Mage::helper('extratabs')->attributeTab2()){
            $this->addTab('att2_'.$data['code'],
                $data['title'],
                'catalog/product_view',
                'extratabs/product/view/attributetab2.phtml'
            );
        }

        // tab3
        if($data = Mage::helper('extratabs')->attributeTab3()){
            $this->addTab('att3_'.$data['code'],
                $data['title'],
                'catalog/product_view',
                'extratabs/product/view/attributetab3.phtml'
            );
        }

        // add cms static block tabs
        if(Mage::helper('extratabs')->staticBlockTab()){
            $_collection = Mage::getModel('cms/block')
                ->getCollection()
                ->addStoreFilter(Mage::app()
                    ->getStore()->getId())
                ->addFieldToFilter('is_active', 1)
                ->addFieldToFilter('identifier',
                    array('like' => 'extratab_%'))->load();
            if (count($_collection)){
                foreach($_collection as $_block){
                    $this->addCmsTab('et_tab-'.$_block->getId(),
                        $this->__($_block->getTitle()),
                        'cms/block',
                        $_block->getId());
                }
            }
        }

        // add review tab
        if(Mage::helper('extratabs')->reviewTab()){
            $this->addTab('tab_review',
                $this->__('Review(s)'),
                'review/product_view_list',
                'extratabs/product/view/tab_review.phtml');
        }
        // add upSells tab
        if(Mage::helper('extratabs')->upsellTab()){
            $this->addTab('upsell_products',
                $this->__('We Also Recommend'),
                'catalog/product_list_upsell',
                'catalog/product/list/upsell.phtml');
        }
        // add related products tab
        if(Mage::helper('extratabs')->relatedTab()){
            $this->addTab('related',
                $this->__('Related Products'),
                'catalog/product_list_related',
                'extratabs/product/list/related.phtml');
        }
        // add product tags tab
        if(Mage::helper('extratabs')->tagsTab()){
            $this->addTab('tags',
                $this->__('Product Tags'),
                'tag/product_list',
                'tag/list.phtml');
        }

        return $this->_tabs;
    }
}
