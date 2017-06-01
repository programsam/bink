<?php

$path = $_GET['f'];
$width = $_GET['w'];
$height = $_GET['h'];


header("Content-type: image/jpeg");
if ($width != "" && $height != "")
{
	imagejpeg(resizeImage("http://s3.amazonaws.com/binkmedia/public/pics/$path", $width, $height), '', 100);
}
else
{
	imagejpeg(resizeImage("http://s3.amazonaws.com/binkmedia/public/pics/$path", 0, 0));
}

/**
* Resize an image and keep the proportions
* @author Allison Beckwith <allison@planetargon.com>
* @param string $filename
* @param integer $max_width
* @param integer $max_height
* @return image
*/
function resizeImage($filename, $max_width, $max_height)
{
   list($orig_width, $orig_height) = getimagesize($filename);

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

   $image = imagecreatefromjpeg($filename);

   imagecopyresampled($image_p, $image, 0, 0, 0, 0, 
                                     $width, $height, $orig_width, $orig_height);

   return $image_p;
} 
?>
