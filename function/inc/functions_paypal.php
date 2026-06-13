<?php //PayPal
function TokenPayPal( $Oauth ) {
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
        CURLOPT_USERPWD => $Oauth,
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'accept-language: en_US'
        ),

    ) );

    $response = curl_exec( $curl );
    $err = curl_error( $curl );
    curl_close( $curl );

    if ( $err ) {
        return $err;
        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Errore chiamata Token PayPal :" . $err . PHP_EOL, 3, LOG_FILE ); //DEBUG
        }
    } else {
        $response_token = json_decode( $response, true );
        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito chiamata Token PayPal :" . $response . PHP_EOL, 3, LOG_FILE ); //DEBUG
        }
        return $response_token;
        //scope,  access_token, token_type , app_id, expires_in, nonce  
    }

}

function CreateOrderPayPal( $donazione ) {
    $curl = curl_init();

    curl_setopt_array( $curl, array(
        //CURLOPT_URL => "https://api.sandbox.paypal.com//v2/checkout/orders",
        CURLOPT_URL => PP_URLAPI . "/v2/checkout/orders",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_HEADER => false,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",

        CURLOPT_POSTFIELDS => '{
    
    "intent": "CAPTURE",
    "payer":{
        "name":{
             "given_name":"' . $donazione->nome . '",
             "surname":"' . $donazione->cognome . '"
        },
        "email_address":"' . $donazione->mail . '",
        "address": {
            "address_line_1": "' . $donazione->indirizzo . ' ' . $donazione->civico . '",
            "postal_code":"' . $donazione->cap . '",
            "admin_area_2": "' . $donazione->citta . '",
            "country_code": "IT"
        }
        
    },
    "purchase_units": [
        {
         "reference_id": "Donazione",    
          "description" : "Donazione libera", 
          "custom_id" :"' . $donazione->CodTrans . '",
          "invoice_id" :"' . $donazione->CodTrans . '",
          "amount": {
            "currency_code": "EUR",
            "value": "' . $donazione->importo . '"
          }
        }
      ],
      "application_context": {
        "brand_name": "' . ORG_NAME . ' - Dona Ora",
        "shipping_preference":"NO_SHIPPING",
        "user_action":"PAY_NOW",
        "return_url": "' . PP_WS . '?esito=OK",
        "cancel_url": "' . PP_WS . '?esito=KO"
      }
    }',
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'accept-language: en_US',
            'authorization: ' . $donazione->token_type . ' ' . $donazione->access_token,
            'content-type: application/json'
        ),
    ) );

    $response = curl_exec( $curl );
    $err = curl_error( $curl );

    curl_close( $curl );

    if ( $err ) {
        return $err;
        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Errore chiamata Create Order PayPal :" . $err . PHP_EOL, 3, LOG_FILE ); //DEBUG
        }
    } else {
        $response_order = json_decode( $response, true );
        //$redirect_URL = "https://www.sandbox.paypal.com/checkoutnow?token=" . $response_order['id'];
        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito chiamata Create Order PayPal :" . $response . PHP_EOL, 3, LOG_FILE ); //DEBUG
        }
        return $response_order[ 'id' ];
    }
    //Create Order - FINE

}

function ScriviOrderPayPal_mysql( $donazione ) {
    // connetto al db
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( !$connection || $connection->connect_errno ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviOrderPayPal_mysql: connessione DB fallita" . PHP_EOL, 3, LOG_FILE );
        return false;
    }
    // preparo lo statement
    if ( !( $stmt = $connection->prepare( "INSERT INTO PayPalCheckout (CodTrans, Id_OrderPayPal, token_type, access_token) VALUES(?,?,?,?);" ) ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviOrderPayPal_mysql prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
        $connection->close();
        return false;
    }
    $stmt->bind_param( 'ssss', $donazione->CodTrans, $donazione->Id_OrderPayPal, $donazione->token_type, $donazione->access_token );
    if ( !$stmt->execute() ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviOrderPayPal_mysql execute failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
    }
    $stmt->close();
    $connection->close();
    return true;
}

function aggiornaOrdinePP( $donazione ) {
    // connetto al db
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( !$connection || $connection->connect_errno ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "aggiornaOrdinePP: connessione DB fallita" . PHP_EOL, 3, LOG_FILE );
        return false;
    }
    // preparo lo statement
    if ( !( $stmt = $connection->prepare( "UPDATE PayPalCheckout SET Payment=?, gross_amount_currency_code=?, gross_amount_value=?, paypal_fee_currency_code=?, paypal_fee_value=?, net_amount_currency_code=?, net_amount_value=?, create_time=?, update_time=?, PP_given_name=?, PP_surname=?, PP_email_address=?, payer_id=?, Status=?  WHERE CodTrans=?;" ) ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "aggiornaOrdinePP prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
        $connection->close();
        return false;
    }
    // associo i parametri ai placeholder
    $stmt->bind_param( 'sssssssssssssss', $donazione->Payment, $donazione->gross_amount_currency_code, $donazione->gross_amount_value, $donazione->paypal_fee_currency_code, $donazione->paypal_fee_value, $donazione->net_amount_currency_code, $donazione->net_amount_value, $donazione->create_time, $donazione->update_time, $donazione->PP_given_name, $donazione->PP_surname, $donazione->PP_email_address, $donazione->payer_id, $donazione->Status, $donazione->CodTrans );
    if ( !$stmt->execute() ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "aggiornaOrdinePP execute failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
    }
    $stmt->close();
    $connection->close();
    return true;
}