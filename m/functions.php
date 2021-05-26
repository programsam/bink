<?php

//Mobile functions

include "../S3.php";

function sql()
{
	include "../settings.php";

	$connection = mysqli_connect($DB_HOST, $DB_USERNAME, $DB_PASSWORD);
	if (! $connection) {
		echo "<h2>Could not connect to mySQL</h2>";
		die;
	}
	if (mysqli_select_db($connection, $DB_NAME) == 0)
	{
		print "<h2>Could not select bink database</h2>";
		die;
	}
	return $connection;
}

function bink_query($querystr)
{
	$connection = sql();
	$result = mysqli_query($connection, $querystr);
	return $result;
}

function directPhone()
{
	include "../settings.php";
	if(strstr($_SERVER['HTTP_USER_AGENT'],'iPhone') || strstr($_SERVER['HTTP_USER_AGENT'],'iPod')) {

	}
	else
	{
		header("Location: $BASE_URL");
	}
}

function resize_bytes($size)
{
   $count = 0;
   $format = array("bytes","kb","mb","gb");
   while(($size/1024)>1 && $count<3)
   {
       $size=$size/1024;
       $count++;
   }
   $return = number_format($size,0,'','.')." ".$format[$count];
   return $return;
}

function fDate($date)
{
	return date("m/d/Y", strtotime($date));
}

function sDate($date)
{
	return date("n/j/y", strtotime($date));
}

function getLocationName($id)
{
	$result = bink_query("select * from locations where id = $id");
	$row = mysqli_fetch_array($result);
	return $row['name'];
}

function getBandName($id, $at=1)
{
	$result = bink_query("select * from bands where id = $id");
	if ($result && mysqli_num_rows($result) > 0)
	{
		$row = mysqli_fetch_array($result);
		if ($at)
			return $row['name'] . " at ";
		else
			return $row['name'];
	}
	else
	{
		return "";
	}
}

function getMusicianInfo($id)
{
	$result = bink_query("select * from musicians where id = $id");
	$row = mysqli_fetch_array($result);
	$musname = $row['name'];
	$link = $row['link'];
	$ret = "<div class='item'>";
	$ret .= "<h2>Musician: $musname</h2>";

	$columns = "jams.date, jams.id, jams.title, jams.locid";

	$result = bink_query("select distinct $columns from musiciansoncollection, jams, musicians where jams.private=0 and musicians.id = musiciansoncollection.musicianid and musiciansoncollection.jamid = jams.id and musicians.id = $id");

	$num = mysqli_num_rows($result);
	$ret .= "<strong>Number of Collections</strong>: $num<br />";
	$ret .= "<strong>Link</strong>: <a href='$link'>$link</a><br />";
	$ret .= "<strong>Played Instruments</strong>: ";
	$result2 = bink_query("select distinct instruments.name from musiciansoncollection, instruments where musiciansoncollection.instrumentid = instruments.id and musiciansoncollection.musicianid = $id;");
	$total = mysqli_num_rows($result2);
	$i=0;
	while ($row = mysqli_fetch_array($result2))
	{
		$i++;
		if ($i == $total)
			$ret .= $row['name'];
		else
			$ret .= $row['name'] . ", ";
	}

	$ret .= "</div>";

	return $ret;
}

function getNumberOf($table, $label)
{
	$result = bink_query("SELECT * FROM `$table`;");
	$num = mysqli_num_rows($result);
	return "<tr><td>$label</td><td>$num</td></tr>";
}

function getEntityByID($id, $table)
{
	$result = bink_query("select * from $table where id = $id");

	if ($result == null)
	{
		return "";
	}
	if (mysqli_num_rows($result) == 0)
	{
		return "";
	}

	$entity = mysqli_fetch_array($result);
	$ret = "";
	if (isset($entity['link']) && $entity['link'] != "" && $entity['link'] != " " && $table != "musicians" && $table != "bands")
	{
		$ret = "<a href='" . $entity['link'] . "'>" . $entity['name'] . "</a>";
	}
	else if ($table == "bands")
	{
		$ret = "<a href='band.php?query=$id'>" . $entity['name'] . "</a>";
	}
	else if ($table == "musicians")
	{
		$ret = "<a href='musician.php?query=$id'>" . $entity['name'] . "</a>";
	}
	else if ($table == "instruments")
	{
		$ret = "<a href='https://en.wikipedia.org/wiki/" . $entity['name'] . "'>" . 	$entity['name'] . "</a>";
	}
	else
	{
		$ret =  $entity['name'];
	}
	return $ret;
}



function getMediaList($id, $type)
{
	include "../settings.php";
	$s3 = new S3($S3_ACCESS_KEY, $S3_SECRET_KEY);

	if ($type == "music")
	{
		$table = "tracks";
		$header = "Music (tap to play)";
	}
	else if ($type == "video")
	{
		$table = "video";
		$header = "Video";
	}
	else
	{
		echo "ERROR: Media type not supported.";
		return;
	}
	$result = bink_query("select * from $table where jamid = $id order by num asc");
	if (mysqli_num_rows($result) == 0)
		return "";

	$ret = "<div class='item'><h1>$header</h1>";

	$ret .= "<ol><table width='100%'>";
	while (	$row = mysqli_fetch_array($result) )
	{
		$ext = pathinfo($row['path'], PATHINFO_EXTENSION);

		if ($ext == "xspf" || $ext == "xspf")
			continue;

		if ($row['title'] == "_BREAK_" || $row['title'] == "--------------------")
		{
			$ret .= "<tr><td colspan=6><br /><hr /><p /><hr /><br /></td></tr>";
		}
		else
		{
			$path = $row['path'];
			$files = $s3 -> getBucket('binkmedia', 'public/' . $path);
			$thisfile = array_pop($files);
			$ret .= "<tr>";
			$ret .= "<td><a href=\"https://s3.amazonaws.com/binkmedia/public/$path\">" . $row['title'] . "</a></td>";
			//$ret .= "<td>" . $ext . "</td>";

			$id = $row['id'];
			$notes = $row['notes'];
			if ($row['notes'] != " " && $row['notes'] != "")
			{
				$ret .= "<td valign='top'><a id='sho$id' href=\"javascript: add('notes$id', '$notes'); show('hid$id'); hide('sho$id'); \">notes</a><a id='hid$id' style=\"visibility:hidden\" href=\"javascript: clear('notes$id'); hide('hid$id'); show('sho$id'); \">x</a></td></tr>";
				$ret .= "<tr><td colspan=5 id='notes$id'></td></tr>";
			}
			else
			{
				$ret .= "<td>&nbsp;</td></tr>";
			}



		}
	}
	$ret .= "</table></ol></div>";
	return $ret;
}

function getPeopleList($id, $type)
{

	if ($type == "musician")
	{
		$header = "Musicians";
		$table = "musiciansoncollection";
		$idlabel = "musicianid";
		$techlabel = "instrumentid";
		$persontable = "musicians";
		$roletable = "instruments";
	}
	else if ($type == "staff")
	{
		$header = "Production Staff";
		$table = "productiononcollection";
		$idlabel = "staffid";
		$techlabel = "roleid";
		$persontable = "staff";
		$roletable = "roles";
	}
	else
	{
		echo "Type not supported!";
		return;
	}
	$result = bink_query("select * from $table where jamid = $id order by $idlabel");
	if (mysqli_num_rows($result) == 0)
		return "";

	$ret = "<div class='item'><h1>$header</h1>";
	$currentMusician = -1;
	while (	$row = mysqli_fetch_array($result) )
	{
		if ($currentMusician != $row[$idlabel])
		{
			if ($currentMusician != -1)
				$ret .= "<br />";
			$ret .= getEntityByID($row[$idlabel], $persontable) . " - ";
			$ret .= getEntityByID($row[$techlabel], $roletable);
		}
		else
		{
			$ret .= ", " . getEntityByID($row[$techlabel], $roletable);
		}
		$currentMusician = $row[$idlabel];
	}
	$ret .= "</div>";
	return $ret;
}

function getPictures($jamid)
{

	$num = mysqli_num_rows(bink_query("select * from pictures where jamid = $jamid"));

	if ($num == 0)
	{
		return "";
	}
	if ($num == 1)
	{
		$toprow = mysqli_fetch_array(bink_query("select * from pictures where jamid = $jamid"));
		$filename = $toprow['filename'];
		return "<div class='item'><h1>Pictures</h1><a href='../getimage.php?f=$jamid/$filename'><img border=0 src='../getimage.php?f=$jamid/$filename&w=300&h=200'></a></div>";
	}
	$row = mysqli_fetch_array(bink_query("SELECT * FROM jams, pictures where jams.defpic = pictures.id and jams.id=$jamid"));

	$ret = "<div class='item'><h1>Pictures</h1>";
	$ret .= "<div id='loadindicator' style='float: right'>Loading...</div>";

	if ($row['filename'])
	{
		$ret .= "<img onLoad=\"setHTML('loadindicator', '');\" name='mainpic' border=0 id='mainpic' src='../getimage.php?f=$jamid/" . $row['filename'] . "&w=300&h=200' />";
	}
	else
	{
		$row = mysqli_fetch_array(bink_query("SELECT * FROM pictures where pictures.jamid = $jamid"));
		$ret .= "<a id='imagelink' href='../getimage.php?f=$jamid/" . $row['filename'] . "'><img name='mainpic' onLoad=\"setHTML('loadindicator', '');\"  border=0 id='mainpic' src='getimage.php?f=$jamid/" . $row['filename'] . "&w=300&h=200' /></a>";
	}
	$ret .= "<br />";
	$ret .= "<table border=0 width='290'><tr><td>";
	$ret .= "[ <a id='prevlink' href=\"javascript:if (start >= 5) start -= 5; queryHTML('picspot', 'imglist.php?jamid=$jamid&start=' + start);\">Previous</a> ]";
	$ret .= "</td><td align='right'>";
	$ret .= "[ <a id='nextlink' href=\"javascript:if (start <= " . ($num-5) . ") start += 5; queryHTML('picspot', 'imglist.php?jamid=$jamid&start=' + start);\">Next</a> ]";
	$ret .= "</td></tr></table>";
	$ret .= "<div id='picspot'>";
	$ret .= "<script language='javascript'>var start = 0; queryHTML('picspot', 'imglist.php?jamid=" . $jamid. "&start=' + start);</script>";
	$ret .= "</div>";


	$ret .= "</div>";
	return $ret;
}
function getNextId($id)
{
	$result = bink_query("select * from jams where private=0 order by date desc;");

	while ($row = mysqli_fetch_array($result))
	{
		if (isset($oldrow) && $row['id'] == $id)
		{
			return $oldrow['id'];
		}

		$oldrow = $row;
	}

	return -1;
}

function getPreviousId($id)
{
	$id = $_GET['id'];

	$result = bink_query("select * from jams where private=0 order by date asc;");

	while ($row = mysqli_fetch_array($result))
	{
		if (isset($oldrow) && $row['id'] == $id)
		{
			return $oldrow['id'];
		}

		$oldrow = $row;
	}

	return -1;
}



function printSearchBar($m = 0)
{

if (isset($_GET['query']))
	$q = $_GET['query'];
else
	$q = "Search";

if ($_SERVER["PHP_SELF"] == "/musician.php")
	$q = "Search";
else if ($_SERVER["PHP_SELF"] == "/band.php")
	$q = "Search";

	$ret = "<input type=text style=\"background:black; color: gray; border-style: solid; height: 30px\" size=12 value=\"$q\" onkeydown=\"if (event.keyCode == 13) location='search.php?query=' + value;\" onClick=\"value='';\">";

	return $ret;
}

function isPhone()
{
	if(strstr($_SERVER['HTTP_USER_AGENT'],'iPhone') || strstr($_SERVER['HTTP_USER_AGENT'],'iPod')) {
    	return 1;
	}
	else
	{
		return 0;
	}
}


function getJams($query)
{
	$result = bink_query($query);
	$ret = "";


	while (	$row = mysqli_fetch_array($result) )
	{
		$ret .= "<div class='item'>";
		$ret .= "<h1><a href='jam.php?id=" . $row['id'] . "'>";
		$ret .= fDate($row['date']) . " - ";
		$ret .= $row['title'] . "</a></h1>";
		$ret .= getBandName($row['bandid']);
		$ret .= getLocationName($row['locid']);
		$ret .= "<br />&nbsp;<br /><div class='quote'>" . $row['notes'] . "";

		if ($row['defpic'] && $row['defpic'] != -1)
		{
			$picrow = mysqli_fetch_array(bink_query("select * from jams, pictures where pictures.id = jams.defpic && jams.id = " . $row['id']));
			$ret .= "<p /><img src='../getimage.php?f=" . $row['id'] . "/" . $picrow['filename'] . "&w=250&h=350' />";
		}

		$ret .= "</div></div>";
	}

	return $ret;
}

function printAJam($id)
{
	$result = bink_query("select * from jams where id = $id");
	$ret = "";
	while (	$row = mysqli_fetch_array($result) )
	{
		$ret .= "<div class='item'>";
		$ret .= "<h1><a href='jam.php?id=" . $row['id'] . "'>";
		$ret .= fDate($row['date']) . " - ";
		$ret .= $row['title'] . "</a></h1>";

		$band = getEntityByID($row['bandid'], "bands");
		$location = getEntityByID($row['locid'], "locations");

		if ($band != "" && $location != "")
			$ret .= $band . " - " . $location;
		else if ($band)
			$ret .= $band;
		else if ($location)
			$ret .= $location;

		$ret .= "<br /><p align='right'>";
		$ret .= embediPhonePlayer($id);
		$ret .= "</p><br /><div class='quote'>" . $row['notes'];
		$ret .= "<p align=right>";
		if (getNextId($id) != "")
			$ret .= "[ <a href='jam.php?id=" . getNextId($id) . "'>Next</a> ]";
		if (getPreviousId($id) != "")
			$ret .= "[ <a href='jam.php?id=" . getPreviousId($id) . "'>Prev</a> ]";
		$ret .= "</p>";
		$ret .= "</div>";
		$ret .= getPeopleList($id, "musician");
		$ret .= getPeopleList($id, "staff");
		$ret .= getPictures($id);
		$ret .= getMediaList($id, "music");
		$ret .= getMediaList($id, "video");
		$ret .= "</div>";
	}

	return $ret;
}

function embediPhonePlayer($id=-1)
{
  $result = bink_query("select * from tracks where jamid = $id order by num asc	");

  $row = mysqli_fetch_array($result);

  $toRet = "<embed target=\"myself\" src=\"play-button.gif\" href=\"https://s3.amazonaws.com/binkmedia/public/" . urlencode($row['path']) . "\" width=\"128\" height=\"16\" autoplay=\"true\" type=\"audio/mp3\" loop=\"true\" controller=\"false\"";

  $i = 1;
  while ($row = mysqli_fetch_array($result))
  {
  	$toRet .= " qtnext" . $i . "=\"<https://s3.amazonaws.com/binkmedia/public/" . urlencode($row['path']) . "> T<myself>\"";
  	$i++;
  }

  $toRet .= "></embed>";

  return $toRet;

}

function generateSearchLink($url, $query, $name, $offset, $length, $order, $sort, $bold = "")
{
	if ($bold)
		return "<u><font size=4><a href='$url.php?query=$query&offset=$offset&length=$length&order=$order&sort=$sort'>$name</u></a></font> | ";
	else
		return "<font size=4><a href='$url.php?query=$query&offset=$offset&length=$length&order=$order&sort=$sort'>$name</a></font> | ";
}


function getJamsSearch($listmode=0, $query=null, $offset=0, $length=3, $order="date", $sort="desc")
{
	$ret = "";
	if ($listmode == 3)
	{
		$ret .= "<div class='item'>";
		$columns = "jams.date, jams.id, jams.title, jams.locid";

		$sql = "select distinct $columns from jams, bands where jams.bandid=bands.id and bands.id = $query";
	}
	elseif ($listmode == 1)
	{
		$ret .= "<div class='item'>";
		$columns = "jams.date, jams.id, jams.title, jams.locid";

		$sql = "select distinct $columns from musiciansoncollection, jams, musicians where jams.private=0 and musicians.id = musiciansoncollection.musicianid and musiciansoncollection.jamid = jams.id and musicians.id = $query";
	}
	elseif ($listmode == 0)
	{
		$ret .= "<div class='item'>";
		$ret .= "<h1>Browsing the Collection</h1>";

		$sql = "select * from jams where private=0";
	}
	elseif ($listmode == 2)
	{
		$ret .= "<div class='item'>";
		$ret .= "<h1>Searching the Collection</h1>";

		$columns = "jams.date, jams.id, jams.title, jams.locid";

		$sTitle = "select $columns from jams where jams.private=0 and title like ('%$query%')";
		$sLocation = "select $columns from locations, jams where jams.private=0 and locations.name like ('%$query%') and jams.locid = locations.id";
		$sBand = "select $columns from bands, jams where jams.private=0 and bands.name like ('%$query%') and jams.bandid = bands.id";
		$sMusicians = "select $columns from musiciansoncollection, jams, musicians where jams.private=0 and musicians.id = musiciansoncollection.musicianid and musiciansoncollection.jamid = jams.id and musicians.name like ('%$query%')";
		$sStaff = "select $columns from productiononcollection, jams, staff where jams.private=0 and staff.id = productiononcollection.staffid and productiononcollection.jamid = jams.id and staff.name like ('%$query%')";
		$sNotes = "select $columns from jams where jams.private=0 and notes like ('%$query%')";
		$sTracks = "select $columns from tracks, jams where jams.private=0 and tracks.jamid = jams.id and tracks.title like ('%$query%')";

		$sql = "($sTitle) union ($sLocation) union ($sBand) union ($sMusicians) union ($sStaff) union ($sNotes) union ($sTracks)";
	}

	$result = bink_query($sql);

	$num = mysqli_num_rows($result);

	if ($length == "all")
		$sql = $sql . " order by $order $sort";
	else
		$sql = $sql . " order by $order $sort limit $offset, $length";

	//echo $sql;

	$result = bink_query($sql);

	$lenbold[$order] = 1;

	if ($listmode == 1)
		$url = "musician";
	elseif ($listmode == 2)
		$url = "search";
	else if ($listmode == 3)
		$url = "band";
	else
		$url = "list";

	$ret .= generateSearchLink($url, $query, "date", $offset, $length, "date", $sort, $lenbold["date"]);

	if ($sort == "desc")
		$ret .= generateSearchLink($url, $query, "reverse", $offset, $length, $order, "asc");
	else
		$ret .= generateSearchLink($url, $query, "forward", $offset, $length, $order, "desc");


	$lenbold[$length] = 1;

	$ret .= generateSearchLink($url, $query, "3", $offset, "3", $order, $sort, $lenbold[3]);
	$ret .= generateSearchLink($url, $query, "5", $offset, "5", $order, $sort, $lenbold[5]);
	$ret .= generateSearchLink($url, $query, "all", $offset, "all", $order, $sort, $lenbold["all"]);

	if ($offset+$length < $num && $length != "all")
		$ret .= generateSearchLink($url, $query, "next", $offset+$length, $length, $order, $sort);

	if ($offset-$length >= 0 && $length != "all")
		$ret .= generateSearchLink($url, $query, "prev", $offset-$length, $length, $order, $sort);



	$ret .= "</div><div class='item'>";

	$ret .= "<table width='100%'>";
	while (	$row = mysqli_fetch_array($result) )
	{
		$ret .= "<tr>";
		$ret .= "<td valign='top'>" . sDate($row['date']) . "&nbsp;&nbsp;</td>";
		$ret .= "<td valign='top'><a href='jam.php?id=" . $row['id'] . "'>";
		$ret .= $row['title'] . "</a></td>";
		$ret .= "</tr>";
	}
	$ret .= "</table>";
	$ret .= "<p align='right'>Listing " . $offset . " - " . ($offset + $length) . " of "  . $num . "</p>";
	$ret .= "</div>";
	return $ret;

}

function todayInHistory()
{

	echo "<div class='item'><h1>Today in BINK! History</h1>";
	echo "These are the jams that happened today, " . date("m/d") . " in previous years on BINK!";

	$sqldate = date("-m-d");

	$out = getJams("SELECT * FROM `jams` where private=0 and date LIKE('%$sqldate');");

	if ($out == "")
		echo "</div><div class='item'>Nothing has happened on this date in previous years!";
	else
		echo $out;

	echo "</div>";
}

function getBandInfo($id)
{
	$result = bink_query("select * from bands where id = $id");
	$row = mysqli_fetch_array($result);
	$musname = $row['name'];
	$link = $row['link'];
	$ret = "<div class='item'>";
	$ret .= "<h2>Band: $musname</h2>";

	$columns = "jams.date, jams.id, jams.title, jams.locid";

	$result = bink_query("select distinct $columns from jams, bands where jams.private=0 and jams.bandid=bands.id and bands.id=$id");

	$num = mysqli_num_rows($result);
	$ret .= "<strong>Number of Collections</strong>: $num<br />";
	$ret .= "<strong>Link</strong>: <a href='$link'>$link</a><br />";
	$ret .= "<strong>Played Locations</strong>: ";
	$result2 = bink_query("select distinct locations.name as name from jams, locations, bands where jams.bandid=bands.id and bands.id = $id and jams.locid = locations.id");
	$total = mysqli_num_rows($result2);
	$i=0;
	while ($row = mysqli_fetch_array($result2))
	{
		$i++;
		if ($i == $total)
			$ret .= $row['name'];
		else
			$ret .= $row['name'] . ", ";
	}

	$ret .= "</div>";

	return $ret;
}

function iconFor($type, $jamid)
{

	if ($type == "sound")
	{
		if(mysqli_num_rows(bink_query("select * from tracks where jamid = $jamid")) > 0)
			return "<img src='../img/soundicon.jpg' width=15 />";
		else
			return "&nbsp;";
	}

	if ($type == "pics")
	{
		if(mysqli_num_rows(bink_query("select * from pictures where jamid = $jamid")) > 0)
			return "<img src='../img/photoicon.jpg' width=15 />";
		else
			return "&nbsp;";
	}
	if ($type == "video")
	{
		if(mysqli_num_rows(bink_query("select * from video where jamid = $jamid")) > 0)
			return "<img src='../img/videoicon.jpg' width=15 />";
		else
			return "&nbsp;";
	}

}


function printJamHeader($id)
{
include "../settings.php";
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"https://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1"/>
<meta name="description" content="BINK! is an experiment in musical documentation."/>
<meta name="keywords" content="music, website, jams, mp3, free, download"/>
<meta name="author" content="Ben Smith"/>
<link rel="stylesheet" type="text/css" href="default.css"/>
<script language="javascript" src="js/ajax.js"></script>
 <head prefix="og: https://ogp.me/ns# fb: https://ogp.me/ns/fb# facebookbink: https://ogp.me/ns/fb/facebookbink#">
  <meta property="fb:app_id" content="139182602788074" />
  <meta property="og:type"   content="facebookbink:collection" />
  <meta property="og:url"    content="<?= $BASE_URL ?>/jam.php?id=<?=$id ?>" />
 <?php
  $result = bink_query("select title, notes from jams where id = $id");
  $row = mysqli_fetch_array($result);
  $title = $row['title'];
  $notes = $row['notes'];
  ?>
  <meta property="og:title"  content="<?= $title ?>" />
  <meta property="og:description"  content="<?=$notes ?>" />
  <meta property="og:image"  content="<?= $BASE_URL ?>/img/header.jpg" /> <title>BINK!</title>
</head>
<body>

<div class="main">
	<div class="gfx">
	<img src="../img/header.jpg" />
	</div>
	<div class="menu">
		<a href="index.php"><span>Recent</span></a>
		<a href="history.php"><span>History</span></a>
		<a href="list.php"><span>Browse</span></a>
		<?=printSearchBar(1) ?>
	</div>
	<div class="content">
<?php
}


function printHeader()
{
directPhone();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"https://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1"/>
<meta name="description" content="BINK! is an experiment in musical documentation."/>
<meta name="keywords" content="music, website, jams, mp3, free, download"/>
<meta name="author" content="Ben Smith"/>
<link rel="stylesheet" type="text/css" href="default.css"/>
<script language="javascript" src="js/ajax.js"></script>
<title>BINK!</title>
</head>
<body>

<div class="main">
	<div class="gfx">
	<img src="../img/header.jpg" />
	</div>
	<div class="menu">
		<a href="index.php"><span>Recent</span></a>
		<a href="history.php"><span>History</span></a>
		<a href="list.php"><span>Browse</span></a>
		<?=printSearchBar(1) ?>
	</div>
	<div class="content">
<?php
}


function printFooter()
{
?>
	<div class='item'>
</div></div>
	<div class="footer">&copy; 2009-2021 <a href="index.php">BINK!</a> created by <a href="http://bsmith.me">Ben Smith</a>. Design by <a href="https://arcsin.se">Arcsin</a></div>
</div>
</body>
</html>
<?php
}
?>
