<?php
require_once('dbFacile_mysqli.php');

/**
 * This class works around the lack of a fetch_all method on some mysqli drivers.
 * Originally reported by mbutomax on Github
 * Reported here: https://github.com/alanszlosek/dbFacile/pull/8
 */
class dbFacile_mysqli2 extends dbFacile_mysqli {
	protected function _fetchAll($result) {
		// this isn't available unless the mysql native driver is being used ... hmm
		$data = array();
		while ($tmp = $result->fetch_array(MYSQLI_ASSOC)) {
			$data[] = $tmp;
		}
		$result->free();
		return $data;
	}
} // mysqli

