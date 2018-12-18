<?php

namespace Algolia\AlgoliaSearch\Block\Adminhtml;

use Algolia\AlgoliaSearch\Factory\ViewModelFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class BaseAdminTemplate extends Template
{
    /** @var ViewModelFactory */
    private $viewModelFactory;

    /**
     * @param Context $context
     * @param ViewModelFactory $viewModelFactory
     * @param array $data
     */
    public function __construct(Context $context, ViewModelFactory $viewModelFactory, array $data = [])
    {
        parent::__construct($context, $data);

        $this->viewModelFactory = $viewModelFactory;
    }

    public function getViewModel()
    {
        return $this->viewModelFactory->create($this);
    }
}
