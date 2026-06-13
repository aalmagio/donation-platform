<?php
//202101081547
/*
*  Add IF before calling save paypalcheckout on WS (check if donation ID is empty)
*  Add IF to test "donazione.esito" in mysql: if WA caputre order (PayPal API); if OK/KO get order (PayPal API)
*/
require '../inc/config.inc.php';
require '../inc/data.inc.php';
error_log( date( '[Y-m-d H:i:s e] ' ) . "paypal.php : " . $_SERVER['QUERY_STRING'] . PHP_EOL, 3, LOG_FILE );
// Valido i parametri ricevuti da PayPal prima di usarli
$pp_token = isset( $_GET[ 'token' ] ) ? (string) $_GET[ 'token' ] : '';
if ( '' === $pp_token ) {
  error_log( date( '[Y-m-d H:i:s e] ' ) . "paypal.php: token PayPal mancante" . PHP_EOL, 3, LOG_FILE );
  header( "Location: " . FORM_ERROR_PAGE );
  exit;
}
$connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
if ( !$connection || $connection->connect_errno ) {
  error_log( date( '[Y-m-d H:i:s e] ' ) . "paypal.php: connessione DB fallita" . PHP_EOL, 3, LOG_FILE );
  header( "Location: " . FORM_ERROR_PAGE );
  exit;
}
// preparo lo statement
if ( !( $stmt = $connection->prepare( "SELECT PayPalCheckout.CodTrans , PayPalCheckout.Id_OrderPayPal, PayPalCheckout.token_type , PayPalCheckout.access_token ,
    Donazione.* ,
    Anagrafica.*
    FROM PayPalCheckout
    LEFT JOIN Donazione
    ON PayPalCheckout.CodTrans= Donazione.CodTrans
    LEFT JOIN Anagrafica
    ON Donazione.Id_a = Anagrafica.Id_a
    WHERE Id_OrderPayPal = ? " ) ) ) {
  error_log( date( '[Y-m-d H:i:s e] ' ) . "paypal.php prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
  $connection->close();
  header( "Location: " . FORM_ERROR_PAGE );
  exit;
}
// associo i parametri ai placeholder
$stmt->bind_param( 's', $pp_token );
// eseguo la query e chiudo
if ( $stmt->execute() ) {
  $result = $stmt->get_result();
  $row = $result->fetch_array( MYSQLI_ASSOC );
	//if ( "OK" == $_GET[ 'esito' ] && "PP" == $row[ 'pay_method' ] && "WA" == $row[ 'esito' ] ) { //Transazione PP OK o KO in stato WA
	if ( "WA" == $row[ 'esito' ] ) { //Transazione PP OK o KO in stato WA
    // Caputre Order
    $curl = curl_init();
    //define("PP_URLAPI", "https://api.sandbox.paypal.com");
    curl_setopt_array( $curl, array(
      CURLOPT_URL => PP_URLAPI . "/v2/checkout/orders/" . $row[ 'Id_OrderPayPal' ] . "/capture",
      CURLOPT_RETURNTRANSFER => true,
			//CURLOPT_SSL_VERIFYHOST => 2,
			//CURLOPT_SSL_VERIFYPEER => true,
			//CURLINFO_HEADER_OUT => true, // DEBUG  
      CURLOPT_HEADER => false,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
			// Positive response
      CURLOPT_HTTPHEADER => array(
        'authorization: ' . $row[ 'token_type' ] . ' ' . $row[ 'access_token' ],
        'content-type: application/json'
      ),
			// FORCE Negative response in sandbox
			/*CURLOPT_HTTPHEADER => array(
			  'authorization: ' . $row[ 'token_type' ] . ' ' . $row[ 'access_token' ],
			  'content-type: application/json',
			  'PayPal-Mock-Response:{"mock_application_codes": "DUPLICATE_INVOICE_ID"}'
			),*/
    ) );

    $response = curl_exec( $curl );
    $err = curl_error( $curl );
    curl_close( $curl );
		//if ( DEBUG == true ) {
		error_log( date( '[Y-m-d H:i:s e] ' ) . "paypal.php - Esito PP checkout orders capture: " . $response . PHP_EOL, 3, LOG_FILE );
		//}
    if ( $err ) {
      echo "cURL Error #:" . $err;
    }
        // Altri casi
    }
	else{
		// Show Order Details
		$curl = curl_init();
		//define("PP_URLAPI", "https://api.sandbox.paypal.com");
		curl_setopt_array( $curl, array(
			CURLOPT_URL => PP_URLAPI . "/v2/checkout/orders/" . $row[ 'Id_OrderPayPal' ] ,
			CURLOPT_RETURNTRANSFER => true,
			//CURLOPT_SSL_VERIFYHOST => 2,
			//CURLOPT_SSL_VERIFYPEER => true,
			//CURLINFO_HEADER_OUT => true, // DEBUG  
			CURLOPT_HEADER => false,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			// Positive response
			CURLOPT_HTTPHEADER => array(
				'authorization: ' . $row[ 'token_type' ] . ' ' . $row[ 'access_token' ],
				'content-type: application/json'
			),
			// FORCE Negative response in sandbox
			/*CURLOPT_HTTPHEADER => array(
			  'authorization: ' . $row[ 'token_type' ] . ' ' . $row[ 'access_token' ],
			  'content-type: application/json',
			  'PayPal-Mock-Response:{"mock_application_codes": "DUPLICATE_INVOICE_ID"}'
			),*/
		) );

		$response = curl_exec( $curl );
		$err = curl_error( $curl );
		curl_close( $curl );
		//if ( DEBUG == true ) {
		error_log( date( '[Y-m-d H:i:s e] ' ) . "paypal.php - Esito PP checkout orders Show Details: " . $response . PHP_EOL, 3, LOG_FILE );
		//}
		if ( $err ) {
			echo "cURL Error #:" . $err;
		}
		
  }
} else {
  // Gestione errore PP
  error_log( date( '[Y-m-d H:i:s e] ' ) . "paypal.php execute failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
  $stmt->close();
  $connection->close();
  header( "Location: " . FORM_ERROR_PAGE );
  exit;
}
//var_dump($row);
$stmt->close();
$connection->close();
// Chiamo DON_WS per l'esito
$convert_row =json_encode($row);
$dati = new stdClass();
$dati->E = json_decode($convert_row);
if (isset($response)){
  $dati->PP = json_decode( $response );    
}

//if ( DEBUG == true ) {
	if ("WA"==$row['esito']){// Esito Donazione su mysql WA
		error_log( date( '[Y-m-d H:i:s e] ' ) . "paypal.php - Esito esito mysql WA: " . $response . PHP_EOL, 3, LOG_FILE );
	}
	else{ // Esito Donazione su mysql NON WA
		error_log( date( '[Y-m-d H:i:s e] ' ) . "paypal.php - Esito esito mysql NON WA (".$row['esito'] ."): " . $response . PHP_EOL, 3, LOG_FILE );
	}
//}
if ( "OK" == ( $_GET[ 'esito' ] ?? '' ) && $pp_token == $row[ 'Id_OrderPayPal' ] && ( !isset( $dati->PP->name ) || "UNPROCESSABLE_ENTITY" != $dati->PP->name ) ) {
  $dati->PP->PP_esito = "OK";
  $secret = md5($row['Id_a'] . SALT_MAIL );
  $redirect_url= $url_di_base."/function/mail.php?d=". $row['Id_a']. "&s=" . $secret ;
} else {
    if (!isset ($dati->PP)){
        $dati->PP = new stdClass();  
    } 
  $dati->PP->Order = $row[ 'Id_OrderPayPal' ];
  $dati->PP->PP_esito = "KO";
	$redirect_url = FORM_ERROR_PAGE . "?e=" . $dati->PP->details[ 0 ]->issue . "&t=" . $row[ 'CodTrans' ];
}
$PP_data = json_encode($dati);  

if(""==$row['CodiceMentor']){
$azione = array( "operation" => "save",
  "param" => "paypalcheckout",
  "data" => $dati
);
$azione_string = json_encode( $azione );
$ch = curl_init( DON_WS );
curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
curl_setopt( $ch, CURLOPT_POSTFIELDS, $azione_string );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
  'Content-Type: application/json',
  'Content-Length: ' . strlen( $azione_string ) ) );
$result = curl_exec( $ch );
}

header( "Location: " . $redirect_url );
exit;