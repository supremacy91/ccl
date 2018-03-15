<?php
namespace IntechSoft\CustomImport\Plugin;

class SaveProductEntityPlugin{

    protected $_stockRegistry;
    protected $_om;
    protected $_resource;
    protected $_connection;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $om,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_resource = $resource;
        $this->_stockRegistry = $stockRegistry;
        $this->_om = $om;
    }

    public function afterSaveProductEntity(\Magento\CatalogImportExport\Model\Import\Product $subject, $result){
        $registryImportFlag = $this->_om->get('\Magento\Framework\Registry');
        if ($result instanceof \Magento\CatalogImportExport\Model\Import\Product) {
            $registryImportFlag->register('importSuccessFlag', 1, true);
        }
        return $result;

    }

    public function beforeSaveProductEntity(\Magento\CatalogImportExport\Model\Import\Product $subject, $params){
        $registryFirstSaveImportFlag = $this->_om->get('\Magento\Framework\Registry');
        if ($registryFirstSaveImportFlag->registry('isFirstSaveImportFlag')) {
            return [$params];
        }
        $registryFirstSaveImportFlag->register('isFirstSaveImportFlag', 1, false);
        $this->_connection = $this->_resource->getConnection();
        $table=$this->_resource->getTableName('cataloginventory_stock_item');
        $productCollection = $this->_om->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
        foreach ($productCollection as $product) {


            $id = $product->getEntityId();
            /*$this->_connection->update(
                $table,
                ["qty"  => new \Zend_Db_Expr('0')],
                ["item_id = $id"]

            );*/
            $query = "update " . $table . " set qty = 0 where product_id = " . $id;
            $this->_connection->query($query);
            //->where(
            //   'item_id = ?', $product->getEntityId());

           /* $product->setStockData(array(
                    'is_in_stock' => 1, //Stock Availability
                    'qty' => 0 //qty
                )
            );
            $product->save();*/



            //$product->setQuantityAndStockStatus(['qty' => 0, 'is_in_stock' => 0]);
           /* $productStockData = $this->_stockRegistry->getStockItem($product->getId());
            $productStockData->setData('is_in_stock', 0);
            $productStockData->setData('qty', 0);
            $productStockData->setData('manage_stock', 1);
            $productStockData->save();*/

          //  $stockItem=$this->_stockRegistry->getStockItem($item['product_id']);
            /*$stockItem=$this->_stockRegistry->getStockItem($item['product_id']); // load stock of that product
            $stockItem->setData('is_in_stock',$stockData['is_in_stock']); //set updated data as your requirementx
            $stockItem->setData('qty',$stockData['qty']); //set updated quantity
            $stockItem->setData('manage_stock',$stockData['manage_stock']);
            $stockItem->setData('use_config_notify_stock_qty',1);
            $stockItem->save(); //save stock of item
            $product->save(); //  also save product*/
        }

    }


}
