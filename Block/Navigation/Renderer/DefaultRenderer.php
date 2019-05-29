<?php

namespace Algolia\AlgoliaSearch\Block\Navigation\Renderer;

use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Framework\View\Element\Template;
use Magento\LayeredNavigation\Block\Navigation\FilterRendererInterface;

class DefaultRenderer extends Template implements FilterRendererInterface
{
    const JS_COMPONENT = 'Algolia_AlgoliaSearch/navigation/attribute-filter';

    /**
     * Path to template file.
     *
     * @var string
     */
    protected $_template = 'Algolia_AlgoliaSearch::layer/filter/js-default.phtml';
//    protected $_template = 'Algolia_AlgoliaSearch::layer/filter/default.phtml';

    /**
     * Returns true if checkox have to be enabled.
     *
     * @return boolean
     */
    public function isMultipleSelectEnabled()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function render(FilterInterface $filter)
    {
        $html = '';
        $this->filter = $filter;

        if ($this->canRenderFilter()) {
            $this->assign('filterItems', $filter->getItems());
            $html = $this->_toHtml();
            $this->assign('filterItems', []);
        }

        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function getJsLayout()
    {
        $filterItems = $this->getFilter()->getItems();

        $jsLayoutConfig = [
            'component' => self::JS_COMPONENT,
            'maxSize'  => (int) $this->getFilter()->getAttributeModel()->getFacetMaxSize(),
            'displayProductCount' => (bool) $this->displayProductCount(),
            'hasMoreItems' => (bool) $this->getFilter()->hasMoreItems(),
            'ajaxLoadUrl' => $this->getAjaxLoadUrl(),
        ];

        foreach ($filterItems as $item) {
            $jsLayoutConfig['items'][] = [
                'label' => $item->getLabel(),
                'count' => $item->getCount(),
                'url' => $item->getUrl(),
                'is_selected' => $item->getData('is_selected')
            ];
        }

        return json_encode($jsLayoutConfig);
    }

    /**
     * {@inheritDoc}
     */
    protected function canRenderFilter()
    {
        return true;
    }

    /**
     * @return FilterInterface
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Indicates if the product count should be displayed or not.
     *
     * @return boolean
     */
    public function displayProductCount()
    {
        return true;
//        return $this->catalogHelper->shouldDisplayProductCountOnLayer();
    }

    /**
     * Get the AJAX load URL (used by the show more and the search features).
     *
     * @return string
     */
    private function getAjaxLoadUrl()
    {
        $qsParams = ['filterName' => $this->getFilter()->getRequestVar()];

        $currentCategory = $this->getFilter()->getLayer()->getCurrentCategory();

        if ($currentCategory && $currentCategory->getId() && $currentCategory->getLevel() > 1) {
            $qsParams['cat'] = $currentCategory->getId();
        }

        $urlParams = ['_current' => true, '_query' => $qsParams];

        return $this->_urlBuilder->getUrl('catalog/navigation_filter/ajax', $urlParams);
    }
}
