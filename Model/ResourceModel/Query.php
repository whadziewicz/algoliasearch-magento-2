<?php

namespace Algolia\AlgoliaSearch\Model\ResourceModel;

use Algolia\AlgoliaSearch\Api\Data\QueryInterface;

class Query extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init(QueryInterface::TABLE_NAME, QueryInterface::FIELD_QUERY_ID);
    }
}
