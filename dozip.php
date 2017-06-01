<?php
 if ( isset($_GET['dl']))
 {
 
 	header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename=binkcollection.zip');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize('/var/tmp/temp.zip'));
    ob_clean();
    flush();
    readfile('/var/tmp/temp.zip');
    exit;
    
 } 

 $output = `rm -rf /var/tmp/*`;

 include "functions.php";
 
 $id = $_GET['id']; 
	//echo "Would be making zip file for $id";
	//echo ". First we need the list of sound files...";
	
	$result = bink_query("select * from tracks where jamid = $id order by num asc");

	if (mysqli_num_rows($result) == 0)
		return "";
	
	$logfile = "/var/tmp/downloadlog";
	
	unlink ($logfile);
	
	$log = fopen($logfile, 'a') or die("can't open file");
			
	$file = "/var/tmp/temp.zip";
	
	$zip = new ZipArchive();
	$zip-> open($file, ZipArchive::CREATE);
	
	while (	$row = mysqli_fetch_array($result) )
	{
		$ext = pathinfo($row['path'], PATHINFO_EXTENSION);
		
		if ($ext == "xspf" || $ext == "xspf")
			continue;
			
		if ($row['title'] == "_BREAK_" || $row['title'] == "--------------------")
		{
			//do nothing
		}
		else //we have found a valid file, and the file is located at $path + the header.
		{
			$path = $row['path'];
			//$fullpath = urlencode($fullpath);
			$split = explode("/", $row['path']);
			$zipfilename = $split[2];
			
			$urlfilename = rawurlencode($split[2]);
			$fullpath = "https://s3.amazonaws.com/binkmedia/public/" . $split[0] . "/" . $split[1] . "/" . $urlfilename;
			$output = `wget $fullpath -O "/var/tmp/$zipfilename" --append-output=/var/tmp/downloadlog`;
			
			
			$zip -> addFile("/var/tmp/$zipfilename", $zipfilename); 
			fwrite($log, "Zipped up $zipfilename into zip file...\n\n");
		}
	}
	$zip -> close();
	
	fwrite($log, "DONE...");
	fwrite($log, "Total ZIP filesize: " . filesize("/var/tmp/temp.zip"));
?>
