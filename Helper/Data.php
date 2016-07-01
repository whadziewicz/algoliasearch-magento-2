<?php

namespace Algolia\AlgoliaSearch\Helper;

use Algolia\AlgoliaSearch\Helper\Entity\AdditionalSectionHelper;
use Algolia\AlgoliaSearch\Helper\Entity\CategoryHelper;
use Algolia\AlgoliaSearch\Helper\Entity\PageHelper;
use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Algolia\AlgoliaSearch\Helper\Entity\SuggestionHelper;
use AlgoliaSearch\Version;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\App\Emulation;

class Data
{
    const COLLECTION_PAGE_SIZE = 100;

    protected $algoliaHelper;

    protected $pageHelper;
    protected $categoryHelper;
    protected $productHelper;
    protected $additionalSectionHelper;

    protected $logger;
    protected $configHelper;
    protected $emulation;
    protected $resource;

    public function __construct(AlgoliaHelper $algoliaHelper,
                                ConfigHelper $configHelper,
                                ProductHelper $producthelper,
                                CategoryHelper $categoryHelper,
                                PageHelper $pageHelper,
                                SuggestionHelper $suggestionHelper,
                                AdditionalSectionHelper $additionalSectionHelper,
                                Emulation $emulation,
                                Logger $logger,
                                ResourceConnection $resource)
    {
        Version::$custom_value = ' Magento 2 (dev)';

        $this->algoliaHelper = $algoliaHelper;

        $this->pageHelper = $pageHelper;
        $this->categoryHelper = $categoryHelper;
        $this->productHelper = $producthelper;
        $this->suggestionHelper = $suggestionHelper;
        $this->additionalSectionHelper = $additionalSectionHelper;

        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->emulation = $emulation;
        $this->resource = $resource;
    }

    public function deleteProductsStoreIndices($storeId = null)
    {
        if ($storeId !== null) {
            if ($this->configHelper->isEnabledBackEnd($storeId) === false) {
                $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

                return;
            }
        }

        $this->algoliaHelper->deleteIndex($this->productHelper->getIndexName($storeId));
    }

    public function deleteCategoriesStoreIndices($storeId = null)
    {
        if ($storeId !== null) {
            if ($this->configHelper->isEnabledBackEnd($storeId) === false) {
                $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

                return;
            }
        }

        $this->algoliaHelper->deleteIndex($this->categoryHelper->getIndexName($storeId));
    }

    public function saveConfigurationToAlgolia($storeId)
    {
        $this->algoliaHelper->resetCredentialsFromConfig();

        if (!($this->configHelper->getApplicationID() && $this->configHelper->getAPIKey())) {
            return;
        }

        if ($this->configHelper->isEnabledBackEnd($storeId) === false) {
            $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

            return;
        }

        $this->algoliaHelper->setSettings($this->categoryHelper->getIndexName($storeId), $this->categoryHelper->getIndexSettings($storeId));

        $this->algoliaHelper->setSettings($this->pageHelper->getIndexName($storeId), $this->pageHelper->getIndexSettings($storeId));
        $this->algoliaHelper->setSettings($this->suggestionHelper->getIndexName($storeId), $this->suggestionHelper->getIndexSettings($storeId));

        foreach ($this->configHelper->getAutocompleteSections() as $section) {
            if ($section['name'] === 'products' || $section['name'] === 'categories' || $section['name'] === 'pages' || $section['name'] === 'suggestions') {
                continue;
            }

            $this->algoliaHelper->setSettings($this->additionalSectionHelper->getIndexName($storeId).'_'.$section['name'], $this->additionalSectionHelper->getIndexSettings($storeId));
        }

        $this->productHelper->setSettings($storeId);
    }

    public function getSearchResult($query, $storeId)
    {
        $resultsLimit = $this->configHelper->getResultsLimit($storeId);

        $index_name = $this->productHelper->getIndexName($storeId);

        $number_of_results = 1000;

        if ($this->configHelper->isInstantEnabled()) {
            $number_of_results = min($this->configHelper->getNumberOfProductResults($storeId), 1000);
        }

        $answer = $this->algoliaHelper->query($index_name, $query, [
            'hitsPerPage'            => $number_of_results, // retrieve all the hits (hard limit is 1000)
            'attributesToRetrieve'   => 'objectID',
            'attributesToHighlight'  => '',
            'attributesToSnippet'    => '',
            'numericFilters'         => 'visibility_search=1',
            'removeWordsIfNoResults' => $this->configHelper->getRemoveWordsIfNoResult($storeId),
            'analyticsTags'          => 'backend-search'
        ]);

        $data = [];

        foreach ($answer['hits'] as $i => $hit) {
            $productId = $hit['objectID'];

            if ($productId) {
                $data[$productId] = [
                    'entity_id' => $productId,
                    'score'     => $resultsLimit - $i
                ];
            }
        }

        return $data;
    }

    public function removeProducts($ids, $store_id = null)
    {
        $store_ids = Algolia_Algoliasearch_Helper_Entity_Helper::getStores($store_id);

        foreach ($store_ids as $store_id) {
            if ($this->configHelper->isEnabledBackEnd($store_id) === false) {
                $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($store_id));
                continue;
            }

            $index_name = $this->productHelper->getIndexName($store_id);

            $this->algoliaHelper->deleteObjects($ids, $index_name);
        }
    }

    public function removeCategories($ids, $store_id = null)
    {
        $store_ids = Algolia_Algoliasearch_Helper_Entity_Helper::getStores($store_id);

        foreach ($store_ids as $store_id) {
            if ($this->configHelper->isEnabledBackEnd($store_id) === false) {
                $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($store_id));
                continue;
            }

            $index_name = $this->categoryHelper->getIndexName($store_id);

            $this->algoliaHelper->deleteObjects($ids, $index_name);
        }
    }

    public function rebuildStoreAdditionalSectionsIndex($storeId)
    {
        if ($this->configHelper->isEnabledBackEnd($storeId) === false) {
            $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

            return;
        }

        $additionnal_sections = $this->configHelper->getAutocompleteSections();

        foreach ($additionnal_sections as $section) {
            if ($section['name'] === 'products' || $section['name'] === 'categories' || $section['name'] === 'pages' || $section['name'] === 'suggestions') {
                continue;
            }

            $index_name = $this->additionalSectionHelper->getIndexName($storeId).'_'.$section['name'];

            $attribute_values = $this->additionalSectionHelper->getAttributeValues($storeId, $section);

            foreach (array_chunk($attribute_values, 100) as $chunk) {
                $this->algoliaHelper->addObjects($chunk, $index_name.'_tmp');
            }

            $this->algoliaHelper->moveIndex($index_name.'_tmp', $index_name);

            $this->algoliaHelper->setSettings($index_name, $this->additionalSectionHelper->getIndexSettings($storeId));
        }
    }

    public function rebuildStorePageIndex($storeId)
    {
        if ($this->configHelper->isEnabledBackEnd($storeId) === false) {
            $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

            return;
        }

        $this->startEmulation($storeId);

        $index_name = $this->pageHelper->getIndexName($storeId);

        $pages = $this->pageHelper->getPages($storeId);

        foreach (array_chunk($pages, 100) as $chunk) {
            $this->algoliaHelper->addObjects($chunk, $index_name.'_tmp');
        }

        $this->algoliaHelper->moveIndex($index_name.'_tmp', $index_name);

        $this->algoliaHelper->setSettings($index_name, $this->pageHelper->getIndexSettings($storeId));

        $this->stopEmulation();
    }

    public function rebuildStoreCategoryIndex($storeId, $categoryIds = null)
    {
        if ($this->configHelper->isEnabledBackEnd($storeId) === false) {
            $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

            return;
        }

        $this->startEmulation($storeId);

        try {
            $collection = $this->categoryHelper->getCategoryCollectionQuery($storeId, $categoryIds);

            $size = $collection->getSize();

            if ($size > 0) {
                $pages = ceil($size / $this->configHelper->getNumberOfElementByPage());
                $collection->clear();
                $page = 1;

                while ($page <= $pages) {
                    $this->rebuildStoreCategoryIndexPage($storeId, $collection, $page, $this->configHelper->getNumberOfElementByPage());

                    $page++;
                }

                unset($indexData);
            }
        } catch (Exception $e) {
            $this->stopEmulation();
            throw $e;
        }

        $this->stopEmulation();
    }

    public function rebuildStoreSuggestionIndex($storeId)
    {
        if ($this->configHelper->isEnabledBackEnd($storeId) === false) {
            $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

            return;
        }

        $collection = $this->suggestionHelper->getSuggestionCollectionQuery($storeId);

        $size = $collection->getSize();

        if ($size > 0) {
            $pages = ceil($size / $this->configHelper->getNumberOfElementByPage());
            $collection->clear();
            $page = 1;

            while ($page <= $pages) {
                $this->rebuildStoreSuggestionIndexPage($storeId, $collection, $page, $this->configHelper->getNumberOfElementByPage());

                $page++;
            }

            unset($indexData);
        }

        $this->moveStoreSuggestionIndex($storeId);
    }

    public function moveStoreSuggestionIndex($storeId)
    {
        if ($this->configHelper->isEnabledBackEnd($storeId) === false) {
            $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

            return;
        }

        $this->algoliaHelper->moveIndex($this->suggestionHelper->getIndexName($storeId).'_tmp', $this->suggestionHelper->getIndexName($storeId));
    }

    public function rebuildStoreProductIndex($storeId, $productIds)
    {
        if ($this->configHelper->isEnabledBackEnd($storeId) === false) {
            $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

            return;
        }

        $this->startEmulation($storeId);

        $this->logger->start('Indexing');
        try {
            $this->logger->start('ok');

            $collection = $this->productHelper->getProductCollectionQuery($storeId, $productIds);

            $size = $collection->getSize();

            $this->logger->log('Store '.$this->logger->getStoreName($storeId).' collection size : '.$size);

            if ($size > 0) {
                $pages = ceil($size / $this->configHelper->getNumberOfElementByPage());
                $collection->clear();
                $page = 1;

                while ($page <= $pages) {
                    $this->rebuildStoreProductIndexPage($storeId, $collection, $page, $this->configHelper->getNumberOfElementByPage());

                    $page++;
                }
            }
        } catch (Exception $e) {
            $this->stopEmulation();
            throw $e;
        }
        $this->logger->stop('Indexing');

        $this->stopEmulation();
    }

    public function rebuildStoreSuggestionIndexPage($storeId, $collectionDefault, $page, $pageSize)
    {
        if ($this->configHelper->isEnabledBackEnd($storeId) === false) {
            $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

            return;
        }

        $collection = clone $collectionDefault;
        $collection->setCurPage($page)->setPageSize($pageSize);
        $collection->load();

        $index_name = $this->suggestionHelper->getIndexName($storeId).'_tmp';

        $indexData = [];

        /** @var $suggestion Mage_Catalog_Model_Category */
        foreach ($collection as $suggestion) {
            $suggestion->setStoreId($storeId);

            $suggestion_obj = $this->suggestionHelper->getObject($suggestion);

            if (strlen($suggestion_obj['query']) >= 3) {
                array_push($indexData, $suggestion_obj);
            }
        }

        if (count($indexData) > 0) {
            $this->algoliaHelper->addObjects($indexData, $index_name);
        }

        unset($indexData);

        $collection->walk('clearInstance');
        $collection->clear();

        unset($collection);
    }

    public function rebuildStoreCategoryIndexPage($storeId, $collectionDefault, $page, $pageSize)
    {
        if ($this->configHelper->isEnabledBackEnd($storeId) === false) {
            $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

            return;
        }

        $collection = clone $collectionDefault;
        $collection->setCurPage($page)->setPageSize($pageSize);
        $collection->load();

        $index_name = $this->categoryHelper->getIndexName($storeId);

        $indexData = [];

        /** @var $category Mage_Catalog_Model_Category */
        foreach ($collection as $category) {
            if (!$this->categoryHelper->isCategoryActive($category->getId(), $storeId)) {
                continue;
            }

            $category->setStoreId($storeId);

            $category_obj = $this->categoryHelper->getObject($category);

            if ($category_obj['product_count'] > 0) {
                array_push($indexData, $category_obj);
            }
        }

        if (count($indexData) > 0) {
            $this->algoliaHelper->addObjects($indexData, $index_name);
        }

        unset($indexData);

        $collection->walk('clearInstance');
        $collection->clear();

        unset($collection);
    }

    protected function getProductsRecords($storeId, $collection)
    {
        $indexData = [];

        $this->logger->start('CREATE RECORDS '.$this->logger->getStoreName($storeId));
        $this->logger->log(count($collection).' product records to create');
        /** @var $product Mage_Catalog_Model_Product */
        foreach ($collection as $product) {
            $product->setStoreId($storeId);

            $json = $this->productHelper->getObject($product);

            array_push($indexData, $json);
        }
        $this->logger->stop('CREATE RECORDS '.$this->logger->getStoreName($storeId));

        return $indexData;
    }

    public function rebuildStoreProductIndexPage($storeId, $collectionDefault, $page, $pageSize)
    {
        if ($this->configHelper->isEnabledBackEnd($storeId) === false) {
            $this->logger->log('INDEXING IS DISABLED FOR '.$this->logger->getStoreName($storeId));

            return;
        }

        $this->logger->start('rebuildStoreProductIndexPage '.$this->logger->getStoreName($storeId).' page '.$page.' pageSize '.$pageSize);

        $objectManager = ObjectManager::getInstance();
        /** @var \Magento\Framework\App\ResourceConnection $resource */
        $resource = $objectManager->create('\Magento\Framework\App\ResourceConnection');
        $ordersTableName = $resource->getTableName('sales_order_item');
        $superTableName = $resource->getTableName('catalog_product_super_link');
        $reviewTableName = $resource->getTableName('review_entity_summary');
        $stockTableName = $resource->getTableName('cataloginventory_stock_item');

        $additionalAttributes = $this->configHelper->getProductAdditionalAttributes($storeId);

        $collection = clone $collectionDefault;

        $collection->setCurPage($page)->setPageSize($pageSize);
        $collection->addCategoryIds();
        $collection->addUrlRewrite();

        if ($this->productHelper->isAttributeEnabled($additionalAttributes, 'stock_qty')) {
            $collection->getSelect()->columns('(SELECT MAX(qty) FROM '.$stockTableName.' AS o LEFT JOIN '.$superTableName.' AS l ON l.product_id = o.product_id WHERE o.product_id = e.entity_id OR l.parent_id = e.entity_id) as stock_qty');
        }

        if ($this->productHelper->isAttributeEnabled($additionalAttributes, 'ordered_qty')) {
            $collection->getSelect()->columns('(SELECT SUM(qty_ordered) FROM '.$ordersTableName.' AS o LEFT JOIN '.$superTableName.' AS l ON l.product_id = o.product_id WHERE o.product_id = e.entity_id OR l.parent_id = e.entity_id) as ordered_qty');
        }

        if ($this->productHelper->isAttributeEnabled($additionalAttributes, 'total_ordered')) {
            $collection->getSelect()->columns('(SELECT SUM(row_total) FROM '.$ordersTableName.' AS o LEFT JOIN '.$superTableName.' AS l ON l.product_id = o.product_id WHERE o.product_id = e.entity_id OR l.parent_id = e.entity_id) as total_ordered');
        }

        if ($this->productHelper->isAttributeEnabled($additionalAttributes, 'rating_summary')) {
            $collection->joinField('rating_summary', $reviewTableName, 'rating_summary', 'entity_pk_value=entity_id', '{{table}}.store_id='.$storeId, 'left');
        }

        $this->logger->start('LOADING '.$this->logger->getStoreName($storeId).' collection page '.$page.', pageSize '.$pageSize);

        $collection->load();

        $this->logger->log('Loaded '.count($collection).' products');
        $this->logger->stop('LOADING '.$this->logger->getStoreName($storeId).' collection page '.$page.', pageSize '.$pageSize);

        $index_name = $this->productHelper->getIndexName($storeId);

        $indexData = $this->getProductsRecords($storeId, $collection);

        $this->logger->start('SEND TO ALGOLIA');
        if (count($indexData) > 0) {
            $this->algoliaHelper->addObjects($indexData, $index_name);
        }
        $this->logger->stop('SEND TO ALGOLIA');

        unset($indexData);

        $collection->walk('clearInstance');
        $collection->clear();

        unset($collection);

        $this->logger->stop('rebuildStoreProductIndexPage '.$this->logger->getStoreName($storeId).' page '.$page.' pageSize '.$pageSize);
    }

    public function startEmulation($storeId)
    {
        $this->logger->start('START EMULATION');

        $this->emulation->startEnvironmentEmulation($storeId);

        $this->logger->stop('START EMULATION');
    }

    public function stopEmulation()
    {
        $this->logger->start('STOP EMULATION');

        $this->emulation->stopEnvironmentEmulation();

        $this->logger->stop('STOP EMULATION');
    }
}
