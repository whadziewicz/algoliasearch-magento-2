<?php

namespace Algolia\AlgoliaSearch\Block\Adminhtml\LandingPage\Edit;

use Magento\Backend\Block\Widget\Context;

abstract class AbstractButton
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * PHP Constructor
     *
     * @param Context $context
     *
     * @return AbstractButton
     */
    public function __construct(
        Context $context
    ) {
        $this->context = $context;
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     *
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }

    /**
     * get the button data
     *
     * @return array
     */
    abstract public function getButtonData();
}
