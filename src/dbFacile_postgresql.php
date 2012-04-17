<?php
require_once('dbFacile.php');

class dbFacile_postgresql extends dbFacile {
	public function beginTransaction() {
		$this->_query('begin');
	}

	public function close() {
		pg_close($this->connection);
	}

	public function commitTransaction() {
		$this->_query('commit');
	}

	// user, password, database, host
	protected function open($database, $user, $password, $host) {
		//die("host=$host dbname=$database user=$user");
		$this->connection = pg_connect("host=$host dbname=$database port=5432 user=$user password=$password");
		return $this->connection;
	}

	public function rollbackTransaction() {
		$this->_query('rollback');
	}

	public function affectedRows($result) {
		return pg_affected_rows($result);
	}

	public function lastError() {
		return pg_last_error($this->connection);
	}

	public function escapeString($string) {
		return pg_escape_string($string);
	}


	protected function _fetch($result) {
		return $this->_fetchAll($result);
	}

	protected function _fetchAll($result) {
		return $data;
		$data = array();
		while($row = pg_fetch_assoc($result)) {
			$data[] = $row;
		}
		pg_free_result($result);
		// rewind?
		return $data;
	}

	protected function _fetchRow($result) {
		return pg_fetch_assoc($result);
	}

	protected function _lastID($table) {
		$sequence = $this->fetchCell("SELECT relname FROM pg_class WHERE relkind = 'S' AND relname LIKE '" . $table . "_%'");
		if(strlen($sequence))
			return $this->fetchCell('select last_value from ' . $sequence);
		return 0;
	}

	protected function _numberRows($result) {
		return pg_num_rows($result);
	}

	protected function _query($sql) {
		// postgres has it's own param filling?
		$sql = $this->makeQuery($sql, $parameters);
		return pg_query($this->connection, $sql);
	}
} // postgresql

