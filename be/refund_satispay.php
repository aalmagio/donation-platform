<?php
/*
 * v 202305252324
 * Created 25/05/2023
 */
if ( !isset( $_SESSION ) ) {
	session_start();
}
require '../inc/config.inc.php';
require '../inc/data.inc.php';
require 'inc/auth-logout.php';
if ( isset( $_POST[ 'syid' ] ) ) { // Controllo Satispay
	require_once( '../vendor/autoload.php' );

	$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );
	if ( USE_SANDBOX == true ) { 
     \SatispayGBusiness\Api::setSandbox( true );
    }
	$authData = json_decode( SY_AUTH );
    
	\SatispayGBusiness\Api::setPublicKey($authData->public_key);
    \SatispayGBusiness\Api::setPrivateKey($authData->private_key);
    \SatispayGBusiness\Api::setKeyId($authData->key_id);
    $payment = \SatispayGBusiness\Payment::get( $_POST[ 'syid' ]);
	
	//print_r($payment);
	if (!empty($payment->id))$chk_satispay[ 'Id' ] = $payment->id;
	if (!empty($payment->code_identifier))$chk_satispay[ 'Codice_transazione_Satispay' ] = $payment->code_identifier;
	if (!empty($payment->type))$chk_satispay[ 'Tipo' ] = $payment->type;
	if (!empty($payment->amount_unit))$chk_satispay[ 'Importo' ] = $payment->amount_unit / 100;
	if (!empty($payment->currency))$chk_satispay[ 'Valuta' ] = $payment->currency;
	if (!empty($payment->status)){
		if ( 'ACCEPTED' == $payment->status ) {
			$chk_satispay[ 'Esito ' ] = "OK";
		} else {
			$chk_satispay[ 'Esito ' ] = "KO";
		}
	}
	if (!empty($payment->metadata->redirect_url))$chk_satispay[ 'URL_pagamento' ] = $payment->metadata->redirect_url;
	if (!empty($payment->sender->id)) $chk_satispay[ 'Id_donatore' ] = $payment->sender->id;
	//$chk_satispay ['Tipo'] = $payment -> type;
	if (!empty($payment->sender->name)) $chk_satispay[ 'Nome_donatore' ] = $payment->sender->name;
	if (!empty($payment->insert_date)) $chk_satispay[ 'Data_ordine' ] = $payment->insert_date;
	if (!empty($payment->expire_date)) $chk_satispay[ 'Scadenza_ordine' ] = $payment->expire_date;
	if (!empty($payment->external_code)) $chk_satispay[ 'CodTrans' ] = $payment->external_code;
    
    $refund = \SatispayGBusiness\Payment::create([
      "flow" => "REFUND",
      "amount_unit" => $payment->amount_unit,
      "currency" => "EUR",
      "parent_payment_uid" => $payment->id,
      "external_code" => $payment->external_code
    ]);
    if (!empty($refund->id)) $ref_satispay[ 'Id' ] = $refund->id;
	
	if (!empty($refund->type))$ref_satispay[ 'Tipo' ] = $refund->type;
	if (!empty($refund->amount_unit))$ref_satispay[ 'Importo' ] = $refund->amount_unit / 100;
	if (!empty($refund->currency))$ref_satispay[ 'Valuta' ] = $refund->currency;
	if (!empty($refund->status)){
		if ( 'ACCEPTED' == $refund->status ) {
			$ref_satispay[ 'Esito' ] = "OK";
		} else {
			$ref_satispay[ 'Esito' ] = "KO";
		}
	}
	if (!empty($refund->expired)) $ref_satispay[ 'Scaduto' ] = $refund->expired;
	if (!empty($refund->sender->id)) $ref_satispay[ 'Id_negozio' ] = $refund->sender->id;
	if (!empty($refund->sender->type)) $ref_satispay[ 'Tipo_negozio' ] = $refund->sender->type;
    if (!empty($refund->receiver->id)) $ref_satispay[ 'Id_donatore' ] = $refund->receiver->id;
	if (!empty($refund->receiver->type)) $ref_satispay[ 'Tipo' ] = $refund->receiver->type;
	if (!empty($payment->insert_date)) $ref_satispay[ 'Data_ordine' ] = $payment->insert_date;
	if (!empty($payment->expire_date)) $ref_satispay[ 'Data_scadenza' ] = $payment->expire_date;
	echo "Transazione<br>";
    foreach ( $chk_satispay as $k => $v ) {
		echo "<strong>" . str_replace( "_", " ", $k ) . "</strong> = " . $v . "<br>";
	}
    echo "<hr>Rimborso<br>";
    foreach ( $ref_satispay as $k => $v ) {
		echo "<strong>" . str_replace( "_", " ", $k ) . "</strong> = " . $v . "<br>";
    }
    if ($ref_satispay[ 'Esito' ] == "OK"){
        $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
        if ( $connection->connect_errno ) {
            trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
        }
        // preparo lo statement
        if ( !( $stmt = $connection->prepare( "UPDATE Donazione SET esito='RF' WHERE CodTrans=?;" ) ) ) {
            trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
        }
   
        if ( !$stmt->bind_param( 's',  $chk_satispay[ 'CodTrans' ] ) ) {
            trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
        }
        // eseguo la query e chiudo
        if ( !$stmt->execute() ) {
            trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
        }
        $stmt->close();
        $connection->close();
    }

}