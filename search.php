<?php
	include "functions.php";
	printHeader();
	if (isset($_GET['offset'])
			|| isset($_GET['length'])
			|| isset($_GET['order'])
			|| isset($_GET['sort'])) {
		$offset = 0;
		$length = 20;
		$order="date";
		$sort="desc";
		$query="";
		if (isset($_GET['offet']))
			$offset = $_GET['offset'];
		if (isset($_GET['length']))
			$length = $_GET['length'];
		if (isset($_GET['order']))
			$order = $_GET['order'];
		if (isset($_GET['sort']))
			$sort = $_GET['sort'];
		if (isset($_GET['query']))
			$query = $_GET['query'];

		echo getJamsSearch(2, $query, $offset, $length, $order, $sort, 2);
	} else {
		echo getJamsSearch(2, $query);
	}
	printFooter();
?>
