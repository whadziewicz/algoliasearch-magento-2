<?php
namespace Algolia\AlgoliaSearch\Block;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\Entity\ProductHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Locale\Currency;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Search\Helper\Data as CatalogSearchHelper;

class Algolia extends Template
{
    protected $config;
    protected $catalogSearchHelper;
    protected $customerSession;
    protected $storeManager;
    protected $objectManager;
    protected $registry;
    protected $productHelper;
    protected $currency;

    protected $priceKey;

    public function __construct(
        Template\Context $context,
        ConfigHelper $config,
        CatalogSearchHelper $catalogSearchHelper,
        Session $customerSession,
        ProductHelper $productHelper,
        Currency $currency,
        Registry $registry,
        array $data = []
    ) {
        $this->config = $config;
        $this->catalogSearchHelper = $catalogSearchHelper;
        $this->customerSession = $customerSession;
        $this->productHelper = $productHelper;
        $this->currency = $currency;
        $this->registry = $registry;

        parent::__construct($context, $data);
    }

    public function getConfigHelper()
    {
        return $this->config;
    }

    public function getProductHelper()
    {
        return $this->productHelper;
    }

    public function getCatalogSearchHelper()
    {
        return $this->catalogSearchHelper;
    }

    public function getCurrencySymbol()
    {
        return $this->currency->getCurrency($this->getCurrencyCode())->getSymbol();
    }
    public function getCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function getPriceKey()
    {
        if ($this->priceKey === null) {
            $groupId = $this->customerSession->getCustomer()->getGroupId();
            $currencyCode = $this->getCurrencyCode();
            $this->priceKey = $this->config->isCustomerGroupsEnabled($this->_storeManager->getStore()->getStoreId()) ? '.' . $currencyCode . '.group_' . $groupId : '.' . $currencyCode . '.default';
        }
        return $this->priceKey;
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getStoreId();
    }
    
    public function getCurrentCategory()
    {
        return $this->registry->registry('current_category');
    }
}