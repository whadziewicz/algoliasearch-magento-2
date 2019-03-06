<?php

namespace Algolia\AlgoliaSearch\Block\Adminhtml\Query\Edit;

use Algolia\AlgoliaSearch\Model\QueryFactory;
use Magento\Backend\Block\Widget\Context;

abstract class AbstractButton
{
    /** @var Context */
    protected $context;

    /** @var QueryFactory */
    protected $queryFactory;

    /**
     * PHP Constructor
     *
     * @param Context $context
     * @param QueryFactory $queryFactory
     *
     * @return AbstractButton
     */
    public function __construct(
        Context $context,
        QueryFactory $queryFactory
    ) {
        $this->context = $context;
        $this->queryFactory = $queryFactory;
    }

    /**
     * Return object
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @return int|null
     */
    public function getObject()
    {
        try {
            $modelId = $this->context->getRequest()->getParam('id');
            /** @var \Algolia\AlgoliaSearch\Model\Query $query */
            $query = $this->queryFactory->create();
            $query->getResource()->load($query, $modelId);

            return $query;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        }

        return null;
    }

    /**
     * Return object ID
     *
     * @return int|null
     */
    public function getObjectId()
    {
        return $this->getObject() ? $this->getObject()->getId() : null;
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
