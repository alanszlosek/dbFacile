dbFacile - The easily used PHP Database Abstraction Class
Version 0.4.1
Copyright (C) 2007-2008 Alan Szlosek

----

README

For now, this has limited information.

I. Installation

	1. Copy dbFacile.php to a location in your document root
	2. Include dbFacile.php in your PHP script
	3. Create a new instance of it, passing DB connection parameters
	4. Use.

II. Usage

	A. Instantiation

		$db = dbFacile::open(TYPE, DATABASE_NAME, USERNAME, PASSWORD, HOSTNAME);
		
		Valid TYPEs are: mssql, mysql, sqlite.
		In the future we'll also have: postgresql, pdo_mssql, pdo_mysql, pdo_postgresql, pdo_sqlite, pdo_sqlite2.
		
		When using SQLite, the open method only needs the file path to the database file.
		
		If you've already established a Database connection and still want to make use of dbFacile, you're in luck. Simple pass a handle to the current connection into the constructor.
		For mysql, you can also do the following because an empty call to mysql_connect() returns the current connection resource.
			$db = dbFacile::open('mysql', mysql_connect());

		You can also enable query logging by calling $db->logToFile(FILENAME).

	B. Fetching Data
	
		$rows = $db->fetch('select * from users') : Performs SQL query and returns two-dimensional array of rows and their named columns. In some cases this returns an iterator object that acts like an array of rows.
		foreach($rows as $row) {
			echo $row['email'] . '<br />';
		}
		This may not look any simpler than the standard PHP API functions, but having all the data available as a two-dimensional array is beneficial because you don't have to manually fetch each row. Treat the data as you would any array of data.

		$db->fetchAll('select * from users') : Performs SQL query and returns two-dimensional array of rows and their named columns. Currently, the MySQL driver uses unbuffered queries to perform the operation, which may be beneficial for large result-sets.
		
		$row = $db->fetchRow('select * from users') : Performs SQL query and returns one-dimensional associative array of fields and values.
		
		$email = $db->fetchCell('select email from users where name=?', array('Alan')) : Replaces ? found in query with a quoted and escaped version of the second paramter, performs SQL query and returns first field from first row in result.
		
		$emailAddresses = $db->fetchColumn('select email from users') : Performs SQL and returns the first column as a 1-dimensional array.

		$keyValue = $db->fetchKeyValue('select id,email from users') : Performs SQL and returns an associative array with users.id as the key and users.email as the value for each. If more than two fields are fetched, the first field is used as the key and an array of the remaining fields becomes the value.

	C. Inserting/Updating Data
	
		Assuming a users table exists with name and email fields:
	
		$id = $db->insert(array('name' => 'Aiden', 'email' => 'aiden@gmail.com'), 'users') : Inserts associative array of data into table, returns newly generated primary key.
		
		$db->update( array('name' => 'Aideen'), 'users', 'name="Aiden"') : Updates records in table with associative array of data satisfying where clause. Returns the number of affected rows.

		<strong>Note:</strong> As used above, all values present in the associative array will be escaped and quoted for use in the constructed query. If a field should <em>not</em> be quoted or escaped, wrap the value in an array:

		$db->update( array('name' => array('concat(first_name," ",last_name)')), 'users');

		The above MySql example will update the name field of each user record with a concatenation of the first_name and last_name fields. Note the reference to concat(), a MySql function.
		
		The update() method can also take additional parameters that act as a where clause. Here are some examples:
			$db->update( array('name' => 'John'), 'users', array('id=3'));
			$db->update( array('name' => 'John'), 'users', 'id=? and email=?', array(3, 'a@g.com'));
			$db->update( array('name' => 'John'), 'users', array('id=? and email=?', array(3, 'a@g.com')));

	D. Transactions
	
		The methods to use are beginTransaction(), commitTransaction() and rollbackTransaction().

III. Other

While creating dbFacile I DID search the web for other classes containing similar functionality to ensure that I wasn't wasting my time. A few of dbFacile's key features were lacking in all of them, and several had many features I would never use.

I evaluated:

* PDO
* MDB2
* ADODb
* ADODb Lite
* ODBC
* Creole

None had easy insert/update methods.

Lastly, fetching table columns and data types in Postgresql is a hassle! The information_schema construct is a stupidly convoluted idea.
