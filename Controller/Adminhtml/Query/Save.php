<?php

namespace Algolia\AlgoliaSearch\Controller\Adminhtml\Query;

use Algolia\AlgoliaSearch\Helper\MerchandisingHelper;
use Algolia\AlgoliaSearch\Helper\ProxyHelper;
use Algolia\AlgoliaSearch\Model\QueryFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class Save extends AbstractAction
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * PHP Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param QueryFactory $queryFactory
     * @param MerchandisingHelper $merchandisingHelper
     * @param StoreManagerInterface $storeManager
     * @param DataPersistorInterface $dataPersistor
     *
     * @return Save
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        QueryFactory $queryFactory,
        MerchandisingHelper $merchandisingHelper,
        StoreManagerInterface $storeManager,
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;

        parent::__construct(
            $context,
            $coreRegistry,
            $queryFactory,
            $merchandisingHelper,
            $storeManager
        );
    }

    /**
     * Execute the action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $data = $this->getRequest()->getPostValue();

        if (!empty($data)) {
            if (empty($data['query_id'])) {
                $data['query_id'] = null;
            }
            $queryId = $data['query_id'];

            /** @var \Algolia\AlgoliaSearch\Model\Query $query */
            $query = $this->queryFactory->create();

            if ($queryId) {
                $query->getResource()->load($query, $queryId);

                if (!$query->getId()) {
                    $this->messageManager->addErrorMessage(__('This query does not exist.'));

                    return $resultRedirect->setPath('*/*/');
                }
            }

            $query->setData($data);

            try {
                $query->getResource()->save($query);

                if (isset($data['algolia_merchandising_positions']) && $data['algolia_merchandising_positions'] != '') {
                    $this->manageQueryRules($query->getId(), $data);
                }

                $this->messageManager->addSuccessMessage(__('The query has been saved.'));
                $this->dataPersistor->clear('algolia_algoliasearch_query');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $query->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('Something went wrong while saving the query. %1', $e->getMessage())
                );
            }

            $this->dataPersistor->set('query', $data);

            return $resultRedirect->setPath('*/*/edit', ['id' => $queryId]);
        }

        return $resultRedirect->setPath('*/*/');
    }

    private function manageQueryRules($queryId, $data)
    {
        $positions = json_decode($data['algolia_merchandising_positions'], true);
        $stores = [];
        if ($data['store_id'] == 0) {
            foreach ($this->storeManager->getStores() as $store) {
                if ($store->getIsActive()) {
                    $stores[] = $store->getId();
                }
            }
        } else {
            $stores[] = $data['store_id'];
        }

        foreach ($stores as $storeId) {
            if (!$positions) {
                $this->merchandisingHelper->deleteQueryRule(
                    $storeId,
                    $queryId,
                    'query'
                );
            } else {
                $this->merchandisingHelper->saveQueryRule(
                    $storeId,
                    $queryId,
                    $positions,
                    'query',
                    $data['query_text'],
                    $data['banner_content']
                );
            }
        }
    }
}
