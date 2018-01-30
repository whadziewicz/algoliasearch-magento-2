<?php

namespace Algolia\AlgoliaSearch\Model;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;

/**
 * Algolia search observer model
 */
class Observer implements ObserverInterface
{
    private $config;
    private $registry;

    public function __construct(ConfigHelper $configHelper, Registry $registry)
    {
        $this->config = $configHelper;
        $this->registry = $registry;
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

                    if ($this->config->preventBackendRendering() === true) {
                        /** @var \Magento\Catalog\Model\Category $category */
                        $category = $this->registry->registry('current_category');
                        $displayMode = $this->config->getBackendRenderingDisplayMode();
                        if ($category && ($displayMode === 'all' || ($displayMode === 'only_products' && $category->getDisplayMode() !== 'PAGE'))) {
                            $layout->getUpdate()->addHandle('algolia_search_handle_prevent_backend_rendering');
                        }
                    }
                }
            }
        }
    }
}
