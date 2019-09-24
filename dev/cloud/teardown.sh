#!/usr/bin/env php
<?php

use Magento\Framework\App\Bootstrap;
require '/app/bootstrap.php';

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$obj = $bootstrap->getObjectManager();

$algoliaHelper = $obj->get('\Algolia\AlgoliaSearch\Helper\AlgoliaHelper');

if ($algoliaHelper) {

    $indices = $algoliaHelper->listIndexes();

    foreach ($indices['items'] as $index) {
        $name = $index['name'];

        if (mb_strpos($name, $this->indexPrefix) === 0) {
            try {
                $algoliaHelper->deleteIndex($name);
                echo 'Index "' . $name . '" has been deleted.';
                echo "\n";
            } catch (Exception $e) {
                // Might be a replica
            }
        }
    }
}
