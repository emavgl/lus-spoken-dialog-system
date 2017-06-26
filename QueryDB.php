<?php
/*
 * Class to connect and query DB
 */

class QueryDB {
	
    // Define a function to debug in peace
    function console_log( $data, $name ){
        error_log(print_r(json_encode($name), TRUE)); 
        error_log(print_r(json_encode($data), TRUE));
    }

	public function query($sql) {
	
		// connect
		define("DB_HOST", "127.0.0.1");
		define("DB_USER", "lus");
		define("DB_PASS", "luspassword");
		define("DB_NAME", "moviedb");
		
		$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		
		//print_r($mysqli);
		
		if ($mysqli->connect_errno) {
			printf("Connect failed: %s\n", $mysqli->connect_error);
			exit();
		}
		
		// query
		console_log($sql, 'my query');
		$result = $mysqli->query($sql);
		
		if (!$result || $result->num_rows == null) {
			console_log(mysql_error(), 'error mysql');
			$db_results = null;
			return $db_results;
		}

		console_log($result, 'db raw result');

		$db_results = array();
		while ($row = $result->fetch_assoc()) {
			//print_r("raw");
			$row["title"] = utf8_encode($row["title"]);
			//print_r($row);
			$db_results[] = $row;
			//echo $row[$class] . "\n";
			//echo "<br/>";
		}

		$result->free();
		$mysqli->close();
		
		return $db_results;
	}
	
}
