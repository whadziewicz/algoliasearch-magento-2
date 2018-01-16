<?php

namespace Algolia\AlgoliaSearch\Model;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
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
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->config->isEnabledFrontEnd()) {
            if ($this->config->getApplicationID() && $this->config->getAPIKey()) {
                if ($this->config->isPopupEnabled() || $this->config->isInstantEnabled()) {
                    /** @var \Magento\Framework\View\Layout $layout */
                    $layout = $observer->getData('layout');

                    $layout->getUpdate()->addHandle('algolia_search_handle');

                    if ($this->config->isDefaultSelector()) {
                        $layout->getUpdate()->addHandle('algolia_search_handle_with_topsearch');
                    } else {
                        $layout->getUpdate()->addHandle('algolia_search_handle_no_topsearch');
                    }
                }
            }
        }
    }
}
