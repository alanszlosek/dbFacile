<?php
namespace dbFacile\tests;

include_once 'CommonTestQueries.php';

/*
Would really like for tests to assert format of constructed SQL queries given varied base SQL and parameter arrays
*/

class Sqlite2Test extends CommonTestQueries
{
    public static function setUpBeforeClass()
    {
        $db = dbFacile::sqlite2();
        $db->open('sqlite2.db');
        $db->execute('create table test (b integer primary key auto_increment, c text)');
    }

    protected function setUp()
    {
        $db = dbFacile::sqlite2();
        $db->open('sqlite2.db');
        $this->db = $db;
    }
    protected function tearDown()
    {
        $this->db->close();
    }
    public static function tearDownAfterClass()
    {
        unlink('sqlite2.db');
    }

}
