<?php // GENERIC FUNCTIONS
/*
 *  Added generateReferralCode
 * 	Added handleError
 */
 
function strip_log_CC( $string ) {
    preg_match( '/"cartan":"[0-9]{13,19}"/', $string, $output_array );
    if ( preg_match( '/"cartan":"[0-9]{13,19}"/', $string ) ) {
        $replacement = substr( $output_array[ 0 ], 0, 14 ) . '...' . substr( $output_array[ 0 ], -5 );
        $string = preg_replace( '/"cartan":"[0-9]{13,19}"/', $replacement, $string );
    }
    //Numero di carta: cardNumber
    preg_match( '/"cardNumber":"[0-9]{13,19}"/', $string, $output_array );
    if ( preg_match( '/"cardNumber":"[0-9]{13,19}"/', $string ) ) {
        $replacement = substr( $output_array[ 0 ], 0, 18 ) . '...' . substr( $output_array[ 0 ], -5 );
        $string = preg_replace( '/"cardNumber":"[0-9]{13,19}"/', $replacement, $string );
    }
    //Numero di carta: number
    preg_match( '/"number":"[0-9]{13,19}"/', $string, $output_array );
    if ( preg_match( '/"number":"[0-9]{13,19}"/', $string ) ) {
        $replacement = substr( $output_array[ 0 ], 0, 18 ) . '...' . substr( $output_array[ 0 ], -5 );
        $string = preg_replace( '/"number":"[0-9]{13,19}"/', $replacement, $string );
    }
    //CVV
    preg_match( '/"cvv":"[0-9]{1,4}"/', $string, $output_array );
    if ( preg_match( '/"cvv":"[0-9]{1,4}"/', $string ) ) {
        $replacement = '"cvv":"xxx"';
        $string = preg_replace( '/"cvv":"[0-9]{1,4}"/', $replacement, $string );
    }
    return $string;
}

function utf8ize( $d ) {
    /*if ( is_array( $d ) ) {
    	foreach ( $d as $k => $v ) {
    		$d[ $k ] = utf8ize( $v );
    	}
    } else if ( is_string( $d ) ) {
    	return utf8_encode( $d );
    }*/
    return $d;
}
if ( !function_exists( 'mb_ucfirst' ) ) {
    function mb_ucfirst( $string ) {
        return mb_strtoupper( mb_substr( $string, 0, 1 ) ) . mb_strtolower( mb_substr( $string, 1 ) );
    }
}

function verifyURL( $url ) {
    /*
     * Function returns an array:
     * 'code' =  state code category 
     *  	1xx Informational
     *	2xx Success
     *	3xx Redirezione
     *	4xx Client Error
     *	5xx Server Error
     * 'staus' = t/f true solo per codici 2xx
     * 'redirect_url' = sif code is 3xx function returns a new url
     *  */
    $handle = curl_init( $url );
    curl_setopt( $handle, CURLOPT_RETURNTRANSFER, TRUE );
    curl_setopt( $handle, CURLOPT_TIMEOUT, 10 );
    $response = curl_exec( $handle );
    $curlinfo = curl_getinfo( $handle );
    $httpCode = $curlinfo[ 'http_code' ];
    $verified_URL[ 'code' ] = substr( $httpCode, 0, 1 );
    if ( "4" == $verified_URL[ 'code' ] || "5" == $verified_URL[ 'code' ] ) { // Error URL
        $verified_URL[ 'stauts' ] = false;
        $verified_URL[ 'redirect_url' ] = false;
    } elseif ( "3" == $verified_URL[ 'code' ] ) { // Redirect
        $verified_URL[ 'stauts' ] = false;
        $verified_URL[ 'redirect_url' ] = $curlinfo[ "redirect_url" ];
    } elseif ( "2" == $verified_URL[ 'code' ] ) { // URL valid
        $verified_URL[ 'stauts' ] = true;
        $verified_URL[ 'redirect_url' ] = false;
        //code 1xx nt managed
        //} elseif ("1"==substr($httpCode,0,1)){ // 
        //   $verified_URL['status'] = false;
        //   $verified_URL['redirect_url'] = false;
    } else { //Code not managed
        $verified_URL[ 'stauts' ] = false;
        $verified_URL[ 'redirect_url' ] = false;
    }
    curl_close( $handle );
    return $verified_URL;
}

function CheckIPAttempts( $ip ) {
    $send_mail    = IPAL_MAIL_ENABLE;
    $time_inteval = (int) IPAL_TIME;
    $n_attempts_alert = (int) IPAL_ATTEMPTS;
    $n_attempts_stop  = (int) IPAL_STOP;
    $enable_stop  = IPAL_STOP_ENABLE;
    $to = ALERT_MAIL;

    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( !$connection ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "CheckIPAttempts: connessione fallita" . PHP_EOL, 3, LOG_FILE );
        return 0;
    }
    $stmt = $connection->prepare(
        "SELECT IP, COUNT(*) AS Tentativi
         FROM Anagrafica
         WHERE IP = ? AND data_ins >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
         GROUP BY IP
         HAVING Tentativi > ?"
    );
    if ( !$stmt ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "CheckIPAttempts prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
        mysqli_close( $connection );
        return 0;
    }
    $stmt->bind_param( 'sii', $ip, $time_inteval, $n_attempts_alert );
    $stmt->execute();
    $result = $stmt->get_result();
    $row_attempts = $result->fetch_assoc();
    $totalRows_attempts = $result->num_rows;
    $stmt->close();
    mysqli_close( $connection );

    $n = isset( $row_attempts[ 'Tentativi' ] ) ? (int) $row_attempts[ 'Tentativi' ] : 0;

    if ( $totalRows_attempts > 0 ) {
        $text = "Attenzione l'ip " . $ip . " ha tentato una donazione per " . $n . " volte su " . $_SERVER[ 'SERVER_NAME' ];
        $mail_headers  = "From: " . ORG_NAME . " <" . ORG_NOREPLY . ">\r\n";
        $mail_headers .= "Reply-To: " . ORG_NOREPLY . "\r\n";
        $mail_headers .= "X-Mailer: PHP/" . phpversion();
        if ( $send_mail == 1 ) {
            mail( $to, '[ALERT] too many attempts from single IP', $text, $mail_headers );
        }
        if ( $n >= $n_attempts_stop && TRUE == $enable_stop ) {
            $errore++;
            $messaggio_errore .= "E052|";
            $arr_errore[ 'E052' ] = "Troppi tentativi dallo stesso IP";
        }
    }
    return $n;
}

function guid() {
    if ( function_exists( 'com_create_guid' ) ) {
        return com_create_guid();
    } else {
        mt_srand( ( double )microtime() * 10000 ); //optional for php 4.2.0 and up.
        $charid = strtoupper( md5( uniqid( rand(), true ) ) );
        $hyphen = chr( 45 ); // "-"
        $uuid = //chr(123).// "{"
            substr( $charid, 0, 8 ) . $hyphen
            . substr( $charid, 8, 4 ) . $hyphen
            . substr( $charid, 12, 4 ) . $hyphen
            . substr( $charid, 16, 4 ) . $hyphen
            . substr( $charid, 20, 12 )
            //.chr(125);// "}"
        ;
        return $uuid;
    }
}

function CleanMyJSON( $json ) {
    return ( preg_replace( '/("[a-zA-Z0-9_\-]+"\s*+:\s*(null|"\s*"|NULL),\s*)*/', '', $json ) );
}

// CODICE FISCALE 
//https://github.com/nigrosimone/CodiceFiscale
require '../lib/CodiceFiscale.php';

function checkIBAN( $iban ) {
    //CREDITS: https://stackoverflow.com/questions/20983339/validate-iban-php
    //Peter Fox and  Rene Terstegen
    $iban = strtolower( str_replace( ' ', '', $iban ) );
    $Countries = array( 'al' => 28, 'ad' => 24, 'at' => 20, 'az' => 28, 'bh' => 22, 'be' => 16, 'ba' => 20, 'br' => 29, 'bg' => 22, 'cr' => 21, 'hr' => 21, 'cy' => 28, 'cz' => 24, 'dk' => 18, 'do' => 28, 'ee' => 20, 'fo' => 18, 'fi' => 18, 'fr' => 27, 'ge' => 22, 'de' => 22, 'gi' => 23, 'gr' => 27, 'gl' => 18, 'gt' => 28, 'hu' => 28, 'is' => 26, 'ie' => 22, 'il' => 23, 'it' => 27, 'jo' => 30, 'kz' => 20, 'kw' => 30, 'lv' => 21, 'lb' => 28, 'li' => 21, 'lt' => 20, 'lu' => 20, 'mk' => 19, 'mt' => 31, 'mr' => 27, 'mu' => 30, 'mc' => 27, 'md' => 24, 'me' => 22, 'nl' => 18, 'no' => 15, 'pk' => 24, 'ps' => 29, 'pl' => 28, 'pt' => 25, 'qa' => 29, 'ro' => 24, 'sm' => 27, 'sa' => 24, 'rs' => 22, 'sk' => 24, 'si' => 19, 'es' => 24, 'se' => 24, 'ch' => 21, 'tn' => 24, 'tr' => 26, 'ae' => 23, 'gb' => 22, 'vg' => 24 );
    $Chars = array( 'a' => 10, 'b' => 11, 'c' => 12, 'd' => 13, 'e' => 14, 'f' => 15, 'g' => 16, 'h' => 17, 'i' => 18, 'j' => 19, 'k' => 20, 'l' => 21, 'm' => 22, 'n' => 23, 'o' => 24, 'p' => 25, 'q' => 26, 'r' => 27, 's' => 28, 't' => 29, 'u' => 30, 'v' => 31, 'w' => 32, 'x' => 33, 'y' => 34, 'z' => 35 );
    if ( array_key_exists( substr( $iban, 0, 2 ), $Countries ) && strlen( $iban ) == $Countries[ substr( $iban, 0, 2 ) ] ) {
        $MovedChar = substr( $iban, 4 ) . substr( $iban, 0, 4 );
        $MovedCharArray = str_split( $MovedChar );
        $NewString = "";

        foreach ( $MovedCharArray AS $key => $value ) {
            if ( !is_numeric( $MovedCharArray[ $key ] ) ) {
                $MovedCharArray[ $key ] = $Chars[ $MovedCharArray[ $key ] ];
            }
            $NewString .= $MovedCharArray[ $key ];
        }

        if ( bcmod( $NewString, '97' ) == 1 ) {
            //IBAN is Valid
            return "OK";
        } else {
            //l'Iban non è valido
            return "E032|";
        }
    } else {
        //IBAN length or country code is wrong
        return "E032|";
    }
}

function checkCC( $number ) {
    //https://stackoverflow.com/questions/174730/what-is-the-best-way-to-validate-a-credit-card-in-php
    $number = preg_replace( '/\D/', '', $number );
    $supportedCircuit = "KO";
    $Circuit = "Nessuno";
    if ( substr( $number, 0, 2 ) == 30 || substr( $number, 0, 2 ) == 36 || substr( $number, 0, 2 ) == 38 ) {
        $supportedCircuit = "KO";
        $Circuit = "Diners";
    } elseif ( substr( $number, 0, 2 ) == 34 || substr( $number, 0, 2 ) == 37 ) {
            $supportedCircuit = "OK";
            $Circuit = "American Express";
        }
        /*elseif(substr($number,0,4)==4539){
        	$supportedCircuit = "OK";
        	$Circuit = "CartaSI";
        }*/
    elseif ( substr( $number, 0, 2 ) >= 40 && substr( $number, 0, 2 ) <= 49 ) {
        $supportedCircuit = "OK";
        $Circuit = "CartaSI";
    }
    elseif ( substr( $number, 0, 2 ) >= 51 && substr( $number, 0, 2 ) <= 55 ) {
        $supportedCircuit = "OK";
        $Circuit = "MasterCard";
    }
    elseif ( substr( $number, 0, 4 ) >= 2221 && substr( $number, 0, 4 ) <= 2720 ) {
        $supportedCircuit = "OK";
        $Circuit = "MasterCard";
    }
    else {
        $supportedCircuit = "KO";
        $Circuit = "Circuito non supportato";
    }
    // Set the string length and parity
    $number_length = strlen( $number );
    $parity = $number_length % 2;
    // Loop through each digit and do the maths
    $total = 0;
    for ( $i = 0; $i < $number_length; $i++ ) {
        $digit = $number[ $i ];
        // Multiply alternate digits by two
        if ( $i % 2 == $parity ) {
            $digit *= 2;
            // If the sum is two digits, add them together (in effect)
            if ( $digit > 9 ) {
                $digit -= 9;
            }
        }
        // Total up the digits
        $total += $digit;
    }
    if ( $total % 10 === 0 ) {
        return array( "", $Circuit, $supportedCircuit );
    } else {
        return array( "E027|", $Circuit, $supportedCircuit );
    }
}

function RemoveDecimal( $numero ) {
    //preg_match('/[,|\.]/', $input_line, $output_array);
    if ( preg_match( '/[,|\.]/', $numero ) ) {
        $numero = substr( $numero, 0, -3 );
    } else $numero = $numero;
    return $numero;
}

function generateReferralCode( $surname, $transactionCode ) {
    // Pulisci il cognome: converti in maiuscolo prima di filtrare i caratteri
    $cleanSurname = preg_replace( '/[^A-Z]/', '', strtoupper( $surname ) );

    // Prendi i primi 5 caratteri del cognome, padding con zeri se troppo corto
    $surnamePart = substr( $cleanSurname, 0, 5 );
    $surnamePart = str_pad( $surnamePart, 5, '0' );

    // Trova la parte numerica del codice di transazione, escludendo le ultime due lettere e il trattino
    // Supponiamo che il formato sia sempre come: 1234567890-AB
    // Quindi dobbiamo prendere i 5 caratteri prima del trattino
    $transactionPart = substr( $transactionCode, -8, 5 );

    // Combina le due parti
    return $surnamePart . $transactionPart;
}

function handleError($message, $code = 500, $extra = null) {
    error_log(date( '[Y-m-d H:i:s e] ' ) . "Errore [$code]: $message" . PHP_EOL, 3, LOG_FILE );
    header('Content-Type: application/json; charset=utf-8');
    $response = [
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message
        ]
    ];
    if (!empty($extra)) {
        $response['error']['details'] = $extra;
    }
    echo json_encode($response);
    exit;
}

