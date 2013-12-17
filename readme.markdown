dbFacile - Trying to make a more helpful PHP Database Abstraction Class

Introduction
====

Features:

* Support for MySQL, MySQLi, Sqlite3, Postgresql
* Query placeholders: ? and #. Values associated with the former are quoted and escaped. Those associated with the latter are inserted as-is, for unquoted numbers and SQL functions in your queries.

Note: there's still no way to prevent quoting and escaping of parameters used withing insert() or update() method calls.

Setup and Installation
====

This was updated on Dec 16, 2013. We now use something resembling the factory pattern for instantiating the correct dbFacile driver subclass. There was an issue where certain versions of the mysqli driver were missing a method; using a factory allows us to elegantly work around this. More info: https://github.com/alanszlosek/dbFacile/pull/8

1. Include dbFacile.php
2. Get an instance of the correct driver class: $db = dbFacile::mysqli()
3. Call $db->open(DATABASE, USERNAME, PASSWORD, HOST), passing the appropriate parameters

Usage
====

Connection Example
----

Connect using MySQLi module, and a persistent connection

    $db = dbFacile::mysqli();
    $db->open('testDB', 'testUser', 'testPass', 'p:192.168.1.15');
		
Additional Notes
----

* When using sqlite3, pass 1 parameter to open(), the database file path.
* To re-use an existing connection resource, pass the handle to the constructor when you instantiate your driver class.

Fetching Data
----

Returns an array of rows. Each row is an associative array of field=>value.
If there are no rows, and empty array is returned (so you won't get PHP notices if you try to loop over the result).

    $rows = $db->fetchRows('select * from users')
    foreach($rows as $row) {
        echo $row['email'] . '<br />';
    }

Returns associative array of fields and values. If row doesn't exist, return value may be null or false. Probably should unify this.

    $row = $db->fetchRow('select * from users');

Returns first field from this final query: "select email from users where name='Alan'". null if row doesn't exist.

    $email = $db->fetchCell('select email from users where name=?', array('Alan'));

Returns first field from this final query: "select email from users where date_created<1231231234"

    $email = $db->fetchCell('select email from users where date_created<#', array('unix_timestamp()'));

Returns a one-dimensional, numerically-indexed array of column values. Empty array if there are no rows.

    $emailAddresses = $db->fetchColumn('select email from users');

Returns an associative array with users.id as the key and users.email as the value for each. Specify more than 2 fields and the key points to a numerically-indexed array of the remaining field values

    $idToEmail = $db->fetchKeyValue('select id,email from users');

Inserting and Updating Data
----

Assuming a users table exists with name and email fields:

Inserts associative array of data into table, returns newly generated primary key.
Returns false if the insertion failed.
Returns 0 if no key was generated.

    $data = array('name' => 'Aiden', 'email' => 'aiden@gmail.com');
    $id = $db->insert($data, 'users');


Updates rows, setting name to 'Aideen' where name was 'Aiden'

    $data = array('name' => 'Aideen');
    $db->update($data, 'users', 'name="Aiden"');

You can also pass an associative array as the where clause:

    $data = array('name' => 'Aideen');
    $where = array('name' => 'Aiden');
    $db->update($data, 'users', $where);

Note: As used above, all values present in the associative arrays will be escaped and quoted.

If you need more control over your where clause, use a combination of a string and parameters:

    $data = array('name' => 'John');
    // SQL will be "update users set name='John' where id>=3 and email='a@g.com'"
    $db->update($data, 'users', 'id>=# and email=?', array(3, 'a@g.com'));

Transactions
----
	
Most driver classes implement the following methods: beginTransaction(), commitTransaction(), rollbackTransaction()
