Algolia Search for Magento
==================

[Algolia Search](http://www.algolia.com) is a hosted full-text, numerical, and faceted search engine capable of delivering realtime results from the first keystroke.

This extension replaces the default search of Magento with a typo-tolerant, fast & relevant search experience backed by Algolia. It's based on [algoliasearch-client-php](https://github.com/algolia/algoliasearch-client-php), [autocomplete.js](https://github.com/algolia/autocomplete.js) and [instantsearch.js](https://github.com/algolia/instantsearch.js).

See features and benefits of [Algolia Search Extension for Magento](https://community.algolia.com/magento).

![Latest version](https://img.shields.io/badge/latest-0.8.3-green.svg)
![PHP >= 5.5.22](https://img.shields.io/badge/php-%3E=5.5.22-green.svg)

Documentation
--------------

Check out our documentation on [community.algolia.com/magento](https://community.algolia.com/magento/m2-documentation/).

Installation
------------

The easiest way to install the extension is to use [Composer](https://getcomposer.org/).

Run the following commands:

- ```$ composer require algolia/algoliasearch-magento-2:dev-master```
- ```$ bin/magento module:enable Algolia_AlgoliaSearch```
- ```$ bin/magento setup:upgrade && bin/magento setup:static-content:deploy```

Demo
--------------

You can check out our [live demo](https://magento.algolia.com) of Magento 1 integration. 
Magento 2 demo is coming!

### Auto-completion menu

Offer End-Users immediate access to your whole catalog from the dropdown menu, whatever your number of categories or attributes.

![demo](gifs/autocomplete.gif)

### Instant search results page

Have your search results page, navigation and pagination updated in realtime, after each keystroke.

![demo](gifs/instantsearch.gif)


