<?php

namespace Algolia\AlgoliaSearch\Setup;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    private $config;

    private $defaultConfigData = [
        'algoliasearch_credentials/credentials/enable_backend' => '1',
        'algoliasearch_credentials/credentials/enable_frontend' => '1',
        'algoliasearch_credentials/credentials/application_id' => '',
        'algoliasearch_credentials/credentials/search_only_api_key' => '',
        'algoliasearch_credentials/credentials/api_key' => '',
        'algoliasearch_credentials/credentials/debug' => '0',
        'algoliasearch_credentials/credentials/index_prefix' => 'magento2_',
        'algoliasearch_credentials/credentials/is_popup_enabled' => '1',
        'algoliasearch_credentials/credentials/is_instant_enabled' => '0',

        'algoliasearch_autocomplete/autocomplete/nb_of_products_suggestions' => '6',
        'algoliasearch_autocomplete/autocomplete/nb_of_categories_suggestions' => '2',
        'algoliasearch_autocomplete/autocomplete/nb_of_queries_suggestions' => '1000',
        'algoliasearch_autocomplete/autocomplete/min_popularity' => '2',
        'algoliasearch_autocomplete/autocomplete/min_number_of_results' => '0',
        'algoliasearch_autocomplete/autocomplete/sections' => 'a:1:{s:18:"_1459261980310_310";a:3:{s:4:"name";s:5:"pages";s:5:"label";s:5:"Pages";s:11:"hitsPerPage";s:1:"2";}}',
        'algoliasearch_autocomplete/autocomplete/excluded_pages' => 'a:1:{s:18:"_1472030916250_250";a:1:{s:9:"attribute";s:8:"no-route";}}',
        'algoliasearch_autocomplete/autocomplete/render_template_directives' => '1',
        'algoliasearch_autocomplete/autocomplete/debug' => '0',

        'algoliasearch_instant/instant/instant_selector' => '.columns',
        'algoliasearch_instant/instant/facets' => 'a:3:{s:18:"_1432907948596_596";a:3:{s:9:"attribute";s:5:"price";s:4:"type";s:6:"slider";s:5:"label";s:5:"Price";}s:18:"_1432907963376_376";a:3:{s:9:"attribute";s:10:"categories";s:4:"type";s:11:"conjunctive";s:5:"label";s:10:"Categories";}s:17:"_1447846054032_32";a:3:{s:9:"attribute";s:5:"color";s:4:"type";s:11:"disjunctive";s:5:"label";s:6:"Colors";}}',
        'algoliasearch_instant/instant/max_values_per_facet' => '10',
        'algoliasearch_instant/instant/sorts' => 'a:3:{s:18:"_1432908018844_844";a:3:{s:9:"attribute";s:5:"price";s:4:"sort";s:3:"asc";s:5:"label";s:12:"Lowest price";}s:18:"_1432908022539_539";a:3:{s:9:"attribute";s:5:"price";s:4:"sort";s:4:"desc";s:5:"label";s:13:"Highest price";}s:18:"_1433768597454_454";a:3:{s:9:"attribute";s:10:"created_at";s:4:"sort";s:4:"desc";s:5:"label";s:12:"Newest first";}}',
        'algoliasearch_instant/instant/replace_categories' => '1',
        'algoliasearch_instant/instant/add_to_cart_enable' => '1',

        'algoliasearch_products/products/number_product_results' => '9',
        'algoliasearch_products/products/product_additional_attributes' => 'a:10:{s:18:"_1427959997377_377";a:4:{s:9:"attribute";s:4:"name";s:10:"searchable";s:1:"1";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}s:18:"_1427960012597_597";a:4:{s:9:"attribute";s:4:"path";s:10:"searchable";s:1:"1";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}s:18:"_1427961262735_735";a:4:{s:9:"attribute";s:10:"categories";s:10:"searchable";s:1:"1";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}s:18:"_1447846016385_385";a:4:{s:9:"attribute";s:5:"color";s:10:"searchable";s:1:"1";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}s:18:"_1427961324936_936";a:4:{s:9:"attribute";s:3:"sku";s:10:"searchable";s:1:"1";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}s:18:"_1427962021621_621";a:4:{s:9:"attribute";s:5:"price";s:10:"searchable";s:1:"0";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}s:18:"_1427977839554_554";a:4:{s:9:"attribute";s:11:"ordered_qty";s:10:"searchable";s:1:"0";s:11:"retrievable";s:1:"0";s:5:"order";s:9:"unordered";}s:18:"_1428566173508_508";a:4:{s:9:"attribute";s:9:"stock_qty";s:10:"searchable";s:1:"0";s:11:"retrievable";s:1:"0";s:5:"order";s:9:"unordered";}s:17:"_1433929490023_23";a:4:{s:9:"attribute";s:14:"rating_summary";s:10:"searchable";s:1:"0";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}s:18:"_1436178594492_492";a:4:{s:9:"attribute";s:10:"created_at";s:10:"searchable";s:1:"0";s:11:"retrievable";s:1:"0";s:5:"order";s:9:"unordered";}}',
        'algoliasearch_products/products/custom_ranking_product_attributes' => 'a:1:{s:18:"_1427960305274_274";a:2:{s:9:"attribute";s:11:"ordered_qty";s:5:"order";s:4:"desc";}}',
        'algoliasearch_products/products/show_suggestions_on_no_result_page' => '1',

        'algoliasearch_categories/categories/category_additional_attributes' => 'a:7:{s:18:"_1427960339954_954";a:4:{s:9:"attribute";s:4:"name";s:10:"searchable";s:1:"1";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}s:18:"_1427960354437_437";a:4:{s:9:"attribute";s:4:"path";s:10:"searchable";s:1:"1";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}s:18:"_1427961004989_989";a:4:{s:9:"attribute";s:11:"description";s:10:"searchable";s:1:"1";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}s:18:"_1427961205511_511";a:4:{s:9:"attribute";s:10:"meta_title";s:10:"searchable";s:1:"1";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}s:18:"_1427961216134_134";a:4:{s:9:"attribute";s:13:"meta_keywords";s:10:"searchable";s:1:"1";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}s:18:"_1427961216916_916";a:4:{s:9:"attribute";s:16:"meta_description";s:10:"searchable";s:1:"1";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}s:18:"_1427977778338_338";a:4:{s:9:"attribute";s:13:"product_count";s:10:"searchable";s:1:"0";s:11:"retrievable";s:1:"1";s:5:"order";s:9:"unordered";}}',
        'algoliasearch_categories/categories/custom_ranking_category_attributes' => 'a:1:{s:18:"_1427961035192_192";a:2:{s:9:"attribute";s:13:"product_count";s:5:"order";s:4:"desc";}}',
        'algoliasearch_categories/categories/show_cats_not_included_in_navigation' => '1',

        'algoliasearch_images/image/width' => '265',
        'algoliasearch_images/image/height' => '265',
        'algoliasearch_images/image/type' => 'image',

        'algoliasearch_queue/queue/active' => '0',
        'algoliasearch_queue/queue/number_of_job_to_run' => '10',

        'algoliasearch_analytics/analytics_group/enable' => '0',
        'algoliasearch_analytics/analytics_group/delay' => '3000',
        'algoliasearch_analytics/analytics_group/trigger_on_ui_interaction' => '1',
        'algoliasearch_analytics/analytics_group/push_initial_search' => '0',

        'algoliasearch_synonyms/synonyms_group/enable_synonyms' => '0',

        'algoliasearch_advanced/advanced/number_of_element_by_page' => '100',
        'algoliasearch_advanced/advanced/remove_words_if_no_result' => 'allOptional',
        'algoliasearch_advanced/advanced/partial_update' => '0',
        'algoliasearch_advanced/advanced/customer_groups_enable' => '0',
        'algoliasearch_advanced/advanced/make_seo_request' => '1',
        'algoliasearch_advanced/advanced/remove_branding' => '0',
        'algoliasearch_advanced/advanced/autocomplete_selector' => '.algolia-search-input',
        'algoliasearch_advanced/advanced/index_product_on_category_products_update' => '1',
    ];

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function getDefaultConfigData()
    {
        return $this->defaultConfigData;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /* SET DEFAULT CONFIG DATA */

        $table = $setup->getTable('core_config_data');
        $alreadyInserted = $setup->getConnection()->query('SELECT path, value FROM '.$table.' WHERE path LIKE "algoliasearch_%"')->fetchAll(\PDO::FETCH_KEY_PAIR);

        foreach ($this->defaultConfigData as $path => $value) {
            if (isset($alreadyInserted[$path])) {
                continue;
            }

            $this->config->saveConfig($path, $value, 'default', 0);
        }

        /* CREATE QUEUE DB TABLE */

        if (!$context->getVersion() || version_compare($context->getVersion(), '1.0.0') < 0) {
            $connection = $setup->getConnection();
            $table = $connection->newTable($setup->getTable('algoliasearch_queue'));

            $table->addColumn('job_id', $table::TYPE_INTEGER, 20,
                ['identity' => true, 'nullable' => false, 'primary' => true]);
            $table->addColumn('pid', $table::TYPE_INTEGER, 20, ['nullable' => true, 'default' => null]);
            $table->addColumn('class', $table::TYPE_TEXT, 50, ['nullable' => false]);
            $table->addColumn('method', $table::TYPE_TEXT, 50, ['nullable' => false]);
            $table->addColumn('data', $table::TYPE_TEXT, 5000, ['nullable' => false]);
            $table->addColumn('max_retries', $table::TYPE_INTEGER, 11, ['nullable' => false, 'default' => 3]);
            $table->addColumn('retries', $table::TYPE_INTEGER, 11, ['nullable' => false, 'default' => 0]);
            $table->addColumn('error_log', $table::TYPE_TEXT, null, ['nullable' => false]);
            $table->addColumn('data_size', $table::TYPE_INTEGER, 11, ['nullable' => true, 'default' => null]);

            $connection->createTable($table);
        }

        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            $connection = $setup->getConnection();
            $connection->changeColumn(
                $setup->getTable('algoliasearch_queue'),
                'data',
                'data',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => '2M',
                ]);
        }

        $setup->endSetup();
    }
}
