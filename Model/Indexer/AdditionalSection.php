<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Algolia\AlgoliaSearch\Model\Indexer;

use Algolia\AlgoliaSearch\Helper\AlgoliaHelper;
use Algolia\AlgoliaSearch\Helper\Data;
use Algolia\AlgoliaSearch\Helper\Entity\PageHelper;
use Algolia\AlgoliaSearch\Helper\Entity\SuggestionHelper;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext as FulltextResource;
use \Magento\Framework\Search\Request\Config as SearchRequestConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Indexer\SaveHandler\Batch;

class AdditionalSection implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    private $storeManager;
    protected $fullAction;

    public function __construct(StoreManagerInterface $storeManager, Data $helper)
    {
        $this->fullAction = $helper;
        $this->storeManager = $storeManager;
    }

    public function execute($ids)
    {
    }

    public function executeFull()
    {
        $storeIds = array_keys($this->storeManager->getStores());

        foreach ($storeIds as $storeId) {
            $this->fullAction->rebuildStoreAdditionalSectionsIndex($storeId);
        }
    }

    public function executeList(array $ids)
    {
    }

    public function executeRow($id)
    {
    }
}