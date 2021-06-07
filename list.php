<?php
	include "functions.php";
	printHeader();
	if ($_GET['offset'] || $_GET['length'] || $_GET['order'] || $_GET['sort']) {
		$offset = 0;
		$length = 10;
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

		echo getJamsSearch(0, null, $offset, $length, $order, $sort);
	}
	else {
		echo getJamsSearch(0, null);
	}
	printFooter();
?>
