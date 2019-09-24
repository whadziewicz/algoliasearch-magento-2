#! /usr/bin/env bash

php echo 'Saving APPID...'
php bin/magento config:set algoliasearch_credentials/credentials/application_id "${ALGOLIA_APPLICATION_ID}"
php echo 'Saving Search Key...'
php bin/magento config:set algoliasearch_credentials/credentials/search_only_api_key "${ALGOLIA_SEARCH_API_KEY}"
php echo 'Saving API key...'
php bin/magento config:set algoliasearch_credentials/credentials/api_key "${ALGOLIA_API_KEY}"
php echo 'Saving prefix...'
php bin/magento config:set algoliasearch_credentials/credentials/index_prefix "${MAGENTO_CLOUD_ENVIRONMENT}_"
php echo 'Saving enable instant...'
php bin/magento config:set algoliasearch_instant/instant/is_instant_enabled "1"

php bin/magento indexer:reindex algolia_products
php bin/magento indexer:reindex algolia_categories
php bin/magento indexer:reindex algolia_pages
php bin/magento indexer:reindex algolia_suggestions
php bin/magento indexer:reindex algolia_additional_sections