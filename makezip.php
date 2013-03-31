<?php

include "functions.php";
$id = $_GET['id'];
	printHeader();
	produceZIPFile($id); 
	printFooter();
?>
