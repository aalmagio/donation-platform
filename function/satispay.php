<?php
//202506 - Security hardening: safe error handling, HMAC signature
/*
 *  send mail via file_get_contents
 */
require '../inc/config.inc.php';
require '../inc/data.inc.php';
require_once '../inc/security.php';
require_once( '../vendor/autoload.php' );
if (isset($_GET)){
	$SY_get = json_encode($_GET);
	error_log( date( '[Y-m-d H:i:s e] ' ) . "satispay.php callback_url GET: " . $SY_get . PHP_EOL, 3, LOG_FILE ); //DEBUG
	$connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
	if ( !$connection ) {
		error_log( date( '[Y-m-d H:i:s e] ' ) . "satispay.php: DB connection failed" . PHP_EOL, 3, LOG_FILE );
		header("location: " . FORM_ERROR_PAGE);
		exit;
	}
	// preparo lo statement
	if ( !( $stmt = $connection->prepare( "SELECT Satispay.id, Satispay.status, Satispay.expired, Satispay.amount_unit,
		Donazione.* ,
		Anagrafica.*
		FROM Satispay
		LEFT JOIN Donazione
		ON Satispay.CodTrans= Donazione.CodTrans
		LEFT JOIN Anagrafica
		ON Donazione.Id_a = Anagrafica.Id_a
		WHERE Satispay.CodTrans = ? " ) ) ) {
		error_log( date( '[Y-m-d H:i:s e] ' ) . "satispay.php: Prepare failed" . PHP_EOL, 3, LOG_FILE );
		$connection->close();
		header("location: " . FORM_ERROR_PAGE);
		exit;
	}
	// associo i parametri ai placeholder
	if ( !$stmt->bind_param( 's', $_GET[ 'payment_id' ] ) ) {
		error_log( date( '[Y-m-d H:i:s e] ' ) . "satispay.php: Binding failed" . PHP_EOL, 3, LOG_FILE );
		$stmt->close();
		$connection->close();
		header("location: " . FORM_ERROR_PAGE);
		exit;
	}
	if ( $stmt->execute() ) {
    	$result = $stmt->get_result();
    	$row = $result->fetch_array( MYSQLI_ASSOC );
		if ( "PENDING" == $row['status'] && "WA" == $row[ 'esito' ] ) { //Transazione Satispay OK o KO in stato WA
            if ( DEBUG == true ) {
                error_log( date( '[Y-m-d H:i:s e] ' ) . "Satispay callback_url  WA" . PHP_EOL, 3, LOG_FILE ); //DEBUG
            }
            // L'ambiente Satispay dipende esplicitamente da USE_SANDBOX, non dal flag di debug
            if ( defined( 'USE_SANDBOX' ) && USE_SANDBOX == true ) {
                \SatispayGBusiness\Api::setSandbox( true );
            }
			$authData = json_decode( SY_AUTH );

			\SatispayGBusiness\Api::setPublicKey($authData->public_key);
			\SatispayGBusiness\Api::setPrivateKey($authData->private_key);
			\SatispayGBusiness\Api::setKeyId($authData->key_id);

			$payment = \SatispayGBusiness\Payment::get($row['id']);
			$SY_payment = json_encode( $payment );
			//if ( DEBUG == true ) {
				error_log( date( '[Y-m-d H:i:s e] ' ) . "Satispay callback_url GET PAYMENT: " . $SY_payment . PHP_EOL, 3, LOG_FILE ); //DEBUG
			//}
			if ("ACCEPTED" == $payment ->status || "CANCELED" == $payment ->status ){ // Pagamneto Satispay o fallito
			$convert_row = json_encode( $row );
			$dati = new stdClass();
			$dati->E = json_decode( $convert_row );
			$convert_payment = json_encode($payment);
			$dati->SY = json_decode( $convert_payment );
				if ("ACCEPTED" == $payment ->status ){
					$dati->SY->SY_esito = "OK";
    				$secret = generate_signature( $row[ 'Id_a' ] );
                    $redirect_url = FORM_THANK_YOU_PAGE . "?p=" . $row[ 'Id_a' ];
    				$mail_url = $url_di_base . "/function/mail.php?d=" . $row[ 'Id_a' ] . "&s=" . $secret ."&c=cb";
				}else {
					$dati->SY->Order = $row[ 'id' ];
					$dati->SY->SY_esito = "KO";
					//$redirect_url = FORM_ERROR_PAGE;
				}

				// c=tnx = redirect browser utente: esci subito senza chiamare DON_WS.
				// DON_WS viene già chiamato dal server callback (c=cb): evita doppia chiamata Magnews.
				if (isset($_GET['c']) && $_GET['c'] == "tnx") {
					// Aggiorna esito in DB prima del redirect: grazie.php controlla DB e troverebbe ancora WA
					// se aspettasse il c=cb (race condition). Il c=cb aggiornerà di nuovo dopo (idempotente).
					if ( "ACCEPTED" == $payment->status ) {
						$stmt_upd = $connection->prepare( "UPDATE Donazione SET esito='OK' WHERE CodTrans=? AND esito='WA'" );
						if ( $stmt_upd ) {
							$stmt_upd->bind_param( 's', $row['CodTrans'] );
							$stmt_upd->execute();
							$stmt_upd->close();
						}
					}
					$stmt->close();
					$connection->close();
                    if ( DEBUG == true ) {
					   error_log( date( '[Y-m-d H:i:s e] ' ) . "satispay.php Tnx (skip DON_WS): " . ($redirect_url ?? '') . PHP_EOL, 3, LOG_FILE ); //DEBUG
				    }
					header( "Location: " . ($redirect_url ?? FORM_ERROR_PAGE) );
					exit;
				}

				// c=cb o nessun c: processa la donazione via DON_WS
				$SY_data = json_encode( $dati );
				$azione = array( "operation" => "save",
					"param" => "satispay",
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
				$stmt->close();
				$connection->close();

                if(isset($_GET['c']) && $_GET['c']=="cb"){
				    if (isset($mail_url)) { file_get_contents( $mail_url ); }
                    if ( DEBUG == true ) {
					   error_log( date( '[Y-m-d H:i:s e] ' ) . "satispay.php Call Back" . ($mail_url ?? '') . PHP_EOL, 3, LOG_FILE ); //DEBUG
				    }
                }
                else{
                    if ( DEBUG == true ) {
					   error_log( date( '[Y-m-d H:i:s e] ' ) . "satispay.php c not set: " . ($redirect_url ?? '') . PHP_EOL, 3, LOG_FILE ); //DEBUG
				    }
                    if (isset($mail_url)) { file_get_contents( $mail_url ); }
                    if (isset($redirect_url)) { header( "Location: " . $redirect_url ); exit; }
                }

			} elseif ( "PENDING" == $payment->status ) { // Pagamneto Satispay Sopseso
                if(isset($_GET['c']) && $_GET['c']=="cb"){
                    if ( DEBUG == true ) {
					   error_log( date( '[Y-m-d H:i:s e] ' ) . "satispay.php Call Back Satus PENDING" . PHP_EOL, 3, LOG_FILE ); //DEBUG
				    }
                } else{
                    header("location: " . SY_BUYERKO);
                    exit;
                }

			}
			else{
                 if(isset($_GET['c']) && $_GET['c']=="cb"){
                    if ( DEBUG == true ) {
					   error_log( date( '[Y-m-d H:i:s e] ' ) . "satispay.php Call Back Satus OTHER" . PHP_EOL, 3, LOG_FILE ); //DEBUG
				    }
                } else{
				    header("location: " . SY_BUYERKO);
				    exit;
                }
			}
		} else {
			if ( DEBUG == true ) {
				error_log( date( '[Y-m-d H:i:s e] ' ) . "Satispay callback_url NOT WA" . PHP_EOL, 3, LOG_FILE ); //DEBUG
			}
			if ( "ACCEPTED" == $row['status']  ){
				//$redirect_url = FORM_THANK_YOU_PAGE . "?payment_id=" . $row[ 'CodTrans' ];
				$redirect_url = FORM_THANK_YOU_PAGE . "?p=" . $row[ 'Id_a' ];
			} else{
				$redirect_url = FORM_ERROR_PAGE;
			}
			$stmt->close();
			$connection->close();
			header( "Location: " . $redirect_url );
			exit;
	}
}
} else { // Senza GET
	header("location: " . $url_di_base);
	exit;
}
