<?php
	include "functions.php";			
	printHeader();
	echo getBandInfo($_GET['query']);
	if ($_GET['offset'] || $_GET['length'] || $_GET['order'] || $_GET['sort'])
		echo getJamsSearch(3, $_GET['query'], $_GET['offset'], $_GET['length'], $_GET['order'], $_GET['sort'], 2);
	else
		echo getJamsSearch(3, $_GET['query']);
	printFooter();
?>