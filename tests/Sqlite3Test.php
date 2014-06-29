<?php
namespace dbFacile\tests;

include_once 'CommonTestQueries.php';
use dbFacile\dbFacile;

/*
Would really like for tests to assert format of constructed SQL queries given varied base SQL and parameter arrays
*/

class Sqlite3Test extends CommonTestQueries
{
    public static function setUpBeforeClass()
    {
        $db = dbFacile::sqlite3();
        $db->open('sqlite3.db');
        $db->execute('create table users (id integer primary key autoincrement, name text, added integer)');
        $db->execute('create table tags (itemId integer primary key, tag text)');
    }

    protected function setUp()
    {
        $db = dbFacile::sqlite3();
        $db->open('sqlite3.db');
        $this->db = $db;
    }
    protected function tearDown()
    {
        $this->db->close();
    }
    public static function tearDownAfterClass()
    {
        unlink('sqlite3.db');
    }

}
