<?php
namespace dbFacile\tests;

date_default_timezone_set('America/Los_Angeles');
include_once '../src/dbFacile/dbFacile.php';

/*
Would really like for tests to assert format of constructed SQL queries given varied base SQL and parameter arrays
*/

class CommonTestQueries extends \PHPUnit_Framework_TestCase
{
    protected $db;

    protected $rows1 = array(
        array('id' => 1, 'name' => 'aaa'),
        array('id' => 2, 'name' => 'bbb'),
        array('id' => 3, 'name' => 'ccc')
    );
    protected $rows2 = array(
        array('itemId' => '1', 'tag' => 'Hello')
    );

    public function testInsertReportsKey()
    {
        $db = $this->db;
        $row = $check = $this->rows1[0];
        unset($row['id']);
        $a = $db->insert('users', $row);
        $this->assertEquals($a, $check['id']);

        $row = $check = $this->rows1[1];
        unset($row['id']);
        $a = $db->insert('users', $row);
        $this->assertEquals($a, $check['id']);

        $row = $check = $this->rows1[2];
        unset($row['id']);
        $a = $db->insert('users', $row);
        $this->assertEquals($a, $check['id']);
    }

    public function testInsertNoAuto()
    {
        $db = $this->db;
        $row = array(
            'itemId' => 123,
            'tag' => 'testing'
        );
        $a = $db->insert('tags', $row);
        $this->assertEquals(true, $a);

        $row2 = $db->fetchRow('select * from tags where itemId=123');
        $this->assertEquals($row, $row2);
    }

    public function testInsertEmpty()
    {
    }

    public function testFetchAll()
    {
        $db = $this->db;
        $rows = $db->fetchRows('select id,name from users order by id');
        foreach ($rows as $i => $row) {
            $this->assertEquals( $this->rows1[ $i ], $row);
        }
    }

    public function testFetchRowsIn()
    {
        $db = $this->db;
        $rows = $db->fetchRows('select id,name from users where id IN ', array(1,3), ' order by id');
        $this->assertEquals($this->rows1[0], $rows[0]);
        $this->assertEquals($this->rows1[2], $rows[1]);
    }

    public function testQueryParts()
    {
        $db = $this->db;
        $id = 1;
        $name = 'aaa';
        $rows = $db->fetchRows('select id,name from users where id>', $id, ' AND name<>', $name, ' order by id');
        foreach ($rows as $i => $row) {
            $this->assertEquals( $this->rows1[ $i+1 ], $row);
        }
    }

    public function testFetchRow()
    {
        $db = $this->db;
        $row = $db->fetchRow('select id,name from users where id = ', 2);
        $this->assertEquals( $this->rows1[ 1 ], $row);
    }

    public function testFetchCell()
    {
        $db = $this->db;
        $row = $db->fetchCell('select id,name from users where id = ', 3);
        $this->assertEquals($row, 3);
    }

    public function testFetchColumn()
    {
        $db = $this->db;
        $row = $db->fetchColumn('select id from users where id > 1 order by id');
        $this->assertEquals($row, array(2,3));
    }

    public function testFetchKeyValue()
    {
        $db = $this->db;
        $row = $db->fetchKeyValue('select id,name from users where id > 1 order by id');
        $data = array(
            2 => 'bbb',
            3 => 'ccc'
        );
        $this->assertEquals($row, $data);
    }

    public function testFetchKeyedRows()
    {
        $db = $this->db;
        $row = $db->fetchKeyedRows('select id,name from users where id > 1 order by id');
        $data = array(
            2 => $this->rows1[1],
            3 => $this->rows1[2],
        );
        $this->assertEquals($row, $data);
    }

    // Update non-existent row?
    public function testUpdate()
    {
        $db = $this->db;
        $data = array('name' => date('Y-m-d H:i:s'));
        $db->update('users', $data, 'id=', 3);

        $row = $db->fetchRow('select id,name from users where id=3');
        $data['id'] = 3;
        $this->assertEquals($data, $row);
    }

    public function testUpdateNumeric()
    {
        $db = $this->db;
        $data = array('added', 5498, 'name' => 'Germy');
        $db->update('users', $data, 'id=', 3);

        $row = $db->fetchRow('select name,added from users where id=3');
        $this->assertEquals(5498, $row['added']);
        $this->assertEquals('Germy', $row['name']);
    }

    public function testInsert()
    {
        $db = $this->db;
        $data = array('name' => 'new');
        $db->insert('users', $data);

        $row = $db->fetchRow('select id,name from users where id=4');
        $data['id'] = 4;
        $this->assertEquals($data, $row);
    }

    public function testDeleteWhereString()
    {
        $db = $this->db;
        $db->delete('users', 'id=', 2);
        $row = $db->fetchRow('select id,name from users where id=', 2);
        $this->assertEquals(false, $row);
    }

    public function testDeleteWhereArray()
    {
        $db = $this->db;
        $data = array('name' => 'new');
        $ret = $db->delete('users', $data);
        $this->assertEquals(1, $ret);
        $row = $db->fetchRow('select id,name from users where name=', 'new');
        $this->assertEquals(false, $row);
    }
}
