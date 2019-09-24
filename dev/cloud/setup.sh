#! /usr/bin/env bash

php bin/magento config:set algoliasearch_credentials/credentials/application_id "${ALGOLIA_APPLICATION_ID}"
php bin/magento config:set algoliasearch_credentials/credentials/search_only_api_key "${ALGOLIA_SEARCH_API_KEY}"
php bin/magento config:set algoliasearch_credentials/credentials/api_key "${ALGOLIA_API_KEY}"
php bin/magento config:set algoliasearch_credentials/credentials/index_prefix "${MAGENTO_CLOUD_ENVIRONMENT}_"
php bin/magento config:set algoliasearch_instant/instant/is_instant_enabled "1"

php bin/magento indexer:reindex algolia_products algolia_categories algolia_pages algolia_suggestions algolia_additional_sections