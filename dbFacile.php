<?php
/*
dbFacile - A Database API that should have existed from the start
Version 0.4.3
See LICENSE for license details.
*/

abstract class dbFacile {
	protected $connection; // handle to Database connection
	protected $query;
	protected $result;
	protected $logFile;
	protected $fields;
	protected $fieldNames;
	protected $schemaNameField;
	protected $schemaTypeField;

	// these flags are not yet implemented (20080630)
	// these flags may not ever be implemented. caching shouldn't occur at this level. (20080924)
	public $filterInvalidFields = false;
	
	public $schema = array(); // this will probably become protected
	//public static $schemaCache; // filename to use when saving/reading full database schema cache
	public static $instance; // last created instance
	public static $instances = array(); // for holding more than 1 instance

	// 2007-08-25
	protected $foreignKeys; // array('TABLE'=>array('FIELD'=>'TO_TABLE.FIELD'))
	protected $reverseForeignKeys; // a data structure that holds the reverse of normal foreign key mappings

	// implement these methods when creating driver subclasses
	// need to add _open() to the mix somehow
	public abstract function beginTransaction();
	public abstract function commitTransaction();
	public abstract function rollbackTransaction();
	public abstract function close();
	protected abstract function _affectedRows();
	protected abstract function _error();
	protected abstract function _escapeString($string);
	protected abstract function _fetch();
	protected abstract function _fetchAll();
	protected abstract function _fetchRow();
	protected abstract function _fields($table);
	protected abstract function _foreignKeys($table);
	protected abstract function _lastID();
	protected abstract function _numberRows();
	protected abstract function _query($sql);
	protected abstract function _rewind($result);
	protected abstract function _tables();

	public function __construct($handle = null) {
		$this->connection = $handle;
		$this->query = $this->result = null;
		$this->parameters = array();
		//$this->numberRecords = 0; // probably no longer needed

		$this->fields = array();
		$this->fieldNames = array();
		$this->primaryKeys = array();
		$this->foreignKeys = array();
		$this->reverseForeignKeys = null;
		$this->logFile = null;
		$this->schema = null;

		dbFacile::$instance = $this;
		// construct new dbFacile::$instances element using host and database name?
	}

	public function __destruct() {
		if($this->logFile)
			fclose($this->logFile);
	}
	
	public static function open($type, $database, $user = '', $password = '', $host = 'localhost') {
		// try to use PDO if available
		switch($type) {
			case 'mssql':
			case 'mysql':
			case 'postgresql':
				$name = 'dbFacile_' . $type;
				if(is_resource($database)) {
					$o = new $name($database);
				}
				if(is_string($database)) {
					$o = new $name();
					$o->_open($database, $user, $password, $host);
				}
				return $o;
				break;
			case 'sqlite':
				if(is_resource($database)) {
					$o = new dbFacile_sqlite($database);
				}
				if(is_string($database)) {
					$o = new dbFacile_sqlite();
					$o->_open($database);
				}
				return $o;
				break;
		}
	}

	public function logToFile($file, $method = 'a+', $start = "Log opened\n\n", $end = "Log closed\n\n") {
		$this->logFile = fopen($file, $method);
	}

	/*
	 * Performs a query using the given string.
	 * Used by the other _query functions.
	 * */
	public function execute($sql, $parameters = array()) {
		$this->query = $sql;
		$this->parameters = $parameters;

		$fullSql = $this->makeQuery($sql, $parameters);

		if($this->logFile)
			$time_start = microtime(true);

		$this->result = $this->_query($fullSql); // sets $this->result
		
		if($this->logFile) {
			$time_end = microtime(true);
			fwrite($this->logFile, date('Y-m-d H:i:s') . "\n" . $fullSql . "\n" . number_format($time_end - $time_start, 8) . " seconds\n\n");
		}

		if(!$this->result && (error_reporting() & 1))
			trigger_error('dbFacile - Error in query: ' . $this->query . ' : ' . $this->_error(), E_USER_WARNING);

		if($this->result) {
			return true;
		} else {
			return false;
		}
	}
	
	/*
	 * Alias for insert
	 * */
	public function add($data, $table) {
		return $this->insert($data, $table);
	}

	/*
	 * Passed an array and a table name, it attempts to insert the data into the table.
	 * Check for boolean false to determine whether insert failed
	 * */
	public function insert($data, $table) {
		// the following block swaps the parameters if they were given in the wrong order.
		// it allows the method to work for those that would rather it (or expect it to)
		// follow closer with SQL convention:
		// insert into the TABLE this DATA
		if(is_string($data) && is_array($table)) {
			$tmp = $data;
			$data = $table;
			$table = $tmp;
			//trigger_error('dbFacile - Parameters passed to insert() were in reverse order, but it has been allowed', E_USER_NOTICE);
		}
		// appropriately quote input data
		// remove invalid fields
		if($this->filterInvalidFields)
			$data = $this->filterFields($data, $table);

		// wrap quotes around values that need them
		// actually, shouldn't quote data yet, since PDO does it for us
		//$data = $this->quoteData($data);

		$sql = 'insert into ' . $table . ' (' . implode(',', array_keys($data)) . ') values(' . implode(',', $this->placeHolders($data)) . ')';

		$this->beginTransaction();	
		if($this->execute($sql, $data)) {
			$id = $this->_lastID($table);
			$this->commitTransaction();
			return $id;
		} else {
			$this->rollbackTransaction();
			return false;
		}
	}

	/*
	 * Passed an array, table name, where clause, and placeholder parameters, it attempts to update a record.
	 * Returns the number of affected rows
	 * */
	public function update($data, $table, $where = null, $parameters = array()) {
		// the following block swaps the parameters if they were given in the wrong order.
		// it allows the method to work for those that would rather it (or expect it to)
		// follow closer with SQL convention:
		// update the TABLE with this DATA
		if(is_string($data) && is_array($table)) {
			$tmp = $data;
			$data = $table;
			$table = $tmp;
			trigger_error('dbFacile - The first two parameters passed to update() were in reverse order, but it has been allowed', E_USER_NOTICE);
		}
		if($this->filterInvalidFields)
			$data = $this->filterFields($data, $table);
		// wrap quotes around values that need them
		//$data = $this->quoteData($data);

		// need field name and placeholder value
		// but how merge these field placeholders with actual $parameters array for the where clause
		$sql = 'update ' . $table . ' set ';
		foreach($data as $key => $value) {
			$sql .= $key . '=:' . $key . ',';
		}
		$sql = substr($sql, 0, -1); // strip off last comma

		if($where) {
			$sql .= ' where ' . $where;
			$data = array_merge($data, $parameters);
		}

		$this->execute($sql, $data);
		return $this->_affectedRows();
	}

	public function delete($table, $where = null, $parameters = array()) {
		$sql = 'delete from ' . $table;
		if($where) {
			$sql .= ' where ' . $where;
		}
		$this->execute($sql, $parameters);
		return $this->_affectedRows();
	}

	/*
	 * Fetches all of the rows (associatively) from the last performed query.
	 * Most other retrieval functions build off this
	 * */
	public function fetchAll($sql, $parameters = array()) {
		//$sql = $this->transformPlaceholders(func_get_args());
		$this->execute($sql, $parameters);
		if($this->_numberRows()) {
			return $this->_fetchAll();
		}
		// no records, thus return empty array
		// which should evaluate to false, and will prevent foreach notices/warnings 
		return array();
	}
	/*
	 * This is intended to be the method used for large result sets.
	 * It is intended to return an iterator, and act upon buffered data.
	 * */
	public function fetch($sql, $parameters = array()) {
		$this->execute($sql, $parameters);
		return $this->_fetch();
	}

	/*
	 * Like fetch(), accepts any number of arguments
	 * The first argument is an sprintf-ready query stringTypes
	 * */
	public function fetchRow($sql = null, $parameters = array()) {
		if($sql != null)
			$this->execute($sql, $parameters);
		if($this->result)
			return $this->_fetchRow();
		return null;
	}

	/*
	 * Fetches the first call from the first row returned by the query
	 * */
	public function fetchCell($sql, $parameters = array()) {
		if($this->execute($sql, $parameters)) {
			return array_shift($this->_fetchRow()); // shift first field off first row
		}
		return null;
	}

	/*
	 * This method is quite different from fetchCell(), actually
	 * It fetches one cell from each row and places all the values in 1 array
	 * */
	public function fetchColumn($sql, $parameters = array()) {
		if($this->execute($sql, $parameters)) {
			$cells = array();
			foreach($this->_fetchAll() as $row) {
				$cells[] = array_shift($row);
			}
			return $cells;
		} else {
			return array();
		}
	}

	/*
	 * Should be passed a query that fetches two fields
	 * The first will become the array key
	 * The second the key's value
	 */
	public function fetchKeyValue($sql, $parameters = array()) {
		if($this->execute($sql, $parameters)) {
			$data = array();
			foreach($this->_fetchAll() as $row) {
				$key = array_shift($row);
				if(sizeof($row) == 1) { // if there were only 2 fields in the result
					// use the second for the value
					$data[ $key ] = array_shift($row);
				} else { // if more than 2 fields were fetched
					// use the array of the rest as the value
					$data[ $key ] = $row;
				}
			}
			return $data;
		} else
			return array();
	}

	/*
	 * Return query and other debugging data if error_reporting to right settings
	 * */
	private function debugging() {
		if(in_array(error_reporting(), array(E_ALL))) {
			return $this->query;
		}
	}

	/*
	 * This combines a query and parameter array into a final query string for execution
	 * PDO drivers don't need to use this
	 */
	protected function makeQuery($sql, $parameters) {
		// bypass extra logic if we have no parameters
		if(sizeof($parameters) == 0)
			return $sql;
		$parts = explode('?', $sql);
		$query = array_shift($parts); // put on first part
	
		$parameters = $this->prepareData($parameters);
		$newParams = array();
		// replace question marks first
		foreach($parameters as $key => $value) {
			if(is_numeric($key)) {
				$query .= $value . array_shift($parts);
				//$newParams[ $key ] = $value;
			} else {
				$newParams[ ':' . $key ] = $value;
			}
		}
		// now replace name place-holders
		// replace place-holders with quoted, escaped values
		/*
		var_dump($query);
		var_dump($newParams);exit;
		*/

		// sort newParams in reverse to stop substring squashing
		krsort($newParams);
		$query = str_replace( array_keys($newParams), $newParams, $query);
		//die($query);
		return $query;
	}

	/*
	 * Used by insert() and update() to filter invalid fields from a data array
	 * */
	private function filterFields($data, $table) {
		$this->buildSchema(); // builds if not previously built
		$fields = $this->schema[ $table ]['fields'];
		foreach($data as $field => $value) {
			if(!array_key_exists($field, $fields))
				unset($data[ $field ]);
		}
		return $data;
	}
	
	/*
	 * This should be protected and overloadable by driver classes
	 */
	private function prepareData($data) {
		$values = array();

		foreach($data as $key=>$value) {
			$escape = true;
			// don't quote or esc
			/*
			if(substr($key,-1) == '=') {
				$escape = false;
				$key = substr($key, 0, strlen($key)-1);
			}
			*/
			// new way to determine whether to quote and escape
			// if value is an array, we treat it as a "decorator" that tells us not to escape the
			// value contained in the array
			if(is_array($value) && !is_object($value)) {
				$escape = false;
				$value = array_shift($value);
			}
			// it's not right to worry about invalid fields in this method because we may be operating on fields
			// that are aliases, or part of other tables through joins 
			//if(!in_array($key, $columns)) // skip invalid fields
			//	continue;
			if($escape)
				$values[$key] = "'" . $this->_escapeString($value) . "'";
			else
				$values[$key] = $value;
		}
		return $values;
	}
	
	/*
	 * Given a data array, this returns an array of placeholders
	 * These may be question marks, or ":email" type
	 */
	private function placeHolders($values) {
		$data = array();
		foreach($values as $key => $value) {
			if(is_numeric($key))
				$data[] = '?';
			else
				$data[] = ':' . $key;
		}
		return $data;
	}
	
	// SCHEMA QUERYING METHODS

	public function getTables() {
		$tables = array();
		foreach($this->_tables() as $row) {
			$tables[] = array_shift($row);
		}
		return $tables;
	}

	/*
	 * Returns an array, indexed by field name with values of true or false.
	 * True means the field should be quoted
	 * */
	private function getTableInfo($table) {
		$rows = $this->_schema($table);
		if($rows) {
			$fields = array();
			foreach($rows as $row) {
				$type = strtolower(preg_replace('/\(.*\)/', '', $row[ $this->schemaTypeField ])); // remove size specifier
				$name = $row[ $this->schemaNameField ];
				if($row[ $this->schemaPrimaryKeyField ]) {
					$this->primaryKeys[ $table ] = $name;
				}
				$fields[$name] = $type;
			}
			//$this->fieldsToQuote[$table] = $fieldsToQuote;
			$this->fieldNames[$table] = array_keys($fields);
			$this->fieldTypes[$table] = $fields;
		} else
			die('dbFacile - Table "' . $table . '" does not exist');
	}
	
	/*
	 * Would really like to build the entire schema at once and cache it
	 * rather than doing table-by-table
	 */
	public function buildSchema() {
		if($this->schema != null)
			return;
		$schema = $this->schema;
		foreach($this->_tables() as $row) {
			$schema[ array_shift($row) ] = array(
				'fields' => array(),
				'keys' => array(),
				'foreignKeys' => array(),
				'primaryKey' => null
			);
		}

		foreach($schema as $table => $other) {
			$fields = $this->_fields($table);
			$schema[ $table ]['fields'] = $fields;
			foreach($fields as $name => $field) {
				if($field['primaryKey'])
					$schema[ $table ]['primaryKey'] = $name;
			}
			$schema[ $table ]['foreignKeys'] = $this->_foreignKeys($table);
		}
		
		$this->schema = $schema;
	}
	
	public function cacheSchemaToFile($file) {
		if($this->schema == null) {
 			if(file_exists($file)) {
 				require($file);
			} else {
				$this->buildSchema();

				$data = '<?php $this->schema = ' . var_export($this->schema, true) . '; ?>';
				file_put_contents($file, $data);
			}
		}
	}

	/*
	 * Returns an array of the table's field names
	 * */
/*
	public function getFieldNames($table) {
		if(!array_key_exists($table, $this->schema))
			$this->buildSchema();
			//$this->getTableInfo($table);
		return array_keys($this->schema[$table]['fields']);
	}

	public function getFieldTypes($table) {
		if(!array_key_exists($table, $this->fieldTypes))
			$this->getTableInfo($table);
		return $this->fieldTypes[$table];
	}

	public function getPrimaryKey($table) {
		if(!array_key_exists($table, $this->primaryKeys))
			$this->getTableInfo($table);
		return $this->primaryKeys[$table];
	}

	public function getForeignKeys($table) {
		if(!array_key_exists($table, $this->foreignKeys))
			$this->foreignKeys[$table] = $this->_foreignKeys($table);
		return $this->foreignKeys[$table];
	}

	public function getReverseForeignKeys() {
		if($this->reverseForeignKeys) {
			return $this->reverseForeignKeys;
		}
		$this->reverseForeignKeys = array();
		foreach($this->getTables() as $table) {
			foreach($this->getForeignKeys($table) as $from => $to) {
				if(!array_key_exists($to, $this->reverseForeignKeys)) {
					$this->reverseForeignKeys[ $to ] = array();
				}
				$this->reverseForeignKeys[ $to ][ $table ] = $table . '.' . $from;
			}
		}
		return $this->reverseForeignKeys;
	}
*/
}

/*
 * To create a new driver, implement the following:
 * protected _open(...)
 * protected _query($sql, $parameters)
 * protected _escapeString
 * protected _error
 * protected _affectedRows
 * protected _numberRows
 * protected _fetch
 * protected _fetchAll
 * protected _fetchRow
 * protected _lastID
 * protected _schema
 * public beginTransaction
 * public commitTransaction
 * public rollbackTransaction
 * public close
 * */

class dbFacile_mssql extends dbFacile {
	public function beginTransaction() {
		//mssql_query('begin', $this->connection);
	}

	public function commitTransaction() {
		//mssql_query('commit', $this->connection);
	}

	public function close() {
		mssql_close($this->connection);
	}

	public function rollbackTransaction() {
		//mssql_query('rollback', $this->connection);
	}

	protected function _affectedRows() {
		return mssql_rows_affected($this->connection);
	}

	protected function _error() {
		return mssql_get_last_message();
	}

	protected function _escapeString($string) {
		$s = stripslashes($string);
		$s = str_replace( array("'", "\0"), array("''", '[NULL]'), $s);
		return $s;
	}

	protected function _fetch() {
		// use mysql_data_seek to get to row index
		return $this->_fetchAll();
	}

	protected function _fetchAll() {
		$data = array();
		while($row = mssql_fetch_assoc($this->result)) {
			$data[] = $row;
		}
		//mssql_free_result($this->result);
		// rewind?
		return $data;
	}

	protected function _fetchRow() {
		return mssql_fetch_assoc($this->result);
	}

	protected function _fields($table) {
		$this->execute('select COLUMN_NAME,DATA_TYPE from INFORMATION_SCHEMA.COLUMNS where TABLE_NAME=?', array($table), false);
		return $this->_fetchAll();
	}

	protected function _foreignKeys($table) {
	}

	protected function _lastID() {
		return $this->fetchCell('select scope_identity()');
	}

	protected function _open($database, $user, $password, $host) {
		$this->connection = mssql_connect($host, $user, $password);
		if($this->connection)
			mssql_select_db($database, $this->connection);
		//$this->buildSchema();
		return $this->connection;
	}

	protected function _numberRows() {
		return mssql_num_rows($this->result);
	}

	protected function _primaryKey($table) {
	}

	protected function _query($sql) {
		return mssql_query($sql, $this->connection);
	}

	protected function _rewind($result) {
	}
	
	protected function _tables() {
	}
} // mssql

class dbFacile_mysql extends dbFacile {
	private $database;

	public function beginTransaction() {
		mysql_query('begin', $this->connection);
	}

	public function close() {
		mysql_close($this->connection);
	}

	public function commitTransaction() {
		mysql_query('commit', $this->connection);
	}

	public function rollbackTransaction() {
		mysql_query('rollback', $this->connection);
	}

	protected function _affectedRows() {
		return mysql_affected_rows($this->connection);
	}

	protected function _error() {
		return mysql_error($this->connection);
	}

	protected function _escapeString($string) {
		return mysql_real_escape_string($string);
	}

	protected function _fetch() {
		// use mysql_data_seek to get to row index
		return $this->_fetchAll();
	}

	protected function _fetchAll() {
		$data = array();
		while($row = mysql_fetch_assoc($this->result)) {
			$data[] = $row;
		}
		//mysql_free_result($this->result);
		// rewind?
		return $data;
	}

	protected function _fetchRow() {
		return mysql_fetch_assoc($this->result);
	}

	protected function _fields($table) {
		$fields = array();
		$this->execute('describe ' . $table, array(), false);
		foreach($this->_fetchAll() as $row) {
			$type = strtolower(preg_replace('/\(.*\)/', '', $row['Type'])); // remove size specifier
			$name = $row['Field'];
			$fields[ $name ] = array('type' => $type, 'primaryKey' => ($row['Key'] == 'PRI'));
		}
		return $fields;
	}

	protected function _foreignKeys($table) {
		$version = mysql_get_server_info($this->connection);
		$parts = explode('-', $version); // strip off non-numeric portion
		$parts = explode('.', $parts[0]); // split numeric parts
		
		// return because this functionality is incomplete. see comment below
		return array();
		
		if($parts[0] == '5' && ($parts[1] > '1' || ($parts[1] == '1' && $parts[2] >= '10'))) { // we can only fetch foreign-key info in 5.1.10+
		
			// this hasn't been tested yet, so please don't expect this to work
			// i'd appreciate it if someone with mysql 5.1 installed could test this and send me the results
			$keys = array();
			$q = 'select CONSTRAINT_SCHEMA as foreignTable,CONSTRAINT_NAME as foreignField, UNIQUE_CONSTRAINT_SCHEMA as localField from INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS where TABLE_NAME=?';
			$this->execute($q, array($table), false);
			foreach($this->_fetchAll() as $row) {
				$keys[ $row['localField'] ] = array('table' => $row['foreignTable'], 'field' => $row['to']);
			}
			
			return $keys;

		} else {
			return array();
		}
	}

	protected function _lastID() {
		return mysql_insert_id($this->connection);
	}

	protected function _numberRows() {
		return mysql_num_rows($this->result);
	}
	
	// user, password, database, host
	protected function _open($database, $user, $password, $host) {
		$this->database = $database;
		$this->connection = mysql_connect($host, $user, $password);
		if($this->connection)
			mysql_select_db($database, $this->connection);
		//$this->buildSchema();
		return $this->connection;
	}

	protected function _query($sql) {
		return mysql_query($sql, $this->connection);
	}

	protected function _rewind($result) {
	}

	protected function _tables() {
		// this should probably use 'show tables' if the mysql version is older and doesn't support the information_schema
		if(!$this->execute("select TABLE_NAME from information_schema.TABLES where TABLE_SCHEMA=? order by TABLE_NAME", array($this->database), false))
			die('Failed to get tables');
		return $this->_fetchAll();
	}
} // mysql

/*
class dbFacile_mysqli extends dbFacile {
	public function __construct($handle = null) {
		parent::__construct();
		$this->schemaNameField = 'Field';
		$this->schemaTypeField = 'Type';
		if($handle != null)
			$this->connection = $handle;
	}
	// user, password, database, host
	protected function _open($args) {
		if(!$args[3])
			$args[3] = 'localhost';
		$this->connection = mysqli_connect($args[3], $args[0], $args[1], $args[2]);
	}
	protected function _query($query) {
		$this->result = mysqli_query($this->connection, $query);
	}
	protected function _escapeString($string) {
		return mysqli_real_escape_string($string);
	}
	protected function _error() {
		return mysqli_error($this->connection);
	}
	protected function _numberRecords() {
		if(mysqli_affected_rows($this->connection)) { // for insert, update, delete
			$this->numberRecords = mysqli_affected_rows($this->connection);
		} elseif(!is_bool($this->result)) { // for selects
			$this->numberRecords = mysqli_num_rows($this->result);
		} else { // will be boolean for create, drop, and other
			$this->numberRecords = 0;
		}
	}
	protected function _fetch() {
		return $this->_fetchAll();
	}
	protected function _fetchAll() {
		$data = array();
		for($i = 0; $i < $this->numberRecords; $i++) {
			$data[] = mysqli_fetch_assoc($this->result);
		}
		mysqli_free_result($this->result);
		return $data;
	}
	protected function _fetchRow() {
		return mysqli_fetch_assoc($this->result);
	}
	protected function _lastID() {
		return mysqli_insert_id($this->connection);
	}
	protected function _schema($table) {
		$this->execute('describe ' . $table);
		return $this->_fetchAll();
	}
	public function beginTransaction() {
		mysqli_autocommit($this->connection, false);
	}
	public function commitTransaction() {
		mysqli_commit($this->connection);
		mysqli_autocommit($this->connection, true);
	}
	public function rollbackTransaction() {
		mysqli_rollback($this->connection);
		mysqli_autocommit($this->connection, true);
	}
	public function close() {
		mysqli_close($this->connection);
	}
} // mysqli
*/

/*
class dbFacile_postgresql extends dbFacile {
	public function __construct($handle = null) {
		parent::__construct($handle);
		$this->schemaNameField = 'column_name';
		$this->schemaTypeField = 'data_type';
	}
	// user, password, database, host
	protected function _open($database, $user, $password, $host) {
		//die("host=$host dbname=$database user=$user");
		$this->connection = pg_connect("host=$host dbname=$database port=5432 user=$user password=$password");
		return $this->connection;
	}
	protected function _query($sql, $parameters) {
		$sql = $this->makeQuery($sql, $parameters);
		return pg_query($this->connection, $sql);
	}
	protected function _escapeString($string) {
		return pg_escape_string($string);
	}
	protected function _error() {
		return pg_last_error($this->connection);
	}
	protected function _affectedRows() {
		return pg_affected_rows($this->result);
	}
	protected function _numberRows() {
		return pg_num_rows($this->result);
	}
	protected function _fetch() {
		return $this->_fetchAll();
	}
	protected function _fetchAll() {
		return $data;
		$data = array();
		while($row = pg_fetch_assoc($this->result)) {
			$data[] = $row;
		}
		pg_free_result($this->result);
		// rewind?
		return $data;
	}
	protected function _fetchRow() {
		return pg_fetch_assoc($this->result);
	}
	protected function _lastID($table) {
		$sequence = $this->fetchCell("SELECT relname FROM pg_class WHERE relkind = 'S' AND relname LIKE '" . $table . "_%'");
		if(strlen($sequence))
			return $this->fetchCell('select last_value from ' . $sequence);
		return 0;
	}
	protected function _schema($table) {
		$this->execute('select column_name,split_part(data_type, \' \', 1) as data_type from information_schema.columns where table_name = \'' . $table . '\' order by ordinal_position');
		return $this->_fetchAll();
	}
	public function beginTransaction() {
		pg_query($this->connection, 'begin');
	}
	public function commitTransaction() {
		pg_query($this->connection, 'commit');
	}
	public function rollbackTransaction() {
		pg_query($this->connection, 'rollback');
	}
	public function close() {
		pg_close($this->connection);
	}
} // postgresql
*/

class dbFacile_sqlite extends dbFacile {
	public function beginTransaction() {
		sqlite_query($this->connection, 'begin transaction');
	}

	public function close() {
		sqlite_close($this->connection);
	}

	public function commitTransaction() {
		sqlite_query($this->connection, 'commit transaction');
	}

	public function rollbackTransaction() {
		sqlite_query($this->connection, 'rollback transaction');
	}

	protected function _affectedRows() {
		return sqlite_changes($this->connection);
	}

	protected function _error() {
		return sqlite_error_string(sqlite_last_error($this->connection));
	}

	protected function _escapeString($string) {
		return sqlite_escape_string($string);
	}

	protected function _fetch() {
		return new dbFacile_sqlite_result($this->result);
	}

	protected function _fetchAll() {
		$rows = sqlite_fetch_all($this->result, SQLITE_ASSOC);
		// free result?
		// rewind?
		return $rows;
	}

	// when passed result
	// returns next row
	protected function _fetchRow() {
		return sqlite_fetch_array($this->result, SQLITE_ASSOC);
	}

	protected function _fields($table) {
		$fields = array();
		foreach($this->fetchAll('pragma table_info(' . $table. ')') as $row) {
			$type = strtolower(preg_replace('/\(.*\)/', '', $row['type'])); // remove size specifier
			$name = $row['name'];
			$fields[ $name ] = array('type' => $type, 'primaryKey' => ($row['pk'] == '1'));
		}
		return $fields;
	}

	protected function _foreignKeys($table) {
		$keys = array();
		$this->execute('pragma foreign_key_list(' . $table . ')', array(), false);
		foreach($this->_fetchAll() as $row) {
			$keys[ $row['from'] ] = array('table' => $row['table'], 'field' => $row['to']);
		}
		return $keys;
	}

	protected function _lastID() {
		return sqlite_last_insert_rowid($this->connection);
	}

	protected function _numberRows() {
		return sqlite_num_rows($this->result);
	}

	protected function _open($database) {
		$this->connection = sqlite_open($database);
		//$this->buildSchema();
		return $this->connection;
	}

	protected function _query($sql) {
		//var_dump($parameters);exit;
		return sqlite_query($this->connection, $sql);
	}

	protected function _rewind($result) {
		sqlite_rewind($result);
	}

	protected function _tables() {
		if(!$this->execute("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name", array(), false))
			die('Failed to get tables');
		return $this->_fetchAll();
	}
} // sqlite

/*
class dbFacile_sqlite_result implements Iterator {
	private $result;
	public function __construct($r) {
		$this->result = $r;
	}
	public function rewind() {
		sqlite_rewind($this->result);
	}
	public function current() {
		$a = sqlite_current($this->result, SQLITE_ASSOC);
		return $a;
	}
	public function key() {
		$a = sqlite_key($this->result);
		return $a;
		// getAttribute(PDO::DRIVER_NAME) to determine the sql to call
		$this->execute('describe ' . $table);
		return $this->_fetchAll();
	}
}
class dbFacile_pdo_postgresql extends dbFacile_pdo {
	public function __construct($handle = null) {
		parent::__construct($handle);
		$this->schemaNameField = 'column_name';
		$this->schemaTypeField = 'data_type';
	}
	protected function _open($database) {
		$this->connection = new PDO("pgsql:host=$host;dbname=$database", $user, $pass");
	}
	protected function _schema($table) {
		// getAttribute(PDO::DRIVER_NAME) to determine the sql to call
		$this->execute('select column_name,split_part(data_type, \' \', 1) as data_type from information_schema.columns where table_name = \'' . $table . '\' order by ordinal_position');
		return $this->_fetchAll();
	}
}
class dbFacile_pdo_sqlite extends dbFacile_pdo {
	public function __construct($handle = null) {
		parent::__construct($handle);
		$this->schemaNameField = 'name';
		$this->schemaTypeField = 'type';
		$this->schemaPrimaryKeyField = 'pk';
	}
	protected function _open($database) {
		$this->connection = new PDO("sqlite:$database");
	}
	protected function _schema($table) {
		// getAttribute(PDO::DRIVER_NAME) to determine the sql to call
		$this->execute('pragma table_info(' . $table. ')');
		return $this->_fetchAll();
	}
}
class dbFacile_pdo_sqlite2 extends dbFacile_pdo {
	public function __construct($handle = null) {
		parent::__construct($handle);
		$this->schemaNameField = 'name';
		$this->schemaTypeField = 'type';
		$this->schemaPrimaryKeyField = 'pk';
	}
	protected function _open($database) {
		$this->connection = new PDO("sqlite2:$database");
	}
	protected function _schema($table) {
		// getAttribute(PDO::DRIVER_NAME) to determine the sql to call
		$this->execute('pragma table_info(' . $table. ')');
		return $this->_fetchAll();
	}
}

abstract class dbFacile_pdo extends dbFacile {
	function __construct($handle = null) {
		parent::__construct();
		$this->schemaNameField = 'name';
		$this->schemaTypeField = 'type';
		if($handle != null)
			$this->connection = $handle;
	}
	protected function _open($type, $database, $user, $pass, $host) {
		$this->connection = new PDO("$type:host=$host;dbname=$database", $user, $pass);
	}
	protected function _query($sql) {
		$this->result = $this->connection->query($sql);
	}
	protected function _escapeString($string) {
		return sqlite_escape_string($string);
	}
	protected function _error() {
		return print_r($this->connection->errorInfo(), true);
	}
	protected function _numberRecords() {
		$this->numberRecords = $this->result->rowCount();
	}
	protected function _fetch() {
		return $this->result;
	}
	protected function _fetchAll() {
		return $this->result->fetchAll(PDO::FETCH_ASSOC);
	}
	protected function _fetchRow() {
		return $this->result->fetch(PDO::FETCH_ASSOC);
	}
	protected function _lastID() {
		return $this->connection->lastInsertId();
	}
	protected function _schema($table) {
		// getAttribute(PDO::DRIVER_NAME) to determine the sql to call
		$this->execute('pragma table_info(' . $table. ')');
		return $this->_fetchAll();
	}
	protected function _begin() {
		$this->connection->beginTransaction();
	}
	protected function _commit() {
		$this->connection->commit();
	}
	protected function _rollback() {
		$this->connection->rollBack();
	}
	public function close() {
		$this->connection = null;
	}
} // pdo
*/

?>
