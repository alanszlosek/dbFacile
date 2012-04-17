<?php
require_once('../splitup/dbFacile_mysql.php');
require('TestSqlite3.php');

class TestMysql extends TestSqlite3 {

	public static function setUpBeforeClass() {
                $db = new dbFacile_mysql();
		$db->open('dbFacile', 'dbfacile', 'dbfacile');
		$db->execute('drop table if exists test');
		$db->execute('create table test (b int(11) primary key auto_increment, c text)');
	}

        protected function setUp() {
                $db = new dbFacile_mysql();
		$db->open('dbFacile', 'dbfacile', 'dbfacile');
		$this->db = $db;
	}
	protected function tearDown() {
		$this->db->close();
	}
	public static function tearDownAfterClass() {
	}

}

