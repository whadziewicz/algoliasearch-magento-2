<?php

namespace Algolia\AlgoliaSearch\Model\Indexer;

use Algolia\AlgoliaSearch\Model\Indexer\Category as CategoryIndexer;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResourceModel;
use Magento\Framework\Indexer\IndexerRegistry;

class CategoryObserver
{
    private $indexer;

    public function __construct(IndexerRegistry $indexerRegistry)
    {
        $this->indexer = $indexerRegistry->get('algolia_categories');
    }

    public function afterSave(
        CategoryResourceModel $categoryResource,
        $result,
        CategoryModel $category
    ) {
        \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->info('ALGOLIA: category afterSave triggered');
        if (!$this->indexer->isScheduled()) {
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->info('ALGOLIA: indexer is not in "On schedule" mode, proceeding with indexing');

            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->info('ALGOLIA: reindexing category id "' . $category->getId() .'"');

            /** @var Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
            $productCollection = $category->getProductCollection();
            CategoryIndexer::$affectedProductIds = (array) $productCollection->getColumnValues('entity_id');
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)->info('ALGOLIA: affected product ids by category save: ' . implode(', ', CategoryIndexer::$affectedProductIds));

            $this->indexer->reindexRow($category->getId());
        }
    }

    public function beforeDelete(
        CategoryResourceModel $categoryResource,
        CategoryModel $category
    ) {
        if (!$this->indexer->isScheduled()) {
            /* we are using products position because getProductCollection() does use correct store */
            $productCollection = $category->getProductsPosition();
            CategoryIndexer::$affectedProductIds = array_keys($productCollection);

            $this->indexer->reindexRow($category->getId());
        }
    }
}
