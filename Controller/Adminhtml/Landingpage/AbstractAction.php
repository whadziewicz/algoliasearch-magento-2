<?php

namespace Algolia\AlgoliaSearch\Controller\Adminhtml\Landingpage;

use Algolia\AlgoliaSearch\Model\LandingPageFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

abstract class AbstractAction extends \Magento\Backend\App\Action
{
    /** @var Registry */
    protected $coreRegistry;

    /** @var LandingPageFactory */
    protected $landingPageFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param LandingPageFactory $landingPageFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        LandingPageFactory $landingPageFactory
    ) {
        parent::__construct($context);

        $this->coreRegistry = $coreRegistry;
        $this->landingPageFactory = $landingPageFactory;
    }

    /** @return bool */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Algolia_AlgoliaSearch::manage');
    }

    /** @return Algolia\AlgoliaSearch\Model\LandingPage */
    protected function initLandingPage()
    {
        $landingPageId = (int) $this->getRequest()->getParam('id');

        /** @var \Algolia\AlgoliaSearch\Model\LandingPage $landingPage */
        $landingPage = $this->landingPageFactory->create();

        if ($landingPageId) {
            $landingPage->getResource()->load($landingPage, $landingPageId);
            if (!$landingPage->getId()) {
                return null;
            }
        }

        $this->coreRegistry->register('algoliasearch_landing_page', $landingPage);

        return $landingPage;
    }
}
