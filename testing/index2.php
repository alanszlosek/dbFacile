<?php
// this file is for testing non-query operations
error_reporting(E_ALL);
include('/home/switchprog/hg/dbFacile/dbFacile.php');

// does it pull the schema properly
$db = dbFacile::open('sqlite', '/home/switchprog/hg/dbFacile/testing/ecommerce.db');
$db->logToFile('db2.log');
$db->cacheSchemaToFile('schema.cache.php');

// does the schema include primary keys

// does the schema include foreign keys

// does dbFacile correctly utilize a dbHandle?

var_dump($db->schema);



?>
