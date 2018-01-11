<?php

namespace Algolia\AlgoliaSearch\Helper\Entity;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Magento\Catalog\Model\Category as MagentoCategory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Algolia\AlgoliaSearch\Helper\Image;
use Magento\Catalog\Model\Category;
use Magento\Framework\DataObject;

class CategoryHelper
{
    protected static $_categoryAttributes;
    protected static $_rootCategoryId = -1;

    private $eventManager;

    private $objectManager;

    private $storeManager;

    private $resourceConnection;

    private $eavConfig;

    private $configHelper;

    private $isCategoryVisibleInMenuCache;

    protected static $_activeCategories;
    protected static $_categoryNames;

    public function __construct(ManagerInterface $eventManager, ObjectManagerInterface $objectManager, StoreManagerInterface $storeManager, ResourceConnection $resourceConnection, Config $eavConfig, ConfigHelper $configHelper)
    {
        $this->eventManager = $eventManager;
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
        $this->configHelper = $configHelper;
    }

    public function getIndexNameSuffix()
    {
        return '_categories';
    }

    public function getIndexSettings($storeId)
    {
        $searchableAttributes = [];
        $unretrievableAttributes = [];

        foreach ($this->configHelper->getCategoryAdditionalAttributes($storeId) as $attribute) {
            if ($attribute['searchable'] == '1') {
                if ($attribute['order'] == 'ordered') {
                    $searchableAttributes[] = $attribute['attribute'];
                } else {
                    $searchableAttributes[] = 'unordered(' . $attribute['attribute'] . ')';
                }
            }

            if ($attribute['retrievable'] != '1') {
                $unretrievableAttributes[] = $attribute['attribute'];
            }
        }

        $customRankings = $this->configHelper->getCategoryCustomRanking($storeId);

        $customRankingsArr = [];

        foreach ($customRankings as $ranking) {
            $customRankingsArr[] = $ranking['order'] . '(' . $ranking['attribute'] . ')';
        }

        // Default index settings
        $indexSettings = [
            'searchableAttributes'    => array_values(array_unique($searchableAttributes)),
            'customRanking'           => $customRankingsArr,
            'unretrievableAttributes' => $unretrievableAttributes,
        ];

        // Additional index settings from event observer
        $transport = new DataObject($indexSettings);
        $this->eventManager->dispatch('algolia_index_settings_prepare', [ // Only for backward compatibility
                'store_id'       => $storeId,
                'index_settings' => $transport,
            ]
        );
        $this->eventManager->dispatch('algolia_categories_index_before_set_settings', [
                'store_id'       => $storeId,
                'index_settings' => $transport,
            ]
        );
        $indexSettings = $transport->getData();

        return $indexSettings;
    }

    public function getAdditionalAttributes($storeId = null)
    {
        return $this->configHelper->getCategoryAdditionalAttributes($storeId);
    }

    public function getCategoryCollectionQuery($storeId, $categoryIds = null)
    {
        $storeRootCategoryPath = sprintf('%d/%d', $this->getRootCategoryId(), $this->storeManager->getStore($storeId)->getRootCategoryId());

        /* @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $categories = $this->objectManager->create('Magento\Catalog\Model\ResourceModel\Category\Collection');

        $unserializedCategorysAttrs = $this->getAdditionalAttributes($storeId);

        $additionalAttr = [];

        foreach ($unserializedCategorysAttrs as $attr) {
            $additionalAttr[] = $attr['attribute'];
        }

        $categories
            ->distinct(true)
            ->addPathFilter($storeRootCategoryPath)
            ->addNameToResult()
            ->addUrlRewriteToResult()
            ->addIsActiveFilter()
            ->setStoreId($storeId)
            ->addAttributeToSelect(array_merge(['name'], $additionalAttr))
            ->addFieldToFilter('level', ['gt' => 1]);

        if (!$this->configHelper->showCatsNotIncludedInNavigation()) {
            $categories->addAttributeToFilter('include_in_menu', 1);
        }

        if ($categoryIds) {
            $categories->addFieldToFilter('entity_id', ['in' => $categoryIds]);
        }

        $this->eventManager->dispatch('algolia_after_categories_collection_build', ['store' => $storeId, 'collection' => $categories]);

        return $categories;
    }

    public function getAllAttributes()
    {
        if (is_null(self::$_categoryAttributes)) {
            self::$_categoryAttributes = [];

            $allAttributes = $this->eavConfig->getEntityAttributeCodes('catalog_category');

            $categoryAttributes = array_merge($allAttributes, ['product_count']);

            $excludedAttributes = [
                'all_children', 'available_sort_by', 'children', 'children_count', 'custom_apply_to_products',
                'custom_design', 'custom_design_from', 'custom_design_to', 'custom_layout_update', 'custom_use_parent_settings',
                'default_sort_by', 'display_mode', 'filter_price_range', 'global_position', 'image', 'include_in_menu', 'is_active',
                'is_always_include_in_menu', 'is_anchor', 'landing_page', 'level', 'lower_cms_block',
                'page_layout', 'path_in_store', 'position', 'small_image', 'thumbnail', 'url_key', 'url_path',
                'visible_in_menu', ];

            $categoryAttributes = array_diff($categoryAttributes, $excludedAttributes);

            foreach ($categoryAttributes as $attributeCode) {
                self::$_categoryAttributes[$attributeCode] = $this->eavConfig->getAttribute('catalog_category', $attributeCode)->getFrontendLabel();
            }
        }

        return self::$_categoryAttributes;
    }

    public function getObject(Category $category)
    {
        $productCollection = $category->getProductCollection();
        $productCollection = $productCollection->addMinimalPrice();

        $category->setProductCount($productCollection->getSize());

        $transport = new DataObject();
        $this->eventManager->dispatch('algolia_category_index_before', ['category' => $category, 'custom_data' => $transport]);
        $customData = $transport->getData();

        $storeId = $category->getStoreId();
        $category->getUrlInstance()->setStore($storeId);
        $path = '';
        foreach ($category->getPathIds() as $categoryId) {
            if ($path != '') {
                $path .= ' / ';
            }
            $path .= $this->getCategoryName($categoryId, $storeId);
        }

        $imageUrl = null;
        try {
            $imageUrl = $category->getImageUrl();
        } catch (\Exception $e) { /* no image, no default: not fatal */
        }

        $data = [
            'objectID'      => $category->getId(),
            'name'          => $category->getName(),
            'path'          => $path,
            'level'         => $category->getLevel(),
            'url'           => $this->getUrl($category),
            'include_in_menu' => $category->getIncludeInMenu(),
            '_tags'         => ['category'],
            'popularity'    => 1,
            'product_count' => $category->getProductCount(),
        ];

        if (!empty($imageUrl)) {
            /** @var Image $imageHelper */
            $imageHelper = $this->objectManager->create('Algolia\AlgoliaSearch\Helper\Image');

            $imageUrl = $imageHelper->removeProtocol($imageUrl);
            $imageUrl = $imageHelper->removeDoubleSlashes($imageUrl);

            $data['image_url'] = $imageUrl;
        }

        foreach ($this->configHelper->getCategoryAdditionalAttributes($storeId) as $attribute) {
            $value = $category->getData($attribute['attribute']);

            $attribute_resource = $category->getResource()->getAttribute($attribute['attribute']);

            if ($attribute_resource) {
                $value = $attribute_resource->getFrontend()->getValue($category);
            }

            if (isset($data[$attribute['attribute']])) {
                $value = $data[$attribute['attribute']];
            }

            if ($value) {
                $data[$attribute['attribute']] = $value;
            }
        }

        $data = array_merge($data, $customData);

        $transport = new DataObject($data);
        $this->eventManager->dispatch('algolia_after_create_category_object', ['category' => $category, 'categoryObject' => $transport]);
        $data = $transport->getData();

        return $data;
    }

    public function getRootCategoryId()
    {
        if (-1 === self::$_rootCategoryId) {
            $collection = $this->objectManager->create('Magento\Catalog\Model\ResourceModel\Category\Collection');
            $collection->addFieldToFilter('parent_id', 0);
            $collection->getSelect()->limit(1);
            $rootCategory = $collection->getFirstItem();
            self::$_rootCategoryId = $rootCategory->getId();
        }

        return self::$_rootCategoryId;
    }

    private function getUrl(Category $category)
    {
        $categoryUrl = $category->getUrl();

        if ($this->configHelper->useSecureUrlsInFrontend($category->getStoreId()) === false) {
            return $categoryUrl;
        }

        $unsecureBaseUrl = $category->getUrlInstance()->getBaseUrl(['_secure' => false]);
        $secureBaseUrl = $category->getUrlInstance()->getBaseUrl(['_secure' => true]);

        if (strpos($categoryUrl, $unsecureBaseUrl) === 0) {
            return substr_replace($categoryUrl, $secureBaseUrl, 0, mb_strlen($unsecureBaseUrl));
        }

        return $categoryUrl;
    }

    public function isCategoryActive($categoryId, $storeId = null)
    {
        $storeId = intval($storeId);
        $categoryId = intval($categoryId);

        if ($path = $this->getCategoryPath($categoryId, $storeId)) {
            // Check whether the specified category is active

            $isActive = true; // Check whether all parent categories for the current category are active
            $parentCategoryIds = explode('/', $path);

            if (count($parentCategoryIds) <= 2) { // Exclude root category
                return false;
            }

            array_shift($parentCategoryIds); // Remove root category

            array_pop($parentCategoryIds); // Remove current category as it is already verified

            $parentCategoryIds = array_reverse($parentCategoryIds); // Start from the first parent

            foreach ($parentCategoryIds as $parentCategoryId) {
                if (!($parentCategoryPath = $this->getCategoryPath($parentCategoryId, $storeId))) {
                    $isActive = false;
                    break;
                }
            }

            if ($isActive) {
                return true;
            }
        }

        return false;
    }

    private function getCategoryPath($categoryId, $storeId = null)
    {
        $categories = $this->getCategories();
        $storeId = intval($storeId);
        $categoryId = intval($categoryId);
        $path = null;

        $categoryKeyId = $categoryId;

        if ($this->getCorrectIdColumn() === 'row_id') {
            $category = $this->getCategoryById($categoryId);
            if ($category) {
                $categoryKeyId = $category->getRowId();
            }
        }

        if(is_null($categoryKeyId)) {
            return $path;
        }

        $key = $storeId . '-' . $categoryKeyId;

        if (isset($categories[$key])) {
            $path = ($categories[$key]['value'] == 1) ? strval($categories[$key]['path']) : null;
        } elseif ($storeId !== 0) {
            $key = '0-' . $categoryKeyId;

            if (isset($categories[$key])) {
                $path = ($categories[$key]['value'] == 1) ? strval($categories[$key]['path']) : null;
            }
        }

        return $path;
    }

    private function getCategories()
    {
        if (is_null(self::$_activeCategories)) {
            self::$_activeCategories = [];

            /** @var \Magento\Catalog\Model\ResourceModel\Category $resource */
            $resource = $this->objectManager->create('\Magento\Catalog\Model\ResourceModel\Category');

            if ($attribute = $resource->getAttribute('is_active')) {
                $connection = $this->resourceConnection->getConnection();
                $select = $connection->select()
                                     ->from(['backend' => $attribute->getBackendTable()], ['key' => new \Zend_Db_Expr("CONCAT(backend.store_id, '-', backend.".$this->getCorrectIdColumn().")"), 'category.path', 'backend.value'])
                                     ->join(['category' => $resource->getTable('catalog_category_entity')], 'backend.'.$this->getCorrectIdColumn().' = category.'.$this->getCorrectIdColumn(), [])
                                     ->where('backend.attribute_id = ?', $attribute->getAttributeId())
                                     ->order('backend.store_id')
                                     ->order('backend.'.$this->getCorrectIdColumn());

                self::$_activeCategories = $connection->fetchAssoc($select);
            }
        }

        return self::$_activeCategories;
    }

    public function getCategoryName($categoryId, $storeId = null)
    {
        if ($categoryId instanceof MagentoCategory) {
            $categoryId = $categoryId->getId();
        }

        if ($storeId instanceof  Store) {
            $storeId = $storeId->getId();
        }

        $categoryId = intval($categoryId);
        $storeId = intval($storeId);

        if (is_null(self::$_categoryNames)) {
            self::$_categoryNames = [];

            /** @var \Magento\Catalog\Model\ResourceModel\Category $categoryModel */
            $categoryModel = $this->objectManager->create('\Magento\Catalog\Model\ResourceModel\Category');

            if ($attribute = $categoryModel->getAttribute('name')) {
                $connection = $this->resourceConnection->getConnection();

                $select = $connection->select()
                                     ->from(['backend' => $attribute->getBackendTable()], [new \Zend_Db_Expr("CONCAT(backend.store_id, '-', backend.".$this->getCorrectIdColumn().")"), 'backend.value'])
                                     ->join(['category' => $categoryModel->getTable('catalog_category_entity')], 'backend.'.$this->getCorrectIdColumn().' = category.'.$this->getCorrectIdColumn(), [])
                                     ->where('backend.attribute_id = ?', $attribute->getAttributeId())
                                     ->where('category.level > ?', 1);

                self::$_categoryNames = $connection->fetchPairs($select);
            }
        }

        $categoryName = null;

        $categoryKeyId = $categoryId;

        if ($this->getCorrectIdColumn() === 'row_id') {
            $category = $this->getCategoryById($categoryId);
            if ($category) {
                $categoryKeyId = $category->getRowId();
            }
        }

        if(is_null($categoryKeyId)) {
            return $categoryName;
        }

        $key = $storeId . '-' . $categoryKeyId;

        if (isset(self::$_categoryNames[$key])) {
            // Check whether the category name is present for the specified store
            $categoryName = strval(self::$_categoryNames[$key]);
        } elseif ($storeId != 0) {
            // Check whether the category name is present for the default store
            $key = '0-' . $categoryKeyId;
            if (isset(self::$_categoryNames[$key])) {
                $categoryName = strval(self::$_categoryNames[$key]);
            }
        }

        return $categoryName;
    }

    private function getCategoryById($categoryId)
    {
        $categories = $this->getCoreCategories();

        return isset($categories[$categoryId]) ? $categories[$categoryId] : null;
    }

    public function isCategoryVisibleInMenu($categoryId, $storeId)
    {
        $key = $categoryId.' - '.$storeId;
        if (isset($this->isCategoryVisibleInMenuCache[$key])) {
            return $this->isCategoryVisibleInMenuCache[$key];
        }

        $categoryId = (int) $categoryId;

        /** @var Category $category */
        $category = $this->objectManager->create('\Magento\Catalog\Model\Category');
        $category = $category->setStoreId($storeId)->load($categoryId);

        $this->isCategoryVisibleInMenuCache[$key] = (bool) $category->getIncludeInMenu();

        return $this->isCategoryVisibleInMenuCache[$key];
    }

    public function getCoreCategories() {
        if (isset($this->coreCategories)) {
            return $this->coreCategories;
        }

        $categoriesData = $this->objectManager->create('Magento\Catalog\Model\ResourceModel\Category\Collection');
        $categoriesData
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('include_in_menu', '1')
            ->addFieldToFilter('level', ['gt' => 1])
            ->addIsActiveFilter();

        $this->coreCategories = [];
        foreach ($categoriesData as $category) {
            $this->coreCategories[$category->getId()] = $category;
        }

        return $this->coreCategories;
    }

    private function getCorrectIdColumn()
    {
        if (isset($this->idColumn)) {
            return $this->idColumn;
        }

        $this->idColumn = 'entity_id';

        if ($this->configHelper->getMagentoEdition() !== 'Community' && version_compare($this->configHelper->getMagentoVersion(), '2.1.0', '>=')) {
            $this->idColumn = 'row_id';
        }

        return $this->idColumn;
    }
}
