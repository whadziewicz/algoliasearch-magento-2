<?php

namespace Algolia\AlgoliaSearch\Helper\Entity;

class PageHelper extends BaseHelper
{
    protected function getIndexNameSuffix()
    {
        return '_pages';
    }

    public function getIndexSettings($storeId)
    {
        return array(
            'attributesToIndex'         => array('slug', 'name', 'unordered(content)'),
            'attributesToSnippet'       => array('content:7')
        );
    }

    public function getPages($storeId)
    {
        /** @var \Magento\Cms\Model\Page $pageModel */
        $pageModel = $this->objectManager->create('\Magento\Cms\Model\Page');
        $magento_pages = $pageModel->getCollection()->addFieldToFilter('is_active', 1);

        $excluded_pages = array_values($this->config->getExcludedPages());

        foreach ($excluded_pages as &$excluded_page)
            $excluded_page = $excluded_page['pages'];

        $pages = array();

        foreach ($magento_pages as $page)
        {
            if (in_array($page->getIdentifier(), $excluded_pages))
                continue;

            $page_obj = array();

            $page_obj['slug'] = $page->getIdentifier();
            $page_obj['name'] = $page->getTitle();

            $page->setStoreId($storeId);

            if (! $page->getId())
                continue;

            $page_obj['objectID'] = $page->getId();

            $pageHelper = $this->objectManager->create('\Magento\Cms\Helper\Page');
            $page_obj['url'] = $pageHelper->getPageUrl($page->getId());
            $page_obj['content'] = $this->strip($page->getContent());

            $pages[] = $page_obj;
        }

        return $pages;
    }
}