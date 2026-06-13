<?php
/*
 * v 202012221040
 * Created 22/12/2020
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
	foreach ( $chk_satispay as $k => $v ) {
		echo "<strong>" . str_replace( "_", " ", $k ) . "</strong> = " . $v . "<br>";
	}
} 
elseif ( isset( $_POST[ 'ccid' ] ) ) { // Contollo GestPay
	//echo "Controllo su GestPay " . $_POST['ccid'] ."<br>";
	//function GetOrderGestPay( $dati ) {    
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, GP_URLAPI . '/v1/payment/detail/' . $_POST[ 'ccid' ] );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
	$headers = array();
	$headers[] = 'Authorization: apikey ' . GP_APIKEY;
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
	$response = curl_exec( $ch );
	$err = curl_error( $ch );
	curl_close( $ch );
	if ( $err ) {
		echo "cURL Error #:" . $err;
        
	} else {
		$payment = ( json_decode( $response, FALSE ) );
		//print_r($payment);
		if ( !empty( $payment->payload->paymentID ) )$chk_gestpay[ 'Id' ] = $payment->payload->paymentID;
		if ( !empty( $payment->payload->bankTransactionID ) )$chk_gestpay[ 'Codice_transazione_GestPay' ] = $payment->payload->bankTransactionID;
		if ( !empty( $payment->payload->transactionState ) )$chk_gestpay[ 'Stato' ] = $payment->payload->transactionState;
		//$chk_gestpay[ 'Importo' ] = $payment->amount_unit / 100;
		if ( !empty( $payment->payload->transactionErrorCode ) ) {
			if ( $payment->payload->transactionErrorCode != "0" ) {
				$chk_gestpay[ 'Codice_Errore' ] = $payment->payload->transactionErrorCode;
				$chk_gestpay[ 'Descrizione_Errore' ] = $payment->payload->transactionErrorDescription;
			} else {
				$chk_gestpay[ 'Codice_Autorizzazione' ] = $payment->payload->authorizationCode;
			}
		}
		//$chk_gestpay[ 'Valuta' ] = $payment->currency;

		if ( !empty( $payment->payload->transactionResult ) ) {
			if ( 'APPROVED' == $payment->payload->transactionResult ) {
				$chk_gestpay[ 'Esito ' ] = "OK";
			} else {
				$chk_gestpay[ 'Esito ' ] = "KO";
			}
		}
		if ( !empty( $payment->payload->paymentMethod ) )$chk_gestpay[ 'Carta' ] = $payment->payload->paymentMethod;
		if ( !empty( $payment->payload->productType ) )$chk_gestpay[ 'Tipo_Prodotto' ] = $payment->payload->productType;
		//$chk_gestpay ['Tipo'] = $payment -> type;
		if ( !empty( $payment->payload->buyer->name ) )$chk_gestpay[ 'Nome' ] = $payment->payload->buyer->name;
		if ( !empty( $payment->buyer->email ) )$chk_gestpay[ 'Mail' ] = $payment->payload->buyer->email;
		if ( !empty( $payment->payload->token ) )$chk_gestpay[ 'Masked_PAN' ] = $payment->payload->token;
		if ( !empty( $payment->payload->tokenExpiryMonth ) )$chk_gestpay[ 'Mese_scadenza' ] = $payment->payload->tokenExpiryMonth;
		if ( !empty( $payment->payload->tokenExpiryYear ) )$chk_gestpay[ 'Anno_scadenza' ] = $payment->payload->tokenExpiryYear;
		if ( !empty( $payment->payload->shopTransactionID ) )$chk_gestpay[ 'CodTrans' ] = $payment->payload->shopTransactionID;
		//$chk_gestpay[ 'Eventi' ] = sizeof($payment->payload->events);
		if ( sizeof( (array)$payment->payload->events ) >= 1 ) {
			for ( $i = 0; $i < sizeof( $payment->payload->events ); $i++ ) {
				//echo $i;
				$chk_gestpay[ 'Tipo_' . $i ] = $payment->payload->events[ $i ]->event->eventtype;
				$chk_gestpay[ 'Importo_' . $i ] = $payment->payload->events[ $i ]->event->eventamount;
				$chk_gestpay[ 'Data_' . $i ] = $payment->payload->events[ $i ]->event->eventdate;
			}
		}
		if ( '0' == $payment->error->code )
			foreach ( $chk_gestpay as $k => $v ) {
				echo "<strong>" . str_replace( "_", " ", $k ) . "</strong> = " . $v . "<br>";
			}
		else {
			echo "Si &egrave; verificato un errore (" . $payment->error->code .  ") " . $payment->error->description ."<br>" ;
			echo "<strong>La transazione non risulta presente o richiamabile tramite API.<br> Se vuoi effettuare ulteriori controlli vai sul backend di GestPay</strong>";
            echo "<br>". htmlspecialchars( $_POST[ 'ccid' ] ?? '', ENT_QUOTES, 'UTF-8' ) ;

		}


	}
}
elseif ( isset( $_POST[ 'ppid' ] ) ) { // Contollo GestPay
    $OAuth = CLIENT_ID_PP . ":" . SECRET_ID_PP;
	$curl = curl_init();
    curl_setopt_array( $curl, array(
        CURLOPT_URL => PP_URLAPI . "/v1/oauth2/token",
        //CURLOPT_URL => "https://api.sandbox.paypal.com/v1/oauth2/token",      
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_USERPWD => $OAuth,
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'accept-language: en_US'
        ),

    ) );

    $response = curl_exec( $curl );
    $err = curl_error( $curl );
    curl_close( $curl );

    if ( $err ) {
		echo $err;
        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Errore chiamata Token PayPal :" . $err . PHP_EOL, 3, LOG_FILE ); //DEBUG
        }
    } else {
        $response_token = json_decode( $response, true ); //scope,  access_token, token_type , app_id, expires_in, nonce
		//echo $response_token['access_token'];
	
		$curl = curl_init();
		curl_setopt_array( $curl, array(
			CURLOPT_URL => PP_URLAPI."/v2/checkout/orders/".$_POST[ 'ppid' ],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				'authorization: Bearer '.$response_token['access_token'],
				'content-type: application/json'
			),
		) );
		$response = curl_exec( $curl );
		$err = curl_error( $curl );
		$payment = json_decode($response);
		curl_close( $curl );

		if ( $err ) {
			echo "cURL Error #:" . $err;
		} else {
			if (!empty($payment->id)) $chk_paypal[ 'Codice_transazione_PayPal' ] = $payment->id;
			if (!empty($payment->status))$chk_paypal[ 'Stato' ] = $payment->status;
			if (!empty($payment->purchase_units['0']-> amount->value))$chk_paypal[ 'Importo' ] = $payment->purchase_units['0']-> amount->value;
			if (!empty($payment->purchase_units['0']-> amount->currency_code))$chk_paypal[ 'Valuta' ] = $payment->purchase_units['0']-> amount->currency_code;
			if (!empty($payment->purchase_units['0']->custom_id))$chk_paypal[ 'CodTrans' ] = $payment->purchase_units['0']->custom_id;
			if (!empty($payment->payer->name->given_name))$chk_paypal[ 'Nome' ] = $payment->payer->name->given_name;
			if (!empty($payment->payer->name->surname))$chk_paypal[ 'Cognome' ] = $payment->payer->name->surname;
			if (!empty($payment->payer->email_address))$chk_paypal[ 'Email' ] = $payment->payer->email_address;
			if (!empty($payment->payer->payer_id))$chk_paypal[ 'Id_User_PayPal' ] = $payment->payer->payer_id;
			if (!empty($payment->purchase_units['0']->payments->captures['0']->status)) $chk_paypal[ 'Status' ] = $payment->purchase_units['0']->payments->captures['0']->status;
			if (!empty($payment->status)){
				if ( 'COMPLETED' == $chk_paypal[ 'Status' ] ) {
					$chk_paypal[ 'Esito ' ] = "OK";
				} else {
					$chk_paypal[ 'Esito ' ] = "KO";
				}
			}
			if (""!= trim($payment->id)){
				foreach ( $chk_paypal as $k => $v ) {
					echo "<strong>" . str_replace( "_", " ", $k ) . "</strong> = " . $v . "<br>";
				}
			} else{
				echo "<strong>La transazione non risulta presente o richiamabile tramite API.<br> Se vuoi effettuare ulteriori controlli vai sul backend di PayPal</strong>";
			}			
		}
	}
}
?>