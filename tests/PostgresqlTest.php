<?php
require_once('../src/dbFacile_postgresql.php');
require_once('Sqlite3Test.php');

class PostgresqlTest extends Sqlite3Test {

	public static function setUpBeforeClass() {
                $db = new dbFacile_postgresql();
		$db->open('dbFacile', 'dbfacile', 'dbfacile');
		$db->execute('drop table if exists test');
		$db->execute('drop sequence test_id_seq');
		$db->execute('create sequence test_id_seq');
		$db->execute("create table test (b integer primary key DEFAULT nextval('test_id_seq'), c text)");
	}

        protected function setUp() {
                $db = new dbFacile_postgresql();
		$db->open('dbFacile', 'dbfacile', 'dbfacile');
		$this->db = $db;
	}
	protected function tearDown() {
		$this->db->close();
	}
	public static function tearDownAfterClass() {
	}

}

