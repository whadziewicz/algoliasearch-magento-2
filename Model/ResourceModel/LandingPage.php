<?php

namespace Algolia\AlgoliaSearch\Model\ResourceModel;

use Algolia\AlgoliaSearch\Api\Data\LandingPageInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;

class LandingPage extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init(LandingPageInterface::TABLE_NAME, LandingPageInterface::FIELD_LANDING_PAGE_ID);
    }
}
