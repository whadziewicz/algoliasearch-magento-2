<?php
namespace Algolia\AlgoliaSearch\Block;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\Data;
use Magento\Framework\View\Element\Template;

class TopSearch extends Template
{
    protected $config;
    protected $catalogSearchHelper;

    public function __construct(
        Template\Context $context,
        ConfigHelper $config,
        Data $catalogSearchHelper,
        array $data = []
    ) {
        $this->config = $config;
        $this->catalogSearchHelper = $catalogSearchHelper;

        parent::__construct($context, $data);
    }

    public function isDefaultSelector()
    {
        return $this->config->isDefaultSelector();
    }

    public function getResultUrl()
    {
        return $this->catalogSearchHelper->getResultUrl();
    }

    public function getQueryParamName()
    {
        return $this->catalogSearchHelper->getQueryParamName();
    }
}