<?php

/**
 * Projet Korleon - Pinterest 
 *
 * @description : Scrappe les 25 dernieres images d'une recherche pinterest
 * @author  julien.anquetil
 * @version 1.0
 */
header('Content-Type: text/html; charset=utf-8');

/**
 * Configuration
 */
//Paramertres du proxy
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


    foreach ($imgs as $img) {
        $url_image = $img->getAttribute('src');
        $image_name = ImageName($url_image);
        $url_big = str_replace('pinimg.com/236x/', 'pinimg.com/736x/', $url_image);
        if (!file_exists("img/" . $image_name)) {
            $image = file_get_contents($url_big);
            file_put_contents("img/" . $image_name, $image);
            echo '<img src="' . $img->getAttribute('src') . '" /> récupérée <br/>' . PHP_EOL;
        } else {
            echo '<img src="' . $img->getAttribute('src') . '" /> existe deja<br />' . PHP_EOL;
        }
    }
}

echo '<form>';
echo '<input type="text" name="search" id="search" placeholder="recherche">';
echo '<input type="submit" value="rechercher">';
echo '</form>';
?>