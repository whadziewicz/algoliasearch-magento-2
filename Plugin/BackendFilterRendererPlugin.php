<?php

namespace Algolia\AlgoliaSearch\Plugin;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;

class BackendFilterRendererPlugin
{
    /** @var LayoutInterface */
    protected $layout;

    /** @var string */
    protected $block = \Algolia\AlgoliaSearch\Block\Navigation\Renderer\DefaultRenderer::class;

    /** @var ConfigHelper */
    private $configHelper;

    /**
     * @param LayoutInterface $layout
     * @param StoreManagerInterface $storeManager
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        LayoutInterface $layout,
        StoreManagerInterface $storeManager,
        ConfigHelper $configHelper
    ) {
        $this->layout = $layout;
        $this->configHelper = $configHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param \Magento\LayeredNavigation\Block\Navigation\FilterRenderer $subject
     * @param \Closure $proceed
     * @param \Magento\Catalog\Model\Layer\Filter\FilterInterface $filter
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundRender(
        \Magento\LayeredNavigation\Block\Navigation\FilterRenderer $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Layer\Filter\FilterInterface $filter
    ) {

        if ($filter instanceof \Magento\CatalogSearch\Model\Layer\Filter\Category) {
            return $proceed($filter);
        }

        if ($this->configHelper->isBackendRenderingEnabled()) {
            $attributeCode = $filter->getAttributeModel()->getAttributeCode();
            $facets = $this->configHelper->getFacets($this->storeManager->getStore()->getId());

            foreach ($facets as $facet) {
                if ($facet['attribute'] == $attributeCode) {
                    return $this->layout
                        ->createBlock($this->block)
                        ->render($filter);
                }
            }
        }

        return $proceed($filter);
    }
}
