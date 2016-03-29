#! /usr/bin/env bash

# start services
service mysql start
service apache2 start

# set configuration variables & volumes
cd /var/www/htdocs
n98-magerun2 --root-dir=/var/www/htdocs config:set algoliasearch_credentials/credentials/application_id $APPLICATION_ID
n98-magerun2 --root-dir=/var/www/htdocs config:set algoliasearch_credentials/credentials/search_only_api_key $SEARCH_ONLY_API_KEY
n98-magerun2 --root-dir=/var/www/htdocs config:set algoliasearch_credentials/credentials/api_key $API_KEY
n98-magerun2 --root-dir=/var/www/htdocs config:set algoliasearch_credentials/credentials/index_prefix $INDEX_PREFIX
n98-magerun2 --root-dir=/var/www/htdocs config:set algoliasearch_credentials/credentials/is_instant_enabled "1"
n98-magerun2 --root-dir=/var/www/htdocs config:set web/unsecure/base_url $BASE_URL
n98-magerun2 --root-dir=/var/www/htdocs config:set web/secure/base_url $BASE_URL
bin/magento setup:store-config:set --base-url=$BASE_URL
bin/magento setup:upgrade

# reindex whole index
#n98-magerun --root-dir=/var/www/htdocs indexer:reindex algolia_search_indexer
#n98-magerun --root-dir=/var/www/htdocs indexer:reindex algolia_search_indexer_cat
#n98-magerun --root-dir=/var/www/htdocs indexer:reindex algolia_search_indexer_pages
#n98-magerun --root-dir=/var/www/htdocs indexer:reindex search_indexer_suggest

# do it after indexing so that var/log doesn't get created as root
n98-magerun2 --root-dir=/var/www/htdocs config:set dev/log/active 1

service apache2 stop
exec /usr/sbin/apache2ctl -D FOREGROUND