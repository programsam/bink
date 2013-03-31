<?php
include "../S3.php";
include "../settings.php";
$s3 = new S3($S3_ACCESS_KEY, $S3_SECRET_KEY);
header("Content-type: text/plain");
$lines = file("status.txt");
$path = trim($lines[1]);
$filename = pathinfo($path, PATHINFO_BASENAME);
echo "<path>" . $path. "</path>";
echo "<filename>" .  $filename . "</filename>";
$info = $s3 -> getObjectInfo("binkmedia", trim($lines[1]));
$size = $info['size'];
echo "<size>$size</size>";

$description = array(
	0 => "Not started", 
	1 => "Downloading...", 
	2 => "Converting...", 
	3 => "Conversion complete.",
	4 => "Uploading...",
	5 => "Deleting temporary files",
	6 => "Finished");

$step = 0;
foreach ($lines as $line)
{
	if (strpos($line, "conversion process complete") != FALSE)
		$step++;
	if (strpos($line, "temporary files") != FALSE)
		$step++;
	if (strpos($line, "file has been returned") != FALSE)
		$step++;
	if (strpos($line, "new file back to AWS") != FALSE)
		$step++;
	if (strpos($line, "conversion is complete") != FALSE)
		$step++;
	if (strpos($line, "to MP3") != FALSE)
		$step++;
	if (strpos($line, "file to temporary directory") != FALSE)
		$step++;
}
echo "<step>$step</step>";
echo "<description>" . $description[$step] . "</description>";

//echo filesize("/var/tmp/$filename");
//$lines = file("/var/tmp/" . $filename);

if ($step == 1)
{
	echo "<progress>";
	$dlsize = filesize("/var/tmp/$filename");
	echo percent($dlsize, $size);
	echo "</progress>";
}

	
//echo $currentline;

if ($step == 2)
{
	echo "<progress>";
	$currentline = $lines[count($lines)-1];
	$start = strpos($currentline, "size= ") + 6;
	$end = strpos($currentline, "time=");
	$len = $end - $start-3;
	$done = substr($currentline, $start, $len);
	$done = $done * 1024;
	echo percent($done, $size);
	echo "</progress>";
}

echo "<details>";
foreach ($lines as $line)
{
	echo "$line";
}
echo "</details>";

function percent($num_amount, $num_total) {
$count1 = $num_amount / $num_total;
$count2 = $count1 * 100;
$count = number_format($count2, 0);
echo $count;
}

?>
