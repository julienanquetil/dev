<?php

/**
 * Projet Korleon - Flickr 
 *
 * @description : Scrappe les 500 dernières images d'une recherche flickr
 * @author  julien.anquetil
 * @version 1.0
 */
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Scraper flickr</title>

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
	<style type="text/css">
		img {max-height:150px;}
	</style>
</head>
  <body>
<div class="container">
<h1>Rechercher des images a scrapper sur flickr</h1>
<form>
<div class="form-group">
    <label for="search">Mot a rechercher</label>
	<div class="input-group">
		<input type="text" name="search" class="form-control" id="search" placeholder="Recherche">
		<div class="input-group-addon"><i class="glyphicon glyphicon-search"></i></div>
  </div>
   <button type="submit" class="btn btn-default">Rechercher</button>
</form>
  
<?php
/**
 * Configuration
 */
//Parametres du proxy
$ProxyIp = '';
$ProxyLogin = '';
$ProxyPassword = '';
if (!file_exists('img/')) {
    mkdir('img/', 0777, true);
}
fopen("img/flickr.xml", "w");


function xml2array ( $xmlObject, $out = array () )
{
    foreach ( (array) $xmlObject as $index => $node )
        $out[$index] = ( is_object ( $node ) ) ? xml2array ( $node ) : $node;

    return $out;
}


if (isset($_GET["search"])) {
	
	$search = urlencode($_GET["search"]);
    $url = "https://api.flickr.com/services/rest/?method=flickr.photos.search&license=1,2,3,4,5,6,7&api_key=83cd8be1b18560cc98780bdb876694e7&tags=".$search."&per_page=500" ;

    //Creation de l'authentification
    if ($ProxyIp != '') {
        $auth = base64_encode($ProxyLogin . ':' . $ProxyPassword);
        $Context = array(
            'http' => array(
                'proxy' => 'tcp://' . $ProxyIp,
                'request_fulluri' => true,
                'header' => "Proxy-Authorization: Basic $auth",
            ),
        );
        $cxContext = stream_context_create($Context);

        $doc = file_get_contents($url, False, $cxContext);
		file_put_contents('img/flickr.xml',$doc);
    } else {
        $doc = file_get_contents($url);
		file_put_contents('img/flickr.xml',$doc);
    }
	
	
	$photos = (array)simplexml_load_file('img/flickr.xml');
	$photos = array_pop($photos);
	echo '<h2>Résultat de la recherche : <span class="text-success">'.$search.'</span></h2>';
	echo '<div class="row row-eq-height">';
	foreach($photos as $photo){
		$item = xml2array($photo);	
		$image_name = $item["@attributes"]["id"]."_".$item["@attributes"]["secret"]."_m.jpg";
		$img_src = "http://farm". $item["@attributes"]["farm"] .".static.flickr.com/".$item["@attributes"]["server"]."/".$image_name;
		 if (!file_exists("img/" . $image_name)) {
			copy($img_src, "img/".$image_name);
            echo ' <div class="col-md-2"><img src="' . $img_src . '"  class="img-responsive"/><p class="text-info text-center "> Récupérée</p></div>' . PHP_EOL;
        } else {
            echo '<div class="col-md-2"><img src="' . $img_src . '"  class="img-responsive"/><p class="text-danger text-center "> Existe deja</p></div>' . PHP_EOL;
        }
	}
	echo '</div>';
}
?>
  </div>
  <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
  </body>
</html>