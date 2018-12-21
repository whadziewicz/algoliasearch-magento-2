<?php

namespace Algolia\AlgoliaSearch\Controller;

use Algolia\AlgoliaSearch\Model\LandingPageFactory;
use Magento\Framework\App\ActionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Router implements \Magento\Framework\App\RouterInterface
{
    /** @var ActionFactory */
    protected $actionFactory;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /**  @var LandingPageFactory */
    protected $landingPageFactory;

    /**
     * @param ActionFactory $actionFactory
     * @param LandingPageFactory $landingPageFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ActionFactory $actionFactory,
        LandingPageFactory $landingPageFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->actionFactory = $actionFactory;
        $this->landingPageFactory = $landingPageFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Validate and match landing pages from Algolia and modify request
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return \Magento\Framework\App\ActionInterface|null
     */
    public function match(\Magento\Framework\App\RequestInterface $request)
    {
        $identifier = trim($request->getPathInfo(), '/');

        /** @var \Algolia\AlgoliaSearch\Model\LandingPage $landingPage */
        $landingPage = $this->landingPageFactory->create();
        $pageId = $landingPage->checkIdentifier($identifier, $this->storeManager->getStore()->getId());
        if (!$pageId) {
            return null;
        }

        $request
            ->setModuleName('algolia')
            ->setControllerName('landingpage')
            ->setActionName('view')
            ->setParam('landing_page_id', $pageId);

        $request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $identifier);
        return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
    }
}
