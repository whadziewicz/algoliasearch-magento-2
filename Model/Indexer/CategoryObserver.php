<?php

namespace Algolia\AlgoliaSearch\Model\Indexer;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\AbstractModel;

class CategoryObserver
{
    private $indexer;

    public function __construct(IndexerRegistry $indexerRegistry)
    {
        $this->indexer = $indexerRegistry->get('algolia_categories');
    }

    public function aroundSave(
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        \Closure $proceed,
        AbstractModel $category
    ) {
        $categoryResource->addCommitCallback(function () use ($category) {
            if (!$this->indexer->isScheduled()) {
                Category::$affectedProductIds = (array) $category->getAffectedProductIds();
                $this->indexer->reindexRow($category->getId());
            }
        });

        return $proceed($category);
    }

    public function aroundDelete(
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        \Closure $proceed,
        AbstractModel $category
    ) {
        $categoryResource->addCommitCallback(function () use ($category) {
            if (!$this->indexer->isScheduled()) {
                Category::$affectedProductIds = (array) $category->getAffectedProductIds();
                $this->indexer->reindexRow($category->getId());
            }
        });

        return $proceed($category);
    }

    public function aroundUpdateAttributes(
        \Magento\Catalog\Model\Category\Action $subject,
        \Closure $closure,
        array $categoryIds,
        array $attrData,
        $storeId
    ) {
        $result = $closure($categoryIds, $attrData, $storeId);
        if (!$this->indexer->isScheduled()) {
            $this->indexer->reindexList(array_unique($categoryIds));
        }

        return $result;
    }

    public function aroundUpdateWebsites(
        \Magento\Catalog\Model\Category\Action $subject,
        \Closure $closure,
        array $categoryIds,
        array $websiteIds,
        $type
    ) {
        $result = $closure($categoryIds, $websiteIds, $type);
        if (!$this->indexer->isScheduled()) {
            $this->indexer->reindexList(array_unique($categoryIds));
        }

        return $result;
    }
}
