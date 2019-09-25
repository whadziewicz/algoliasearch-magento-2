<?php

use Magento\Framework\App\Bootstrap;
require '/app/app/bootstrap.php';

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$obj = $bootstrap->getObjectManager();

$algoliaHelper = $obj->get('\Algolia\AlgoliaSearch\Helper\AlgoliaHelper');

/**
 * @param $algoliaHelper Algolia\AlgoliaSearch\Helper\AlgoliaHelper
 * @param array $indices
 */
function deleteIndexes($algoliaHelper, array $indices)
{
    foreach ($indices['items'] as $index) {
        $name = $index['name'];

        if (mb_strpos($name, getenv('MAGENTO_CLOUD_ENVIRONMENT')) === 0) {
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

if ($algoliaHelper) {
    $indices = $algoliaHelper->listIndexes();
    if (count($indices) > 0) {
        deleteIndexes($algoliaHelper, $indices);
    }

    $replicas = $algoliaHelper->listIndexes();
    if (count($replicas) > 0) {
        deleteIndexes($algoliaHelper, $replicas);
    }
}
