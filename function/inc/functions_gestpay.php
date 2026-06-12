<?php // GestPay
function CreateOrderGestPay( $dati ) {
    $dati_GP = new stdClass();
    $dati_GP->shopLogin = GP_COD_ESE;
    $dati_GP->amount = $dati->importo;
    $dati_GP->currency = "EUR";
    $dati_GP->shopTransactionID = $dati->CodTrans;
    $dati_GP->clientIP = $dati->IP;
    $dati_GP->itemType = "digital";
    $dati_GP->recurrent = "false";
    $dati_GP->responseURLs = new stdClass();
    $dati_GP->responseURLs->buyerOK = GP_BUYEROK;
    $dati_GP->responseURLs->buyerKO = GP_BUYERKO;
    $dati_GP->responseURLs->serverNotificationURL = GP_NOTIFURL;
    if ( "regular" == $dati->GPtransactionType ) {
        $dati_GP->transDetails = new stdClass();
        $dati_GP->transDetails->type = "08"; //Mail order
        //$dati_GP->transDetails->type  ="01F"; //Recurring first        
        //$dati_GP->transDetails->type  ="03F"; //Unscheduled first 
    }
    /*else{
           $dati_GP->transDetails  = new stdClass();
           $dati_GP->transDetails->type  ="EC"; //Ecommerce
       }*/
    $data_string = json_encode( $dati_GP );

    //POST payment/create
    $curl = curl_init();
    curl_setopt_array( $curl, array(
        CURLOPT_URL => GP_URLAPI . "/v1/payment/create",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data_string,
        CURLOPT_HTTPHEADER => array(
            'authorization: apikey ' . GP_APIKEY,
            'content-type: application/json',
            'Content-length:' . strlen( $data_string )
        ),
    ) );
    $response = curl_exec( $curl );
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "GP CreateOrderGestPay stringa: " . $data_string . PHP_EOL, 3, LOG_FILE ); //DEBUG
        error_log( date( '[Y-m-d H:i:s e] ' ) . "GP CreateOrderGestPay Risposta: " . $response . PHP_EOL, 3, LOG_FILE ); //DEBUG	
    }
    $err = curl_error( $curl );
    curl_close( $curl );
    if ( $err ) {
        echo "cURL Error #:" . $err;
    } else return ( json_decode( $response, FALSE ) );
}

function SubmitOrderGestPay( $dati ) {
    $GP_submit = new stdClass();

    $GP_submit->paymentTypeDetails = new stdClass();
    $GP_submit->paymentTypeDetails->creditcard = new stdClass();
    if ( "oneoff" == $dati->GPtransactionType ) {
        $GP_submit->paymentTypeDetails->creditcard->number = $dati->cartan;
        $GP_submit->paymentTypeDetails->creditcard->expMonth = $dati->exp_mm;
        $GP_submit->paymentTypeDetails->creditcard->expYear = $dati->exp_yy;
        $GP_submit->paymentTypeDetails->creditcard->CVV = $dati->cvv;
        $GP_submit->paymentTypeDetails->creditcard->DCC = null;
        $GP_submit->buyer = new stdClass();
        $GP_submit->buyer->email = $dati->mail;
        $GP_submit->buyer->name = $dati->titolare;
    } else {
        $GP_submit->paymentTypeDetails->creditcard->number = "";
        $GP_submit->paymentTypeDetails->creditcard->token = $dati->token;
        $GP_submit->paymentTypeDetails->creditcard->requestToken = "";
        $GP_submit->paymentTypeDetails->creditcard->expMonth = ""; //$dati->tokenExpiryMonth;
        $GP_submit->paymentTypeDetails->creditcard->expYear = ""; //$dati->tokenExpiryYear;
        $GP_submit->paymentTypeDetails->creditcard->CVV = ""; //$dati->cvvcode;  
    }
    $GP_submit->responseURLs = new stdClass();
    $GP_submit->responseURLs->buyerOK = GP_BUYEROK;
    $GP_submit->responseURLs->buyerKO = GP_BUYERKO;
    $GP_submit->responseURLs->serverNotificationURL = GP_NOTIFURL;
    $GP_submit->shopLogin = GP_COD_ESE;
    $data_string = json_encode( $GP_submit );
    $curl = curl_init();

    curl_setopt_array( $curl, array(
        CURLOPT_URL => GP_URLAPI . "/v1/payment/submit",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data_string,
        CURLOPT_HTTPHEADER => array(
            'paymentToken: ' . $dati->paymentToken,
            'content-type: application/json',
            'Content-length:' . strlen( $data_string )
        ),
    ) );

    $response = curl_exec( $curl );
    $err = curl_error( $curl );
    curl_close( $curl );
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "DON_WS GP SubmitOrderGestPay Stringa: " . strip_log_CC( $data_string ) . PHP_EOL, 3, LOG_FILE ); //DEBUG
        //error_log( date( '[Y-m-d H:i:s e] ' ) . "GP SubmitOrderGestPay Risposta: " . json_decode( $response ) . PHP_EOL, 3, LOG_FILE ); //DEBUG
    }
    if ( $err ) {
        echo "cURL Error #:" . $err;
    } else {
        return ( json_decode( $response, FALSE ) );
    }
}

function GeneraTokenGestPay( $dati ) {
    $GP_submit = new stdClass();
    $GP_submit->shopLogin = GP_COD_ESE;
    $GP_submit->requestToken = "MASKEDPAN";
    $GP_submit->creditCard = new stdClass();
    $GP_submit->creditCard->number = $dati->cartan;
    $GP_submit->creditCard->expMonth = $dati->exp_mm;
    $GP_submit->creditCard->expYear = $dati->exp_yy;
    $GP_submit->creditCard->CVV = $dati->cvv;
    $data_string = json_encode( $GP_submit );
    $curl = curl_init();

    curl_setopt_array( $curl, array(
        CURLOPT_URL => GP_URLAPI . "/v1/shop/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data_string,
        CURLOPT_HTTPHEADER => array(
            'authorization: apikey ' . GP_APIKEY,
            'paymentToken: ' . $dati->paymentToken,
            'content-type: application/json',
            'Content-length:' . strlen( $data_string )
        ),
    ) );

    $response = curl_exec( $curl );
    $err = curl_error( $curl );
    curl_close( $curl );
    if ( $err ) {
        echo "cURL Error #:" . $err;
    } else return ( json_decode( $response, FALSE ) );

}

function GetOrderGestPay( $dati ) {

    error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamta GetOrderGestPay :" . $dati->paymentID . PHP_EOL, 3, LOG_FILE ); //DEBUG

    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, GP_URLAPI . '/v1/payment/detail/' . $dati->paymentID );
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
        return ( json_decode( $response, FALSE ) );
    }
}

function ScriviOrderGestPay_mysql( $donazione ) {
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviOrderGestPay_mysql Dati: " . strip_log_CC(json_encode( $donazione)) . PHP_EOL, 3, LOG_FILE ); //DEBUG
    }
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( $connection->connect_errno ) {
        trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
    }
    // preparo lo statement
    // shopTransactionID = CodTrans
    /*if ( !( $stmt = $connection->prepare( "INSERT INTO GestPayREST (shopTransactionID, transactionResult , transactionErrorCode, transactionErrorDescription, bankTransactionID , authorizationCode , paymentID, currency , country , company, tdLevel , buyername , buyermail, riskResponseCode , riskResponseDescription, alertCode, alertDescription, cvvPresent, maskedPAN, paymentMethod, productType , token, tokenExpiryMonth, tokenExpiryYear, paymentToken) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)" ) ) ) {*/
    if ( !( $stmt = $connection->prepare( "INSERT INTO GestPayREST (shopTransactionID, transactionResult , transactionErrorCode, transactionErrorDescription, bankTransactionID , authorizationCode , paymentID, currency , country , company, tdLevel ,  alertCode, alertDescription, cvvPresent, maskedPAN, paymentMethod, productType , token, tokenExpiryMonth, tokenExpiryYear, paymentToken) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)" ) ) ) {
        trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
    }
    // associo i parametri ai placeholder
    $cod_ese_sella = GP_COD_ESE;
    if ( !$stmt->bind_param( 'sssssssssssssssssssss', $donazione->CodTrans, $donazione->transactionResult, $donazione->transactionErrorCode, $donazione->transactionErrorDescription, $donazione->bankTransactionID, $donazione->authorizationCode, $donazione->paymentID, $donazione->currency, $donazione->country, $donazione->company, $donazione->tdLevel, /* $donazione->buyer->name, $donazione->buyer->email, $donazione->risk->riskResponseCode, $donazione->risk->riskResponseDescription,*/ $donazione->alertCode, $donazione->alertDescription, $donazione->cvvPresent, $donazione->maskedPAN, $donazione->paymentMethod, $donazione->productType, $donazione->token, $donazione->tokenExpiryMonth, $donazione->tokenExpiryYear, $donazione->paymentToken ) ) {
        trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    // eseguo la query e chiudo
    if ( !$stmt->execute() ) {
        trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    $codice_ordine = $stmt->insert_id;
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "DON_WS scrivo in db GestPayREST ordine: " . $codice_ordine . PHP_EOL, 3, LOG_FILE ); //DEBUG
    }
    $stmt->close();
    $connection->close();
    return ( $codice_ordine );
}

function AggiornaDonazioneNo3DGP( $transactionResult, $CodTrans ) { // Da aggregare a aggiornaEsitoDonazione
    //Unificare con aggiornaEsitoDonazione
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( $connection->connect_errno ) {
        trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
    }
    // preparo lo statement
    if ( !( $stmt = $connection->prepare( "UPDATE Donazione SET esito=? WHERE CodTrans=?;" ) ) ) {
        trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
    }
    // associo i parametri ai placeholder
    if ( !$stmt->bind_param( 'ss', $transactionResult, $CodTrans ) ) {
        trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    // eseguo la query e chiudo
    if ( !$stmt->execute() ) {
        trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    $stmt->close();
    $connection->close();
}

function AggiornaGetPayREST( $donazione ) {
    // connetto al db
    if (!isset($donazione->buyer) || !is_object($donazione->buyer)) {
        $donazione->buyer = (object)[
            'name' => '',
            'mail' => ''
        ];
    }

    // 2. Stesso per risk (se serve)
    if (!isset($donazione->risk) || !is_object($donazione->risk)) {
        $donazione->risk = (object)[
            'riskResponseCode' => '',
            'riskResponseDescription' => ''
        ];
    }

    // 3. Connetto al DB
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( $connection->connect_errno ) {
        trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
    }
    // preparo lo statement
    if ( !( $stmt = $connection->prepare( "UPDATE GestPayREST SET transactionResult = ?, transactionErrorCode = ?, transactionErrorDescription = ?, bankTransactionID = ?, authorizationCode = ?, currency = ?, country = ?, tdLevel = ?, company = ?, buyername = ?, buyermail = ?, riskResponseCode = ?, riskResponseDescription = ?, alertCode = ?, alertDescription = ?, cvvPresent = ?, maskedPAN = ?, PaymentMethod = ?, productType = ?, token = ?, tokenExpiryMonth = ?, tokenExpiryYear = ?  WHERE shopTransactionID=?;" ) ) ) {
        trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
    }
    if ( isset( $donazione->shopTransactionID ) && "" != $donazione->shopTransactionID ) {
        // associo i parametri ai placeholder
        if ( !$stmt->bind_param( 'sssssssssssssssssssssss', $donazione->transactionResult, $donazione->transactionErrorCode, $donazione->transactionErrorDescription, $donazione->bankTransactionID, $donazione->authorizationCode, $donazione->currency, $donazione->country, $donazione->tdLevel, $donazione->company, $donazione->buyer->name, $donazione->buyer->mail, $donazione->risk->riskResponseCode, $donazione->risk->riskResponseDescription, $donazione->alertCode, $donazione->alertDescription, $donazione->cvvPresent, $donazione->maskedPAN, $donazione->paymentMethod, $donazione->productType, $donazione->token, $donazione->tokenExpiryMonth, $donazione->tokenExpiryYear, $donazione->shopTransactionID ) ) {
            trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
        }
    } else {
        // associo i parametri ai placeholder
        if ( !$stmt->bind_param( 'sssssssssssssssssssssss', $donazione->transactionResult, $donazione->transactionErrorCode, $donazione->transactionErrorDescription, $donazione->bankTransactionID, $donazione->authorizationCode, $donazione->currency, $donazione->country, $donazione->tdLevel, $donazione->company, $donazione->buyer->name, $donazione->buyer->mail, $donazione->risk->riskResponseCode, $donazione->risk->riskResponseDescription, $donazione->alertCode, $donazione->alertDescription, $donazione->cvvPresent, $donazione->maskedPAN, $donazione->paymentMethod, $donazione->productType, $donazione->token, $donazione->tokenExpiryMonth, $donazione->tokenExpiryYear, $donazione->CodTrans ) ) {
            trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
        }
    }
    // eseguo la query e chiudo
    if ( !$stmt->execute() ) {
        trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    $stmt->close();
    $connection->close();

}

function aggiornaMandatoToken_mysql( $mandato ) {
    // connetto al db
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( $connection->connect_errno ) {
        trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
    }
    // preparo lo statement
    if ( !( $stmt = $connection->prepare( "UPDATE Mandato SET Token=?, meseToken =?, annoToken=?, Errore =? WHERE Id_mandato=?;" ) ) ) {
        trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
    }
    // associo i parametri ai placeholder
    if ( !$stmt->bind_param( 'ssssi', $mandato->GP_token, $mandato->GP_tokenExpiryMonth, $mandato->GP_tokenExpiryYear, $mandato->errore_mandato_Mentor, $mandato->Id_mandato ) ) {
        trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    // eseguo la query e chiudo
    if ( !$stmt->execute() ) {
        trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    $stmt->close();
    $connection->close();
}