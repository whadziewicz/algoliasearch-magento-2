#!/usr/bin/env bash
set -e # exit when error
set -x # debug messages

npm install algoliasearch-extensions-bundle@latest --save
cp node_modules/algoliasearch-extensions-bundle/dist/algoliaBundle.min.js* ../../view/frontend/web/
cp node_modules/algoliasearch-extensions-bundle/dist/algoliaAdminBundle.min.js* ../../view/adminhtml/web/
