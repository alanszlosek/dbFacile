<?php
namespace dbFacile\tests;

require 'CommonTestQueries.php';

class MysqliTest extends CommonTestQueries
{
    public static function setUpBeforeClass()
    {
        $db = dbFacile::mysqli();
        $db->open('dbFacile', 'dbfacile', 'dbfacile');
        $db->execute('drop table if exists users');
        $db->execute('drop table if exists tags');
        $db->execute('create table users (id int(11) primary key auto_increment, name text, added int(11))');
        $db->execute('create table tags (itemId int(11) primary key, tag text)');
    }

    protected function setUp()
    {
        $db = dbFacile::mysqli();
        $db->open('dbFacile', 'dbfacile', 'dbfacile');
        $this->db = $db;
    }
    protected function tearDown()
    {
        $this->db->close();
    }
    public static function tearDownAfterClass()
    {
    }

}
