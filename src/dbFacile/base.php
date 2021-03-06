<?php
namespace dbFacile;

/*
dbFacile - base class, which driver classes extend 
*/

abstract class base
{
    protected $connection; // handle to Database connection
    protected $logFile;
    protected $fullSQL; // holds previously executed SQL statement
    protected $_queryCount = 0;

    // implement these methods to create driver subclass
    abstract public function affectedRows($result = null);
    //public function beginTransaction();
    abstract public function close();
    //public function commitTransaction();
    abstract public function error();
    abstract public function escapeString($string);
    abstract public function lastID($table = null);
    abstract public function numberRows($result);
    //public abstract function open();
    //public function quoteField($field);
    abstract public function rewind($result);
    //public function rollbackTransaction();

    abstract protected function _fetch($result);
    abstract protected function _fetchAll($result);
    abstract protected function _fetchRow($result);
    //protected abstract function _fields($table);
    // Should return a result handle, or false
    abstract protected function _query($sql);

    public function __construct($handle = null)
    {
        $this->connection = $handle;
    }

    /*
     * Performs a query using the given string.
     * Used by the other _query functions.
     * */
    public function execute()
    {
        return $this->_execute(func_get_args());
    }
    public function _execute($queryParts)
    {
        $this->fullSQL = $this->makeQuery($queryParts);

        /*
        if($this->logFile)
            $time_start = microtime(true);
        */

        $result = $this->_query($this->fullSQL); // sets $this->result
        $this->_queryCount++;

        /*
        // Should look up ay PHP-FIG logging interface recommendations ...
        if ($this->logFile) {
            $time_end = microtime(true);
            fwrite($this->logFile, date('Y-m-d H:i:s') . "\n" . $fullSql . "\n" . number_format($time_end - $time_start, 8) . " seconds\n\n");
        }

        if(!$this->result && (error_reporting() & 1))
            trigger_error('dbFacile - Error in query: ' . $this->query . ' : ' . $this->_error(), E_USER_WARNING);
        */

        // I know getting a real true or false is handy,
        // but returning the result handle gives more flexibility
        // and honestly, many oof the convenience functions check result anyway, so just pass it to them
        return $result;
    }

    public function previousQuery()
    {
        return $this->fullSQL;
    }
    public function queryCount()
    {
        return $this->_queryCount;
    }

    /*
     * Passed an array and a table name, it attempts to insert the data into the table.
     * Check for boolean false to determine whether insert failed
     * $data can be empty ... MySQL will use defaults, and Sqlite3 will if you use the "INSERT ... DEFAULT VALUES" syntax
     * */
    public function insert($table, $data = array())
    {
        $sql = $this->_insert($table, $data);
        $result = $this->execute($sql);
        if (!$result) {
            // Error
            return false;
        }
        // This should return true if insert succeeded, but no ID was generated
        return $this->lastID($table);
    }

    protected function _insert($table, $data = array())
    {
        $fields = array();
        $values = array();
        // Only loop once ... say by to 2 array_map() + 2 call_user_func() calls
        foreach ($data as $key => $value) {
            $fields[] = $this->quoteField($key);
            if (is_a($value, '\dbFacile\passthrough')) {
                $values[] = $value;
            } else {
                $values[] = $this->quoteEscapeString($value);
            }
        }
        return 'INSERT INTO ' . $this->quoteField($table) . ' (' . implode(',', $fields) . ') VALUES(' . implode(',', $values) . ')';
    }

    /*
     * Passed an array, table name, where clause, and placeholder parameters, it attempts to update a record.
     * Returns the number of affected rows
     * */
    public function update($table, $data, $whereHash = array())
    {
        // Get query parts, and shift off table and data
        $args = func_get_args();
        array_shift($args);
        array_shift($args);

        $sql = 'UPDATE ' . $this->quoteField($table) . ' SET ';
        foreach ($data as $key => $value) {
            // Numeric keys are how we allow numeric values to be used in update()
            if (is_a($value, '\dbFacile\passthrough')) {
                $sql .= $this->quoteField($key) . '=' . $value . ',';
            } else {
                $sql .= $this->quoteField($key) . '=' . $this->quoteEscapeString($value) . ',';
            }
        }
        $sql = substr($sql, 0, -1); // strip off last comma

        if ($args) {
            $sql .= $this->whereHelper($args);
        }
        $result = $this->execute($sql);

        return $this->affectedRows($result);
    }

    public function replace($table, $data = array())
    {
        $sql = $this->_replace($table, $data);
        $result = $this->execute($sql);
        if (!$result) {
            // Error
            return false;
        }
        // This should return true if insert succeeded, but no ID was generated
        return $this->lastID($table);
    }

    protected function _replace($table, $data = array())
    {
        $fields = array();
        $values = array();
        foreach ($data as $key => $value) {
            $fields[] = $this->quoteField($key);
            if (is_a($value, '\dbFacile\passthrough')) {
                $values[] = $value;
            } else {
                $values[] = $this->quoteEscapeString($value);
            }
        }
        return 'REPLACE INTO ' . $this->quoteField($table) . ' (' . implode(',', $fields) . ') VALUES(' . implode(',', $values) . ')';
    }

    // @args: table, where
    public function delete($table, $whereHash = array())
    {
        $args = func_get_args();
        $table = array_shift($args);

        $sql = 'DELETE FROM ' . $this->quoteField($table);
        if ($args) {
            $sql .= $this->whereHelper($args);
        }
        $result = $this->execute($sql);
        return $this->affectedRows($result);
    }

    /*
     * This is intended to be the method used for large result sets.
     * It is intended to return an iterator, and act upon buffered data.
     * Takes SQL alternations
     * */
    public function fetch($sql)
    {
        $result = $this->_execute(func_get_args());
        return $this->_fetch($result);
    }

    /*
     * Fetches all of the rows where each is an associative array.
     * Tries to use unbuffered queries to cut down on execution time and memory usage,
     * but you'll only see a benefit with extremely large result sets.
     * */
    public function fetchRows($sql)
    {
        $result = $this->_execute(func_get_args());
        if ($result)
        {
            return $this->_fetchAll($result);
        }
        return array();
    }
    // Alias of fetchRows()
    public function fetchAll($sql)
    {
        return call_user_func_array(array($this, 'fetchRows'), func_get_args());
    }

    /*
     * Fetches the first cell from the first row returned by the query
     * */
    public function fetchCell($sql)
    {
        $result = $this->_execute(func_get_args());
        if ($result) {

            $row = $this->_fetchRow($result);
            if (!$row) return null;
            return array_shift($row); // shift first field off first row
        }

        return null;
    }

    /*
     * This method is quite different from fetchCell(), actually
     * It fetches one cell from each row and places all the values in 1 array
     * */
    public function fetchColumn($sql)
    {
        $result = $this->_execute(func_get_args());
        if ($result) {
            $cells = array();
            foreach ($this->_fetchAll($result) as $row) {
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
    public function fetchKeyValue($sql)
    {
        $result = $this->_execute(func_get_args());
        if(!$result) return array();

        $data = array();
        foreach ($this->_fetchAll($result) as $row) {
            if (sizeof($row) == 2) { // if there were only 2 fields in the result
                // use first column's value for key, second for value
                $data[ reset($row) ] = next($row);
            } else { // if more than 2 fields were fetched
                trigger_error('dbFacile - fetchKeyValue() will soon only return key/value pairs. Use fetchKeyedRows() if you want each row indexed by a custom key', E_USER_DEPRECATED);
                // use the full row as the value
                // DEPRECATION NOTICE
                $data[ reset($row) ] = $row;
            }
        }

        return $data;
    }

    /*
     * Should be passed a query that fetches at least two fields
     * The first field's value will become the array key
     * The array value will be the full row
     */
    public function fetchKeyedRows($sql)
    {
        $result = $this->_execute(func_get_args());
        if(!$result) return array();

        $data = array();
        foreach ($this->_fetchAll($result) as $row) {
            // use first column's value for key
            $data[ reset($row) ] = $row;
        }

        return $data;
    }

    /*
     * Like fetch(), accepts any number of arguments
     * The first argument is an sprintf-ready query stringTypes
     * */
    public function fetchRow($sql = null)
    {
        $result = $this->_execute(func_get_args());
        // not all results look like resources, so I don't think is_resource($result) is portable
        if ($result) {
            return $this->_fetchRow($result);
        }
        return null;
    }

    // These are defaults, since these statements are common across a few DBMSes
    // Override in driver class if they are incorrect
    public function beginTransaction()
    {
        // need to return true or false
        $this->_query('begin');
    }

    public function commitTransaction()
    {
        $this->_query('commit');
    }

    public function rollbackTransaction()
    {
        $this->_query('rollback');
    }

    public function quoteField($field)
    {
        return '`' . $field . '`';
    }

    public function quoteEscapeString($value)
    {
        return "'" . $this->escapeString($value) . "'";
    }

    protected function whereHelper($where) {
        if (count($where) > 1) {
            return $this->whereAlternations($where);
        } elseif ($where) {
            return $this->whereHash($where[0]);
        }
    }

    protected function whereHash($where)
    {
        if (!$where) {
            return;
        }
        $sql = ' WHERE ';
        foreach ($where as $key => $value) {
            if (is_array($value)) {
                $sql .= $this->quoteField($key) . ' IN (' . implode(',', $value) . ' AND ';
            } else {
                $sql .= $this->quoteField($key) . '=' . $this->quoteEscapeString($value) . ' AND ';
            }
        }
        return substr($sql, 0, -4);
    }
    protected function whereAlternations($where)
    {
        // empty array
        if (!$where) {
            return;
        }
        $sql = ' WHERE ';
        $sql .= $this->makeQuery($where);
        return $sql;
    }
    /**
     * Takes an array of query parts ... the even numbered indexes must contain strings
     * The odd indexes are expected to contain values that need to be quoted and escaped for the final query
     */
    protected function makeQuery($parts)
    {
        $sql = '';
        while ($parts)
        {
            $sql .= array_shift($parts);
            if ($parts)
            {
                // uhh, this "IN (1,2,3)" stuff is annoying
                $part = array_shift($parts);
                if (is_array($part)) {
                    $sql .= ' (' . implode(',', array_map(array($this,'quoteEscapeString'),$part)) . ')';
                } else {
                    // Odd elements are values that need to be quoted+escaped
                    $sql .= $this->quoteEscapeString($part);
                }
            }
        }
        return $sql;
    }
}
