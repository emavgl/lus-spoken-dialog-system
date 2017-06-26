<?php
/**
 * Class for Attribute-Value & Utterance label to SQL Query Conversion
 * 
 * @author estepanov
 */
class Slu2DB {
	
    // Define a function to debug in peace
    function console_log( $data, $name ){
        error_log(print_r(json_encode($name), TRUE)); 
        error_log(print_r(json_encode($data), TRUE));
    }


	/**
	 * Map SLU concepts & utterance classes to DB columns
	 * 
	 * EXTEND!
	 */

	private $mapping = array(
		"award" => "imdb_score",
        "actor.name" => "actors",
        "actor.nationality" => "country",
        "actor.type" => "actors",
        "actor" => "actors",
        "rating.name" => "imdb_score",
        "person.nationality" => "country", //todo this is kinda wrong, but the info is not in the db
        "person" => "actors",
        "movie.name" => "title",
        "movie.subject" => "genres",
        "movie.genre" => "genres",
        "movie.release_date" => "year",
		"release_date" => "year",
        "movie.language" => "language",
        "movie.gross_revenue" => "gross",
        "movie.location" => "country", //todo wrong
        "movie.release_region" => "country",
        "movie.star_rating" => "imdb_score",
        "movie.duration" => "duration",
		"duration" => "duration",
		"budget" => "budget",
		"movie.budget" => "budget",
        "movie.keywords" => "plot_keywords",
        "movie.likes" => "movie_facebook_likes",
        "movie" => "title",
        "director.name" => "director",
        "director.nationality" => "country", //todo wrong
        "director" => "director",
        "character.name" => "actors", // todo wrong
        "character" => "actors", //todo wrong
        "producer.name" => "director", //todo maybe wrong
        "producer" => "director" //todo maybe wrong
	);
	
	/**
	 * Returns db column w.r.t. $str
	 */
	public function db_mapping($str) {
		console_log($str, 'calling db_mapping with');
		return $this->mapping[$str];
	}
	
	/**
	 * Meta function to
	 * - map slu concepts to DB
	 * - map utterance classifier class to db
	 * - construct sql query
	 */
	public function slu2sql($concepts, $class) {
		console_log($class, 'slu2sql class');
		$db_class    = $this->db_mapping($class);
		
		$db_concepts = array();
		foreach ($concepts as $attr => $val) {
			$db_concepts[$this->db_mapping($attr)] = $val;
		}
		
				
		// construct SQL query
		$query  = 'SELECT DISTINCT * FROM movie WHERE ';
		
		$tmp = array();
		foreach ($db_concepts as $attr => $val) {
			//$tmp[] = $attr . ' LIKE "%' . $val . '%"';
			$tmp[] = $attr . ' LIKE "' . $val . '%"';
		}
		$query .= implode(' AND ', $tmp);
		$query .= ';';
		
		return $query;
	}
}