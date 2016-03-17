<?php

namespace Algolia\AlgoliaSearch\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class ImageType implements ArrayInterface
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'image',         'label' => __('Base Image')),
            array('value'=>'small_image',   'label' => __('Small Image')),
            array('value'=>'thumbnail',     'label' => __('Thumbnail')),
        );
    }
}