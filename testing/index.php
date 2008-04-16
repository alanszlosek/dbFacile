<?php
error_reporting(E_ALL);
include('/home/switchprog/hg/dbFacile/dbFacile.php');

$db = dbFacile::open('sqlite', '/home/switchprog/hg/dbFacile/testing/testing.db');
$db->logToFile('db.log');

$create = false;

if($create) {
	//$db->execute('create table test (b integer auto_increment, c text, primary key(b))');

	echo $db->insert(array('c' => 'aaa'), 'test') . '<br />';
	echo $db->insert(array('c' => 'bbb'), 'test') . '<br />';
	echo $db->insert(array('c' => 'ccc'), 'test') . '<br />';
	exit;
}

echo 'fetch<br />';
$rows = $db->fetchAll('select * from test');
var_dump($rows);

echo '<br /><br />fetch with ?<br />';
$rows = $db->fetchAll('select * from test where b > ?', array('1'));
var_dump($rows);

echo '<br /><br />fetchRow<br />';
$row = $db->fetchRow('select * from test where b = ?', array('1'));
var_dump($row);

echo '<br /><br />fetchCell<br />';
$row = $db->fetchCell('select * from test where b = ?', array('1'));
var_dump($row);

echo '<br /><br />fetchColumn<br />';
$row = $db->fetchColumn('select * from test where b > ?', array('1'));
var_dump($row);

echo '<br /><br />fetchKeyValue<br />';
$row = $db->fetchKeyValue('select * from test where b > ?', array('1'));
var_dump($row);

$db->update( array('c' => date('Y-m-d H:i:s')), 'test', 'b=?', array(3));
echo '<br /><br />after update<br />';
$rows = $db->fetchAll('select * from test where b > ?', array('1'));
var_dump($rows);

$db->insert( array('c' => 'new'), 'test');
echo '<br /><br />after insert<br />';
$row = $db->fetchRow('select * from test where c = ?', array('new'));
var_dump($row);

$db->insert( 'test', array('c' => 'new'));
echo '<br /><br />after insert (with switched params)<br />';
$row = $db->fetchRow('select * from test where c = ?', array('new'));
var_dump($row);

$db->delete('test', 'c=?', array('new'));
echo '<br /><br />after delete<br />';
$row = $db->fetchRow('select * from test where c = ?', array('new'));
var_dump($row);
?>
