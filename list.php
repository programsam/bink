<?php
	include "functions.php";			
	printHeader();
	if ($_GET['offset'] || $_GET['length'] || $_GET['order'] || $_GET['sort'])
		echo getJamsSearch(0, null, $_GET['offset'], $_GET['length'], $_GET['order'], $_GET['sort']);
	else
		echo getJamsSearch(0, null);
	printFooter();
?>