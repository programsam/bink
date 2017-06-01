<?php
	include "functions.php";
	$trackid = -1;
	if (isset($_GET['trackid']))
		$trackid = $_GET['trackid'];

	$id = -1;
	if (isset($_GET['id']))
	  $id = $_GET['id'];
	printJamHeader($id);
	echo printAJam($id, $trackid);
	
	printFooter();
?>
