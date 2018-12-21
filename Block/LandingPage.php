<?php

namespace Algolia\AlgoliaSearch\Block;

use Algolia\AlgoliaSearch\Model\LandingPage as LandingPageModel;
use Algolia\AlgoliaSearch\Model\LandingPageFactory;
use Magento\Store\Model\ScopeInterface;

class LandingPage extends \Magento\Framework\View\Element\AbstractBlock implements
    \Magento\Framework\DataObject\IdentityInterface
{
    /** @var \Magento\Cms\Model\Template\FilterProvider */
    protected $_filterProvider;

    /** @var LandingPageModel */
    protected $landingPage;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var LandingPageFactory */
    protected $landingPageFactory;

    /** @var \Magento\Framework\View\Page\Config */
    protected $pageConfig;

    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param LandingPageModel $landingPage
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param LandingPageFactory $landingPageFactory
     * @param \Magento\Framework\View\Page\Config $pageConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        LandingPageModel $landingPage,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        LandingPageFactory $landingPageFactory,
        \Magento\Framework\View\Page\Config $pageConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->landingPage = $landingPage;
        $this->_filterProvider = $filterProvider;
        $this->_storeManager = $storeManager;
        $this->landingPageFactory = $landingPageFactory;
        $this->pageConfig = $pageConfig;
    }

    /**
     * Retrieve Page instance
     *
     * @return \Magento\Cms\Model\Page
     */
    public function getPage()
    {
        if (!$this->hasData('page')) {
            if ($this->getPageId()) {
                /** @var LandingPageModel $page */
                $page = $this->landingPageFactory->create();
                $page->setStoreId($this->_storeManager->getStore()->getId())->load($this->getPageId(), 'url_key');
            } else {
                $page = $this->landingPage;
            }
            $this->setData('page', $page);
        }
        return $this->getData('page');
    }

    /**
     * Prepare global layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $page = $this->getPage();
        $this->pageConfig->addBodyClass('algolia-landingpage-' . $page->getUrlKey());
        $metaTitle = $page->getMetaTitle();
        $this->pageConfig->getTitle()->set($page->getTitle() ? $page->getTitle() : $metaTitle);
        $this->pageConfig->setKeywords($page->getMetaKeywords());
        $this->pageConfig->setDescription($page->getMetaDescription());

        return parent::_prepareLayout();
    }

    /**
     * Prepare HTML content
     *
     * @return string
     */
    protected function _toHtml()
    {
        $html = $this->_filterProvider->getPageFilter()->filter($this->getPage()->getContent());
        return $html;
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        return [\Algolia\AlgoliaSearch\Model\LandingPage::CACHE_TAG . '_' . $this->getPage()->getId()];
    }
}
