dbFacile
Trying to make a more helpful PHP Database Abstraction Class

Introduction
====

Features:

* Support for MySQL, MySQLi, Sqlite3, Postgresql
* Query placeholders: ? and #. Values associated with the former are quoted and escaped. Those associated with the latter are inserted as-is. With those you can have unquoted number and SQL functions in your queries.

Note: there's still no way to prevent quoting and escaping of parameters used withing insert() or update() method calls.

Setup and Installation
====

1. Include the appropriate dbFacile_*.php file for the DBMS you're using
2. Create a new instance of the class: $db = new dbFacile_mysqli()
3. Call open(DATABASE, USERNAME, PASSWORD, HOST), passing the appropriate parameters

Usage
====

Example
----

    // Connect using MySQLi module, and a persistent connection
    $db = new dbFacile_mysqli();
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

    $keyValue = $db->fetchKeyValue('select id,email from users');

Inserting and Updating Data
----

Assuming a users table exists with name and email fields:

Inserts associative array of data into table, returns newly generated primary key.
Returns false if the insertion failed.
Returns 0 if no key was generated.

    $id = $db->insert(array('name' => 'Aiden', 'email' => 'aiden@gmail.com'), 'users');


Updates rows, setting name to 'Aideen' where name was 'Aiden'

    $db->update( array('name' => 'Aideen'), 'users', 'name="Aiden"');

Note: As used above, all values present in the associative array will be escaped and quoted for use in the update query.

The update() method can also take additional parameters that act as a where clause. Here are some examples:

    $db->update( array('name' => 'John'), 'users', 'id=3');
    $db->update( array('name' => 'John'), 'users', 'id=? and email=?', array(3, 'a@g.com'));

Transactions
----
	
Most driver classes implement the following methods: beginTransaction(), commitTransaction(), rollbackTransaction()
