<?php
namespace Algolia\AlgoliaSearch\Plugin;

use Magento\Catalog\Model\Category;
use Magento\Framework\ObjectManagerInterface;

class CategoryUrlPlugin
{
    const FRONTEND_URL = 'Magento\Framework\Url';
    protected $objectManager;

    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }
    
    public function aroundGetUrlInstance(Category $category, \Closure $proceed)
    {
        if ($category->getStoreId() == 0) {
            return $proceed();
        } else {
            return $this->objectManager->create(self::FRONTEND_URL)->setStoreId($category->getStoreId());
        }
    }
}