<?php

namespace Algolia\AlgoliaSearch\Model\Observer\ClickAnalytics;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CheckoutCartProductAddBefore implements ObserverInterface
{
    /** @var ConfigHelper */
    private $configHelper;

    private $analyticsParams = [
        'queryID',
        'indexName',
        'objectID',
    ];

    /**
     * CheckoutCartProductAddAfter constructor.
     *
     * @param ConfigHelper $configHelper
     */
    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        $requestInfo = $observer->getEvent()->getInfo();

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
