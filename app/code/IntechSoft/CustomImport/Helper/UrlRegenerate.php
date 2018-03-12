<?php

namespace IntechSoft\CustomImport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\Helper\Context;

class UrlRegenerate extends AbstractHelper
{
    /**
     * @var ProductUrlRewriteGenerator
     */
    protected $productUrlRewriteGenerator;

    /**
     * @var UrlPersistInterface
     */
    protected $urlPersist;

    protected $storeManager;
    protected $productStatus;
    protected $logger;

    /**
     * @var Collection
     */
    protected $collection;

    public function __construct(
        Collection $collection,
        ProductUrlRewriteGenerator $productUrlRewriteGenerator,
        UrlPersistInterface $urlPersist,
        StoreManagerInterface $storeManager,
        Status $productStatus,
        Context $context
    ) {
        $this->collection = $collection;
        $this->productUrlRewriteGenerator = $productUrlRewriteGenerator;
        $this->urlPersist = $urlPersist;
        $this->storeManager = $storeManager;
        $this->productStatus = $productStatus;
        $this->logger =  $context->getLogger();
        parent::__construct($context);
    }

    public function regenerateUrl()
    {
        $this->logger->info('Start: regenerate URL');

        if ($this->storeManager->isSingleStoreMode()) {
            $stores = [$this->storeManager->getStore(0)];
        } else {
            $stores = $this->storeManager->getStores();
        }

        foreach ($stores as $store) {
            $store_id = $store->getId();
            $this->collection->addStoreFilter($store_id)->setStoreId($store_id);
            $this->collection->addAttributeToSelect(['url_path', 'url_key']);
            $this->collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
            $list = $this->collection->load();

            if(count($list)) {
                foreach ($list as $product) {
                    $product->setStoreId($store_id);
                    $this->urlPersist->deleteByData([
                        UrlRewrite::ENTITY_ID => $product->getId(),
                        UrlRewrite::ENTITY_TYPE => ProductUrlRewriteGenerator::ENTITY_TYPE,
                        UrlRewrite::REDIRECT_TYPE => 0,
                        UrlRewrite::STORE_ID => $store_id
                    ]);
                    try {
                        $this->urlPersist->replace(
                            $this->productUrlRewriteGenerator->generate($product)
                        );
                    } catch (\Exception $e) {
                        $this->logger->error('Duplicated url for ' . $product->getId() . ' store id ' . $store_id);
                        $this->logger->error('Original Error: ' . $e->getMessage());
                    }
                }
            }
        }

        $this->logger->info('Finished: regenerate URL');
    }

}