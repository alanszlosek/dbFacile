<?php
include('../splitup/dbFacile_sqlite3.php');

class sqlite3_test extends PHPUnit_Framework_TestCase {
	protected static $db;

	protected $rows1 = array(
		array('b' => 1, 'c' => 'aaa'),
		array('b' => 2, 'c' => 'bbb'),
		array('b' => 3, 'c' => 'ccc')
	);
	protected $rows2 = array(
		array('id' => '1', 'name' => 'Hello')
	);

        public static function setUpBeforeClass() {
		unlink('sqlite3.db');
                $db = new dbFacile_sqlite3();
		$db->open('sqlite3.db');

		/*
		$db->execute('create table test (b integer auto_increment, c text, primary key(b))');
		$db->execute('create table test2 (id integer, name text, primary key (id,name))');
		*/

		$db->execute('create table test (b integer primary key autoincrement, c text)');
		$db->execute('create table test2 (id integer, name text, primary key (id,name))');
		Main::$db = $db;
	}

        public function testInsertReportsKey() {
		$db = Main::$db;
		$row = $this->rows1[0];
		unset($row['b']);
		$a = $db->insert($row, 'test');
		$this->assertEquals($a, 1);

		$row = $this->rows1[1];
		unset($row['b']);
		$a = $db->insert($row, 'test');
		$this->assertEquals($a, 2);

		$row = $this->rows1[2];
		unset($row['b']);
		$a = $db->insert($row, 'test');
		$this->assertEquals($a, 3);

		// begin support for multi-field primary keys
		$b = $db->insert( $this->rows2[0], 'test2');
		$this->assertEquals($b, 1);
	}

	public function testFetchAll() {
		$db = Main::$db;
		$rows = $db->fetchAll('select * from test order by b');
		foreach($rows as $i => $row) {
			$this->assertEquals( $this->rows1[ $i ], $row);
		}
	}

	public function testPlaceholders() {
		$db = Main::$db;
		$rows = $db->fetchAll('select * from test where b > ? order by b', array('1'));
		foreach($rows as $i => $row) {
			$this->assertEquals( $this->rows1[ $i+1 ], $row);
		}
	}

	public function testFetchRow() {
		$db = Main::$db;
		$row = $db->fetchRow('select * from test where b = ?', array('2'));
		$this->assertEquals( $this->rows1[ 1 ], $row);
	}

	public function testFetchCell() {
		$db = Main::$db;
		$row = $db->fetchCell('select b,c from test where b = ?', array('3'));
		$this->assertEquals($row, 3);
	}

	public function testFetchColumn() {
		$db = Main::$db;
		$row = $db->fetchColumn('select b from test where b > 1 order by b');
		$this->assertEquals($row, array(2,3));
	}

	public function testFetchKeyValue() {
		$db = Main::$db;
		$row = $db->fetchKeyValue('select b,c from test where b > 1 order by b');
		$data = array(
			2 => 'bbb',
			3 => 'ccc'
		);
		$this->assertEquals($row, $data);
	}

	public function testUpdate() {
		$db = Main::$db;
		$data = array('c' => date('Y-m-d H:i:s'));
		$db->update($data, 'test', 'b=?', array(3));

		$row = $db->fetchRow('select b,c from test where b=3');
		$data['b'] = 3;
		$this->assertEquals($data, $row);
	}

	public function testInsert() {
		$db = Main::$db;
		$data = array('c' => 'new');
		$db->insert($data, 'test');

		$row = $db->fetchRow('select b,c from test where b=4');
		$data['b'] = 4;
		$this->assertEquals($data, $row);
	}

	public function testDeleteWhereString() {
		$db = Main::$db;
		$db->delete('test', 'b=?', array('2'));
		$row = $db->fetchRow('select b,c from test where b=?', array('2'));
		$this->assertEquals(false, $row);
	}

	public function testDeleteWhereArray() {
		$db = Main::$db;
		$data = array('c' => 'new');
		$db->delete('test', $data);
		$row = $db->fetchRow('select b,c from test where b=?', array('new'));
		$this->assertEquals(false, $row);
	}
}

