<?php

namespace Algolia\AlgoliaSearch\Helper\Entity;

use Magento\Framework\DataObject;
use Magento\Search\Model\Query;

class SuggestionHelper extends BaseHelper
{
    protected function getIndexNameSuffix()
    {
        return '_suggestions';
    }

    public function getIndexSettings($storeId)
    {
        $indexSettings = [
            'searchableAttributes' => ['query'],
            'customRanking'        => ['desc(popularity)', 'desc(number_of_results)', 'asc(date)'],
            'typoTolerance'        => false,
            'attributesToRetrieve' => ['query'],
        ];

        $transport = new DataObject($indexSettings);
        $this->eventManager->dispatch('algolia_suggestions_index_before_set_settings', ['store_id' => $storeId, 'index_settings' => $transport]);
        $indexSettings = $transport->getData();

        return $indexSettings;
    }

    public function getObject(Query $suggestion)
    {
        $suggestionObject = [
            'objectID'          => $suggestion->getData('query_id'),
            'query'             => $suggestion->getData('query_text'),
            'number_of_results' => (int) $suggestion->getData('num_results'),
            'popularity'        => (int) $suggestion->getData('popularity'),
            'updated_at'        => (int) strtotime($suggestion->getData('updated_at')),
        ];

        $transport = new DataObject($suggestionObject);
        $this->eventManager->dispatch('algolia_after_create_suggestion_object', ['suggestion' => $transport, 'suggestionObject' => $suggestion]);
        $suggestionObject = $transport->getData();

        return $suggestionObject;
    }

    public function getPopularQueries($storeId)
    {
        $collection = $this->objectManager->create('\Magento\Search\Model\ResourceModel\Query\Collection');
        $collection->getSelect()->where('num_results >= ' . $this->config->getMinNumberOfResults() . ' AND popularity >= ' . $this->config->getMinPopularity() . ' AND query_text != "__empty__"');
        $collection->getSelect()->limit(12);
        $collection->setOrder('popularity', 'DESC');
        $collection->setOrder('num_results', 'DESC');
        $collection->setOrder('updated_at', 'ASC');

        if ($storeId) {
            $collection->getSelect()->where('store_id = ?', (int) $storeId);
        }

        $collection->load();

        $suggestions = [];

        foreach ($collection as $suggestion) {
            if (strlen($suggestion['query_text']) >= 3) {
                $suggestions[] = $suggestion['query_text'];
            }
        }

        return array_slice($suggestions, 0, 9);
    }

    public function getSuggestionCollectionQuery($storeId)
    {
        /** @var \Magento\Search\Model\ResourceModel\Query\Collection $collection */
        $collection = $this->objectManager->create('\Magento\Search\Model\ResourceModel\Query\Collection');
        $collection = $collection->addStoreFilter($storeId)->setStoreId($storeId);

        $collection->getSelect()->where('num_results >= ' . $this->config->getMinNumberOfResults($storeId) . ' AND popularity >= ' . $this->config->getMinPopularity($storeId) . ' AND query_text != "__empty__"');

        $this->eventManager->dispatch('algolia_after_suggestions_collection_build', ['store' => $storeId, 'collection' => $collection]);

        return $collection;
    }
}
