<?php

include "functions.php";
sql();

$id = $_GET['jamid'];
$start = $_GET['start'];

	if ($start < 0)
	{
		$start = 0;
		print "<script language=\"javascript\">alert('test');</script>";
		exit;
	}

	$result = bink_query("SELECT * FROM `pictures` WHERE `jamid` = $id LIMIT $start,5");
		
	while ($picrow = mysqli_fetch_array($result))
	{
		$filename = $picrow['filename'];
		$picid = $picrow['id'];
		print "<a border=0 href=\"javascript:setPicture('getimage.php?f=$id/$filename&w=500&h=400', 'getimage.php?f=$id/$filename');\"><img border=0 src='getimage.php?f=$id/$filename.thm&w=100&h=100' /></a>";
	}


?>
