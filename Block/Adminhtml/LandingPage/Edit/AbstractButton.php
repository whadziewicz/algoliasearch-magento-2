<?php

namespace Algolia\AlgoliaSearch\Block\Adminhtml\LandingPage\Edit;

use Algolia\AlgoliaSearch\Model\LandingPageFactory;
use Magento\Backend\Block\Widget\Context;

abstract class AbstractButton
{
    /**  @var Context */
    protected $context;

    /** @var LandingPageFactory */
    protected $landingPageFactory;

    /**
     * PHP Constructor
     *
     * @param Context $context
     * @param LandingPageFactory $landingPageFactory
     *
     * @return AbstractButton
     */
    public function __construct(
        Context $context,
        LandingPageFactory $landingPageFactory
    ) {
        $this->context = $context;
        $this->landingPageFactory = $landingPageFactory;
    }

    /**
     * Return object ID
     *
     * @return int|null
     */
    public function getObjectId()
    {
        try {
            $modelId = $this->context->getRequest()->getParam('id');

            /** @var \Algolia\AlgoliaSearch\Model\LandingPage $landingPage */
            $landingPage = $this->landingPageFactory->create();
            $landingPage->getResource()->load($landingPage, $modelId);
            return $landingPage->getId();
        } catch (NoSuchEntityException $e) {
        }
        return null;
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
