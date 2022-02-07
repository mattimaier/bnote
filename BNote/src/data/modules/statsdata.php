<?php

/**
 * Data Access Class for statistics.
 * @author matti
 *
 */
class StatsData extends AbstractData {

	private $dir_prefix;

	/**
	 * Build data provider.
	 */
	function __construct($dir_prefix = "") {
		$this->dir_prefix = $dir_prefix;

		$this->fields = array();

		$this->references = array();
		$this->table = "";

		$this->init($dir_prefix);
	}
	
	function last6MonthsNumberEvents($table) {
		// get the number of events for each month in the last 6 months, omits zero counts
		$this->regex->isDbItem($table, "database table");
		$query = "SELECT year(begin) as y, month(begin) as m, count(*) as num 
				FROM `$table` 
				WHERE begin < NOW() 
				GROUP BY year(begin), month(begin) 
				ORDER BY year(begin), month(begin) DESC";
		$res = $this->database->getSelection($query);
		
		// compute year-month series for the last 6 months
		$firstDay = Data::subtractMonthsFromDate(Data::convertDateFromDb(Data::getDateNow()), 6);
		
		// render result
		$values = array();
		$labels = array();
		$indexDate = $firstDay;
		for($i = 0; $i < 6; $i++) {
			$y = substr($indexDate, -4, 4);
			$m = substr($indexDate, -7, 2);
			$label = $y . "-" . $m;
			$count = intval($this->findByMonthAndYear($y, $m, $res));
			array_push($labels, $label);
			array_push($values, $count);
			$indexDate = Data::addMonthsToDate($indexDate, 1);
		}
		
		return array(
			"series" => $values,
			"labels" => $labels
		);
	}
	
	protected function findByMonthAndYear($y, $m, $selection) {
		for($i = 1; $i < count($selection); $i++) {
			$rowY = $selection[$i]["y"];
			$rowM = $selection[$i]["m"];
			if($rowY == $y && $rowM == $m) {
				return $selection[$i]["num"];
			}
		}
		return 0;
	}
	
	function membersPerGroup() {
		$query = "SELECT `group`, `name`, count(* ) as num 
				FROM `contact_group` JOIN `group` ON `contact_group`.`group` = `group`.`id` 
				GROUP BY `group`";
		$selection = $this->database->getSelection($query);
		
		// render result
		$values = array();
		$labels = array();
		
		for($i = 1; $i < count($selection); $i++) {
			$row = $selection[$i];
			array_push($values, $row['num']);
			array_push($labels, $row['name']);
		}
		
		return array(
				"series" => $values,
				"labels" => $labels
		);
	}
	
	function memberRehearsalPerformance() {
		// select the top members who participate the most often in rehearsals in the last year
		$dateOneYearAgo = Data::subtractMonthsFromDate(Data::convertDateFromDb(Data::getDateNow()), 12);
		$query = "SELECT c.name, c.surname, i.name as instrument, count(*) as score 
				FROM `rehearsal_user` ru 
				 JOIN `rehearsal` r ON ru.rehearsal = r.id 
				 JOIN `user` u ON ru.user = u.id 
				 JOIN `contact` c ON u.contact = c.id 
				 JOIN `instrument`i ON c.instrument = i.id 
				 JOIN (SELECT @curRow := 0) x
				WHERE r.`begin` >= ? AND ru.participate = 1 
				GROUP BY ru.`user` 
				ORDER BY score DESC
				LIMIT 0,5";
		return $this->rankResults($this->database->getSelection($query, array(array("s", $dateOneYearAgo))));
	}
	
	function memberVotePerformance() {
		// select the top members who participated in the most number of votes in the last year
		$dateOneYearAgo = Data::subtractMonthsFromDate(Data::convertDateFromDb(Data::getDateNow()), 12);
		$query = "SELECT c.name, c.surname, i.name as instrument, count(*) as score 
				FROM `vote_option_user` vou 
				 JOIN `vote_option` vo ON vou.vote_option = vo.id 
				 JOIN `vote` v ON vo.vote = v.id 
				 JOIN `user` u ON vou.user = u.id 
				 JOIN `contact` c ON u.contact = c.id 
				 JOIN `instrument`i ON c.instrument = i.id 
				 JOIN (SELECT @curRow := 0) x
				WHERE v.`end` >= ? 
				GROUP BY vou.`user` 
				ORDER BY score DESC 
				LIMIT 0,5";
		return $this->rankResults($this->database->getSelection($query, array(array("s", $dateOneYearAgo))));
	}
	
	function memberOptionPerformance() {
		// select the top members who voted the most often with yes in the last year
		$dateOneYearAgo = Data::subtractMonthsFromDate(Data::convertDateFromDb(Data::getDateNow()), 12);
		$query = "SELECT c.name, c.surname, i.name as instrument, count(*) as score
		FROM `vote_option_user` vou
		JOIN `vote_option` vo ON vou.vote_option = vo.id
		JOIN `vote` v ON vo.vote = v.id
		JOIN `user` u ON vou.user = u.id
		JOIN `contact` c ON u.contact = c.id
		JOIN `instrument`i ON c.instrument = i.id
		JOIN (SELECT @curRow := 0) x
		WHERE v.`end` >= ? AND vou.choice = 1
		GROUP BY vou.`user`
		ORDER BY score DESC
		LIMIT 0,5";
		return $this->rankResults($this->database->getSelection($query, array(array("s", $dateOneYearAgo))));
	}
	
	private function rankResults($selection) {
		// rank manually since the implementation is not always fitting on the db systems
		array_push($selection[0], "rank");
		for($i = 1; $i < count($selection); $i++) {
			$selection[$i]["rank"] = $i;
		}
		return $selection;
	}
}