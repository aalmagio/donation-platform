<?php
// File di test per la generazione dei QR code (non usato in produzione)
//declare(strict_types=1);

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

require_once('../vendor/autoload.php');
require_once('../inc/config.inc.php');

$options = new QROptions(
  [
    'eccLevel' => QRCode::ECC_L,
    //'outputType' => QRCode::OUTPUT_MARKUP_SVG,
    'version'      => 10,
	//'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
	'outputType'   => QRCode::OUTPUT_IMAGE_JPG,
   
	//'outputType'   => QROutputInterface::GDIMAGE_PNG,
    //'version' => 5,
    //'eccLevel'            => EccLevel::L,
	'bgColor'             => [100, 0, 0],
	'imageTransparent'    => false,
	'scale'               => 20,
   /*     'drawLightModules'    => true,
        'drawCircularModules' => true,
	'circleRadius'        => 0.4,    */
  ]
);

$qrcode = (new QRCode($options))->render(URL_DI_BASE . '/ticket.php?d=0&s=test','../img/qr/test.jpg' );

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <title>Create QR Codes in PHP</title>
  <link rel="stylesheet" href="/css/styles.min.css">
</head>
<body style="background-color: #000;">
<h1>Creating QR Codes in PHP</h1>
<div class="container">
 <!-- <img src='<?= $qrcode ?>' alt='QR Code' width='800' height='800'>-->
    <img src="../img/qr/9f175959ea32bfedb3ebb7abd7f68c81.jpg" alt='QR Code' width='250' height='250'>
</div>
</body>
</html>