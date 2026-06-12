<?php
// 202506 - Security hardening: prepared statements, safe error handling, HMAC signatures
require '../inc/config.inc.php';
require '../inc/data.inc.php';
require '../inc/security.php';

if ( DEBUG == true ) {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "gestPay.php : " . $_SERVER['QUERY_STRING'] . PHP_EOL, 3, LOG_FILE );
}
$errore = "";
if ( !isset( $_GET[ 'a' ] ) || GP_COD_ESE != $_GET[ 'a' ] ) {
	$errore .= "Non coincide il codice eserente<br>";
}
if ( !isset( $_GET[ 'paymentID' ] ) ) {
	$errore .= "Non è definito il codice del pagamento<br>";
}
if ( trim( $errore ) == "" ) {
	$azione_data[ 'TransactionResult' ] = $_GET[ 'Status' ];

	$connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
	if ( !$connection ) {
		error_log( date( '[Y-m-d H:i:s e] ' ) . "gestpay.php: connessione DB fallita" . PHP_EOL, 3, LOG_FILE );
		http_response_code(500);
		exit;
	}

	// Prepared statement per paymentID
	$stmt = $connection->prepare( "SELECT GestPayREST.shopTransactionID, GestPayREST.paymentID, GestPayREST.transactionErrorCode, GestPayREST.transactionErrorDescription, Donazione.CodTrans, Donazione.Id_a, Donazione.importo, Donazione.centro, Donazione.pay_method, Donazione.nota, Donazione.tessera, Donazione.tipotessera, Donazione.esito, Donazione.`data`, Donazione.tipo, Donazione.codicePartner, Anagrafica.* FROM GestPayREST LEFT JOIN Donazione ON GestPayREST.shopTransactionID = Donazione.CodTrans LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a WHERE GestPayREST.paymentID = ?" );
	if ( !$stmt ) {
		error_log( date( '[Y-m-d H:i:s e] ' ) . "gestpay.php prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
		$connection->close();
		http_response_code(500);
		exit;
	}
	$stmt->bind_param( 's', $_GET[ 'paymentID' ] );
	$stmt->execute();
	$result = $stmt->get_result();
	$row_donazione = $result->fetch_assoc();
	$totalRows_donazione = $result->num_rows;
	$stmt->close();

	error_log( date( '[Y-m-d H:i:s e] ' ) . "Query gestpay.php: (" . $totalRows_donazione . ") paymentID=" . $_GET[ 'paymentID' ] . PHP_EOL, 3, LOG_FILE );

	if ( $totalRows_donazione > 0 && $row_donazione ) {
		foreach ( $row_donazione as $key => $value ) {
			if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
				$azione_data[ $key ] = $value;
			}
		}
	} else {
		error_log( date( '[Y-m-d H:i:s e] ' ) . "gestpay.php: nessun risultato per paymentID=" . $_GET[ 'paymentID' ] . PHP_EOL, 3, LOG_FILE );
	}

	if ( $row_donazione && isset( $row_donazione[ 'tipo' ] ) && $row_donazione[ 'tipo' ] == "regular" ) {
		$stmt2 = $connection->prepare( "SELECT Id_mandato, frequenza, Token, meseToken, annoToken, nomeTitolare FROM Mandato WHERE Id_a = ?" );
		if ( $stmt2 ) {
			$id_a = intval( $row_donazione[ 'Id_a' ] );
			$stmt2->bind_param( 'i', $id_a );
			$stmt2->execute();
			$result2 = $stmt2->get_result();
			$row_mandato = $result2->fetch_assoc();
			$stmt2->close();
			if ( $row_mandato ) {
				foreach ( $row_mandato as $key => $value ) {
					if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
						$azione_data[ $key ] = $value;
					}
				}
			}
		}
	}
	$connection->close();

	if ( DEBUG == true ) {
		error_log( date( '[Y-m-d H:i:s e] ' ) . "ESITO 3D : " . ($row_donazione['CodTrans'] ?? 'N/A') . " - Status: " . $_GET['Status'] . PHP_EOL, 3, LOG_FILE );
	}

	$azione = array( "operation" => "save",
		"param" => "GestPay3D",
		"data" => $azione_data
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

	error_log( date( '[Y-m-d H:i:s e] ' ) . "gestpay.php chiamata save GestPay3D : " . $azione_string . PHP_EOL, 3, LOG_FILE );
} else { // errore risposta 3D
	error_log( date( '[Y-m-d H:i:s e] ' ) . "gestpay.php Errore 3D Secure: " . strip_tags($errore) . PHP_EOL, 3, LOG_FILE );
	echo "Errore 3D Secure: " . htmlspecialchars($errore, ENT_QUOTES, 'UTF-8');
}

if ( isset($azione_data) && "OK" == ($azione_data[ 'TransactionResult' ] ?? '') ) {
	$secret = generate_signature( $row_donazione[ 'Id_a' ] );
	$redirect_url = $url_di_base . "/function/mail.php?d=" . $row_donazione[ 'Id_a' ] . "&s=" . $secret;
}
if ( isset( $redirect_url ) ) {
	header( "Location: " . $redirect_url );
	exit;
}
