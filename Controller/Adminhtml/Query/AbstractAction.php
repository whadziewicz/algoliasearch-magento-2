<?php

namespace Algolia\AlgoliaSearch\Controller\Adminhtml\Query;

use Algolia\AlgoliaSearch\Model\QueryFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

abstract class AbstractAction extends \Magento\Backend\App\Action
{
    /** @var Registry */
    protected $coreRegistry;

    /** @var QueryFactory */
    protected $queryFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param QueryFactory $queryFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        QueryFactory $queryFactory
    ) {
        parent::__construct($context);

        $this->coreRegistry = $coreRegistry;
        $this->queryFactory = $queryFactory;
    }

    /** @return bool */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Algolia_AlgoliaSearch::manage');
    }

    /** @return Algolia\AlgoliaSearch\Model\Query */
    protected function initQuery()
    {
        $queryId = (int) $this->getRequest()->getParam('id');

        /** @var \Algolia\AlgoliaSearch\Model\Query $queryFactory */
        $query = $this->queryFactory->create();

        if ($queryId) {
            $query->getResource()->load($query, $queryId);
            if (!$query->getId()) {
                return null;
            }
        }

        $this->coreRegistry->register('algoliasearch_query', $query);

        return $query;
    }
}
