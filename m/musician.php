<?php
	include "functions.php";			
	printHeader();
	echo getMusicianInfo($_GET['query']);
	if (isset($_GET['offset']) || isset($_GET['length']) || isset($_GET['order']) || isset($_GET['sort']))
		echo getJamsSearch(1, $_GET['query'], $_GET['offset'], $_GET['length'], $_GET['order'], $_GET['sort'], 2);
	else
		echo getJamsSearch(1, $_GET['query']);
	printFooter();
?>