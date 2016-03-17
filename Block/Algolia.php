<?php

namespace Algolia\AlgoliaSearch\Block;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Locale\Currency;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Search\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;

class Algolia extends Template implements \Magento\Framework\Data\CollectionDataSourceInterface
{
    public $config;
    public $catalogSearchHelper;
    public $customerSession;
    public $storeManager;
    public $request;
    public $form;
    public $objectManager;
    public $registry;
    public $productHelper;

    public function __construct(ConfigHelper $config,
                                Data $catalogSearchHelper,
                                Template\Context $context,
                                Session $customerSession,
                                \Algolia\AlgoliaSearch\Helper\Entity\ProductHelper $productHelper,
                                StoreManagerInterface $storeManager,
                                Http $request,
                                FormKey $form,
                                Currency $currency,
                                ObjectManagerInterface $objectManager,
                                Registry $registry,
                                array $data = [])
    {
        $this->config               = $config;
        $this->catalogSearchHelper  = $catalogSearchHelper;
        $this->customerSession      = $customerSession;
        $this->storeManager         = $storeManager;
        $this->request              = $request;
        $this->form                 = $form;
        $this->objectManager        = $objectManager;
        $this->registry             = $registry;
        $this->productHelper        = $productHelper;
        $this->currency             = $currency;

        parent::__construct($context, $data);
    }

    public function getAssetRepository()
    {
        return $this->_assetRepo;
    }
}