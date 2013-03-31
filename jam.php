<?php
	include "functions.php";
	$trackid = -1;
	if (isset($_GET['trackid']))
		$trackid = $_GET['trackid'];

	printJamHeader($_GET['id']);
	echo printAJam($_GET['id'], $trackid);
	printFooter();
?>
