<?php

include "functions.php";
include "settings.php";

sql();

if (isset($_GET['closer']))
{
?>
<html>
<body onload="self.close();">
</body>
</html>
<?php
	exit();
}

if (isset($_GET['trackstyle']))
{
	$trackid = $_GET['trackid'];
	$result = mysqli_query("select * from tracks where id = $trackid");
	$trackrow = mysqli_fetch_array($result);
	$result2 = mysqli_query("select * from jams where id = " . $trackrow['jamid']);
	$jamrow = mysqli_fetch_array($result2);
	$jamid = $jamrow['id'];
	
	//print_r($trackrow);
	
	$url = $BASE_URL . "jam.php?id=$jamid&trackid=$trackid";
	$encodedurl = urlencode($url);
	$redirecturi = urlencode($BASE_URL . "/share.php?closer=1");
	
	$title = $jamrow['title'];
	$encodedtitle = urlencode($title);

	$tracktitle = $trackrow['title'];
	$encodedtracktitle = urlencode($tracktitle);
	
	$facebookName = "$encodedtitle - $encodedtracktitle";
	
	$encodedbody = urlencode("Check out the track $tracktitle from $title");
	$encodedlisten = urlencode("Go to $url to listen");
	$encodedsubject = urlencode("Check out the track $tracktitle from $title");
	
	echo "[ <a target=\'_blank\" href=\"mailto:friends@email.address?subject=$encodedsubject&body=" . $encodedbody . ". " .  $encodedlisten . "\">Send an Email</a> ]";
	
	echo "[ <a target=\'_blank\" href=\"https://twitter.com/share?url=" . $encodedurl . "&text=" . $encodedsubject . "\">Tweet</a> ]";
	
	echo "[ <a target=\'_blank\" href=\"https://www.facebook.com/dialog/feed?
	  app_id=139182602788074&
	  link=$encodedurl&
	  picture=" . $BASE_URL . "/img/header.jpg&
	  name=$facebookName&
	  caption=BINK!%20Collection&
	  description=$encodedsubject&
	  redirect_uri=$redirecturi\">Share on Facebook</a> ]";
	  
	  echo "<p /><form>Copy to clipboard:&nbsp;&nbsp;<input type=text size=70 value=\"$url\" style=\"background: black; color: white\" /></form>";

	exit;

}

$jamid = $_GET['jamid'];
$title = $_GET['title'];

$encodedtitle = urlencode($title);
$url = $BASE_URL . "/jam.php?id=$jamid";
$encodedurl = urlencode($url);

$encodedsubject = urlencode("Check out $title on BINK!");
$encodedbody = urlencode("Hey, check out this new music I found on BINK! ");
$encodedlisten = urlencode("Go to " . $BASE_URL . "/jam.php?id=$jamid to listen.");
$redirecturi = urlencode($BASE_URL . "/share.php?closer=1");

$result = mysqli_query("select * from tracks where jamid = $jamid");

?>
<div style="float:right"><a href="javascript:hide('shareBox')"><img src="img/close.png" border=0 /></a></div>
<h3>Share Collection "<?=$title ?>"</h3>
<br />
<?php

echo "[ <a target=\'_blank\" href=\"mailto:friends@email.address?subject=$encodedsubject&body=" . $encodedbody . $encodedlisten . "\">Send an Email</a> ]";

echo "[ <a target=\'_blank\" href=\"https://twitter.com/share?url=" . $encodedurl . "&text=" . $encodedsubject . "\">Tweet</a> ]";

echo "[ <a target=\'_blank\" href=\"https://www.facebook.com/dialog/feed?
  app_id=139182602788074&
  link=$encodedurl&
  picture=" . $BASE_URL . "/img/header.jpg&
  name=$encodedtitle&
  caption=BINK!%20Collection&
  description=$encodedsubject&
  redirect_uri=$redirecturi\">Share on Facebook</a> ]";
  
   echo "<p /><form>Copy to clipboard:&nbsp;&nbsp;<input type=text size=70 value=\"$url\" style=\"background: black; color: white\" /></form>";

?>

<br /></p>
<h3>Share a Track from Collection "<?=$title ?>"</h3>
<p>
<br />
<select id="track" onChange="queryHTML('trackResults', 'share.php?trackstyle=1&trackid=' + track.value)">
	<option value="-1"> -- Select a Track -- </option>
<?php

while ($row = mysqli_fetch_array($result))
{
	echo "<option value=\"" . $row['id'] . "\">" . $row['title'] . "</option>";
}

?>
</select>
<div id="trackResults"></div>
</p>
