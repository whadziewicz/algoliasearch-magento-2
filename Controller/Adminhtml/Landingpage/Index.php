<?php

namespace Algolia\AlgoliaSearch\Controller\Adminhtml\Landingpage;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Backend\App\Action
{
    /** @var Algolia\AlgoliaSearch\Helper\ConfigHelper */
    protected $configHelper;

    /**
     * @param Context       $context
     * @param ConfigHelper  $configHelper
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper
    ) {
        parent::__construct($context);
        $this->configHelper = $configHelper;
    }

    /** @return \Magento\Framework\View\Result\Page */
    public function execute()
    {
        $breadMain = __('Algolia | Landing Page Builder');

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Algolia_AlgoliaSearch::manage');
        $resultPage->getConfig()->getTitle()->prepend($breadMain);

        $dataPersistor = $this->_objectManager->get(\Magento\Framework\App\Request\DataPersistorInterface::class);
        $dataPersistor->clear('landing_page');

        return $resultPage;
    }
}
