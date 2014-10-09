<?php
namespace dbFacile\tests;

require('CommonTestQueries.php');

class PostgresqlTest extends CommonTestQueries
{
    protected function setUpBeforeClass()
    {
        $db = \dbFacile\factory::postgresql();
        $db->open('dbFacile', 'dbfacile', 'dbfacile');
        $db->execute('drop table if exists users');
        $db->execute('drop sequence users_id_seq');
        $db->execute('drop table if exists tags');
        $db->execute('create sequence users_id_seq');
        $db->execute("create table users (id integer primary key DEFAULT nextval('users_id_seq'), name text, added integer)");
        $db->execute('create table tags (itemId integer primary key, tag text)');
        $this->db = $db;
    }
    protected function tearDown()
    {
        $this->db->close();
    }

}
