<?php
include "S3.php";

function sql()
{
	include "settings.php";
	if (!@mysql_connect($DB_HOST, $DB_USERNAME, $DB_PASSWORD)) {
		echo "<h2>Could not connect to mySQL</h2>";
		die;
	}
	if (mysql_select_db($DB_NAME) == 0)
	{
		print "<h2>Could not select bink database</h2>";
		die;
	}
	
}

function newPlaylist()
{
	$token = "N/A";
	do 
	{
		$token = randString(32);
		$result = mysql_query("select * from playlists where id = '$token'");
	} while (mysql_num_rows($result) > 0);
	
	return $token;
}

function getToken()
{
	 $token = "N/A";
	 $result = mysql_query("select * from tokens where ip = '" . $_SERVER['REMOTE_ADDR'] . "'");
	 if (mysql_num_rows($result))
	 {
		 $row = mysql_fetch_array($result);
		 $token = $row['token'];
	 }
	 else
	 {
		 $token = randString(16);
		 mysql_query("insert into tokens (ip, token) values ('" . $_SERVER['REMOTE_ADDR'] . "', '$token')");
	 }
	 
	 return $token;
}

function deleteOldTokens()
{
	$result = mysql_query("select * from tokens");
	
	while ($row = mysql_fetch_array($result))
	{
	
		$age = checkTokenAge($row['ip']);
		
		if ($age > 5)
		{
			mysql_query("delete from tokens where ip = '" . $row['ip'] . "'");
		}
	}

}

function checkTokenAge($ip)
{

	 $result = mysql_query("select timestampdiff(HOUR, (SELECT date from tokens where ip = '$ip'), NOW()) as diff;");
	 $row = mysql_fetch_array($result);
	 $age = $row['diff'];
	 
	 return $age;
}

function validToken($ip, $token)
{
	deleteOldTokens();
	$result = mysql_query("select * from tokens where ip = '$ip' and token = '$token'");
	
	if (mysql_num_rows($result) === 0)
	{
		return false;
	}
	
	return true;

}

function randString($length, $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
{
    $str = '';
    $count = strlen($charset);
    while ($length--) {
        $str .= $charset[mt_rand(0, $count-1)];
    }
    return $str;
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

function directPhone()
{
	include "settings.php";
	if(strstr($_SERVER['HTTP_USER_AGENT'],'iPhone') || strstr($_SERVER['HTTP_USER_AGENT'],'iPod')) {
    	header("Location: " . $BASE_URL . "/m");
	}
}

function getMusicianInfo($id)
{
	$result = mysql_query("select * from musicians where id = $id");
	$row = mysql_fetch_array($result);
	$musname = $row['name'];
	$link = $row['link'];
	$ret = "<div class='item'>";
	$ret .= "<h2>Musician: $musname</h2>";
	
	$columns = "jams.date, jams.id, jams.title, jams.locid";
	
	$result = mysql_query("select distinct $columns from musiciansoncollection, jams, musicians where jams.private=0 and musicians.id = musiciansoncollection.musicianid and musiciansoncollection.jamid = jams.id and musicians.id = $id");

	$num = mysql_num_rows($result);
	$ret .= "<strong>Number of Collections</strong>: $num<br />";
	$ret .= "<strong>Link</strong>: <a href='$link'>$link</a><br />";
	$ret .= "<strong>Played Instruments</strong>: ";
	$result2 = mysql_query("select distinct instruments.name from musiciansoncollection, instruments where musiciansoncollection.instrumentid = instruments.id and musiciansoncollection.musicianid = $id;");
	$total = mysql_num_rows($result2);
	$i=0;
	while ($row = mysql_fetch_array($result2))
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

function getBandInfo($id)
{
	$result = mysql_query("select * from bands where id = $id");
	$row = mysql_fetch_array($result);
	$musname = $row['name'];
	$link = $row['link'];
	$ret = "<div class='item'>";
	$ret .= "<h2>Band: $musname</h2>";
	
	$columns = "jams.date, jams.id, jams.title, jams.locid";
	
	$result = mysql_query("select distinct $columns from jams, bands where jams.private=0 and jams.bandid=bands.id and bands.id=$id");

	$num = mysql_num_rows($result);
	$ret .= "<strong>Number of Collections</strong>: $num<br />";
	$ret .= "<strong>Link</strong>: <a href='$link'>$link</a><br />";
	$ret .= "<strong>Played Locations</strong>: ";
	$result2 = mysql_query("select distinct locations.name as name from jams, locations, bands where jams.bandid=bands.id and bands.id = $id and jams.locid = locations.id");
	$total = mysql_num_rows($result2);
	$i=0;
	while ($row = mysql_fetch_array($result2))
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

function makethumb($id, $filename, $max_width=100, $max_height=100)
{
  include "settings.php";
  $s3 = new S3($S3_ACCESS_KEY, $S3_SECRET_KEY);
  echo "With id $id and filename $filename\n";

	$awspath = "http://binkmedia.s3.amazonaws.com/public/pics/$id/$filename";
	$localthmpath = "/var/tmp/$filename.thm";
	$uploadpath = "public/pics/$id/$filename.thm";

	echo "AWS path: $awspath\n";  
	echo "Local path: $localthmpath\n";
	echo "Upload path: $uploadpath\n";
  list($orig_width, $orig_height) = getimagesize($awspath);

   $width = $orig_width;
   $height = $orig_height;

   # taller
   if ($max_height != 0 && $max_width != 0)
   {
	   if ($height > $max_height) {
		   $width = ($max_height / $height) * $width;
		   $height = $max_height;
	   }

	   # wider
	   if ($width > $max_width) {
		   $height = ($max_width / $width) * $height;
		   $width = $max_width;
	   }
   }

   $image_p = imagecreatetruecolor($width, $height);

   $image = imagecreatefromjpeg($awspath);

   imagecopyresampled($image_p, $image, 0, 0, 0, 0, 
                                     $width, $height, $orig_width, $orig_height);

   imagejpeg($image_p,  $localthmpath); 
   $s3 -> putObjectFile($localthmpath, "binkmedia", $uploadpath, "public-read-write", null, "image/jpeg");
   unlink($localthmpath);
}

function generateSearchLink($url, $query, $name, $offset, $length, $order, $sort, $bold = "")
{
	if ($bold)
		return "[ <font size=2><a href='$url.php?query=$query&offset=$offset&length=$length&order=$order&sort=$sort'>$name</font></a> ]";
	else
		return "[ <a href='$url.php?query=$query&offset=$offset&length=$length&order=$order&sort=$sort'>$name</a> ]";
}

function getJamsSearch($listmode=0, $query=null, $offset=0, $length=20, $order="date", $sort="desc")
{
	
	sql();

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
	
	$result = mysql_query($sql);
	
	$num = mysql_num_rows($result);

	if ($length == "all")
		$sql = $sql . " order by $order $sort";
	else
		$sql = $sql . " order by $order $sort limit $offset, $length";

	//echo $sql;

	$result = mysql_query($sql);

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
	$ret .= generateSearchLink($url, $query, "id", $offset, $length, "id", $sort, $lenbold["id"]);
	$ret .= generateSearchLink($url, $query, "location", $offset, $length, "locid", $sort, $lenbold["locid"]);

	if ($sort == "desc")
		$ret .= generateSearchLink($url, $query, "reverse", $offset, $length, $order, "asc"); 	
	else
		$ret .= generateSearchLink($url, $query, "forward", $offset, $length, $order, "desc");
	
	
	$lenbold[$length] = 1;
	
	$ret .= generateSearchLink($url, $query, "5", $offset, "5", $order, $sort, $lenbold[5]);
	$ret .= generateSearchLink($url, $query, "10", $offset, "10", $order, $sort, $lenbold[10]);
	$ret .= generateSearchLink($url, $query, "20", $offset, "20", $order, $sort, $lenbold[20]);
	$ret .= generateSearchLink($url, $query, "50", $offset, "50", $order, $sort, $lenbold[50]);
	$ret .= generateSearchLink($url, $query, "100", $offset, "100", $order, $sort, $lenbold[100]);
	$ret .= generateSearchLink($url, $query, "all", $offset, "all", $order, $sort, $lenbold["all"]);

	if ($offset+$length < $num && $length != "all")
		$ret .= generateSearchLink($url, $query, "next", $offset+$length, $length, $order, $sort);
	
	if ($offset-$length >= 0 && $length != "all")
		$ret .= generateSearchLink($url, $query, "prev", $offset-$length, $length, $order, $sort);
	

	
	$ret .= "<br />Listing " . $offset . " - " . ($offset + $length) . " of "  . $num;
	$ret .= "</div><div class='item'><h1>Results</h1>";
	
	$ret .= "<table width='100%'>";
	while (	$row = mysql_fetch_array($result) )
	{
		$ret .= "<tr>";
		$ret .= "<td>" . fDate($row['date']) . "</td>";
		$ret .= "<td><a href='jam.php?id=" . $row['id'] . "'>"; 
		$ret .= $row['title'] . "</a></td>";
		$ret .= "<td>" . getEntityByID($row['locid'], "locations") . "</td>";
		$ret .= "<td>" . iconFor("sound", $row['id']) . "</td>";
		$ret .= "<td>" . iconFor("pics", $row['id']) . "</td>";
		$ret .= "<td>" . iconFor("video", $row['id']) . "</td>";
		$ret .= "</tr>";	
	}
	$ret .= "</table></div>";
	return $ret;	

}

function iconFor($type, $jamid)
{

	if ($type == "sound")
	{
		if(mysql_num_rows(mysql_query("select * from tracks where jamid = $jamid")) > 0)
			return "<img src='img/soundicon.jpg' width=15 />";
		else
			return "&nbsp;";
	}
	
	if ($type == "pics")
	{
		if(mysql_num_rows(mysql_query("select * from pictures where jamid = $jamid")) > 0)
			return "<img src='img/photoicon.jpg' width=15 />";
		else
			return "&nbsp;";
	}
	if ($type == "video")
	{
		if(mysql_num_rows(mysql_query("select * from video where jamid = $jamid")) > 0)
			return "<img src='img/videoicon.jpg' width=15 />";
		else
			return "&nbsp;";
	}
		
}

function getJams($query)
{
	sql();
	$result = mysql_query($query);
	$ret = "";
	
	
	while (	$row = mysql_fetch_array($result) )
	{
		$ret .= "<div class='item'>";
		$ret .= "<h1><a href='jam.php?id=" . $row['id'] . "'>"; 
		$ret .= fDate($row['date']) . " - ";
		$ret .= $row['title'] . "</a></h1>";
		$ret .= getBandName($row['bandid']);
		$ret .= getLocationName($row['locid']);
		$ret .= "<br />&nbsp;<br /><div class='quote'>" . $row['notes'] . "</div>";
		
		if ($row['defpic'] && $row['defpic'] != -1)
		{
			$picrow = mysql_fetch_array(mysql_query("select * from jams, pictures where pictures.id = jams.defpic && jams.id = " . $row['id']));
			$ret .= "<p align='center'><img src='getimage.php?f=" . $row['id'] . "/" . $picrow['filename'] . "&w=300&h=400' /></p>";
		}
		
		$ret .= "</div>";	
	}

	return $ret;	
}

function getJamsMobile($query)
{
	sql();
	$result = mysql_query($query);
	$ret = "";
	
	
	while (	$row = mysql_fetch_array($result) )
	{
		$ret .= "<div class='item'>";
		$ret .= "<h1><a href='jam.php?id=" . $row['id'] . "'>"; 
		$ret .= fDate($row['date']) . " - ";
		$ret .= $row['title'] . "</a></h1>";
		$ret .= getBandName($row['bandid']);
		$ret .= getLocationName($row['locid']);
		$ret .= "<br />&nbsp;<br /><div class='quote'>" . $row['notes'];
		
		if ($row['defpic'] && $row['defpic'] != -1)
		{
			$picrow = mysql_fetch_array(mysql_query("select * from jams, pictures where pictures.id = jams.defpic && jams.id = " . $row['id']));
			$ret .= "<p /><img src='../getimage.php?f=" . $row['id'] . "/" . $picrow['filename'] . "&w=200&h=300' />";
		}
		
		$ret .= "</div></div>";	
	}

	return $ret;	
}

function getEntityByID($id, $table)
{
	$result = mysql_query("select * from $table where id = $id");
	
	if ($result == null)
		return "";
	if (mysql_num_rows($result) == 0)
		return "";
		
	$entity = mysql_fetch_array($result);
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
		$ret = "<a href='http://en.wikipedia.org/wiki/" . $entity['name'] . "'>" . 	$entity['name'] . "</a>";
	}
	else
	{
		$ret =  $entity['name'];
	}
	return $ret;
}



function getMediaList($id, $type)
{
	include "settings.php";
	$s3 = new S3($S3_ACCESS_KEY, $S3_SECRET_KEY);

	/**
	 * These two lines are required for reading MP3 file information.
	 */
	//require_once('getid3/getid3.php');
	//$getID3 = new getID3;

	if ($type == "music")
	{
		$table = "tracks";
		$header = "Music";
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

	$result = mysql_query("select * from $table where jamid = $id order by num asc");
	if (mysql_num_rows($result) == 0)
		return "";
	
	$ret = "<script language='javascript' src='js/ajax.js'></script>";
	$ret .= "<div class='item'><h1>$header</h1>";
	
	$ret .= "<ol><table width='100%'>";
	while (	$row = mysql_fetch_array($result) )
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
			/**
			 * These two lines are required for reading MP3 file information.
			 */
			/*
			//$ThisFileInfo = $getID3->analyze("https://s3.amazonaws.com/binkmedia/public/$path");
			//getid3_lib::CopyTagsToComments($ThisFileInfo);
			*/
			$ret .= "<tr>";
			$ret .= "<td><a href=\"https://s3.amazonaws.com/binkmedia/public/$path\"><li>" . $row['title'] . "</li></a></td>";
			/*$ret .= "<td> " . resize_bytes(filesize("https://s3.amazonaws.com/binkmedia/public/$path")) . "</td>"; */
			$ret .= "<td>" . resize_bytes($thisfile['size']) . "</td>";
			$ret .= "<td>" . $ext . " file </td>";
			//$ret .= "<td>" . $ThisFileInfo['playtime_string']. "</td>";
			//$ret .= "<td>" . ($ThisFileInfo['audio']['bitrate']/1000). " KB/s </td>";
			
			$id = $row['id'];
			$notes = $row['notes'];
			if ($row['notes'] != " " && $row['notes'] != "")
			{
				$ret .= "<td><a id='sho$id' href=\"javascript: add('notes$id', '$notes'); show('hid$id'); hide('sho$id'); \">notes</a><a id='hid$id' style=\"visibility:hidden\" href=\"javascript: clear('notes$id'); hide('hid$id'); show('sho$id'); \">hide</a></td></tr>";
				$ret .= "<tr><td colspan=5 align=center id='notes$id'></td></tr>";
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

function produceZIPFile($id)
{
	include "settings.php";
	//exec('watchlog.php?id=$id');
	?>
	<script language="javascript">
	function ajaxRequest(){
	 var activexmodes=["Msxml2.XMLHTTP", "Microsoft.XMLHTTP"] //activeX versions to check for in IE
	 if (window.ActiveXObject){ //Test for support for ActiveXObject in IE first (as XMLHttpRequest in IE7 is broken)
	  for (var i=0; i<activexmodes.length; i++){
   	try{
    	return new ActiveXObject(activexmodes[i])
	   }
   	catch(e){
    //suppress error
	   } 
	  }
	 }
	 else if (window.XMLHttpRequest) // if Mozilla, Safari etc
		  return new XMLHttpRequest()
	 else
	  return false
	}
	
	function updateStatus()
	{
		var mygetrequest=new ajaxRequest()
		mygetrequest.onreadystatechange=function(){
	 if (mygetrequest.readyState==4){
	  if (mygetrequest.status==200 || window.location.href.indexOf("http")==-1){
	  var textbox = document.getElementById("result");
	   textbox.value=mygetrequest.responseText;
	   textbox.scrollTop = document.getElementById("result").scrollHeight;
	   if (textbox.value.indexOf('DONE...') != -1)
	   {
	   	   
	   	   document.location = "/dozip.php?dl=1";
	   	   clearInterval(intervalid);
	   }
	   	
	  }
	  else{
	   alert("An error has occured making the request")
	  }
	 }
	}
	
	mygetrequest.open("GET", "/viewlog.php", true)
	mygetrequest.send(null);
	}
	var intervalid = setInterval('updateStatus();', 500);
	</script>
	<div class='item'>
	<h1>Creating ZIP file for download</h1>
	Depending on the size of your order, this may take some time.
	The box below displays the server's output as it downloads and compresses
	each file in the collection you've requested into a ZIP.  Once the process
	has been completed, you will automatically receive the ZIP file for download.
	The file will be called "binkcollection.zip".
	<img src="<?= $BASE_URL ?>/dozip.php?id=<?=$id ?>" width=0 height=0/>
	<textarea style="background:black; color: white" id="result" cols=80 rows=10></textarea>
	</div>
	<?	
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
	
	$result = mysql_query("select * from $table where jamid = $id order by $idlabel");
	if (mysql_num_rows($result) == 0)
		return "";
		
	$ret = "<div class='item'><h1>$header</h1>";
	$currentMusician = -1;
	while (	$row = mysql_fetch_array($result) )
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

function getNextId($id)
{
	$result = mysql_query("select * from jams where private=0 order by date desc;");
	
	while ($row = mysql_fetch_array($result))
	{
		if ($row['id'] == $id)
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
	
	$result = mysql_query("select * from jams where private=0 order by date asc;");
	
	while ($row = mysql_fetch_array($result))
	{
		if ($row['id'] == $id)
		{
			return $oldrow['id'];
		}
		
		$oldrow = $row;
	}
	
	return -1;
}



function printAJam($id, $trackid)
{
	sql();
	$result = mysql_query("select * from jams where id = $id");
	$ret = "";
	while (	$row = mysql_fetch_array($result) )
	{
		$id = $row['id'];
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
		
		$ret .= getLocationMap($row['locid']);
		
		if(mysql_num_rows(mysql_query("select * from tracks where jamid = $id")) > 0)
			$ret .= printCustomPlayer($id, $trackid);
		$ret .= "<br />&nbsp;<br /><div class='quote'>" . $row['notes'];
		$ret .= "<p align=right>";
		if (getNextId($id) != "")
			$ret .= "[ <a href='jam.php?id=" . getNextId($id) . "'>Next</a> ]";
		if (getPreviousId($id) != "")
			$ret .= "[ <a href='jam.php?id=" . getPreviousId($id) . "'>Prev</a> ]";
		$ret .= "[ <a href='admin/main.php?id=$id'>Edit</a> ]";
		$ret .= "[ <a href=\"javascript:show('shareBox');queryHTML('shareBox', 'share.php?jamid=$id&title=" . urlencode($row['title']) . "')\">Share</a> ]";
		$ret .= getShareBox($id, $row['title']);
		$ret .= "</p>";
		$ret .= "</div>";
		$ret .= getPeopleList($id, "musician");
		$ret .= getPeopleList($id, "staff");
		$ret .= getPictures($id);
		$ret .= getMediaList($id, "music");
		$ret .= getMediaList($id, "video");
		$ret .= "</div>";
		if(mysql_num_rows(mysql_query("select * from tracks where jamid = $id")) > 0)
			$ret .= "<div class='item'>[ <a href='/makezip.php?id=$id'>Download as ZIP</a> ]</div>";

	}

	return $ret;	
}

function getShareBox($jamid, $title)
{
	
?>
<div id='shareBox' style='visibility:hidden; position: absolute; top: 25%; right: 25%; bottom: 25%; left: 25%; margin: 5px; padding: 25px; border: solid 3px grey;z-index:5; background: black; color: white'>
</div>
<?php
}

function getPictures($jamid)
{
	
	$num = mysql_num_rows(mysql_query("select * from pictures where jamid = $jamid"));
	if ($num == 0)
	{
		return "";
	}
	if ($num == 1)
	{
		$toprow = mysql_fetch_array(mysql_query("select * from pictures where jamid = $jamid"));
		$filename = $toprow['filename'];
		return "<div class='item'><h1>Pictures</h1><a href='getimage.php?f=$jamid/$filename'><img border=0 src='getimage.php?f=$jamid/$filename&w=500&h=400'></a></div>";
	}
	$row = mysql_fetch_array(mysql_query("SELECT * FROM jams, pictures where jams.defpic = pictures.id and jams.id=$jamid"));

	$ret = "<div class='item'><h1>Pictures</h1>";
	$ret .= "<div id='loadindicator' style='float: right'>Loading...</div>";

	if ($row['filename'])
	{
		$ret .= "<img onLoad=\"setHTML('loadindicator', '');\" name='mainpic' border=0 id='mainpic' src='getimage.php?f=$jamid/" . $row['filename'] . "&w=500&h=400' />";
	}
	else
	{
		$row = mysql_fetch_array(mysql_query("SELECT * FROM pictures where pictures.jamid = $jamid"));
		$ret .= "<a id='imagelink' href='getimage.php?f=$jamid/" . $row['filename'] . "'><img name='mainpic' onLoad=\"setHTML('loadindicator', '');\"  border=0 id='mainpic' src='getimage.php?f=$jamid/" . $row['filename'] . "&w=500&h=400' /></a>";
	}
	$ret .= "<br />";
	$ret .= "<table border=0 width='500'><tr><td>";
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

function fDate($date)
{
	return date("m/d/Y", strtotime($date));
}

function getLocationName($id)
{
	$result = mysql_query("select * from locations where id = $id");
	$row = mysql_fetch_array($result);
	return $row['name'];
}

function getBandName($id, $at=1)
{
	$result = mysql_query("select * from bands where id = $id");
	if (mysql_num_rows($result) > 0)
	{
		$row = mysql_fetch_array($result);
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

function getNumberOf($table, $label)
{
	$result = mysql_query("SELECT * FROM `$table`;");
	$num = mysql_num_rows($result);
	return "<tr><td>$label</td><td>$num</td></tr>";
}



function getInfo()
{
	sql();

	$result = mysql_query("SELECT * FROM `jams` where private=0 ORDER BY `date`;");
	$row = mysql_fetch_array($result);
	$earliest = $row['date'];
	$earliest = date("n/j/y", strtotime($earliest));
		?>
		<div class='item'><h1>Statistics</h1>
		<table width=100%>
			<?= getNumberOf("jams", "Collections") ?>
			<?= getNumberOf("tracks", "Tracks"); ?>
			<?= getNumberOf("video", "Videos"); ?>
			<?= getNumberOf("musicians", "Musicians"); ?>
			<?= getNumberOf("pictures", "Pictures"); ?>
			<?= getNumberOf("locations", "Locations"); ?>
			<td>Earliest Collection</td><td><?= $earliest ?></td></tr>
			</table>
		</div>
<?php

	printLogs("Upcoming Features & Fixes", "select * from upcoming order by time desc;");
	printLogs("Change History", "select * from changelog order by time desc limit 20;");
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

function printLogs($title, $query)
{
		echo "<div class='item'><h1>$title</h1>";
		echo "<ul>";
		$result = mysql_query($query);

		while ($row = mysql_fetch_array($result))
		{
			echo "<li> (" . date("n/j/y g:ia", strtotime($row['time'])). ") " . $row['change'];
		}
		echo "</ul></div>";
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

function printSearchBar($m = 0)
{

if ($_GET['query'])
	$q = $_GET['query'];
else
	$q = "Search";

if ($_SERVER["PHP_SELF"] == "/musician.php")
	$q = "Search";
else if ($_SERVER["PHP_SELF"] == "/band.php")
	$q = "Search";

	$ret = "<input type=text style=\"background:black; color: gray; border-color: gray; vertical-align: text-top; width: 115px; height: 25px\" value=\"$q\" onkeydown=\"if (event.keyCode == 13) location='/search.php?query=' + value;\" onClick=\"value='';\" />";

	return $ret;
}


function printRSS($podcast=0)
{

include "settings.php";
header("Content-Type: application/xml");

?>
<rss version="2.0">
	<channel>
		<title>BINK!</title>
		<link><?= $BASE_URL ?></link>
		<?php
		if ($podcast)
		{
		?>
		<description>A podcast of BINK&#039;s latest postings</description>
		<?php
		}
		else
		{
		?>
		<description>A listing of BINK&#039;s latest postings</description>
		<?php
		}
		?>
		<language>English</language>
		<managingEditor>Ben Smith</managingEditor>
		<webMaster>Ben Smith</webMaster>
		<pubDate>Mon, 12 Apr 2010 19:34:28 -0400</pubDate>
		<lastBuildDate>Mon, 12 Apr 2010 19:34:28 -0400</lastBuildDate>
		
		<image>
			<title>BINK</title>
			<url><?= $BASE_URL ?>/img/header.jpg</url>
			<link><?= $BASE_URL ?></link>
			<width>200</width>
			<height>400</height>
		</image>
		
<?php

	sql();
	$result = mysql_query("select * from jams where private=0 order by date desc limit 0,20 ");
	$ret = "";
	
	if ($podcast)
	{
		while (	$row = mysql_fetch_array($result) )
		{
			$innerresults = mysql_query("select * from tracks where tracks.jamid = " . $row['id'] . " order by num asc");
			
			$seconds = 1000;
			while ($innerrow = mysql_fetch_array($innerresults))
			{
				echo "\t\t<item>\n";
				echo "\t\t\t<title>" . $row['title'] . " - " . $innerrow['title'] . "</title>\n";
				echo "\t\t\t<link>http://mustbehighorlow.com/bink/jam.php?id=" . $row['id'] . "</link>\n";
				echo "\t\t\t<description>";
				if (getBandName($row['bandid']))
					echo getBandName($row['bandid']);
				if (getLocationName($row['locid']))
					echo getLocationName($row['locid']) . ". ";
				echo  $row['notes'] . "</description>\n";
				echo "<pubDate>" . date(DATE_RSS, strtotime($row['date']) + $seconds) . "</pubDate>";
				
				$seconds--;
				
				$filename = substr(strrchr($innerrow['path'], "/"), 1);
				$filename = rawurlencode($filename);
				
				echo "<enclosure url=\"http://binkmedia.s3.amazonaws.com/public/snd/" . $row['id'] . "/$filename\" type=\"audio/mpeg\"/>";
				echo "\t\t</item>\n";
			}
		}
	}
	else
	{
		while (	$row = mysql_fetch_array($result) )
		{
			echo "\t\t<item>\n";
			echo "\t\t\t<title>" . $row['title'] . "</title>\n";
			echo "\t\t\t<link>http://mustbehighorlow.com/bink/jam.php?id=" . $row['id'] . "</link>\n";
			echo "\t\t\t<description>";
			if (getBandName($row['bandid']))
				echo getBandName($row['bandid']);
			if (getLocationName($row['locid']))
				echo getLocationName($row['locid']) . ". ";
			echo  $row['notes'] . "</description>\n";
			echo "<pubDate>" . date(DATE_RSS, strtotime($row['date'])) . "</pubDate>";
			echo "\t\t</item>\n";
		}
	}

?>
	</channel>
</rss>

<?php

}

function printPlayer($id)
{

sql();

$row = mysql_fetch_array(mysql_query("select * from jams where private=0 and id = $id"));
$jtitle = urlencode($row['title']);

$num = mysql_num_rows(mysql_query("select * from tracks where jamid = $id"));
if ($num == 0)
	return;

echo "<div style='position:relative; left: 295px; top: 30px; height: 0px; width: 0px'><object type='application/x-shockwave-flash' width=300 height=15  data='xspf/xspf_player_slim.swf?playlist_url=xspf.php?id=$id&player_title=$jtitle'><param name='movie' value='xspf/xspf_player_slim.swf?playlist_url=xspf.php?id=$id&player_title=$jtitle' /></object></div>";

}

function getLocationInfoWindow($id, $name)
{
		$subresult = mysql_query("select * from jams where locid = $id limit 3;");
		$jamlist = "";
		if ($subresult == null)
			return "";
		while ($subrow = mysql_fetch_array($subresult))
		{
			$jamlist .= "<a href='jam.php?id=" . $subrow['id'] . "'>" . $subrow['title'] . "</a>. ";
		}
		$info = "$name<hr />At this location: $jamlist";
		
		return $info;
}

function getMasterMap()
{
	sql();
	$result = mysql_query("select * from locations where address <> ''");
	
	$timeout = 0;
	$scriptStr = "<script type=\"text/javascript\">\nfunction loadMarkers() {\n";
		
	while ($row = mysql_fetch_array($result))
	{
		$lat = $row['lat'];
		$lon = $row['lon'];
		
		$info = getLocationInfoWindow($row['id'], $row['name']);
		
		if ($lat != "" && $lat != null && $lat != " ")
		{
			$scriptStr .= "addMarker($lat, $lon, \"$info\", false);\n";
			$timeout += 500;
		}
	}
	
	$scriptStr .= "\n}</script><div id='map_canvas' style='width:585px; height:500px'></div>";
	return $scriptStr;
}

function getLocationMap($locid)
{
	sql();
	$ret = "";
	$result = mysql_query("select * from locations where id = $locid");
	$row = mysql_fetch_array($result);
	$name = $row['name'];
	$lat = $row['lat'];
	$lon = $row['lon'];
	$info = getLocationInfoWindow($row['id'], $row['name']);
	
	if ($lat != "" && $lat != null && $lat != " ")
	{
		$ret .= "<script type=\"text/javascript\">\nfunction loadMarkers() { \n\t addMarker($lat, $lon, \"$info\", true);\n }</script><p /><div id='map_canvas' style='width:585px; height:200px'></div>";
	}
	return $ret;
}





function printJamHeader($id)
{

include "settings.php";

if ($root == 0)
{
	$leading = "../";
	$adminStr = "main.php";
}
else
{
	$leading = "";
	$adminStr = "admin/main.php";
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1"/>
<meta name="description" content="BINK! is an experiment in musical documentation."/>
<meta name="keywords" content="music, website, jams, mp3, free, download"/> 
<meta name="author" content="Ben Smith"/> 
 <head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# facebookbink: http://ogp.me/ns/fb/facebookbink#">
  <meta property="fb:app_id" content="139182602788074" /> 
  <meta property="og:type"   content="facebookbink:collection" /> 
  <meta property="og:url"    content="<?= $BASE_URL ?>/jam.php?id=<?=$id ?>" /> 
  <?php
  sql();
  $result = mysql_query("select title, notes from jams where id = $id");
  $row = mysql_fetch_array($result);
  $title = $row['title'];
  $notes = $row['notes'];
  ?>
  <meta property="og:title"  content="<?= $title ?>" /> 
  <meta property="og:description"  content="<?=$notes ?>" /> 
  <meta property="og:image"  content="<?= $BASE_URL ?>/img/header.jpg" /> 
<?php
if ($root == 0)
	echo "<META HTTP-EQUIV='PRAGMA' CONTENT='NO-CACHE'>";
?>
<link rel="stylesheet" type="text/css" href="<?= $leading ?>default.css"/>
<script language="javascript" src="<?= $leading ?>js/ajax.js"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script> 
<script type="text/javascript"> 
  var geocoder;
  var map;
  var lastopen;
  var timeout = 0;
  function initialize() {
    var myOptions = {
      zoom: 4,
      center:  new google.maps.LatLng(37.987, -84.476),
      mapTypeId: google.maps.MapTypeId.ROADMAP
    }
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
    loadMarkers();
  }
  
  function addMarker(lat, lng, title, center)
  {
  	setTimeout("callAddMarker(" + lat + ", " + lng + ", \"" + title + "\", " + center + ");", timeout);
  	timeout += 30;
  }
  
  function callAddMarker(lat, lng, title, center) {
  	var latlng = new google.maps.LatLng(lat, lng);
  	
  	if (center)
  	{
  		map.setCenter(latlng);
  		map.setZoom(15);
  	}
   
    var marker = new google.maps.Marker({
    	position: latlng,
    	animation: google.maps.Animation.DROP,
    	map: map});
 	 

 	 if (title ==null)
 	 	return;
 	 
 	 var infowindow = new google.maps.InfoWindow({
 	 	content: title
 	 	});
 	 	
 	 google.maps.event.addListener(marker, 'click', function() {
 	 	if (lastopen != null)
 	 		lastopen.close();
 	 	infowindow.open(map,marker);
 	 	lastopen = infowindow;
 	 });
 	 
  }
</script> 
<title>BINK!</title>
</head>
<body onload="initialize();">
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=139182602788074";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>

<div class="main">
	<div class="gfx">
	<table border=0 width="100%">
	<tr>
	<td width="670px"><img src="<?= $leading ?>img/header.jpg" /></td>
	<td width="120px"><a href="<?= $leading ?>feed.php"><img src=<?= $leading ?>img/rss-0.91.gif border=0 /></a></td>
	<td width="30px"><a href="feed.php?podcast=1"><img src=<?= $leading ?>img/podcast.gif border=0 width=25/></a></td>
	<td width="20px"><div class="fb-like" data-href="<?= $BASE_URL ?>" data-send="true" data-layout="button_count" data-width="20" data-show-faces="true" data-colorscheme="dark" data-font="lucida grande"></div></fb:like></td>
	</tr>
	</table>
	</div>
	<div class="menu">
		<a href="<?= $leading ?>index.php"><span>Recent</span></a>
		<a href="<?= $leading ?>history.php"><span>History</span></a>
		<a href="<?= $leading ?>player.php"><span>Player</span></a>
		<a href="<?= $leading ?>list.php"><span>Browse</span></a>
 	    <a href="/timeline/"><span>Timeline</span></a>
		<a href="<?= $leading ?>maps.php"><span>Map</span></a>
		<a href="<?= $leading ?>news.php"><span>Tweets</span></a>
 		<a href="/admin/main.php"><span>Admin</span></a>
	
		<span><?=printSearchBar() ?></span>
	</div>
	<div class="content">		
<?php
}

function printHeader($root=1, $maps=0)
{

include "settings.php";

if ($root == 0)
{
	$leading = "../";
	$adminStr = "main.php";
}
else
{
	$leading = "";
	$adminStr = "admin/main.php";
}

directPhone();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
<script charset="utf-8" src="http://widgets.twimg.com/j/2/widget.js"></script>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1"/>
<meta name="description" content="BINK! is an experiment in musical documentation."/>
<meta name="keywords" content="music, website, jams, mp3, free, download"/> 
<meta name="author" content="Ben Smith"/> 
<meta name="google-site-verification" content="jMHQ1vNX95MNBOYsGemuRSIsT-CH_3Rwh6N3wEg69bY" />
<meta property="og:title" content="BINK! An experiment in musical documentation" />
<meta property="og:type" content="website" />
<meta property="og:url" content="<?= $BASE_URL ?>" />
<meta property="og:image" content="<?= $BASE_URL ?>img/header.jpg" />
<meta property="og:site_name" content="BINK!" />
<meta property="fb:admins" content="11801699" />
<?php
if ($root == 0)
	echo "<META HTTP-EQUIV='PRAGMA' CONTENT='NO-CACHE'>";
?>
<link rel="stylesheet" type="text/css" href="<?= $leading ?>default.css"/>
<?php
if ($maps)
{
?>
<script language="javascript" src="<?= $leading ?>js/ajax.js"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script> 
<script type="text/javascript"> 
  var geocoder;
  var map;
  var lastopen;
  var timeout = 0;
  function initialize() {
    var myOptions = {
      zoom: 4,
      center:  new google.maps.LatLng(37.987, -84.476),
      mapTypeId: google.maps.MapTypeId.ROADMAP
    }
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
    loadMarkers();
  }
  
  function addMarker(lat, lng, title, center)
  {
  	setTimeout("callAddMarker(" + lat + ", " + lng + ", \"" + title + "\", " + center + ");", timeout);
  	timeout += 30;
  }
  
  function callAddMarker(lat, lng, title, center) {
  	var latlng = new google.maps.LatLng(lat, lng);
  	
  	if (center)
  	{
  		map.setCenter(latlng);
  		map.setZoom(15);
  	}
   
    var marker = new google.maps.Marker({
    	position: latlng,
    	animation: google.maps.Animation.DROP,
    	map: map});
 	 

 	 if (title ==null)
 	 	return;
 	 
 	 var infowindow = new google.maps.InfoWindow({
 	 	content: title
 	 	});
 	 	
 	 google.maps.event.addListener(marker, 'click', function() {
 	 	if (lastopen != null)
 	 		lastopen.close();
 	 	infowindow.open(map,marker);
 	 	lastopen = infowindow;
 	 });
 	 
  }
<?php
} //end maps
?>
</script> 
<title>BINK!</title>
</head>
<body onload="initialize();">

<!-- this is the facebook code to asynchronously load the API -->


<!-- end the facebook code -->

<div class="main">
<div class="gfx"> 
	<table border=0 width="100%">
	<tr>
	<td width="820px" colspan=3>
	<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- binkmusic -->
<ins class="adsbygoogle"
     style="display:inline-block;width:820px;height:200px"
     data-ad-client="ca-pub-1453391221011492"
     data-ad-slot="7514258764"></ins>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>		
	</td>
	<tr>
	<td width="670px"><img src="<?= $leading ?>img/header.jpg" /></td>
	<td width="120px"><a href="<?= $leading ?>feed.php"><img src=<?= $leading ?>img/rss-0.91.gif border=0 /></a></td>
	<td width="30px"><a href="feed.php?podcast=1"><img src=<?= $leading ?>img/podcast.gif border=0 width=25/></a></td><td width="80px">
	
	<!-- this is the actual like button -->
	
	 <iframe src="http://www.facebook.com/plugins/like.php?href=<?= $BASE_URL ?>&colorscheme=dark&width=50&layout=button_count"
        scrolling="no" frameborder="0"
        style="border:none; width:80px; height: 30px"></iframe>
	
	<!-- end the actual like button -->
	</td>
	</tr>
	</table>
</div>
	<div class="menu">
		<a href="<?= $leading ?>index.php"><span>Recent</span></a>
		<a href="<?= $leading ?>history.php"><span>History</span></a>
		<a href="<?= $leading ?>player.php"><span>Player</span></a>
		<a href="<?= $leading ?>list.php"><span>Browse</span></a>
 	    <a href="/timeline/"><span>Timeline</span></a>
		<a href="<?= $leading ?>maps.php"><span>Map</span></a>
		<a href="<?= $leading ?>news.php"><span>Tweets</span></a>
 		<a href="/admin/main.php"><span>Admin</span></a>
		
	
		<span><?=printSearchBar() ?></span>
	</div>
	<div class="content">
<?php
}


function printFooter()
{
?>
	<div class='item'>
<p align=right>[ <a href='<?= $_SERVER['HTTP_REFERER']?>'>Go Back</a> ]</p></div></div>
	<div class="footer">&copy; 2009-2014 <a href="index.php">BINK!</a> created by <a href="http://bensmith.zapto.org">Ben Smith</a>. Design by <a href="http://arcsin.se">Arcsin</a></div>
</div>
</body>
</html>
<?php
}

function printCustomPlayer($jamid, $trackid = -1)
{

	if ($trackid == -1)
	{
  		$toRet = "<div style=\"padding-left: 0px; border-left: 0px; padding-top: 20px;\"><object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\"
			id=\"MusicPlayer\" width=\"580\" height=\"20\"
			codebase=\"http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab\">
			<param name=\"movie\" value=\"MusicPlayer.swf?jamid=$jamid\" />
			<param name=\"quality\" value=\"high\" />
			<param name=\"bgcolor\" value=\"#869ca7\" />
			<param name=\"allowScriptAccess\" value=\"sameDomain\" />
			<embed src=\"MusicPlayer.swf?jamid=$jamid\" quality=\"high\" bgcolor=\"#869ca7\"
				width=\"580\" height=\"20\" name=\"MusicPlayer\" align=\"middle\"
				play=\"true\"
				loop=\"false\"
				quality=\"high\"
				allowScriptAccess=\"sameDomain\"
				type=\"application/x-shockwave-flash\"
				pluginspage=\"http://www.adobe.com/go/getflashplayer\">
			</embed>
		</object></div>";
	}
	else
	{
		$toRet = "<div style=\"padding-left: 0px; border-left: 0px; padding-top: 20px;\"><object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\"
			id=\"MusicPlayer\" width=\"580\" height=\"20\"
			codebase=\"http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab\">
			<param name=\"movie\" value=\"MusicPlayer.swf?jamid=$jamid\" />
			<param name=\"quality\" value=\"high\" />
			<param name=\"bgcolor\" value=\"#869ca7\" />
			<param name=\"allowScriptAccess\" value=\"sameDomain\" />
			<embed src=\"MusicPlayer.swf?jamid=$jamid&trackid=$trackid\" quality=\"high\" bgcolor=\"#869ca7\"
				width=\"580\" height=\"20\" name=\"MusicPlayer\" align=\"middle\"
				play=\"true\"
				loop=\"false\"
				quality=\"high\"
				allowScriptAccess=\"sameDomain\"
				type=\"application/x-shockwave-flash\"
				pluginspage=\"http://www.adobe.com/go/getflashplayer\">
			</embed>
		</object></div>";
	}
	
	return $toRet;

}

?>
