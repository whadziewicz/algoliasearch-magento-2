<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Algolia\AlgoliaSearch\Model\Indexer;

use Algolia\AlgoliaSearch\Helper\AlgoliaHelper;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext as FulltextResource;
use \Magento\Framework\Search\Request\Config as SearchRequestConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Indexer\SaveHandler\Batch;

class Product implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    private $storeManager;
    protected $productHelper;
    protected $algoliaHelper;
    protected $batch;
    protected $fullAction;

    public function __construct(StoreManagerInterface $storeManager,
                                ProductHelper $productHelper,
                                Data $helper,
                                Batch $batch,
                                AlgoliaHelper $algoliaHelper)
    {
        $this->fullAction = $helper;
        $this->storeManager = $storeManager;
        $this->productHelper = $productHelper;
        $this->algoliaHelper = $algoliaHelper;
        $this->batch = $batch;
    }

    public function execute($ids)
    {
        $storeIds = array_keys($this->storeManager->getStores());

        foreach ($storeIds as $storeId) {
            if ($ids !== null) {
                $indexName = $this->productHelper->getIndexName($storeId);
                $this->algoliaHelper->deleteObjects($ids, $indexName);
            }
            else {
                $this->fullAction->saveConfigurationToAlgolia($storeId);
            }

            $this->fullAction->rebuildStoreProductIndex($storeId, $ids);
        }
    }

    public function executeFull()
    {
        $this->execute(null);
    }

    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    public function executeRow($id)
    {
        $this->execute([$id]);
    }
}