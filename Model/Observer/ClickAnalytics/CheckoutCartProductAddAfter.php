<?php

namespace Algolia\AlgoliaSearch\Model\Observer\ClickAnalytics;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class CheckoutCartProductAddAfter implements ObserverInterface
{
    /** @var ConfigHelper */
    private $configHelper;

    /** @var LoggerInterface */
    private $logger;

    /**
     * CheckoutCartProductAddAfter constructor.
     * @param ConfigHelper $configHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConfigHelper $configHelper,
        LoggerInterface $logger
    ) {
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $this->logger->debug('CheckoutCartProductAddAfter');

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        if ($this->configHelper->isClickConversionAnalyticsEnabled($product->getStoreId())
            && $this->configHelper->getConversionAnalyticsMode($product->getStoreId()) === 'place_order'
        ) {
            $quoteItem->setData('algoliasearch_query_param', $product->getData('algoliasearch_query_param'));
        }
    }
}
