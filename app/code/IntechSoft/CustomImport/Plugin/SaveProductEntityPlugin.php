<?php
namespace IntechSoft\CustomImport\Plugin;

use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;

/**
 * Class SaveProductEntityPlugin
 *
 * @package IntechSoft\CustomImport\Plugin
 */
class SaveProductEntityPlugin
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var \Zend\Log\Logger
     */
    private $logger;

    /**
     * SaveProductEntityPlugin constructor.
     *
     * @param Registry           $registry
     * @param ResourceConnection $resource
     */
    public function __construct(
        Registry $registry,
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
        $this->registry = $registry;
    }

    /**
     * @param ImportProduct $subject
     * @param               $result
     * @return mixed
     */
    public function afterSaveProductEntity(ImportProduct $subject, $result)
    {
        if ($result instanceof ImportProduct) {
            $this->registry->register('importSuccessFlag', 1, true);
        }

        return $result;
    }

    /**
     * @param ImportProduct $subject
     * @param array         $params
     *
     * @deprecated
     */
//    public function beforeSaveProductEntity(ImportProduct $subject, array $params)
//    {
//        if (!$this->registry->registry('isFirstSaveImportFlag')) {
//            $this->registry->register('isFirstSaveImportFlag', 1, false);
//
//            $connection = $this->resource->getConnection();
//
//            $select = $connection->select()
//                ->from($this->resource->getTableName('catalog_product_entity'), 'entity_id')
//                ->where('type_id IN(?)', ['configurable']);
//
//            $selectQuery = $select->assemble();
//
//            $values = ['qty' => 0, 'is_in_stock' => 0/*, 'stock_status_changed_auto' => 1*/];
//            $where = sprintf('product_id IN (%1$s)', $selectQuery);
//            $connection->update($this->resource->getTableName('cataloginventory_stock_item'), $values, $where);
//
////            $values = ['qty' => 0, 'stock_status' => 0];
////            $where = sprintf('product_id IN (%1$s)', $selectQuery);
////            $connection->update($this->resource->getTableName('cataloginventory_stock_status'), $values, $where);
//        }
//    }
}
