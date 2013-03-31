<?php
	include "functions.php";			
	printHeader();
	if ($_GET['offset'] || $_GET['length'] || $_GET['order'] || $_GET['sort'])
		echo getJamsSearch(2, $_GET['query'], $_GET['offset'], $_GET['length'], $_GET['order'], $_GET['sort'], 2);
	else
		echo getJamsSearch(2, $_GET['query']);
	printFooter();
?>