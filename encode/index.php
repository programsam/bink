<?php
include "../functions.php";


$file = $_GET['url'];

if (! isset($_GET['url']))
{
	pr("Failure, no file to encode");
	exit;
}


if (! isset($_GET['delete']))
	awsToMP3($file, FALSE);

if ($_GET['delete'] == 1)
	awsToMP3($file, TRUE);
else
	awsToMP3($file, FALSE);

function clear()
{
	 $handle = fopen("status.txt", "w"); 
	fwrite($handle, "\n");	
	fclose($handle);
}

function pr($str)
{
	 $handle = fopen("status.txt", "a"); 
	fwrite($handle, "$str\n");	
	flush();
	fclose($handle);
}

function awsToMP3($path, $delete)
{
clear();
$s3 = new S3('$S3_ACCESS_KEY', '$S3_SECRET_KEY');

$filename = explode('/', $path);
$srcpath = $filename[0] . "/" . $filename[1] . "/" . $filename[2] . "/";
$filename = $filename[3];
$title = explode('.', $filename);
$title = $title[0];
$newfile = $srcpath . $title . ".mp3";

pr("$path");
pr("Downloading file to temporary directory...");

$s3 -> getObject("binkmedia", $path, "/var/tmp/" . $filename);

pr("Converting to MP3");
makemp3("\"/var/tmp/" . $filename . "\"", "\"/var/tmp/" . $title . ".mp3\"");
pr("MP3 conversion is complete");
pr("Putting new file back to AWS: $newfile");
$s3 -> putObjectFile("/var/tmp/" . $title . ".mp3", "binkmedia", $newfile, "public-read-write");


pr("File has been returned to AWS.");
pr("Deleting temporary files...");
unlink("/var/tmp/" . $title . ".mp3");
unlink("/var/tmp/" . $filename);

if ($delete)
{
	pr("Deleting unconverted file...");
	$s3 -> deleteObject("binkmedia", $path);
}
else
{
	pr("NOT Deleting unconverted file...");
}

pr("File conversion process complete...");
}

function makemp3($input, $output)
{
pr("Executing this command: ffmpeg -y -v 3 -i $input -ab 192k $output");
runExternal("ffmpeg -y -v 3 -i $input -ab 192k $output", &$code);
}

function runExternal( $cmd, &$code ) {

$descriptorspec = array(0 => array("pipe", "r"), // stdin is a pipe that the child will read from
1 => array("pipe", "w"), // stdout is a pipe that the child will write to
2 => array("pipe", "w") // stderr is a file to write to
);

$pipes= array();
$process = proc_open($cmd, $descriptorspec, $pipes);

$output= "";

if (!is_resource($process)) return false;

#close child's input imidiately
fclose($pipes[0]);

stream_set_blocking($pipes[1],false);
stream_set_blocking($pipes[2],false);

$todo= array($pipes[1],$pipes[2]);

while( true ) {
$read= array();
if( !feof($pipes[1]) ) $read[]= $pipes[1];
if( !feof($pipes[2]) ) $read[]= $pipes[2];

if (!$read) break;

$ready= stream_select($read, $write=NULL, $ex= NULL, 2);

if ($ready === false) {
break; #should never happen - something died
}

foreach ($read as $r) {
$s= fread($r,1024);
pr($s);
}
}

fclose($pipes[1]);
fclose($pipes[2]);

$code= proc_close($process);

return $output;
}

?>
