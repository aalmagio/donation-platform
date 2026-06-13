<?php
// PATH assoluti nel fil perche' deve esserla lanciato da CRON.
$site_folder = dirname(__DIR__) . '/';
require $site_folder . 'inc/config.inc.php';
require PERCORSO_DI_BASE . '/inc/data.inc.php';
if ( isset( $_GET[ 'p' ] ) && $_GET[ 'p' ] == "Sm3moRato" ) {
    define( 'INVIO_MAIL', 1 ); // Numero di invio
    if ( isset( $_GET[ 'n' ] ) && is_numeric( $_GET[ 'n' ] ) ) {
        $invio_n = $_GET[ 'n' ];
    } else {
        $invio_n = INVIO_MAIL;
    }
  
    if ( USE_SANDBOX == true ) {
        define( 'MN_REMINDER1_EMAIL_ID', 40 );
        $query_recipient = "SELECT Donazione.importo, Donazione.pay_method, Donazione.CodTrans, Donazione.remainder, Anagrafica.Id_a, Anagrafica.nome, Anagrafica.cognome, Anagrafica.mail, Anagrafica.tel FROM Donazione LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a WHERE Donazione.esito = 'OK'  AND Donazione.remainder < " . $invio_n . " LIMIT 5;";
    } else {
        define( 'MN_REMINDER1_EMAIL_ID', 40 ); //Donato
        $query_recipient = "SELECT Donazione.importo, Donazione.pay_method, Donazione.CodTrans, Donazione.remainder, Anagrafica.Id_a, Anagrafica.nome, Anagrafica.cognome, Anagrafica.mail, Anagrafica.tel FROM Donazione LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a WHERE Donazione.esito = 'OK'  AND Donazione.remainder < " . $invio_n . " LIMIT 100;";
    }
    //echo $query_recipient;
    if ( isset( $_GET[ 'm' ] ) && is_numeric( $_GET[ 'm' ] ) ) {
        $template_MN = $_GET[ 'm' ];
    } else {
        $template_MN = MN_REMINDER1_EMAIL_ID;
    }
    //1.	Selezione i destinatari
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );
    $recipient = mysqli_query( $connection, $query_recipient )or die( mysqli_error( $connection ) );
    $row_recipient = mysqli_fetch_assoc( $recipient );
    $totalRows_recipient = mysqli_num_rows( $recipient );
    $n_sent = 0;
    //2.	Per ogni destinatario
    ob_start();
    if ( $totalRows_recipient >= 1 ) {
        do {
            $secret = md5( $row_recipient[ 'Id_a' ] . SALT_MAIL );
            switch ( $row_recipient[ 'pay_method' ] ) {
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
            // Testo personalizzabile della mail di promemoria.
            // Se l'evento prevede un ingresso con QR code, descrivi qui come usarlo.
            $frase_donazione = "Grazie ancora per la tua donazione a " . ORG_NAME . "!<br><br>
    Qui di seguito trovi il <strong>QRCODE</strong> associato alla tua donazione. <strong>Conservalo</strong>: ti servirà come ricevuta o lasciapassare se previsto dall'iniziativa.";
            if ( USE_MAGNEWS == true ) { //MagNews
                $redirect_url = FORM_THANK_YOU_PAGE . "?d=" . $row_recipient[ 'Id_a' ] . "&s=" . $secret;

                $url_ch = MN_API_URL . "/v19/simplemessages/message";

                $values = array(
                    "type" => "email",
                    "fromemail" => FROM_MAIL,
                    "fromname" => FROM_NAME,
                    "replyto" => FROM_MAIL,
                    "to" => strtolower( $row_recipient[ 'mail' ] ),
                );
                if ( USE_SANDBOX == true ) {
                    $options = array(
                        "usenewsletterastemplate" => "true",
                        "idnewsletter" =>  $template_MN,
                        "renderatsend" => "true",
                        "temp.nome" => "[TEST] " . ucfirst( strtolower( $row_recipient[ 'nome' ] ) ),
                        "temp.cognome" => ucfirst( strtolower( $row_recipient[ 'cognome' ] ) ),
                        "temp.mail" => strtolower( $row_recipient[ 'mail' ] ),
                        "temp.tel" => $row_recipient[ 'tel' ],
                        //"temp.nota" => "Questo è il mo commentp!",
                        "temp.importo" => $row_recipient[ 'importo' ],
                        "temp.pay_method" => $pay_method,
                        "temp.CodTrans" => $row_recipient[ 'CodTrans' ],
                        "temp.codice_s" => $secret,
                        "temp.codice_d" => $row_recipient[ 'Id_a' ],
                        "temp.testo_mail" => $frase_donazione,
                        "temp.imgqr" => $url_di_base . "/img/qr/'.$secret.'.jpg",
                        "temp.qr_MN" => $url_di_base . '/ticket.php?d=' . $row_recipient[ 'Id_a' ] . '&s=' . $secret,
                        "temp.personalcode" => $row_recipient['CodicePersonale'],

                    );
                } else {
                    $options = array(
                        "usenewsletterastemplate" => "true",
                        "idnewsletter" =>  $template_MN,
                        "renderatsend" => "true",
                        "temp.nome" => ucfirst( strtolower( $row_recipient[ 'nome' ] ) ),
                        "temp.cognome" => ucfirst( strtolower( $row_recipient[ 'cognome' ] ) ),
                        "temp.mail" => strtolower( $row_recipient[ 'mail' ] ),
                        "temp.tel" => $row_recipient[ 'tel' ],
                        //"temp.nota" => "Questo è il mo commentp!",
                        "temp.importo" => $row_recipient[ 'importo' ],
                        "temp.pay_method" => $pay_method,
                        "temp.CodTrans" => $row_recipient[ 'CodTrans' ],
                        "temp.codice_s" => $secret,
                        "temp.codice_d" => $row_recipient[ 'Id_a' ],
                        "temp.testo_mail" => $frase_donazione,
                        "temp.imgqr" => $url_di_base . "/img/qr/'.$secret.'.jpg",
                        "temp.qr_MN" => $url_di_base . '/ticket.php?d=' . $row_recipient[ 'Id_a' ] . '&s=' . $secret,
                         "temp.personalcode" => $row_recipient['CodicePersonale'],

                    );

                }
                $data = array(
                    "options" => $options,
                    "values" => $values
                );


                $data_string = json_encode( $data, JSON_UNESCAPED_UNICODE );
                //$data_string = CleanMyJSON( $data_string );
                $access_token = MN_APP_SECRET; //see OAuth 2 section.
                if ( DEBUG == true ) {
                    error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata Send message Magnews: " . $data_string . PHP_EOL, 3, LOG_FILE );
                }
                $ch = curl_init();
                curl_setopt( $ch, CURLOPT_URL, $url_ch );
                curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
                curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                $headers = array( "Content-Type: application/json", "Authorization: Bearer $access_token" );
                curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

                $result = json_decode( curl_exec( $ch ), true );
                if ( DEBUG == true ) {
                    error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito Send message Magnews: " . json_encode( $result ) . PHP_EOL, 3, LOG_FILE );
                }
                curl_close( $ch );
                // Update count remainder
                //$connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
                if ( $connection->connect_errno ) {
                    trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
                }
                // preparo lo statement
                if ( !( $stmt = $connection->prepare( "UPDATE `Donazione` SET `remainder` = ? WHERE `Donazione`.`Id_a` = ? AND `Donazione`.`CodTrans` = ?;" ) ) ) {
                    trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
                }
                // associo i parametri ai placeholder
                //$invio_n = INVIO_MAIL;
                if ( !$stmt->bind_param( 'iis', $invio_n, $row_recipient[ 'Id_a' ], $row_recipient[ 'CodTrans' ] ) ) {
                    trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
                }
                // eseguo la query e chiudo
                if ( !$stmt->execute() ) {
                    trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
                }
                $stmt->close();
                //$message .="Inviato remainder a (" . $row_recipient[ 'Id_a' ]  .") ". $row_recipient[ 'nome' ]  ." ". $row_recipient[ 'cognome' ]  ."<br>";
                echo "Inviato remainder a (" . $row_recipient[ 'Id_a' ] . ") " . $row_recipient[ 'nome' ] . " " . $row_recipient[ 'cognome' ] . "<br>";
                ob_flush();
                // Update count remainder
                $n_sent++;

            }
        } while ( $row_recipient = mysqli_fetch_assoc( $recipient ) );
        ob_end_clean();
        $connection->close();

        $nome_mittente_a = ORG_NAME;
        $mail_mittente_a = ORG_NOREPLY;
        $mail_destinatario_a = ORG_EMAIL; // destinatario del report del cron
        //$mail_oggetto_a = "[CRON STUDIO] Invio mail x tessera in regalo";
        $mail_oggetto_a = ( true == DEBUG ) ? "[CRON TEST] Invio remainder mail QR" : "[CRON] Invio remainder mail x QR";
        $mail_corpo_a = "Ho inviato " . $n_sent . " mail di remainder via cron per i " . $totalRows_recipient . " destinatari dei QR  \r\n" . date( 'l jS \of F Y h:i:s A' );;
        $mail_headers_a = "From: " . $nome_mittente_a . " <" . $mail_mittente_a . ">\r\n";
        $mail_headers_a .= "Reply-To: " . $mail_mittente_a . "\r\n";
        $mail_headers_a .= "X-Mailer: PHP/" . phpversion();
        mail( $mail_destinatario_a, $mail_oggetto_a, $mail_corpo_a, $mail_headers_a );
    } else {
        echo "Non ci sono promemoria da inviare";
    }
} else {
    header( 'HTTP/1.0 403 Forbidden' );
    echo "Non dovresti esser qui!";
    exit;
}
