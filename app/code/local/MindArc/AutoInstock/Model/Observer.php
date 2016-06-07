<?php
class MindArc_AutoInstock_Model_Observer {

    public function updateStockAvailability(Varien_Event_Observer $observer) {
        $product = $observer->getProduct();
        $stockData = $product->getStockData();

        if ( $product && $stockData['qty'] ) {
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getEntityId()); // Load the stock for this product
            $stock->setData('is_in_stock', 1); // Set the Product to InStock
            $stock->save(); // Save
            Mage::log('increament stock');
        }
    }

//    public function updateCronAction() {
//        $collection = Mage::getResourceModel('cataloginventory/stock_item_collection');
//        $outQty = Mage::getStoreConfig('cataloginventory/item/options_min_qty');
//        $collection->addFieldToFilter('qty', array('gt' => $outQty));
//        $collection->addFieldToFilter('is_in_stock', 0);
//
//        foreach ($collection as $item) {
//            $item->setData('is_in_stock', 1);
//        }
//        $collection->save();
//
//        $bundleCollection = Mage::getModel('catalog/product')->getCollection()
//            ->addAttributeToSelect('*')
//            ->addAttributeToSelect('type')
//            ->addAttributeToFilter('type_id', 'bundle')
//            ->joinField('is_in_stock', 'cataloginventory/stock_item', 'is_in_stock', 'product_id=entity_id', '{{table}}.stock_id=1', 'left')
//            ->addAttributeToFilter('is_in_stock', array('eq' => 0));
//
//        foreach ($bundleCollection as $bundle) {
//            $selectionCollection = $bundle->getTypeInstance(true)->getSelectionsCollection($bundle->getTypeInstance(true)->getOptionsIds($bundle), $bundle);
//
//            $allInStock = true;
//            foreach ($selectionCollection as $option) {
//                $stockItem = $option->getStockItem();
//                if ($stockItem->getQty() <= 0 || $stockItem->getIsInStock() <= $outQty) {
//                    $allInStock = false;
//                }
//            }
//
//            if ($allInStock) {
//                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($bundle);
//                $stockItem->setData('is_in_stock', 1);
//                $stockItem->save();
//            }
//        }
//    }
}