<?php

/**
 * Projet Korleon - Pinterest 
 *
 * @description : Scrappe les 25 dernieres images d'une recherche pinterest
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
	<title>Scraper Pinterest</title>

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
	<style type="text/css">
		img {max-height:200px;}
	</style>
</head>
  <body>
<div class="container">
<h1>Rechercher des images a scrapper sur Pinterest</h1>
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

function ImageName($filePath) {
    $fileParts = pathinfo($filePath);
    if (!isset($fileParts['filename'])) {
        $fileParts['filename'] = substr($fileParts['basename'], 0, strrpos($fileParts['basename'], '.'));
    }
    return $fileParts['basename'];
}

if (isset($_GET["search"])) {
    $search = $_GET["search"];
    $url = "https://www.pinterest.com/search/pins/?q=" . $search;

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
    } else {
        $doc = file_get_contents($url);
    }

    $classname = "pinImg fullBleed loaded fade";
    $domdocument = new DOMDocument();
    libxml_use_internal_errors(true);
    $domdocument->loadHTML($doc);
    $a = new DOMXPath($domdocument);
    $imgs = $a->query('//img[contains(@class,"pinImg")]');
	echo '<h2>Résultat de la recherche : <span class="text-success">'.$search.'</span></h2>';
	echo '<div class="row row-eq-height">';
    foreach ($imgs as $img) {
        $url_image = $img->getAttribute('src');
        $image_name = ImageName($url_image);
        $url_big = str_replace('pinimg.com/236x/', 'pinimg.com/736x/', $url_image);
        if (!file_exists("img/" . $image_name)) {
            $image = file_get_contents($url_big);
            file_put_contents("img/" . $image_name, $image);
            echo ' <div class="col-md-2"><img src="' . $url_image . '"  class="img-responsive"/><p class="text-info text-center "> Récupérée</p></div>' . PHP_EOL;
        } else {
            echo '<div class="col-md-2"><img src="' . $url_image . '"  class="img-responsive"/><p class="text-danger text-center "> Existe deja</p></div>' . PHP_EOL;
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