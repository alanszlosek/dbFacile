<?php
namespace dbFacile\tests;

require('CommonTestQueries.php');

/*
Would really like for tests to assert format of constructed SQL queries given varied base SQL and parameter arrays
*/

class Sqlite3Test extends CommonTestQueries
{
    protected function setUp()
    {
        $db = \dbFacile\factory::sqlite3();
        $db->open('sqlite3.db');
        // drop tables if exist
        $db->execute('drop table if exists users');
        $db->execute('drop table if exists tags');
        $db->execute('create table users (id integer primary key autoincrement, name text, added integer)');
        $db->execute('create table tags (itemId integer primary key, tag text)');
        $this->db = $db;

        $this->doInsertions();
    }
    protected function tearDown()
    {
        $this->db->close();
        unlink('sqlite3.db');
    }

}
