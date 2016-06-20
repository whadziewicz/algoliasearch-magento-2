<?php
namespace Algolia\AlgoliaSearch\Model\Product;

use Magento\Framework\ObjectManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;

class Url extends \Magento\Catalog\Model\Product\Url
{

    const FRONTEND_URL = 'Magento\Framework\Url';
    const BACKEND_URL = 'Magento\Backend\Model\Url';

    protected $objectManager;

    public function __construct(
        \Magento\Framework\UrlFactory $urlFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filter\FilterManager $filter,
        \Magento\Framework\Session\SidResolverInterface $sidResolver,
        UrlFinderInterface $urlFinder,
        ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        $this->objectManager = $objectManager;
        parent::__construct($urlFactory, $storeManager, $filter, $sidResolver, $urlFinder, $data);
    }

    /**
     * The only rewritten line in this method is the return statement
     */
    public function getUrl(\Magento\Catalog\Model\Product $product, $params = [])
    {
        $routePath = '';
        $routeParams = $params;

        $storeId = $product->getStoreId();

        $categoryId = null;

        if (!isset($params['_ignore_category']) && $product->getCategoryId() && !$product->getDoNotUseCategoryId()) {
            $categoryId = $product->getCategoryId();
        }

        if ($product->hasUrlDataObject()) {
            $requestPath = $product->getUrlDataObject()->getUrlRewrite();
            $routeParams['_scope'] = $product->getUrlDataObject()->getStoreId();
        } else {
            $requestPath = $product->getRequestPath();
            if (empty($requestPath) && $requestPath !== false) {
                $filterData = [
                    UrlRewrite::ENTITY_ID => $product->getId(),
                    UrlRewrite::ENTITY_TYPE => \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator::ENTITY_TYPE,
                    UrlRewrite::STORE_ID => $storeId,
                ];
                if ($categoryId) {
                    $filterData[UrlRewrite::METADATA]['category_id'] = $categoryId;
                }
                $rewrite = $this->urlFinder->findOneByData($filterData);
                if ($rewrite) {
                    $requestPath = $rewrite->getRequestPath();
                    $product->setRequestPath($requestPath);
                } else {
                    $product->setRequestPath(false);
                }
            }
        }

        if (isset($routeParams['_scope'])) {
            $storeId = $this->storeManager->getStore($routeParams['_scope'])->getId();
        }

        if ($storeId != $this->storeManager->getStore()->getId()) {
            $routeParams['_scope_to_url'] = true;
        }

        if (!empty($requestPath)) {
            $routeParams['_direct'] = $requestPath;
        } else {
            $routePath = 'catalog/product/view';
            $routeParams['id'] = $product->getId();
            $routeParams['s'] = $product->getUrlKey();
            if ($categoryId) {
                $routeParams['category'] = $categoryId;
            }
        }

        // reset cached URL instance GET query params
        if (!isset($routeParams['_query'])) {
            $routeParams['_query'] = [];
        }

        /**
         * This is the only line changed from the default method.
         * For reference, the original line: $this->getUrlInstance()->setScope($storeId)->getUrl($routePath, $routeParams);
         * getUrlInstance() is a private method, so a new method has been written that will create a frontend Url object if
         * the store scope is not the admin scope.
         */
        return $this->getStoreScopeUrlInstance($storeId)->getUrl($routePath, $routeParams);
    }
    
    public function getStoreScopeUrlInstance($storeId)
    {
        if ($storeId == 0) {
            return $this->objectManager->create(self::BACKEND_URL);
        } else {
            return $this->objectManager->create(self::FRONTEND_URL);
        }
    }

}