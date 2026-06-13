<?php
/*
 * v 202012221040
 * Created 22/12/2020
 */
if ( !isset( $_SESSION ) ) {
	session_start();
}
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

require '../inc/config.inc.php';
require '../inc/data.inc.php';
require 'inc/auth-logout.php';


if ( isset( $_POST[ 'qrid' ] ) ) { // Controllo Satispay
    $secret = md5( $_POST[ 'qrid' ] . SALT_MAIL );
    require_once('../vendor/autoload.php');
	$options = new QROptions(
      [
        'eccLevel' => QRCode::ECC_L,
        //'outputType' => QRCode::OUTPUT_MARKUP_SVG,
        'version'      => 10,
        //'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
          'outputType'   => QRCode::OUTPUT_IMAGE_JPG,
        //'version' => 5,
        //'eccLevel'            => EccLevel::L,
        'bgColor'             => '#FFFFFF', // overrides the imageTransparent setting
        'imageTransparent'    => true,
        'scale'               => 20,
            'drawLightModules'    => true,
            'drawCircularModules' => true,
        'circleRadius'        => 0.4,    
      ]
    );

    $qrcode = (new QRCode($options))->render($url_di_base.'/ticket.php?d='.$_POST[ 'qrid' ].'&s='.$secret.'', dirname(__DIR__).'/img/qr/'.$secret.'.jpg' );
    
} 
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
    <img src="../img/qr/<?php echo $secret; ?>.jpg" alt='QR Code' width='250' height='250'>
</div>
</body>
</html>