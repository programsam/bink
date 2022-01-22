<?php
include "S3.php";

function sql()
{
	include "settings.php";

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

function newPlaylist()
{
	$token = "N/A";
	do
	{
		$token = randString(32);
		$result = bink_query("select * from playlists where id = '$token'");
	} while (mysqli_num_rows($result) > 0);

	return $token;
}

function getToken()
{
	 $token = "N/A";
	 $result = bink_query("select * from tokens where ip = '" . $_SERVER['REMOTE_ADDR'] . "'");
	 if (mysqli_num_rows($result))
	 {
		 $row = mysqli_fetch_array($result);
		 $token = $row['token'];
	 }
	 else
	 {
		 $token = randString(16);
		 bink_query("insert into tokens (ip, token) values ('" . $_SERVER['REMOTE_ADDR'] . "', '$token')");
	 }
	 return $token;
}

function deleteOldTokens()
{
	$result = bink_query("select * from tokens");

	while ($row = mysqli_fetch_array($result))
	{

		$age = checkTokenAge($row['ip']);

		if ($age > 5)
		{
			bink_query("delete from tokens where ip = '" . $row['ip'] . "'");
		}
	}

}

function checkTokenAge($ip)
{

	 $result = bink_query("select timestampdiff(HOUR, (SELECT date from tokens where ip = '$ip'), NOW()) as diff;");
	 $row = mysqli_fetch_array($result);
	 $age = $row['diff'];

	 return $age;
}

function validToken($ip, $token)
{
	deleteOldTokens();
	$result = bink_query("select * from tokens where ip = '$ip' and token = '$token'");

	if (mysqli_num_rows($result) === 0)
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
	return (strstr($_SERVER['HTTP_USER_AGENT'],'iPhone') ||
					strstr($_SERVER['HTTP_USER_AGENT'],'iPod') ||
					strstr($_SERVER['HTTP_USER_AGENT'],'Android') ||
					strstr($_SERVER['HTTP_USER_AGENT'],'iPad'));
}

function directPhone()
{
	include "settings.php";
	if(isPhone()) {
			//then we are using mobile mode and need to redirect them
    	header("Location: " . $BASE_URL . "/m");
	} else {
		//then we are not using a phone and it's ok to keep going
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

function makethumb($id, $filename, $max_width=100, $max_height=100)
{
  include "settings.php";
  $s3 = new S3($S3_ACCESS_KEY, $S3_SECRET_KEY);
  echo "With id $id and filename $filename\n";

	$awspath = "https://binkmedia.s3.amazonaws.com/public/pics/$id/$filename";
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

function generateSearchLink($url, $query, $name, $offset=0, $length, $order, $sort, $bold="")
{
	if ($bold)
		return "[ <big><a href='$url.php?query=$query&offset=$offset&length=$length&order=$order&sort=$sort'>$name</big></a> ]";
	else
		return "[ <a href='$url.php?query=$query&offset=$offset&length=$length&order=$order&sort=$sort'>$name</a> ]";
}

function getJamsSearch($listmode=0, $query=null, $offset=0, $length=20, $order="date", $sort="desc")
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

	$result = bink_query($sql);

	$lenbold[$order] = 1;
	$lenbold[$length] = 1;
	$lenbold[$length] = 1;

	if ($listmode == 1)
		$url = "musician";
	elseif ($listmode == 2)
		$url = "search";
	else if ($listmode == 3)
		$url = "band";
	else
		$url = "list";

	$ret .= generateSearchLink($url, $query, "date", $offset, $length, "date", $sort, isset($lenbold["date"]));
	$ret .= generateSearchLink($url, $query, "id", $offset, $length, "id", $sort, isset($lenbold["id"]));
	$ret .= generateSearchLink($url, $query, "location", $offset, $length, "locid", $sort, isset($lenbold["locid"]));

	if ($sort == "desc")
		$ret .= generateSearchLink($url, $query, "ascending", $offset, $length, $order, "asc");
	else
		$ret .= generateSearchLink($url, $query, "descending", $offset, $length, $order, "desc");

	$ret .= generateSearchLink($url, $query, "5", $offset, "5", $order, $sort, isset($lenbold["5"]));
	$ret .= generateSearchLink($url, $query, "10", $offset, "10", $order, $sort, isset($lenbold["10"]));
	$ret .= generateSearchLink($url, $query, "20", $offset, "20", $order, $sort, isset($lenbold["20"]));
	$ret .= generateSearchLink($url, $query, "50", $offset, "50", $order, $sort, isset($lenbold["50"]));
	$ret .= generateSearchLink($url, $query, "100", $offset, "100", $order, $sort, isset($lenbold["100"]));
	$ret .= generateSearchLink($url, $query, "all", $offset, "all", $order, $sort, isset($lenbold["all"]));

	if ($length != "all" && $offset+$length < $num)
		$ret .= generateSearchLink($url, $query, "next", $offset+$length, $length, $order, $sort);
	if ($length != "all" && $offset-$length >= 0)
		$ret .= generateSearchLink($url, $query, "prev", $offset-$length, $length, $order, $sort);

	if ($length != "all")
		$ret .= "<br />Listing " . $offset . " - " . ($offset + $length) . " of "  . $num;
	else
		$ret .= "<br />Listing all $num...";

	$ret .= "</div><div class='item'><h1>Results</h1>";

	$ret .= "<table width='100%'>";
	while (	$row = mysqli_fetch_array($result) )
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
		if(mysqli_num_rows(bink_query("select * from tracks where jamid = $jamid")) > 0)
			return "<img src='img/soundicon.jpg' width=15 />";
		else
			return "&nbsp;";
	}

	if ($type == "pics")
	{
		if(mysqli_num_rows(bink_query("select * from pictures where jamid = $jamid")) > 0)
			return "<img src='img/photoicon.jpg' width=15 />";
		else
			return "&nbsp;";
	}
	if ($type == "video")
	{
		if(mysqli_num_rows(bink_query("select * from video where jamid = $jamid")) > 0)
			return "<img src='img/videoicon.jpg' width=15 />";
		else
			return "&nbsp;";
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
		$ret .= "<br />&nbsp;<br /><div class='quote'>" . $row['notes'] . "</div>";

		if ($row['defpic'] && $row['defpic'] != -1)
		{
			$picrow = mysqli_fetch_array(bink_query("select * from jams, pictures where pictures.id = jams.defpic && jams.id = " . $row['id']));
			$ret .= "<p align='center'><img src='getimage.php?f=" . $row['id'] . "/" . $picrow['filename'] . "&w=300&h=400' /></p>";
		}

		$ret .= "</div>";
	}

	return $ret;
}

function getJamsMobile($query)
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
		$ret .= "<br />&nbsp;<br /><div class='quote'>" . $row['notes'];

		if ($row['defpic'] && $row['defpic'] != -1)
		{
			$picrow = mysqli_fetch_array(bink_query("select * from jams, pictures where pictures.id = jams.defpic && jams.id = " . $row['id']));
			$ret .= "<p /><img src='../getimage.php?f=" . $row['id'] . "/" . $picrow['filename'] . "&w=200&h=300' />";
		}

		$ret .= "</div></div>";
	}

	return $ret;
}

function getEntityByID($id, $table)
{
	$result = bink_query("select * from $table where id = $id");

	if ($result == null)
		return "";
	if (mysqli_num_rows($result) == 0)
		return "";

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
	include "settings.php";
	$s3 = new S3($S3_ACCESS_KEY, $S3_SECRET_KEY);

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

	$result = bink_query("select * from $table where jamid = $id order by num asc");
	if (mysqli_num_rows($result) == 0)
		return "";

	$ret = "<script language='javascript' src='js/ajax.js'></script>";
	$ret .= "<div class='item'><h1>$header</h1>";

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
			$ret .= "<td><a href=\"https://s3.amazonaws.com/binkmedia/public/$path\"><li>" . $row['title'] . "</li></a></td>";
			$ret .= "<td>" . resize_bytes($thisfile['size']) . "</td>";
			$ret .= "<td>" . $ext . " file </td>";

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
	<?php
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

function getShareBox($jamid, $title)
{

?>
<div id='shareBox' style='visibility:hidden; position: absolute; top: 25%; right: 25%; bottom: 25%; left: 25%; margin: 5px; padding: 25px; border: solid 3px grey;z-index:5; background: black; color: white'>
</div>
<?php
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
		return "<div class='item'><h1>Pictures</h1><a href='getimage.php?f=$jamid/$filename'><img border=0 src='getimage.php?f=$jamid/$filename&w=500&h=400'></a></div>";
	}
	$row = mysqli_fetch_array(bink_query("SELECT * FROM jams, pictures where jams.defpic = pictures.id and jams.id=$jamid"));

	$ret = "<div class='item'><h1>Pictures</h1>";
	$ret .= "<div id='loadindicator' style='float: right'>Loading...</div>";

	if ($row['filename'])
	{
		$ret .= "<img onLoad=\"setHTML('loadindicator', '');\" name='mainpic' border=0 id='mainpic' src='getimage.php?f=$jamid/" . $row['filename'] . "&w=500&h=400' />";
	}
	else
	{
		$row = mysqli_fetch_array(bink_query("SELECT * FROM pictures where pictures.jamid = $jamid"));
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
	$result = bink_query("select * from locations where id = $id");
	if ($result)
	{
		$row = mysqli_fetch_array($result);
		return $row['name'];
	}
	else
	{
		return "";
	}
}

function getBandName($id, $at=1)
{
	$result = bink_query("select * from bands where id = $id");
	if ($result)
	{
		if (mysqli_num_rows($result) > 0)
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
	else
	{
		return "";
	}
}

function getNumberOf($table, $label)
{
	$result = bink_query("SELECT * FROM `$table`;");
	$num = mysqli_num_rows($result);
	return "<tr><td>$label</td><td>$num</td></tr>";
}

function printAJam($id, $trackid)
{
	$result = bink_query("select * from jams where id = $id");
	$ret = "";
	while (	$row = mysqli_fetch_array($result) )
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

		if(mysqli_num_rows(bink_query("select * from tracks where jamid = $id")) > 0)
			$ret .= printCustomPlayer($id, $trackid);
		$ret .= "<br />&nbsp;<br /><div class='quote'>" . $row['notes'];
		$ret .= "<p align=right>";
		if (getNextId($id) != "")
			$ret .= "[ <a href='jam.php?id=" . getNextId($id) . "'>Next</a> ]";
		if (getPreviousId($id) != "")
			$ret .= "[ <a href='jam.php?id=" . getPreviousId($id) . "'>Prev</a> ]";
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
		if(mysqli_num_rows(bink_query("select * from tracks where jamid = $id")) > 0)
			$ret .= "<div class='item'>[ <a href='/makezip.php?id=$id'>Download as ZIP</a> ]</div>";

	}

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

if (isset($_GET['query']))
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

	$result = bink_query("select * from jams where private=0 order by date desc limit 0,20 ");
	$ret = "";

	if ($podcast)
	{
		while (	$row = mysqli_fetch_array($result) )
		{
			$innerresults = bink_query("select * from tracks where tracks.jamid = " . $row['id'] . " order by num asc");

			$seconds = 1000;
			while ($innerrow = mysqli_fetch_array($innerresults))
			{
				echo "\t\t<item>\n";
				echo "\t\t\t<title>" . $row['title'] . " - " . $innerrow['title'] . "</title>\n";
				echo "\t\t\t<link>https://binkmusic.com/bink/jam.php?id=" . $row['id'] . "</link>\n";
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

				echo "<enclosure url=\"https://binkmedia.s3.amazonaws.com/public/snd/" . $row['id'] . "/$filename\" type=\"audio/mpeg\"/>";
				echo "\t\t</item>\n";
			}
		}
	}
	else
	{
		while (	$row = mysqli_fetch_array($result) )
		{
			echo "\t\t<item>\n";
			echo "\t\t\t<title>" . $row['title'] . "</title>\n";
			echo "\t\t\t<link>https://binkmusic.com/jam.php?id=" . $row['id'] . "</link>\n";
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

function getLocationInfoWindow($id, $name)
{
		$subresult = bink_query("select * from jams where locid = $id limit 3;");
		$jamlist = "";
		if ($subresult == null)
			return "";
		while ($subrow = mysqli_fetch_array($subresult))
		{
			$jamlist .= "<a href='jam.php?id=" . $subrow['id'] . "'>" . $subrow['title'] . "</a>. ";
		}
		$info = "$name<hr />At this location: $jamlist";

		return $info;
}

function getMasterMap()
{
	$result = bink_query("select * from locations where address <> ''");

	$timeout = 0;
	$scriptStr = "<script type=\"text/javascript\">\nfunction loadMarkers() {\n";

	while ($row = mysqli_fetch_array($result))
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
	$ret = "";
	$result = bink_query("select * from locations where id = $locid");
	$row = mysqli_fetch_array($result);
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
$leading = "";
$adminStr = "admin/main.php";

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"https://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1"/>
<meta name="description" content="BINK! is an experiment in musical documentation."/>
<meta name="keywords" content="music, website, jams, mp3, free, download"/>
<meta name="author" content="Ben Smith"/>
  <?php
  $result = bink_query("select title, notes from jams where id = $id");
  $row = mysqli_fetch_array($result);
  $title = $row['title'];
  $notes = $row['notes'];
  ?>
<link rel="stylesheet" type="text/css" href="<?= $leading ?>default.css"/>
<script language="javascript" src="<?= $leading ?>js/ajax.js"></script>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?= $GOOGLE_MAPS_KEY ?>"></script>
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
<div class="main">
	<div class="gfx">
		<img src="<?= $leading ?>img/header.jpg" />
	</div>
	<div class="menu">
		<a href="<?= $leading ?>index.php"><span>Recent</span></a>
		<a href="<?= $leading ?>history.php"><span>History</span></a>
		<a href="<?= $leading ?>list.php"><span>Browse</span></a>
		<a href="<?= $leading ?>maps.php"><span>Map</span></a>
		<a href="<?= $leading ?>news.php"><span>Tweets</span></a>
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
"https://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1"/>
<meta name="description" content="BINK! is an experiment in musical documentation."/>
<meta name="keywords" content="music, website, jams, mp3, free, download"/>
<meta name="author" content="Ben Smith"/>
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
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?=$GOOGLE_MAPS_KEY ?>"></script>
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
<?php
} else {
?>
<script type="text/javascript">
function initialize() {

}
</script>
<?php
} //end maps
?>
<title>BINK!</title>
</head>
<body onload="initialize();">
<div class="main">
<div class="gfx">
	<img src="<?= $leading ?>img/header.jpg" />
</div>
	<div class="menu">
		<a href="<?= $leading ?>index.php"><span>Recent</span></a>
		<a href="<?= $leading ?>history.php"><span>History</span></a>
		<a href="<?= $leading ?>list.php"><span>Browse</span></a>
		<a href="<?= $leading ?>maps.php"><span>Map</span></a>
		<a href="<?= $leading ?>news.php"><span>Tweets</span></a>
		<span><?=printSearchBar() ?></span>
	</div>
	<div class="content">
<?php
}


function printFooter()
{
?>
	</div>
	<div class="footer">&copy; 2009-2022 <a href="index.php">BINK!</a> created by <a href="http://bsmith.me">Ben Smith</a>. Design by <a href="https://arcsin.se">Arcsin</a></div>
</div>
</body>
</html>
<?php
}

function printCustomPlayer($jamid, $trackid = -1)
{
	return "<div style=\"margin-top: 10px\"><em>In-page player not available. You can still download the files individually or as a ZIP file, however.</em></div>";
}

?>
