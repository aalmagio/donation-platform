<?php 
if ( !isset( $_SESSION ) ) {
	session_start();
}
require '../inc/config.inc.php';
require '../inc/data.inc.php';
require_once( 'vendor/autoload.php' );
require 'inc/auth-logout.php';
if ( !function_exists( "GetSQLValueString" ) ) {
	function GetSQLValueString( $theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "" ) {
		if ( PHP_VERSION < 6 ) {
			$theValue = get_magic_quotes_gpc() ? stripslashes( $theValue ) : $theValue;
		}
		//$theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);
		$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );
		$theValue = $conn->real_escape_string( $theValue );
		if ( isset( $conn ) ) {
			mysqli_close( $conn );
		}
		switch ( $theType ) {
			case "text":
				$theValue = ( $theValue != "" ) ? "'" . $theValue . "'": "NULL";
				break;
			case "long":
			case "int":
				$theValue = ( $theValue != "" ) ? intval( $theValue ) : "NULL";
				break;
			case "double":
				$theValue = ( $theValue != "" ) ? doubleval( $theValue ) : "NULL";
				break;
			case "date":
				$theValue = ( $theValue != "" ) ? "'" . $theValue . "'": "NULL";
				break;
			case "defined":
				$theValue = ( $theValue != "" ) ? $theDefinedValue : $theNotDefinedValue;
				break;
		}

		return $theValue;
	}
}
function utf8ize( $d ) {
	if ( is_array( $d ) ) {
		foreach ( $d as $k => $v ) {
			$d[ $k ] = utf8ize( $v );
		}
	} else if ( is_string( $d ) ) {
		return utf8_encode( $d );
	}
	return $d;
}
// Gestione caratteri spciali -FINE
$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );
if(USE_SANDBOX == true) {
	\SatispayGBusiness\Api::setSandbox(true);
}
$authData = json_decode( SY_AUTH );

\SatispayGBusiness\Api::setPublicKey($authData->public_key);
\SatispayGBusiness\Api::setPrivateKey($authData->private_key);
\SatispayGBusiness\Api::setKeyId($authData->key_id);

$payment = \SatispayGBusiness\Payment::get($_GET['syid']);
//print_r($payment);
$chk_satispay ['Id'] = $payment -> id;
$chk_satispay ['Codice_transazione_Satispay'] = $payment -> code_identifier;
$chk_satispay ['Tipo'] = $payment -> type;
$chk_satispay ['Importo'] = $payment -> amount_unit/100;
$chk_satispay ['Valuta'] = $payment -> currency;
if ('ACCEPTED' == $payment -> status){
	$chk_satispay ['Esito '] = "OK";
}
else{
	$chk_satispay ['Esito '] = "KO";
}
$chk_satispay ['URL_pagamento'] = $payment -> metadata->redirect_url;
$chk_satispay ['Id_donatore'] = $payment -> sender -> id;
//$chk_satispay ['Tipo'] = $payment -> type;
$chk_satispay ['Nome_donatore'] = $payment ->sender->name;
$chk_satispay ['Data_ordine'] = $payment -> insert_date;
$chk_satispay ['Scadenza_ordine'] = $payment -> expire_date;
$chk_satispay ['CodTrans'] = $payment -> external_code;
?>


<?php require('inc/head.inc.php'); ?>
<body>
<?php require('inc/nav_hor.inc.php'); ?>
<div class="container-fluid">
	<div class="row">
		<?php require('inc/nav_ver.inc.php'); ?>
		<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
			<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
				<h1 class="h2">Verifica Singola Donazione Satispay</h1>
			</div>
			<!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
			<p class="alert-info">Dati letti via API dal backend di Satispay</p>
			<?php
			foreach ($chk_satispay as $k => $v){
				echo "<strong>".str_replace("_", " ",$k)."</strong> = " .$v ."<br>";
			}
			
			if ( isset( $chk_satispay ['CodTrans'] ) ) {
				$query_satispay = sprintf( "SELECT * FROM Satispay WHERE CodTrans = '%s';",
        		trim( $chk_satispay[ 'CodTrans' ] ), 0, 20 );
				$singola_satispay = mysqli_query( $conn, $query_satispay )or die( mysqli_error($conn) );
				$row_satispay = mysqli_fetch_assoc( $singola_satispay );
				$totalRows_singola_satispay = mysqli_num_rows( $singola_satispay );
				$query_singola_donazione = sprintf( "SELECT Donazione.*,
					Anagrafica.*
					FROM Donazione
					LEFT JOIN Anagrafica
					ON Donazione.Id_a =Anagrafica.Id_a   
					WHERE Donazione.CodTrans = '%s';",
					trim( $chk_satispay ['CodTrans'] ), 0, 20 );
				//echo $query_singola_donazione;
				$singola_donazione = mysqli_query( $conn, $query_singola_donazione )or die( mysqli_error($conn) );
				$row_singola_donazione = mysqli_fetch_assoc( $singola_donazione );
				//var_dump($row_singola_donazione);
				$totalRows_singola_donazione = mysqli_num_rows( $singola_donazione );
				//echo $totalRows_singola_donazione;
				if ( "TG" == substr( $chk_satispay ['CodTrans'], -2 ) ) {
					$conn_tes = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME_TGIFT )or trigger_error( mysqli_error($conn_tes), E_USER_ERROR );
					$query_tesserainregalo = sprintf( "SELECT *
					FROM Voucher
					WHERE CodTrans = '%s';",
						$chk_satispay ['CodTrans'] );
					$tesserainregalo = mysqli_query( $conn_tes, $query_tesserainregalo )or die( mysqli_error($conn) );
					$row_tesserainregalo = mysqli_fetch_assoc( $tesserainregalo );
					$totalRows_tesserainregalo = mysqli_num_rows( $tesserainregalo );
				}
			} 
			?>
			<hr>
			<p class="alert-info">Dati letti dal DB</p>
			<?php
			foreach ($row_satispay as $k => $v){
				if("amount_unit"!=$k){
					echo "<strong>".str_replace("_", " ",$k)."</strong> = " .$v ."<br>";
				}
				else{
					echo "<strong>".str_replace("_", " ",$k)."</strong> = " .$v/100 ."<br>";	
				}
			}?>
			<hr>
			<p class="alert-info">Altri dati letti dal DB</p>
			<?php
			foreach ($row_singola_donazione as $k => $v){
				echo "<strong>".str_replace("_", " ",$k)."</strong> = " .$v ."<br>";
			}?>
		</main>
	</div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>
