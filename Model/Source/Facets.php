<?php

namespace Algolia\AlgoliaSearch\Model\Source;

use Algolia\AlgoliaSearch\Helper\Entity\Producthelper;
use Algolia\AlgoliaSearch\Model\Source\Contracts\AttributesGetter;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

/**
 * Algolia custom sort order field
 */
class Facets extends AbstractTable
{
    protected function getTableData()
    {
        $productHelper = $this->productHelper;

        return [
            'attribute' => [
                'label' => 'Attribute',
                'values' => function () use ($productHelper) {
                    $options = [];

                    foreach ($productHelper->getAdditionalAttributes() as $attribute) {
                        $options[$attribute['attribute']] = $attribute['attribute'];
                    }

                    return $options;
                }
            ],
            'type' => [
                'label' => 'Facet type',
                'values' => [
                    'conjunctive'   => 'Conjunctive',
                    'disjunctive'   => 'Disjunctive',
                    'slider'        => 'Slider',
                    'priceRanges'   => 'Price Ranges'
                ]
            ],
            'label' => [
                'label' => 'Label'
            ]
        ];
    }
}