<?php
error_reporting(E_ALL);
include('/home/switchprog/hg/dbFacile/dbFacile.php');

$db = dbFacile::open('sqlite', '/home/switchprog/hg/dbFacile/testing/ecommerce.db');
$db->logToFile('db2.log');
$db->cacheSchemaToFile('schema.cache.php');

var_dump($db->schema);



?>
