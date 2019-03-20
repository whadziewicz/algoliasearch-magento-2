<?php

namespace Algolia\AlgoliaSearch\DataProvider\Query;

use Algolia\AlgoliaSearch\Model\ResourceModel\Query\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;

class QueryDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $query) {
            $this->loadedData[$query->getId()] = $query->getData();
        }

        $data = $this->dataPersistor->get('query');

        if (!empty($data)) {
            $query = $this->collection->getNewEmptyItem();
            $query->setData($data);
            $this->loadedData[$query->getId()] = $query->getData();
            $this->dataPersistor->clear('query');
        }

        return $this->loadedData;
    }
}
