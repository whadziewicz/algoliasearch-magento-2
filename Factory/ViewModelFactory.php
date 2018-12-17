<?php

namespace Algolia\AlgoliaSearch\Factory;

use Magento\Backend\Block\Template;
use Magento\Framework\ObjectManagerInterface;

class ViewModelFactory
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param Template $block
     *
     * @return object
     */
    public function create($block)
    {
        $templateName = $block->getTemplate();
        $classNameSuffix = $this->transformTemplateToClassNameSuffix($templateName);

        // Turns "Algolia\AlgoliaSearch\Block\Adminhtml\BaseAdminTemplate"
        // to "Algolia\AlgoliaSearch\ViewModel\Adminhtml\[[specificViewModelName]]"
        $viewModelClassName = str_replace(
            ['\Block\\', 'BaseAdminTemplate'],
            ['\ViewModel\\', $classNameSuffix],
            get_class($block)
        );

        return $this->objectManager->create($viewModelClassName);
    }

    /**
     * It turns template name (eg. "Algolia_AlgoliaSearch::support/overview.phtml")
     * to class name suffix (eg. "Support\Overview"),
     * which is later on used to create ViewModel object
     *
     * @param string $templateName
     *
     * @return string
     */
    private function transformTemplateToClassNameSuffix($templateName)
    {
        $className = str_replace(['Algolia_AlgoliaSearch::', '.phtml'], '', $templateName);

        $classNameParts = explode('/', $className);
        $classNameParts = array_map(function ($part) {
            return ucfirst($part);
        }, $classNameParts);

        $className = implode('\\', $classNameParts);

        return $className;
    }
}
