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
		if (isset($_GET['offet']))
			$offset = $_GET['offset'];
		if (isset($_GET['length']))
			$length = $_GET['length'];
		if (isset($_GET['order']))
			$order = $_GET['order'];
		if (isset($_GET['sort']))
			$sort = $_GET['sort'];
		echo getJamsSearch(2, $_GET['query'], $_GET['offset'], $_GET['length'], $_GET['order'], $_GET['sort'], 2);
	} else {
		echo getJamsSearch(2, $_GET['query']);
	}
	printFooter();
?>
