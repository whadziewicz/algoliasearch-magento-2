<?php

namespace Algolia\AlgoliaSearch\Model;
use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ObserverInterface;

/**
 * Algolia search observer model
 */
class Observer implements ObserverInterface
{
    protected $config;
    protected $product_helper;
    protected $helper;

    public function __construct(ConfigHelper $configHelper)
    {
        $this->config = $configHelper;
        /*$this->product_helper       = Mage::helper('algoliasearch/entity_producthelper');
        $this->category_helper      = Mage::helper('algoliasearch/entity_categoryhelper');
        $this->suggestion_helper    = Mage::helper('algoliasearch/entity_suggestionhelper');

        $this->helper               = Mage::helper('algoliasearch');*/
    }

    public function addBundleToAdmin(DataObject $observer)
    {
        $req  = Mage::app()->getRequest();

        if (strpos($req->getPathInfo(), 'system_config/edit/section/algoliasearch') !== false)
            $observer->getLayout()->getUpdate()->addHandle('algolia_bundle_handle');
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->config->isEnabledFrontEnd()) {
            if ($this->config->getApplicationID() && $this->config->getAPIKey()) {
                if ($this->config->isPopupEnabled() || $this->config->isInstantEnabled()) {
                    $observer->getLayout()->getUpdate()->addHandle('algolia_search_handle');

                    if ($this->config->isDefaultSelector()) {
                        $observer->getLayout()->getUpdate()->addHandle('algolia_search_handle_with_topsearch');
                    }
                    else {
                        $observer->getLayout()->getUpdate()->addHandle('algolia_search_handle_no_topsearch');
                    }
                }
            }
        }
    }
}