<?php
	function ImageName($filePath){
		$fileParts = pathinfo($filePath);
		if(!isset($fileParts['filename'])){
			$fileParts['filename'] = substr($fileParts['basename'], 0, strrpos($fileParts['basename'], '.'));
		}
		return $fileParts[basename];
	}

	$result = array();
	$url = "https://www.pinterest.com/search/pins/?q=tatto%20ass";
    $doc = file_get_contents($url);
    $classname = "pinImg fullBleed loaded fade";
    $domdocument = new DOMDocument();
	libxml_use_internal_errors(true);
    $domdocument->loadHTML($doc);
    $a = new DOMXPath($domdocument);
    $imgs  =  $a->query('//img[contains(@class,"pinImg")]');
	
	
	foreach($imgs as $img)
	{
		$url_image = $img->getAttribute('src');	
		$image_name = ImageName($url_image);
		if (!file_exists("img/".$image_name)) {
			$image = file_get_contents($url_image);
			file_put_contents("img/".$image_name, $image);
			echo 'ImgSrc: ' . $img->getAttribute('src') .' OK<br />' . PHP_EOL;
		} else {
			echo 'ImgSrc: ' . $img->getAttribute('src') .' existe deja<br />' . PHP_EOL;
		}
		
		
		
	}
	exit();
	
	
?>