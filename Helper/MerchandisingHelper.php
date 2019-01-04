<?php

namespace Algolia\AlgoliaSearch\Helper;

use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;

class MerchandisingHelper
{
    /** @var Data */
    private $coreHelper;

    /** @var ProductHelper */
    private $productHelper;

    /** @var AlgoliaHelper */
    private $algoliaHelper;

    public function __construct(
        Data $coreHelper,
        ProductHelper $productHelper,
        AlgoliaHelper $algoliaHelper
    ) {
        $this->coreHelper = $coreHelper;
        $this->productHelper = $productHelper;
        $this->algoliaHelper = $algoliaHelper;
    }

    public function saveQueryRule($storeId, $entityId, $rawPositions, $entityType, $query = null)
    {
        if ($this->coreHelper->isIndexingEnabled($storeId) === false) {
            return;
        }

        $productsIndexName = $this->coreHelper->getIndexName($this->productHelper->getIndexNameSuffix(), $storeId);

        $positions = $this->transformPositions($rawPositions);

        $rule = [
            'objectID' => $this->getQueryRuleId($entityId, $entityType),
            'description' => 'MagentoGeneratedQueryRule',
            'condition' => [
                'pattern' => '',
                'anchoring' => 'is',
                'context' => 'magento-' . $entityType . '-' . $entityId,
            ],
            'consequence' => [
                'promote' => $positions,
            ],
        ];

        if (!is_null($query) && $query != '') {
            $rule['condition']['pattern'] = $query;
        }

        // Not catching AlgoliaSearchException for disabled query rules on purpose
        // It displays correct error message and navigates user to pricing page
        $this->algoliaHelper->saveRule($rule, $productsIndexName);
    }

    public function deleteQueryRule($storeId, $entityId, $entityType)
    {
        if ($this->coreHelper->isIndexingEnabled($storeId) === false) {
            return;
        }

        $productsIndexName = $this->coreHelper->getIndexName($this->productHelper->getIndexNameSuffix(), $storeId);
        $ruleId = $this->getQueryRuleId($entityId, $entityType);

        // Not catching AlgoliaSearchException for disabled query rules on purpose
        // It displays correct error message and navigates user to pricing page
        $this->algoliaHelper->deleteRule($productsIndexName, $ruleId);
    }

    private function transformPositions($positions)
    {
        $transformedPositions = [];

        foreach ($positions as $objectID => $position) {
            $transformedPositions[] = [
                'objectID' => (string) $objectID,
                'position' => $position,
            ];
        }

        return $transformedPositions;
    }

    private function getQueryRuleId($entityId, $entityType)
    {
        return 'magento-' . $entityType . '-' . $entityId;
    }
}
