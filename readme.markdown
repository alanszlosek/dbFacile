dbFacile - Trying to make a more helpful PHP Database Abstraction Class

Introduction
====

Note: Oct 2014 brought lots of breaking changes. We dropped support for query placeholders (question marks and pound-signs), and now enforce that table name be the first param to many methods.

* Support for MySQL, MySQLi, Sqlite3, Postgresql
* When you write queries, alternate between strings and values to be escaped+quoted

Setup and Installation
====

This was updated on Dec 16, 2013. We now use something resembling the factory pattern for instantiating the correct dbFacile driver subclass. There was an issue where certain versions of the mysqli driver were missing a method; using a factory allows us to elegantly work around this. More info: https://github.com/alanszlosek/dbFacile/pull/8

1. Install with PHP composer
2. Get an instance of the correct driver class: $db = \dbFacile\factory::mysqli()
3. Call $db->open(DATABASE, USERNAME, PASSWORD, HOST), passing the appropriate parameters

Usage
====

Connection Example
----

Connect using MySQLi module, and a persistent connection

    $db = \dbFacile\factory::mysqli();
    $db->open('testDB', 'testUser', 'testPass', 'p:192.168.1.15');
		
Additional Notes
----

* When using sqlite3, pass 1 parameter to open(), the database file path.
* To re-use an existing connection resource, pass the handle to the constructor when you instantiate your driver class.

Fetching Data
----

**Fetch Rows**

Returns an array of rows. Each row is an associative array of field=>value. If there are no rows, an empty array is returned (so you won't get PHP notices if you try to loop over the result).

    $rows = $db->fetchRows('select * from users')
    foreach($rows as $row) {
        echo $row['email'] . '<br />';
    }

**Fetch a single row**

Returns associative array of fields and values. If row doesn't exist, return value may be null or false. Probably should unify this.

    $row = $db->fetchRow('select * from users');

**Fetch a single value from a single row**

Returns first field from the first row.

    $email = $db->fetchCell("select email from users where name='Alan'");

**Fetch a column of data**

Returns a one-dimensional, numerically-indexed array of column values. Empty array if there are no rows.

    $emailAddresses = $db->fetchColumn('select email from users');

**Fetch data as a key value pair**

Returns an associative array where keys come from users.id and values come from users.email.

    $idToEmail = $db->fetchKeyValue('select id,email from users');
    /*
    $idToEmail = array(
        344 => 'john@john.com',
        798 => 'brenda@brenda.com',
    );
    */

**Fetch rows, where each is indexed by a column value**

Returns an associative array using values from users.id as keys and values from users.email as corresponding values.

    $idToEmail = $db->fetchKeyedRows('select id,name,email from users');
    /*
    $idToEmail = array(
        344 => array(
            'id' => 344,
            'name' => 'John',
            'email' => 'john@john.com'
        ),
    );
    */

**Fetch using query parameters**

Returns the first user row where date_created is earlier than now.

    $row = $db->fetchRow('select email from users where date_created<', time());

Inserting and Updating Data
----

Assuming a users table exists with name and email fields:

**Insert a row**

Inserts using an associative array of data, returns newly generated primary key (if table generates a key automatically).
Returns false if the insertion failed.
Returns 0 if no key was generated.

    $data = array('name' => 'Aiden', 'email' => 'aiden@gmail.com');
    $id = $db->insert('users', $data);

**Update rows**

Updates rows, setting name to 'Aideen' where name was 'Aiden'

    $set_data = array('name' => 'Aideen');
    $db->update('users', $set_data, 'name=', 'Aiden');

You can also pass an associative array as the where clause:

    $set_data = array('name' => 'Aideen');
    $where = array('name' => 'Aiden');
    $db->update('users', $set_data, $where);

Note: As used above, all values present in the associative arrays will be escaped and quoted.

If you need more control over your where clause, use a combination of a string and parameters:

    $set_data = array('name' => 'John');
    $id = 3;
    $email = 'a@g.com';
    // SQL will be "update users set name='John' where id>='3' and email='a@g.com'"
    $db->update('users', $set_data, 'id>=', $id, ' and email=', $email);

**Delete rows**

Using a string where clause. "delete from users where id<'123'"

    $cutoff = 123;
    $num_deleted = $db->delete('users', 'id<', $cutoff);

Using an associative-array/hash for the where clause. "delete from users where id IN (1,2,3)"

    $where_data = array('id' => array(1,2,3));
    $num_deleted = $db->delete('users', $where_data);

Transactions
----
	
Most driver classes implement the following methods: beginTransaction(), commitTransaction(), rollbackTransaction()
