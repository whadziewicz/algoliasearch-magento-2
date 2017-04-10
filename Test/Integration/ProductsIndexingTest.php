<?php

namespace Algolia\AlgoliaSearch\Test\Integration;

use Algolia\AlgoliaSearch\Model\Indexer\Product;
use Magento\CatalogInventory\Model\StockRegistry;

class ProductsIndexingTest extends IndexingTestCase
{
    public function testOnlyOnStockProducts()
    {
        $this->setConfig('cataloginventory/options/show_out_of_stock', 0);

        $this->setOneProductOutOfStock();

        /** @var Product $indexer */
        $indexer = $this->getObjectManager()->create('\Algolia\AlgoliaSearch\Model\Indexer\Product');

        $this->processTest($indexer, 'products', 185);
    }

    public function testIncludingOutOfStock()
    {
        $this->setConfig('cataloginventory/options/show_out_of_stock', 1);

        $this->setOneProductOutOfStock();

        /** @var Product $indexer */
        $indexer = $this->getObjectManager()->create('\Algolia\AlgoliaSearch\Model\Indexer\Product');

        $this->processTest($indexer, 'products', 186);
    }

    public function testDefaultIndexableAttributes()
    {
        $empty = serialize([]);

        $this->setConfig('algoliasearch_products/products/product_additional_attributes', $empty);
        $this->setConfig('algoliasearch_instant/instant/facets', $empty);
        $this->setConfig('algoliasearch_instant/instant/sorts', $empty);
        $this->setConfig('algoliasearch_products/products/custom_ranking_product_attributes', $empty);

        /** @var Product $indexer */
        $indexer = $this->getObjectManager()->create('\Algolia\AlgoliaSearch\Model\Indexer\Product');
        $indexer->executeRow(994);

        $this->algoliaHelper->waitLastTask();

        $results = $this->algoliaHelper->getObjects($this->indexPrefix.'default_products', array('994'));
        $hit = reset($results['results']);

        $defaultAttributes = array(
            'objectID',
            'name',
            'url',
            'visibility_search',
            'visibility_catalog',
            'categories',
            'categories_without_path',
            'thumbnail_url',
            'image_url',
            'in_stock',
            'price',
            'type_id',
            'algoliaLastUpdateAtCET',
        );

        foreach ($defaultAttributes as $key => $attribute) {
            $this->assertArrayHasKey($attribute, $hit, 'Products attribute "'.$attribute.'" should be indexed but it is not"');
            unset($hit[$attribute]);
        }

        $extraAttributes = implode(', ', array_keys($hit));
        $this->assertEmpty($hit, 'Extra products attributes ('.$extraAttributes.') are indexed and should not be.');
    }

    public function testNoProtocolImageUrls()
    {
        $additionAttributes = $this->configHelper->getProductAdditionalAttributes();
        $additionAttributes[] = [
            'attribute' => 'media_gallery',
            'searchable' => '0',
            'retrievable' => '1',
            'order' => 'unordered',
        ];

        $this->setConfig('algoliasearch_products/products/product_additional_attributes', serialize($additionAttributes));

        /** @var Product $indexer */
        $indexer = $this->getObjectManager()->create('\Algolia\AlgoliaSearch\Model\Indexer\Product');
        $indexer->executeRow(994);

        $this->algoliaHelper->waitLastTask();

        $results = $this->algoliaHelper->getObjects($this->indexPrefix.'default_products', array('994'));
        $hit = reset($results['results']);

        $this->assertStringStartsWith('//', $hit['image_url']);
        $this->assertStringStartsWith('//', $hit['thumbnail_url']);

        $this->assertArrayHasKey('media_gallery', $hit);

        foreach ($hit['media_gallery'] as $galleryImageUrl) {
            $this->assertStringStartsWith('//', $galleryImageUrl);
        }
    }

    private function setOneProductOutOfStock()
    {
        /** @var StockRegistry $stockRegistry */
        $stockRegistry = $this->getObjectManager()->create('Magento\CatalogInventory\Model\StockRegistry');
        $stockItem = $stockRegistry->getStockItemBySku('24-MB01');
        $stockItem->setIsInStock(false);
        $stockRegistry->updateStockItemBySku('24-MB01', $stockItem);
    }
}
