<?php
require_once('../src/dbFacile.php');
require('Sqlite3Test.php');

class MysqliTest extends Sqlite3Test {

	public static function setUpBeforeClass() {
		$db = dbFacile::mysqli();
		$db->open('dbFacile', 'dbfacile', 'dbfacile');
		$db->execute('drop table if exists test');
		$db->execute('drop table if exists test2');
		$db->execute('create table test (b int(11) primary key auto_increment, c text)');
		$db->execute('create table test2 (b int(11) primary key, c text)');
	}

	protected function setUp() {
		$db = dbFacile::mysqli();
		$db->open('dbFacile', 'dbfacile', 'dbfacile');
		$this->db = $db;
	}
	protected function tearDown() {
		$this->db->close();
	}
	public static function tearDownAfterClass() {
	}

}

