<?php

include "functions.php";

if (! isset($_GET['action']))
{
	echo "<response>Please specify an action, sir!</response>";
}
else if ($_GET['action'] == "login")
{
	if ($_GET['password'] == "h0m3pl4t3")
	{
		echo "<token>" . getToken() . "</token>";
		return;
	}
	else
	{
		echo "FAILURE!";
		return;
	}

}
else if ($_GET['action'] == "tracks")
{
	$jamid = $_GET['id'];
	
	$result = bink_query("SELECT tracks.id as id, tracks.num, tracks.title, tracks.path, tracks.notes FROM tracks, jams where tracks.jamid = jams.id and jamid = $jamid order by tracks.num asc");
	
	while ($row = mysqli_fetch_array($result))
	{
		echo "<track>";
		echo "<trackid>" . $row['id'] . "</trackid>";
		echo "<num>" . $row['num'] . "</num>";
		echo "<title>" . $row['title'] . "</title>";
		echo "<path>" . $row['path'] . "</path>";
		echo "<notes>" . $row['notes'] . "</notes>";
		echo "<extension>" . pathinfo($row['path'], PATHINFO_EXTENSION) . "</extension>";
		echo "</track>";
	}
}
else if ($_GET['action'] == "trackList")
{
	$sql = "";
	if ($_GET['query'])
	{
		$query = $_GET['query'];
		$columns = "tracks.num, tracks.id as trackid, tracks.title as tracktitle, tracks.path, jams.date, jams.id as jamid, jams.title as jamtitle";
	
		$sTitle = "select $columns from jams, tracks where jams.title like ('%$query%') and tracks.jamid = jams.id";
		
		$sLocation = "select $columns from locations, jams, tracks where locations.name like ('%$query%') and jams.locid = locations.id";
		
		$sBand = "select $columns from bands, jams, tracks where bands.name like ('%$query%') and jams.bandid = bands.id and tracks.jamid = jams.id";
		
		$sMusicians = "select $columns from musiciansoncollection, jams, musicians, tracks where musicians.id = musiciansoncollection.musicianid and musiciansoncollection.jamid = jams.id and musicians.name like ('%$query%') and tracks.jamid = jams.id";
		
		$sStaff = "select $columns from productiononcollection, jams, staff, tracks where staff.id = productiononcollection.staffid and productiononcollection.jamid = jams.id and staff.name like ('%$query%') and tracks.jamid = jams.id";
		
		$sNotes = "select $columns from jams, tracks where jams.notes like ('%$query%') and tracks.jamid = jams.id";
		
		$sTracks = "select $columns from tracks, jams where tracks.jamid = jams.id and tracks.title like ('%$query%')";
	
		$sql = "($sTitle) union ($sLocation) union ($sBand) union ($sMusicians) union ($sStaff) union ($sNotes) union ($sTracks) ";
	}
	else
	{
		$sql = "select tracks.num, tracks.id as trackid, tracks.title as tracktitle, tracks.path, jams.date, jams.id as jamid, jams.title as jamtitle from tracks, jams where tracks.jamid = jams.id order by jams.date desc, tracks.num asc;";
	}
	
	echo "$sql";
	
	$result = bink_query($sql);
	
	while ($row = mysqli_fetch_array($result))
	{
		echo "<track>";
		echo "<jamtitle>" . $row['jamtitle'] . "</jamtitle>";
		echo "<jamdate>" . $row['date'] . "</jamdate>";
		echo "<trackid>" . $row['trackid'] . "</trackid>";
		echo "<num>" . $row['num'] . "</num>";
		echo "<title>" . $row['tracktitle'] . "</title>";
		echo "<path>" . $row['path'] . "</path>";
		echo "<notes>" . $row['notes'] . "</notes>";
		echo "<extension>" . pathinfo($row['path'], PATHINFO_EXTENSION) . "</extension>";
		echo "</track>";
	}
	
}
else if ($_GET['action'] == "list")
{

	if (isset($_GET['query']) && $_GET['query'] != "")
	{
		$query = $_GET['query'];
		$columns = "jams.date, jams.id, jams.title, jams.locid, jams.bandid";
	
		$sTitle = "select $columns from jams where title like ('%$query%')";
		$sLocation = "select $columns from locations, jams where locations.name like ('%$query%') and jams.locid = locations.id";
		$sBand = "select $columns from bands, jams where bands.name like ('%$query%') and jams.bandid = bands.id";
		$sMusicians = "select $columns from musiciansoncollection, jams, musicians where musicians.id = musiciansoncollection.musicianid and musiciansoncollection.jamid = jams.id and musicians.name like ('%$query%')";
		$sStaff = "select $columns from productiononcollection, jams, staff where staff.id = productiononcollection.staffid and productiononcollection.jamid = jams.id and staff.name like ('%$query%')";
		$sNotes = "select $columns from jams where notes like ('%$query%')";
		$sTracks = "select $columns from tracks, jams where tracks.jamid = jams.id and tracks.title like ('%$query%')";
	
		$sql = "($sTitle) union ($sLocation) union ($sBand) union ($sMusicians) union ($sStaff) union ($sNotes) union ($sTracks) order by date desc";
	}
	else
	{
		$sql = "select * from jams order by date desc limit 50";
	}
	
	$result = bink_query($sql);
	
	while ($row = mysqli_fetch_array($result))
	{
		echo "<jam>";
		echo "<id>" . $row['id'] . "</id>";
		echo "<date>" . date("m/d/Y", strtotime($row['date'])) . "</date>";
		echo "<title>" . $row['title'] . "</title>";
		echo "<location>" . getLocationName($row['locid']) . "</location>";
		echo "<band>" . getBandName($row['bandid'], 0). "</band>";
		
		$media = "";
		
		$imgresult = bink_query("select * from pictures where jamid = " . $row['id'] . ";");
		$num = mysqli_num_rows($imgresult);
		
		if ($num > 0)
			$media .= "P";
		
		$vidresult = bink_query("select * from video where jamid = " . $row['id'] . ";");
		$num = mysqli_num_rows($vidresult);
		
		if ($num > 0)
			$media .= "V";
		
		
		$trkresult = bink_query("select * from tracks where jamid = " . $row['id'] . ";");
		$num = mysqli_num_rows($trkresult);
		
		if ($num > 0)
			$media .= "A";
			
		echo "<media>$media</media>";
				
		
		echo "</jam>";
	}
}
else if ($_GET['action'] == "newplaylist")
{
	$id = newPlaylist();
	
	$tracks = $_GET['tracks'];
	$trackarray = explode(",", $tracks);
	
	foreach ($trackarray as $thistrack)
	{ 
		bink_query("insert into playlists (id, trackid) values ('$id', $thistrack)");
	}
	echo "<playlist><id>$id</id></playlist>";
}

/*
Begin here those functions dealing with authentication!
*/

else if (! isset($_GET['token']))
{
	echo "NO TOKEN FOUND!";
	return;
}
else
{
	if (! validToken($_SERVER['REMOTE_ADDR'], $_GET['token']))
	{
		echo "INVALID TOKEN!";
		return;
	}
}

/*

At this point, the user is authenticated with a token;
we can allow them to do whatever they want to the database.

Functions above this break do not require authentication!

*/

include "settings.php";
$s3 = new S3($S3_ACCESS_KEY, $S3_SECRET_KEY);

if ($_GET['action'] == "view")
{
	$result = bink_query("select * from jams where id = " . $_GET['id']);
	
	$row = mysqli_fetch_array($result);
		echo "<jam>";
		echo "<id>" . $row['id'] . "</id>";
		echo "<date>" . date("m/d/Y", strtotime($row['date'])) . "</date>";
		echo "<title>" . $row['title'] . "</title>";
		if (getLocationName($row['locid']) == "")
			echo "<location>Click to add location...</location>";
		else
			echo "<location>" . getLocationName($row['locid']) . "</location>";
			
		if (getBandName($row['bandid'],0) == "")
			echo "<band>Click to add band...</band>";
		else
			echo "<band>" . getBandName($row['bandid'],0) . "</band>";
		echo "<notes>" . $row['notes'] . "</notes>";
		
		if ($row['private'] == 1)
			$private = "true";
		else if ($row['private'] == 0)
			$private = "false";
			
		echo "<isprivate>$private</isprivate>";
		
		echo "</jam>";

}
else if ($_GET['action'] == "setprivate")
{
	$id = $_GET['id'];
	if ($_GET['private'] == "true")
		$private = 1;
	else if ($_GET['private'] == "false")
		$private = 0;
		
	bink_query("update jams set private=$private where id=$id");
}
else if ($_GET['action'] == "delete")
{
	$id = $_GET['id'];
	
	bink_query("delete from jams where id = $id");
	bink_query("delete from musiciansoncollection where jamid = $id");
	bink_query("delete from productiononcollection where jamid = $id");
	bink_query("delete from pictures where jamid = $id");
	bink_query("delete from tracks where jamid = $id");
	bink_query("delete from video where jamid = $id");

	/**
	 * Delete all pictures from amazon's S3.
	 */
	$files = $s3 -> getBucket("binkmedia", "public/pics/" . $id);
	$delfiles = array();
	
	foreach($files as $file)
	{
		array_push($delfiles, $file['name']);
	}
	
	foreach($delfiles as $file)
	{
		$s3 -> deleteObject("binkmedia", $file);
	}
	
	/**
	 * Delete all tracks from amazon's S3.
	 */
	$files = $s3 -> getBucket("binkmedia", "public/snd/" . $id);
	$delfiles = array();
	
	foreach($files as $file)
	{
		array_push($delfiles, $file['name']);
	}
	
	foreach($delfiles as $file)
	{
		$s3 -> deleteObject("binkmedia", $file);
	}
	
	/**
	 * Delete all videos from amazon's S3.
	 */
	$files = $s3 -> getBucket("binkmedia", "public/video/" . $id);
	$delfiles = array();
	
	foreach($files as $file)
	{
		array_push($delfiles, $file['name']);
	}
	
	foreach($delfiles as $file)
	{
		$s3 -> deleteObject("binkmedia", $file);
	}
	
}
else if ($_GET['action'] == "new")
{
	$today = date("Y-m-d");
	bink_query("insert into jams (id, date, title, notes, locid, bandid, private) values (null, '$today', 'New Collection', 'Add Notes Here', -1, -1, 1)");
	$result = bink_query("select * from jams order by id desc limit 1");
	$row = mysqli_fetch_array($result);
	
	echo "<jam>";
	echo "<id>" . $row['id'] . "</id>";
	echo "<date>" . date("m/d/Y", strtotime($row['date'])) . "</date>";
	echo "<title>" . $row['title'] . "</title>";
	echo "<location>Click to add location...</location>";
	echo "<band>Click to add band...</band>";
	echo "<notes>" . $row['notes'] . "</notes>";
	echo "</jam>";

}
else if ($_GET['action'] == "managedata")
{
	$field = $_GET['field'];
	$querystr = "";
	if (isset($_GET['query']))
	{
		$querystr = " where name like ('%" . $_GET['query'] . "%')";
	}
	$result = bink_query("select * from $field $querystr");
	
	while ($row = mysqli_fetch_array($result))
	{
		echo "<" . $field . ">";
		echo "<id>" . $row['id'] . "</id>";
		echo "<name>" . $row['name'] . "</name>";
		
		if ($field == "musicians" || $field == "locations" || $field=="bands")
			echo "<link>" . $row['link'] . "</link>";
		
		if ($field == "locations")
		{
			echo "<address>" . $row['address'] . "</address>";
		}
		
		echo "</" . $field . ">";
	}
}
else if ($_GET['action'] == "data")
{
	$field = $_GET['field'];
	$querystr = "";
	if (isset($_GET['query']))
	{
		$querystr = " where name like ('%" . $_GET['query'] . "%')";
	}
	$result = bink_query("select * from $field $querystr");
	
	while ($row = mysqli_fetch_array($result))
	{
		echo "<" . $field . ">";
		echo "<id>" . $row['id'] . "</id>";
		echo "<label>" . $row['name'] . "</label>";
		echo "</" . $field . ">";
	}
}
else if ($_GET['action'] == "edit")
{
	$connection = sql();

	$field = $_GET['field'];
	$value = urldecode($_GET['value']);
	$value = mysqli_escape_string($connection, $value);
	$id = $_GET['id'];
	
	if ($field == "date")
		$value = date("Y-m-d", strtotime($value));
	
	bink_query("update jams set $field='$value' where id = $id");
	
	echo "<response>success</response>";
	mysqli_close($connection);
}
else if ($_GET['action'] == "deleteitem")
{
	$type = $_GET['field'];
	$id = $_GET['id'];
	
	bink_query("delete from $type where id = $id");
	
	echo "<id>$id</id>";
	
}
else if ($_GET['action'] == "editdataitem")
{
	$connection = sql();

	$type = $_GET['type'];
	$id = $_GET['id'];
	$field = $_GET['field'];
	$value = mysqli_escape_string($connection, $_GET['value']);
	bink_query("update $type set $field = '$value' where id = $id");
	
	echo "<sucess>true</sucess>";
	mysqli_close($connection);
	
}
else if ($_GET['action'] == "additem")
{
	$type = $_GET['type'];
	$name = $_GET['name'];
	
	bink_query("insert into $type (id, name) values (null, '$name')");
	$result = bink_query("select max(id) as id from $type");
	$row = mysqli_fetch_array($result);
	$newid = $row['id'];
	
	echo "<id>$newid</id><name>$name</name>";
}
else if ($_GET['action'] == "musicians")
{
	$id = $_GET['id'];
	$result = bink_query("select musicianid, jamid, instrumentid, musicians.name as musician, instruments.name as instrument from musiciansoncollection, musicians, instruments where musiciansoncollection.musicianid = musicians.id and musiciansoncollection.instrumentid = instruments.id  and jamid=$id;");
	
	while ($row = mysqli_fetch_array($result))
	{
		echo "<musician>";
		echo "<musicianid>" . $row['musicianid'] . "</musicianid>";
		echo "<jamid>" . $row['jamid'] . "</jamid>";
		echo "<instrumentid>" . $row['instrumentid'] . "</instrumentid>";
		echo "<name>" . $row['musician'] . "</name>";
		echo "<instrument>" . $row['instrument'] . "</instrument>";
		echo "</musician>";
	}
}
else if ($_GET['action'] == "addmustocol")
{
	$jamid = $_GET['jamid'];
	$musid = $_GET['musid'];
	$insid = $_GET['insid'];
	
	bink_query("insert into musiciansoncollection (jamid, instrumentid, musicianid) values ($jamid, $insid, $musid)");
}
else if ($_GET['action'] == "delmusfromcol")
{
	$jamid = $_GET['jamid'];
	$musid = $_GET['musid'];
	$insid = $_GET['insid'];
	
	bink_query("delete from musiciansoncollection where jamid = $jamid and musicianid = $musid and instrumentid = $insid");
}
else if ($_GET['action'] == "staff")
{
	$id = $_GET['id'];
	$result = bink_query("select staffid, jamid, roleid, staff.name as name, roles.name as role from productiononcollection, staff, roles where productiononcollection.staffid = staff.id and productiononcollection.roleid = roles.id  and jamid=$id;");
	
	
	while ($row = mysqli_fetch_array($result))
	{
		echo "<staff>";
		echo "<staffid>" . $row['staffid'] . "</staffid>";
		echo "<jamid>" . $row['jamid'] . "</jamid>";
		echo "<roleid>" . $row['roleid'] . "</roleid>";
		echo "<name>" . $row['name'] . "</name>";
		echo "<role>" . $row['role'] . "</role>";
		echo "</staff>";
	}
}
else if ($_GET['action'] == "addstafftocol")
{
	$jamid = $_GET['jamid'];
	$staffid = $_GET['staffid'];
	$roleid = $_GET['roleid'];
	
	bink_query("insert into productiononcollection (jamid, staffid, roleid) values ($jamid, $staffid, $roleid)");
	echo "<success />";
}
else if ($_GET['action'] == "delstafffromcol")
{
	$jamid = $_GET['jamid'];
	$staffid = $_GET['staffid'];
	$roleid = $_GET['roleid'];
	
	bink_query("delete from productiononcollection where jamid = $jamid and staffid = $staffid and roleid = $roleid");
}
else if ($_GET['action'] == "reordertracks")
{
	$jamid = $_GET['jamid'];
	
	$result = bink_query("select id from tracks where jamid = $jamid");
	
	while ($row = mysqli_fetch_array($result))
	{
		$newnum = $_GET['trackid' . $row['id']];
		bink_query("update tracks set num = $newnum where id = " . $row['id']);
		
		echo "update tracks set num = $newnum where id = " . $row['id'];
	}
}
else if ($_GET['action'] == "reordervideo")
{
	$jamid = $_GET['jamid'];
	
	$result = bink_query("select id from video where jamid = $jamid");
	
	while ($row = mysqli_fetch_array($result))
	{
		$newnum = $_GET['videoid' . $row['id']];
		bink_query("update video set num = $newnum where id = " . $row['id']);
	}
}
else if ($_GET['action'] == "next")
{
	$id = $_GET['id'];
	
	$result = bink_query("select * from jams order by date desc;");
	
	while ($row = mysqli_fetch_array($result))
	{
		if ($row['id'] == $id)
		{
			echo "<id>" . $oldrow['id'] . "</id>";
			exit;
		}
		
		$oldrow = $row;
	}
	
	echo "<id>-1</id>";
}
else if ($_GET['action'] == "previous")
{
	$id = $_GET['id'];
	
	$result = bink_query("select * from jams order by date asc;");
	
	while ($row = mysqli_fetch_array($result))
	{
		if ($row['id'] == $id)
		{
			echo "<id>" . $oldrow['id'] . "</id>";
			exit;
		}
		
		$oldrow = $row;
	}
	
	echo "<id>-1</id>";
}
else if ($_GET['action'] == "edittrack")
{
	$connection = sql();
	$trackid = $_GET['trackid'];
	$field = $_GET['field'];
	$value = mysqli_escape_string($connection, $_GET['value']);
	
	bink_query("update tracks set $field = '$value' where id = $trackid");
	
	echo "<sucess />";
	mysqli_close($connection);
}
else if ($_GET['action'] == "strip")
{

$id = $_GET['jamid']; 
bink_query("delete from tracks where jamid = $id");

$files = $s3 -> getBucket('binkmedia', 'public/snd/' . $id);
$i = 0; 
$connection = sql();
foreach ($files as $file)
{
	$i++;
	$fullpath = $file['name'];
	//Split off the paths
	$splitfile = explode("/", $fullpath);
	//Extract the last portion of the path: the filename with extension
	$filename = $splitfile[3];
	//Check to see if it's not empty; directories will be empty
	if (strlen($filename) > 0) {
		//Split off the extension
		$title = explode(".", $filename);
		//Just get the name before extension: this is now the title
		$title = $title[0];
		//Add it to an array to be sorted.
		$titles[$i] = mysqli_escape_string($connection, $title);
		//Add the filename to a dictionary for later lookup
		$filenames[mysqli_escape_string($connection, $title)] = mysqli_escape_string($connection, $filename);
	} else {
		echo "$fullpath is a directory; skipping that.";
	}
}

//Now we sort the array and add it to the database
if ($titles != null)
{
	//Sort by lexicographical ordering--to get track numbers if necessary
	asort($titles);
	$j=0;
	foreach ($titles as $title)
	{
		$j++;
		//complicated regular expression parsing to remove extra crap
		$striptitle = preg_replace("(\d+)", "", $title, 1);
		$striptitle = preg_replace("(\s+)", "", $striptitle, 1);
	   	bink_query("insert into tracks (id, jamid, num, title, path) values (NULL, $id, $j, '$striptitle', 'snd/$id/" . $filenames[$title] . "');");
	   	//echo "insert into tracks (id, jamid, num, title, path) values (NULL, $id, $j, '$striptitle', 'snd/$id/" . $filenames[$title] . "');";
	}

	}
}
else if ($_GET['action'] == "files")
{
$id = $_GET['jamid']; 
if (! isset($_GET['jamid']))
	return;

$files = $s3 -> getBucket('binkmedia', 'public/snd/' . $id);

$i = 0; 
	foreach ($files as $file)
	{
		$i++;
		echo "<file>";
		$fullpath = $file['name'];
	    echo "<path>$fullpath</path>";
		$filename = pathinfo($fullpath, PATHINFO_FILENAME);
		$ext = pathinfo($fullpath, PATHINFO_EXTENSION);
		echo "<filename>$filename</filename>";
		echo "<extension>$ext</extension>";
		echo "</file>";
	}
	
	
}
else if ($_GET['action'] == "scan")
{
$id = $_GET['jamid']; 
if (! isset($_GET['jamid'])) {
	echo "<error>No jam specified!</error>";
	return;
}

$files = $s3 -> getBucket('binkmedia', 'public/snd/' . $id);

/**
 * Tracks
 */
bink_query("delete from tracks where jamid = $id");

$connection = sql();

$i = 0; 
foreach ($files as $file)
{
	$i++;
	$fullpath = $file['name'];
	//Split off the paths
	$title = pathinfo($fullpath, PATHINFO_FILENAME);
	$filename = pathinfo($fullpath, PATHINFO_BASENAME);
	//Add it to an array to be sorted.
	if ($title !== $filename) {
		$filenames[$i] = mysqli_escape_string($connection, $filename);
		//Add the filename to a dictionary for later lookup
		$titles[mysqli_escape_string($connection, $filename)] = mysqli_escape_string($connection, $title);
		echo "Full Path: " . $fullpath . " Title: " . $title . "Filename: " . $filename . "<br />";
	}
}
mysqli_close($connection);

//Now we sort the array and add it to the database
if ($titles != null)
{
	//Sort by lexicographical ordering--to get track numbers if necessary
	asort($filenames);
	$j=0;
	foreach ($filenames as $filename)
	{
		$j++;
		bink_query("insert into tracks (id, jamid, num, title, path) values (NULL, $id, $j, '" . $titles[$filename] . "', 'snd/$id/$filename');");
		echo "insert into tracks (id, jamid, num, title, path) values (NULL, $id, $j, '" . $titles[$filename] . "', 'snd/$id/$filename');";
	}

}

/**
 * Pictures
 */
bink_query("delete from pictures where jamid = $id"); 
$files = $s3 -> getBucket('binkmedia', 'public/pics/' . $id);
$filenames = array();
$tothm = array();
print_r($files);
//First, splice off the paths and extract the filenames from the file array.
foreach ($files as $file)
{
	echo "Starting a file...";
	$fullpath = $file['name'];
	//Split off the paths
	$splitfile = explode("/", $fullpath);
	//Extract the last portion of the path: the filename with extension
	$filename = $splitfile[3];
	//Push the file onto the array for thumbnail processing.
	array_push($filenames, $filename);
}
foreach ($filenames as $filename)
{
	echo $filename . "\n";
	$ext = substr($filename, strlen($filename)-3);
	echo "Extension: $ext\n";
	if ($ext != "thm")
	{
		//We aren't dealing with a thumbnail, so it's safe to add to the pictures table.
		bink_query("insert into pictures (id, jamid, filename) values (NULL, $id, '" . $filename . "');");
		echo "\tSearching for file: $filename.thm\n";
		$result = array_search("$filename.thm", $filenames);
		echo "\t\tResult: $result\n";
		if ($result == 0)
			array_push($tothm, $filename);
	}//if dealing with an image and not a thumbnail
}//outer foreach

if (isset($tothm[0]))
{
	echo "Making thumbs...\n";

	foreach ($tothm as $thisfile)
	{
		echo "Current file: " . $thisfile . "\n";
		makethumb($id, $thisfile);
	}
}

/**
 * Videos
 */
bink_query("delete from video where jamid = $id");
$files = $s3 -> getBucket('binkmedia', 'public/video/' . $id);

foreach ($files as $file)
{
	$i++;
	$fullpath = $file['name'];
	//Split off the paths
	$splitfile = explode("/", $fullpath);
	//Extract the last portion of the path: the filename with extension
	$filename = $splitfile[3];
	//Split off the extension
	$title = explode(".", $filename);
	//Just get the name before extension: this is now the title
	$title = $title[0];
	
	bink_query("INSERT INTO `video` (`id`,`jamid`,`title`,`num`, `path`) VALUES (NULL, '$id', '$title', '$i', 'video/$id/$filename');");
	//echo "INSERT INTO `video` (`id`,`jamid`,`title`,`num`, `path`) VALUES (NULL, '$id', '$title', '$i', 'video/$id/$filename');";
}

}
else if ($_GET['action'] == "deletefile")
{
	$path = $_GET['path'];
	
	$s3 -> deleteObject('binkmedia', $path);
}
else if ($_GET['action'] == "deltrack")
{
	$trackid = $_GET['trackid'];
	$path = $_GET['path'];
	
	bink_query("delete from tracks where id = $trackid");
	
	$s3 -> deleteObject('binkmedia', 'public/' . $path);
}
else if ($_GET['action'] == "delvideo")
{
	$id = $_GET['videoid'];
	$path = $_GET['path'];
	
	bink_query("delete from video where id = $id");
	
	$s3 -> deleteObject('binkmedia', 'public/' . $path);
}
else if ($_GET['action'] == "pics")
{

	$jamid = $_GET['id'];
	$result = bink_query("select * from pictures where jamid = $jamid");
	$defpicresult = bink_query("select defpic from jams where id = $jamid");
	$defpicrow = mysqli_fetch_array($defpicresult);
	$defpicnum = $defpicrow['defpic'];
	
	while ($row = mysqli_fetch_array($result))
	{
		if ($row['id'] == $defpicnum)
			$defpic = "true";
		else
			$defpic = "false";
		echo "<image>";
		echo "<defpic>$defpic</defpic>";
		echo "<jamid>$jamid</jamid>";
		echo "<imageid>" . $row['id'] . "</imageid>";
		echo "<filename>" . $row['filename'] . "</filename>";
		echo "</image>";
	}

}
else if ($_GET['action'] == "video")
{

	$jamid = $_GET['id'];
	$result = bink_query("select * from video where jamid = $jamid order by num asc");
	
	while ($row = mysqli_fetch_array($result))
	{
		echo "<video>";
		echo "<jamid>$jamid</jamid>";
		echo "<num>" . $row['num'] . "</num>";
		echo "<videoid>" . $row['id'] . "</videoid>";
		echo "<title>" . $row['title'] . "</title>";
		echo "<path>" . $row['path'] . "</path>";
		echo "</video>";
	}

}
else if ($_GET['action'] == "editvideo")
{
	$videoid = $_GET['videoid'];
	$field = $_GET['field'];
	$value = $_GET['value'];
	
	bink_query("update video set $field = '$value' where id = $videoid");
	echo "update videos set $field = '$value' where id = $videoid";
}
else if ($_GET['action'] == "setdefpic")
{
	$jamid = $_GET['jamid'];
	$defid = $_GET['defpic'];

	$result = bink_query("select defpic from jams where id = $jamid");
	$row = mysqli_fetch_array($result);
	$frompic = $row['defpic'];
	
	bink_query("update jams set defpic = $defid where id = $jamid");
	
	echo "<result><imageset>$defid</imageset><imageunset>$frompic</imageunset></result>";
}
else if ($_GET['action'] == "delpic")
{
	$picid = $_GET['picid'];
	$jamid = $_GET['jamid'];
	$filename = $_GET['filename'];
	bink_query("delete from pictures where id = $picid");
	
	$result = bink_query("select defpic from jams where id = $jamid");
	$row = mysqli_fetch_array($result);
	$defpic = $row['defpic'];
	
	if ($picid == $defpic)
		bink_query("update jams set defpic = -1 where id = $jamid");
	
	//unlink("pics/$jamid/$filename");
	//unlink("pics/$jamid/$filename.thm");
	$s3 -> deleteObject('binkmedia', 'public/pics/' . $jamid . '/' . $filename);
	$s3 -> deleteObject('binkmedia', 'public/pics/' . $jamid . '/' . $filename . '.thm');
	
	echo "<imagedeleted>$picid</imagedeleted>";
}
else if ($_GET['action'] == "setbreak")
{
	$id = $_GET['id'];
	
	bink_query("insert into tracks (id, jamid, num, title, path) values (NULL, $id, 0, '--------------------', '');");

}
else if ($_GET['action'] == "getaddresses")
{
	$result = bink_query("select * from locations where address <> ''");
	
	while ($row = mysqli_fetch_array($result))
	{
		echo "<location>";
		echo "<address>" . $row['address'] . "</address>";
		echo "<name>" . $row['name'] . "</name>";
		echo "</location>\n";
	}
}


?>
