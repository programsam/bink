<?php

include "functions.php";

printHeader();
if (isset($_GET['dir']) && $_GET['dir'] == 1)
		echo getJams("select * from jams where private=0 order by id desc limit 0,10");
	else
		echo getJams("select * from jams where private=0 order by date desc limit 0,10");
printFooter();
?>
