<?php
include_once('../src/dbFacile_sqlite3.php');

/*
Would really like for tests to assert format of constructed SQL queries given varied base SQL and parameter arrays
*/

class TestSqlite3 extends PHPUnit_Framework_TestCase {
	protected $db;

	protected $rows1 = array(
		array('b' => 1, 'c' => 'aaa'),
		array('b' => 2, 'c' => 'bbb'),
		array('b' => 3, 'c' => 'ccc')
	);
	protected $rows2 = array(
		array('id' => '1', 'name' => 'Hello')
	);

	public static function setUpBeforeClass() {
                $db = new dbFacile_sqlite3();
		$db->open('sqlite3.db');
		$db->execute('create table test (b integer primary key autoincrement, c text)');
	}

        protected function setUp() {
                $db = new dbFacile_sqlite3();
		$db->open('sqlite3.db');
		$this->db = $db;
	}
	protected function tearDown() {
		$this->db->close();
	}
	public static function tearDownAfterClass() {
		unlink('sqlite3.db');
	}

        public function testInsertReportsKey() {
		$db = $this->db;
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
	}

	public function testFetchAll() {
		$db = $this->db;
		$rows = $db->fetchAll('select * from test order by b');
		foreach($rows as $i => $row) {
			$this->assertEquals( $this->rows1[ $i ], $row);
		}
	}

	public function testPlaceholders() {
		$db = $this->db;
		$rows = $db->fetchAll('select * from test where b > ? order by b', array('1'));
		foreach($rows as $i => $row) {
			$this->assertEquals( $this->rows1[ $i+1 ], $row);
		}
	}

	public function testFetchRow() {
		$db = $this->db;
		$row = $db->fetchRow('select * from test where b = ?', array('2'));
		$this->assertEquals( $this->rows1[ 1 ], $row);
	}

	public function testFetchCell() {
		$db = $this->db;
		$row = $db->fetchCell('select b,c from test where b = ?', array('3'));
		$this->assertEquals($row, 3);
	}

	public function testFetchColumn() {
		$db = $this->db;
		$row = $db->fetchColumn('select b from test where b > 1 order by b');
		$this->assertEquals($row, array(2,3));
	}

	public function testFetchKeyValue() {
		$db = $this->db;
		$row = $db->fetchKeyValue('select b,c from test where b > 1 order by b');
		$data = array(
			2 => 'bbb',
			3 => 'ccc'
		);
		$this->assertEquals($row, $data);
	}

	public function testUpdate() {
		$db = $this->db;
		$data = array('c' => date('Y-m-d H:i:s'));
		$db->update($data, 'test', 'b=?', array(3));

		$row = $db->fetchRow('select b,c from test where b=3');
		$data['b'] = 3;
		$this->assertEquals($data, $row);
	}

	public function testInsert() {
		$db = $this->db;
		$data = array('c' => 'new');
		$db->insert($data, 'test');

		$row = $db->fetchRow('select b,c from test where b=4');
		$data['b'] = 4;
		$this->assertEquals($data, $row);
	}

	public function testDeleteWhereString() {
		$db = $this->db;
		$db->delete('test', 'b=?', array('2'));
		$row = $db->fetchRow('select b,c from test where b=?', array('2'));
		$this->assertEquals(false, $row);
	}

	public function testDeleteWhereArray() {
		$db = $this->db;
		$data = array('c' => 'new');
		$db->delete('test', $data);
		$row = $db->fetchRow('select b,c from test where b=?', array('new'));
		$this->assertEquals(false, $row);
	}
}

