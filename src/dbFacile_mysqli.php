<?php
require_once('dbFacile.php');

class dbFacile_mysqli extends dbFacile {
	public function affectedRows($result = null) {
		return $this->connection->affected_rows;
	}

	public function beginTransaction() {
		$this->connection->autocommit(false);
	}

	public function close() {
		$this->connection->close();
	}

	public function commitTransaction() {
		$this->connection->commit();
		// Turn auto-commit back on
		$this->connection->autocommit(true);
	}

	public function error() {
		return $this->connection->error;
	}

	public function escapeString($string) {
		return $this->connection->real_escape_string($string);
	}

	public function lastError() {
		return $this->connection->error;
	}

	public function lastID($table = null) {
		$id = $this->connection->insert_id;
		// $id will be 0 if insert succeeded, but statement didn't generate a new id (no auto-increment)
		if ($id == 0) return false;
		return $id;
	}

	// Hmm
	public function numberRows($result) {
		if(mysqli_affected_rows($this->connection)) { // for insert, update, delete
			$this->numberRecords = mysqli_affected_rows($this->connection);
		} elseif(!is_bool($result)) { // for selects
			$this->numberRecords = mysqli_num_rows($result);
		} else { // will be boolean for create, drop, and other
			$this->numberRecords = 0;
		}
	}

	public function open($database, $user, $password, $host='localhost', $charset='utf-8') {
                // force opening a new link because we might be selecting a different database
                $this->connection = new mysqli($host, $user, $password, $database);
                return $this->connection;
        }

	public function rewind($result) {
		$result->data_seek(0);
	}

	public function rollbackTransaction() {
		$this->connection->rollback();
		$this->connection->autocommit(true);
	}


	protected function _fetch($result) {
		return $this->_fetchAll($result);
	}
	protected function _fetchAll($result) {
		$data = $result->fetch_all(MYSQLI_ASSOC);
		$result->free();
		return $data;
	}
	protected function _fetchRow($result) {
		return $result->fetch_assoc();
	}
	protected function _query($query) {
		return $this->connection->query($query);
	}
} // mysqli

