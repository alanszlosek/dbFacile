<?php
namespace dbFacile\tests;

require_once 'CommonTestQueries.php';

class PostgresqlTest extends CommonTestQueries
{
    public static function setUpBeforeClass()
    {
        $db = dbFacile::postgresql();
        $db->open('dbFacile', 'dbfacile', 'dbfacile');
        $db->execute('drop table if exists test');
        $db->execute('drop sequence test_id_seq');
        $db->execute('create sequence test_id_seq');
        $db->execute("create table test (b integer primary key DEFAULT nextval('test_id_seq'), c text)");
    }

    protected function setUp()
    {
        $db = dbFacile::postgresql();
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
