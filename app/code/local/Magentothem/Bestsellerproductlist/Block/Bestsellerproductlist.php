<?php
class Magentothem_Bestsellerproductlist_Block_Bestsellerproductlist extends Mage_Catalog_Block_Product_List
{
    public function __construct()
    {
        $this->setStoreId(Mage::app()->getStore()->getId());
    }

    public function getColumnCount()
    {
        return $this->getConfig('items');
    }

    public function getConfig($att) 
    {
        $config = Mage::getStoreConfig('bestsellerproductlist');
        if (isset($config['bestsellerproduct_config'][$att]) ) {
            $value = $config['bestsellerproduct_config'][$att];
            return $value;
        }
    }

    public function cut_string3($string,$number){
        if(strlen($string) <= $number) {
            return $string;
        }
        else {  
            if(strpos($string," ",$number) > $number){
                $new_space = strpos($string," ",$number);
                $new_string = substr($string,0,$new_space)."..";
                return $new_string;
            }
            $new_string = substr($string,0,$number)."..";
            return $new_string;
        }
    }

    protected function _getProductCollection()
    { 
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $table_prefixx = Mage::getConfig()->getTablePrefix(); 
        $res = $write->query("select max(qo) as des_qty,`product_id`,`parent_item_id` FROM (select sum(`qty_ordered`) AS qo,`product_id`,created_at,store_id,`parent_item_id` from ".$table_prefixx."sales_flat_order_item Group By `product_id`) AS t1 where store_id = ".$this->getStoreId()." AND parent_item_id is null Group By `product_id` order By des_qty desc"); 
        
        while ($row = $res->fetch()) 
        { 
            $maxQty[]=$row['product_id'];
            
        }
        $attributes = Mage::getSingleton('catalog/config')->getProductAttributes();
        $collection = Mage::getModel('catalog/product')
                        ->getCollection()
                        ->addAttributeToSelect($attributes)
                        ->addAttributeToFilter('entity_id', array('in' => $maxQty))
                        ->setOrder($this->getRequest()->getParam('order'), $this->getRequest()->getParam('dir')); 

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        $limit = (int)$this->getRequest()->getParam('limit') ? (int)$this->getRequest()->getParam('limit') : (int)$this->getToolbarBlock()->getDefaultPerPageValue();
        $collection->setPageSize($limit)->setCurPage($this->getRequest()->getParam('p'));
        Mage::getModel('review/review')->appendSummary($collection);
        //$collection->load();
        return $collection;
    }

}