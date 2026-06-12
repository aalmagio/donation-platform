<?php //Satisapy
function SatispayGetPayment( $dati ) {
        if ( DEBUG == true ) {
            \SatispayGBusiness\Api::setSandbox( true );
    	}
    	$authData = json_decode( SY_AUTH );
    	
    	\SatispayGBusiness\Api::setPublicKey( $authData->public_key );
        \SatispayGBusiness\Api::setPrivateKey( $authData->private_key );
        \SatispayGBusiness\Api::setKeyId( $authData->key_id );
    	
    	$payment = \SatispayGBusiness\Payment::create( [
    		"flow" => "MATCH_CODE",
    		"amount_unit" => $dati->importo . "00", //Importo in centesimi 199 = 1.99
    		"currency" => "EUR",
            "callback_url" => URL_DI_BASE . "/function/satispay.php?payment_id=" . $dati->CodTrans ."&c=cb", //CodTrans
            "external_code" => $dati->CodTrans, //CodTrans
    		"metadata" => [
    			"order_id" => $dati->CodTrans
    		],
            "redirect_url" => URL_DI_BASE . "/function/satispay.php?payment_id=" . $dati->CodTrans."&c=tnx"
    	] );
    	return($payment); 
    }
function ScriviOrderSatisPay_mysql( $donazione ) {
    // connetto al db
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( $connection->connect_errno ) {
        trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
    }
    // preparo lo statement  
    if ( !( $stmt = $connection->prepare( "INSERT INTO Satispay (CodTrans, id, code_identifier, type, amount_unit, currency, status, expired, insert_date, expire_date, flow ) VALUES (?,?,?,?,?,?,?,?,?,?,?);" ) ) ) {
        trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
    }
    // associo i parametri ai placeholder
    if ( !$stmt->bind_param( 'sssssssssss', $donazione->CodTrans, $donazione->id, $donazione->code_identifier, $donazione->type, $donazione->amount_unit, $donazione->currency, $donazione->status, $donazione->expired, $donazione->insert_date, $donazione->expire_date, $donazione->flow ) ) {
        trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }

    // eseguo la query e chiudo
    if ( !$stmt->execute() ) {
        trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    $stmt->close();
    $connection->close();
}

function aggiornaOrdineSatyspay( $donazione ) {
    // connetto al db
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( $connection->connect_errno ) {
        trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error . PHP_EOL, 3, LOG_FILE );
        }
    }
    // preparo lo statement
    if ( !( $stmt = $connection->prepare( "UPDATE Satispay SET amount_unit=?, status=?, sender_id=?, sender_name=? WHERE id=?;" ) ) ) {
        trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Prepare failed: (" . $connection->connect_errno . ") " . $connection->connect_error . PHP_EOL, 3, LOG_FILE );
        }
    }
    // associo i parametri ai placeholder
    if ( !$stmt->bind_param( 'sssss', $donazione->amount_unit, $donazione->SY_status, $donazione->sender_id, $donazione->sender_name, $donazione->SY_id ) ) {
        trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $errno->error, E_USER_ERROR );
        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Binding parameters failed: (" . $stmt->errno . ") " . $errno->error . PHP_EOL, 3, LOG_FILE );
        }

    }

    // eseguo la query e chiudo
    if ( !$stmt->execute() ) {
        trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Binding parameters failed: (" . $stmt->errno . ") " . $errno->error . PHP_EOL, 3, LOG_FILE );
        }
    }
    $stmt->close();
    $connection->close();
}
