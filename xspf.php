<?php

include "functions.php";

sql();


if ($_GET['mode'] == "index")
{
	header("Content-Type: application/xml");
	echo "<?xml version='1.0' encoding='UTF-8'?><playlist version='0' xmlns = 'http://xspf.org/ns/0/'>";
	echo "<title>Welcome to BINK!</title><trackList>";


	$result = mysql_query("select * from jams where private=0 order by date desc");
	
	$sql = "";
	$i=0;
	$num = mysql_num_rows($result);
	while ($row = mysql_fetch_array($result))
	{
		if ($i < $num-1)
			$tunion = " union ";
		else
			$tunion = "";
			
		$sql .= "(select tracks.title, tracks.path, jams.title as jamtitle, jams.id, jams.defpic from tracks, jams where jams.id = " . $row['id'] . " and tracks.jamid = jams.id order by tracks.num asc) $tunion";
		$i++;
		
	}
	
	$result2 = mysql_query($sql);
	while ($trkrow = mysql_fetch_array($result2))
	{
		$loc = $trkrow['path'];
		$loc = addslashes($loc);
		$filespt = explode(".", $loc);
		if ($filespt[1] != "mp3" && $filespt[1] != "MP3")
			continue;
		$annot = $trkrow['jamtitle'] . " - " . $trkrow['title'];
		echo "<track>";
		echo "<annotation>" . $annot . "</annotation>";
		echo "<location>http://s3.amazonaws.com/binkmedia/public/" . stripslashes($loc) . "</location>";
		
		if ($trkrow['defpic'])
		{
			$picrow = mysql_fetch_array(mysql_query("select * from pictures where id = " . $trkrow['defpic']));
			
			echo "<image>http://s3.amazonaws.com/binkmedia/public/pics/" . $trkrow['id'] . "/" . $picrow['filename'] . "</image>";
		}
		
		echo "<info>jam.php?id=" . $trkrow['id'] . "</info>";
		echo "</track>";
	}
	echo "</trackList></playlist>";
}
else
{
	$id = $_GET['id'];

	$result = mysql_query("SELECT * FROM jams WHERE id = $id");
	$jamrow = mysql_fetch_array($result);
	$jtitle = urlencode($jamrow['title']);
	header("Content-Type: application/xml");
	echo "<?xml version='1.0' encoding='UTF-8'?><playlist version='0' xmlns = 'http://xspf.org/ns/0/'>";
	echo "<title>$jtitle</title><trackList>";


	$result = mysql_query("SELECT * FROM `tracks` WHERE jamid=$id ORDER BY `num` ASC;");

	while ($trkrow = mysql_fetch_array($result))
	{
		$loc = $trkrow['path'];
		$extension = pathinfo($loc, PATHINFO_EXTENSION);
		
		if ($extension != "mp3" && $extension != "MP3")
			continue;
		$loc = addslashes($loc);
		$annot = $trkrow['title'];
		echo "<track>";
		echo "<annotation>" . $annot . "</annotation>";
		echo "<location>http://s3.amazonaws.com/binkmedia/public/" . stripslashes($loc) . "</location>";
		echo "</track>";
	}
	echo "</trackList></playlist>";
}
?>