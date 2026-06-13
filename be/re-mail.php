<?php
//    declare(strict_types=1);
require '../inc/config.inc.php';
require '../inc/data.inc.php';
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
function LeggiDati_mysql( $richiesta ) {
    $answer_donazione = ( object )array();
    if ( isset( $richiesta->CodTrans ) && trim( $richiesta->CodTrans ) != "" && preg_match( "/^[A-Z]{1}-[0-9]{17}-[A-Z]{2}/", $richiesta->CodTrans ) ) { // Query su codice transazione
        $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error( $connection ), E_USER_ERROR );
        //mysql_select_db(DB_DBNAME, $connection);
        $query_donazione = sprintf( "SELECT Anagrafica.*, Donazione.CodTrans, Donazione.importo, Donazione.pay_method, Donazione.tessera, Donazione.centro, Donazione.tipotessera, Donazione.esito, Donazione.`data`, Donazione.tipo, Donazione.codicePartner, Donazione.ringraziata FROM Donazione LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a WHERE Donazione.CodTrans = '%s'", $richiesta->CodTrans );
        $donazione = mysqli_query( $connection, $query_donazione )or die( mysqli_error( $connection ) );
        $row_donazione = mysqli_fetch_assoc( $donazione );
        $totalRows_donazione = mysqli_num_rows( $donazione );

        foreach ( $row_donazione as $key => $value ) {
            if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                $answer_donazione->$key = $value;
            }
        }
        if ( $row_donazione[ 'tipo' ] == "regular" ) {
            //mysql_select_db(DB_DBNAME, $connection);
            $query_mandato = sprintf( "SELECT frequenza, importo as importomandato FROM Mandato WHERE Id_a =%s", $row_donazione[ 'Id_a' ] );
            $mandato = mysqli_query( $connection, $query_mandato )or die( mysqli_error( $connection ) );
            $row_mandato = mysqli_fetch_assoc( $mandato );
            $totalRows_mandato = mysqli_num_rows( $mandato );
            foreach ( $row_mandato as $key => $value ) {
                if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                    $answer_donazione->$key = $value;
                }
            }
        }
        $connection->close();
        return ( $answer_donazione );
    } 
    elseif ( isset( $richiesta->Id_a ) && trim( $richiesta->Id_a ) != "" && is_numeric( $richiesta->Id_a ) ) { // Query su codice anagrafica
        $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error( $connection ), E_USER_ERROR );
        //mysql_select_db(DB_DBNAME, $connection);
        $query_anagrafica = sprintf( "SELECT * FROM Anagrafica WHERE Id_a = %s", $richiesta->Id_a );
        $anagrafica = mysqli_query( $connection, $query_anagrafica )or die( mysqli_error( $connection ) );
        $row_anagrafica = mysqli_fetch_assoc( $anagrafica );
        $totalRows_anagrafica = mysqli_num_rows( $anagrafica );
        foreach ( $row_anagrafica as $key => $value ) {
            if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                $answer_donazione->$key = $value;
            }
        }
        $query_donazione = sprintf( "SELECT Donazione.CodTrans, Donazione.importo, Donazione.pay_method, Donazione.tessera, Donazione.centro, Donazione.tipotessera, Donazione.esito, Donazione.`data`, Donazione.tipo, Donazione.codicePartner, Donazione.ringraziata FROM Donazione WHERE Donazione.Id_a = '%s'", $row_anagrafica[ 'Id_a' ] );
        $donazione = mysqli_query( $connection, $query_donazione )or die( mysqli_error( $connection ) );
        $row_donazione = mysqli_fetch_assoc( $donazione );
        $totalRows_donazione = mysqli_num_rows( $donazione );
        if ( $totalRows_donazione == 1 ) {
            foreach ( $row_donazione as $key => $value ) {
                if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                    $answer_donazione->$key = $value;
                }
            }
        }
        if ( $row_anagrafica[ 'operazione' ] == "regular" ) {
            $query_mandato = sprintf( "SELECT frequenza, importo FROM Mandato WHERE Id_a = %s", $row_anagrafica[ 'Id_a' ] );
            $mandato = mysqli_query( $connection, $query_mandato )or die( mysqli_error( $connection ) );
            $row_mandato = mysqli_fetch_assoc( $mandato );
            $totalRows_mandato = mysqli_num_rows( $mandato );
            foreach ( $row_mandato as $key => $value ) {
                if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                    $answer_donazione->$key = $value;
                }
            }
        }
        $connection->close();
        return ( $answer_donazione );
    }
    elseif ( isset( $richiesta->paymentID ) && trim( $richiesta->paymentID ) != "" && is_numeric( $richiesta->paymentID ) ) { // Query su ID pagamento GestPay

        $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error( $connection ), E_USER_ERROR );
        //mysql_select_db(DB_DBNAME, $connection);
        $query_donazione = sprintf( "SELECT GestPayREST.shopTransactionID, 
		Donazione.Id_a, Donazione.CodTrans, Donazione.importo, Donazione.pay_method, Donazione.tessera, Donazione.centro, Donazione.tipotessera, Donazione.esito, Donazione.`data`, Donazione.tipo, Donazione.codicePartner, Donazione.ringraziata
		FROM `GestPayREST`
		LEFT JOIN  Donazione
		on GestPayREST.shopTransactionID = Donazione.CodTrans
		WHERE GestPayREST.paymentID = '%s'", $richiesta->paymentID );
        $donazione = mysqli_query( $connection, $query_donazione )or die( mysqli_error( $connection ) );
        $row_donazione = mysqli_fetch_assoc( $donazione );
        $totalRows_donazione = mysqli_num_rows( $donazione );
        $answer_donazione = ( object )array();
        if ( $totalRows_donazione == 1 ) {
            foreach ( $row_donazione as $key => $value ) {
                if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                    $answer_donazione->$key = $value;
                }
            }
        }
        $query_anagrafica = sprintf( "SELECT * FROM Anagrafica WHERE Id_a = %s", $row_donazione[ 'Id_a' ] );
        $anagrafica = mysqli_query( $connection, $query_anagrafica )or die( mysqli_error( $connection ) );
        $row_anagrafica = mysqli_fetch_assoc( $anagrafica );
        $totalRows_anagrafica = mysqli_num_rows( $anagrafica );

        foreach ( $row_anagrafica as $key => $value ) {
            if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                $answer_donazione->$key = $value;
            }
        }
        if ( $row_anagrafica[ 'operazione' ] == "regular" ) {
            $query_mandato = sprintf( "SELECT frequenza, importo FROM Mandato WHERE Id_a = %s", $row_anagrafica[ 'Id_a' ] );
            $mandato = mysqli_query( $connection, $query_mandato )or die( mysqli_error( $connection ) );
            $row_mandato = mysqli_fetch_assoc( $mandato );
            $totalRows_mandato = mysqli_num_rows( $mandato );
            foreach ( $row_mandato as $key => $value ) {
                if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                    $answer_donazione->$key = $value;
                }
            }
        }
        $connection->close();
        return ( $answer_donazione );
    }
    elseif ( isset( $richiesta->g ) && trim( $richiesta->g ) != "" ) { // Query su GUID per tesser in regalo
        $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME_TGIFT )or trigger_error( mysqli_error( $connection ), E_USER_ERROR );
        if ( $connection->connect_errno ) {
            trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
        }
        $query_donato = sprintf( "SELECT * FROM Voucher  WHERE GUID = '%s'", $richiesta->g );
        $donato = mysqli_query( $connection, $query_donato )or die( mysqli_error( $connection ) );
        $row_donato = mysqli_fetch_assoc( $donato );
        $totalRows_donato = mysqli_num_rows( $donato );
        if ( 1 <> $totalRows_donato ) {
            $answer_donazione->Esito = "KO";
            $answer_donazione->record = $totalRows_donato;
        } else {
            foreach ( $row_donato as $key => $value ) {
                if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                    $answer_donazione->$key = $value;
                }
            }
        }
        $connection->close();
        return ( $answer_donazione );
    }
    elseif ( isset( $richiesta->Id_ag ) && trim( $richiesta->Id_ag ) != "" ) {
        $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME_TGIFT )or trigger_error( mysqli_error( $connection ), E_USER_ERROR );
        if ( $connection->connect_errno ) {
            trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
        }
        $query_donati = sprintf( "SELECT * FROM Voucher  WHERE Id_donatore = '%s'", $richiesta->Id_ag );
        $donati = mysqli_query( $connection, $query_donati )or die( mysqli_error( $connection ) );
        $row_donati = mysqli_fetch_assoc( $donati );
        $totalRows_donati = mysqli_num_rows( $donati );
        if ( 0 == $totalRows_donati ) {
            $answer_donazione->Esito = "KO";
            $answer_donazione->record = $totalRows_donati;
        } else {
            //$answer_donazione->nrecord =$totalRows_donati;
            do {
                $indice = $row_donati[ 'Id_donato' ];
                $answer_donazione->$indice = ( object )array();
                foreach ( $row_donati as $key => $value ) {
                    if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                        $answer_donazione->$indice->$key = $value;
                    }
                }
            } while ( $row_donati = mysqli_fetch_assoc( $donati ) );
        }
        $connection->close();
        return ( $answer_donazione );
    }
}
if ( DEBUG == true ) {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "re-mail.php query stirng: " . $_SERVER[ 'QUERY_STRING' ] . PHP_EOL, 3, LOG_FILE ); //DEBUG
}
// controllo il secret
$query_data = ( object )array();
$query_data->Id_a = $_GET[ 'd' ];
$secret = md5( $_GET[ 'd' ] . SALT_MAIL );
// controllo i dati
$emailuserdata = call_user_func_array( 'LeggiDati_mysql', array( $query_data ) ); ;

if ( DEBUG == true ) {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "re-mail.php LeggiDati_mysql: " . json_encode( $emailuserdata) . PHP_EOL, 3, LOG_FILE ); //DEBUG
}

if ( empty( $emailuserdata ) ) {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata a re-mail.php con [d] non valido" . $_GET[ 'd' ] . PHP_EOL, 3, LOG_FILE );
    $redirect_url = FORM_ERROR_PAGE;
    if ( true == DEBUG ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata a re-mail.php con [d] non valido -> redirect a $redirect_url" . PHP_EOL, 3, EM_DEBUG_LOG_FILE );
    }
    
    header( "Location: " . $redirect_url );
    exit;
}

require_once('../vendor/autoload.php');
$options = new QROptions(
  [
    'eccLevel' => QRCode::ECC_L,
    //'outputType' => QRCode::OUTPUT_MARKUP_SVG,
    'version'      => 10,
    //'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
      'outputType'   => QRCode::OUTPUT_IMAGE_JPG,
    //'version' => 5,
    //'eccLevel'            => EccLevel::L,
    'bgColor'             => '#FFFFFF', // overrides the imageTransparent setting
    'imageTransparent'    => true,
    'scale'               => 20,
        'drawLightModules'    => true,
        'drawCircularModules' => true,
    'circleRadius'        => 0.4,    
  ]
);

$qrcode = (new QRCode($options))->render($url_di_base.'/ticket.php?d='.$_GET[ 'd' ].'&s='.$secret.'', dirname(__DIR__).'/img/qr/'.$secret.'.jpg' );

switch ($emailuserdata->pay_method) {
            case 'PP':
              $pay_method = "PayPal";
              break;
            case 'CC':
            case 'ST':
              $pay_method = "Carta di credito";
              break;
            case 'SY':
              $pay_method = "Satispay";
              break;
            case 'SD':
              $pay_method = "SDD";
              break;
        };
// Testo personalizzabile della mail di reinvio
$frase_donazione = "<em>Questa mail sostituisce le precedenti.</em><br><br>

Grazie di cuore per la tua donazione a " . ORG_NAME . ": il tuo contributo è prezioso e ci aiuterà a portare avanti i nostri progetti.<br><br>

Qui di seguito trovi il <strong>QRCODE</strong> associato alla tua donazione. <strong>Conservalo</strong>: ti servirà come ricevuta o lasciapassare se previsto dall'iniziativa.";

if ( USE_MAGNEWS == true ) { //MagNews
    $redirect_url = FORM_THANK_YOU_PAGE . "?d=" . $emailuserdata ->Id_a ."&s=". $secret;

    $url_ch = MN_API_URL . "/v19/simplemessages/message";

    $values = array(
        "type" => "email",
        "fromemail" => FROM_MAIL,
        "fromname" => FROM_NAME,
        "replyto" => FROM_MAIL,
        "to" => strtolower($emailuserdata->mail),
    ); 
    if (USE_SANDBOX == true){
        $options =array(
            "usenewsletterastemplate"=> "true",
            "idnewsletter"=> MN_TNX_EMAIL_ID,
            //"idnewsletter"=> 81,
            "renderatsend"=> "true",
            "temp.nome" => "[TEST] " . ucfirst(strtolower($emailuserdata->nome)),
            "temp.cognome" => ucfirst(strtolower($emailuserdata->cognome)),
            "temp.mail" => strtolower($emailuserdata->mail),
            "temp.tel" => $emailuserdata->tel,		
            "temp.nota" => "Questo è il mo commentp!",
            "temp.importo" => $emailuserdata->importo,
            "temp.pay_method" => $pay_method,
            "temp.CodTrans" => $emailuserdata->CodTrans,
            "temp.codice_s" => $secret,
            "temp.codice_d" => $query_data->Id_a,
            "temp.testo_mail" => $frase_donazione,
            "temp.imgqr" => $url_di_base."/img/qr/'.$secret.'.jpg",
            "temp.qr_MN" => $url_di_base.'/ticket.php?d='.$_GET[ 'd' ].'&s='.$secret,
            "temp.personalcode" => $emailuserdata->CodicePersonale,

        );
    }
    else{
        $options =array(
            "usenewsletterastemplate"=> "true",
            //"idnewsletter"=> 81,
             "idnewsletter"=> MN_TNX_EMAIL_ID,
            "renderatsend"=> "true",
            "temp.nome" => ucfirst(strtolower($emailuserdata->nome)),
            "temp.cognome" => ucfirst(strtolower($emailuserdata->cognome)),
            "temp.mail" => strtolower($emailuserdata->mail),
            "temp.tel" => $emailuserdata->tel,		
            "temp.nota" => "Questo è il mo commentp!",
            "temp.importo" => $emailuserdata->importo,
            "temp.pay_method" => $pay_method,
            "temp.CodTrans" => $emailuserdata->CodTrans,
            "temp.codice_s" => $secret,
            "temp.codice_d" => $query_data->Id_a,
            "temp.testo_mail" => $frase_donazione,
            "temp.imgqr" => $url_di_base."/img/qr/'.$secret.'.jpg",
            "temp.qr_MN" => $url_di_base.'/ticket.php?d='.$_GET[ 'd' ].'&s='.$secret,
            "temp.personalcode" => $emailuserdata->CodicePersonale,

        );

    }

    $data = array(
        "options" => $options, 
        "values" => $values
    );    


    $data_string = json_encode($data, JSON_UNESCAPED_UNICODE);
    //$data_string = CleanMyJSON( $data_string );
    $access_token = MN_APP_SECRET; //see OAuth 2 section.
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata Send message Magnews: " . $data_string . PHP_EOL, 3, LOG_FILE );
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,  $url_ch );
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = array("Content-Type: application/json", "Authorization: Bearer $access_token");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = json_decode( curl_exec( $ch ), true );
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito Send message Magnews: " . json_encode( $result ) . PHP_EOL, 3, LOG_FILE );
    }
    curl_close( $ch );
}
else{
    $body = json_encode($emailuserdata);
    $redirect_url = FORM_THANK_YOU_PAGE . "?d=" . $emailuserdata ->Id_a ."&s=". $_GET[ 's' ];


/*
    require '../vendor/phpmailer/phpmailer/src/Exception.php';
    require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require '../vendor/phpmailer/phpmailer/src/SMTP.php';
    */
    //use PHPMailer\PHPMailer\Exception;
    //use PHPMailer\PHPMailer\PHPMailer;

    $mail = new PHPMailer\PHPMailer\PHPMailer();
    try {
        //Server settings
        $mail->SMTPDebug = 0; // debugging: 1 = errors and messages, 2 = messages only
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = MAIL_SMTP;                     //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = FROM_MAIL;                     //SMTP username
        $mail->Password   = MAIL_PWD;                               //SMTP password
        $mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
        $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        //Recipients
        $mail->setFrom(FROM_MAIL, FROM_NAME);
        $mail->addAddress($emailuserdata->mail, ucfirst(strtolower($emailuserdata->nome)).' '.ucfirst(strtolower($emailuserdata->cognome)));     //Add a recipient

        $mail->addReplyTo(FROM_MAIL, FROM_NAME);
        //$mail->addCC('cc@example.com');
        //$mail->addBCC('bcc@example.com');

        //Attachments
        //$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
        //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

        //Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = ucfirst(strtolower($emailuserdata->nome)). ', grazie per la tua donazione';
        //$mail->Body    = 'Questi sono i dati:<br>'. $body;
        $mail->Body = file_get_contents('../email/'.FORM_LANG.'/index.singola.html');
        // Sostituzione
        $indirizzo="";
        if ($emailuserdata->indirizzo!="") $indirizzo .= $emailuserdata->indirizzo. ' '. $emailuserdata->civico. '<br>';
        if ($emailuserdata->cap!="") $indirizzo .= $emailuserdata->cap;
        if ($emailuserdata->citta!="") $indirizzo .= ' '. ucfirst(strtolower($emailuserdata->citta));
        if ($emailuserdata->provincia!="") $indirizzo .= ' ('. strtoupper($emailuserdata->provincia.')');
        $datipersonali="";
        if ($emailuserdata->tel!="") $datipersonali .= '<strong>telefono</strong>: '. $emailuserdata->tel;
        if ($emailuserdata->mail!="") $datipersonali .= '<br><strong>email</strong>: '. strtolower($emailuserdata->mail);



        $search = array('{{NOME}}', '{{IMPORTO}}', '{{PAY_METHOD}}','{{CODTRANS}}','{{COGNOME}}','{{INDIRIZZO}}', '{{DATI_PERSONALI}}', '{{FRASE_DONAZIONE}}' );
        $replace = array(ucfirst(strtolower($emailuserdata->nome)), $emailuserdata->importo, $pay_method, $emailuserdata->CodTrans, ucfirst(strtolower($emailuserdata->cognome)), ucfirst(strtolower($indirizzo)), $datipersonali, $frase_donazione);
        $mail->Body =str_replace($search, $replace, $mail->Body);



        //$mail->AltBody = 'Questi sono i dati: '. $body;

        $mail->send();
        //echo 'Message has been sent';
        // connetto al db
        $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
        if ( $connection->connect_errno ) {
            trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
        }
        // preparo lo statement
        if ( !( $stmt = $connection->prepare( "UPDATE Donazione SET ringraziata='Y' WHERE CodTrans=?;" ) ) ) {
            trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
        }

            // associo i parametri ai placeholder
        if ( !$stmt->bind_param( 's',  $emailuserdata->CodTrans ) ) {
            trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
        }
        // eseguo la query e chiudo
        if ( !$stmt->execute() ) {
            trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
        }
        $stmt->close();
        $connection->close();
    } catch (Exception $e) {
        //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Mail.php Message could not be sent. Mailer Error: ". $mail->ErrorInfo  . PHP_EOL, 3, EM_DEBUG_LOG_FILE );

    }


}
if ("N" == $emailuserdata->ringraziata){
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( $connection->connect_errno ) {
        trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
    }
    // preparo lo statement
    if ( !( $stmt = $connection->prepare( "UPDATE Donazione SET ringraziata='Y' WHERE CodTrans=?;" ) ) ) {
        trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
    }

        // associo i parametri ai placeholder
    if ( !$stmt->bind_param( 's',  $emailuserdata->CodTrans ) ) {
        trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    // eseguo la query e chiudo
    if ( !$stmt->execute() ) {
        trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    $stmt->close();
    $connection->close();
}

foreach ( $result as $k => $v ) {
		echo "<strong>" . str_replace( "_", " ", $k ) . "</strong> = " . $v . "<br>";
	}

