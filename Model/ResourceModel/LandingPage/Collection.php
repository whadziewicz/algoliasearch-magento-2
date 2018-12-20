<?php

namespace Algolia\AlgoliaSearch\Model\ResourceModel\LandingPage;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
//    protected $_idFieldName = 'landing_page_id';
//
//    protected $_eventPrefix = 'algoliasearch_landing_page_collection';
//
//    protected $_eventObject = 'landing_page_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Algolia\AlgoliaSearch\Model\LandingPage::class,
            \Algolia\AlgoliaSearch\Model\ResourceModel\LandingPage::class
        );
    }
}
