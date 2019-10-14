<?php

namespace Algolia\AlgoliaSearch\Model\Observer\ClickAnalytics;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class CheckoutCartProductAddBefore implements ObserverInterface
{
    /** @var ConfigHelper */
    private $configHelper;

    /** @var LoggerInterface */
    private $logger;

    private $analyticsParams = [
        'queryID',
        'indexName',
        'objectID'
    ];

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
        $requestInfo = $observer->getEvent()->getInfo();
        $this->logger->debug('CheckoutCartProductAddBefore', $requestInfo);

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        if ($this->configHelper->isClickConversionAnalyticsEnabled($product->getStoreId())
            && $this->configHelper->getConversionAnalyticsMode($product->getStoreId()) === 'place_order'
        ) {

            $params = [];
            foreach ($this->analyticsParams as $param) {
                if (isset($requestInfo['as_' . $param])) {
                    $params[$param] = $requestInfo['as_' . $param];
                }
            }

            if (isset($params['queryID'])) {
                $product->setData('algoliasearch_query_param', json_encode($params));
            }
        }
    }
}
