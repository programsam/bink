<?php

$path = $_GET['f'];
$width = $_GET['w'];
$height = $_GET['h'];


header("Content-type: image/jpeg");
imagejpeg("http://s3.amazonaws.com/binkmedia/public/pics/$path"); 
?>
