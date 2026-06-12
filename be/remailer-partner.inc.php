<?php
// Invio Mail e aggiorno DB  - INIZIO
$n_sent = 0;
//2.	Per ogni destinatario
ob_start();
if ( $totalRows_recipient >= 1 ) {
    do {
        if(isset($row_recipient['Tipo']) && $row_recipient['Tipo'] =='C'){ 
            $invito = "cena+festa";
            $orario = "20.30. Mi raccomando sii puntuale!";
        } else { 
            $invito ="festa";
            $orario = "21.30";
        }
        $secret = md5( "partner".$row_recipient[ 'Id_ticket' ] . SALT_MAIL );
        // Testo personalizzabile della mail di invito partner (eventi con ingresso tramite QR code)
        $frase_donazione ="Questa mail contiene il codice QR per accedere alla <strong>".$invito."</strong> organizzata da " . ORG_NAME . ": porta questa mail con te sul tuo telefono o salva l'immagine del QR code.<br>
        Ti aspettiamo dalle <strong>".$orario."</strong>.<br><br>
        Questo lasciapassare ti è stato inviato grazie alla generosità del nostro partner <strong style =\"background-color: #FFFF00\">" . $row_recipient[ 'Nome' ] . "</strong>
        <br>
        <br>
        Qui di seguito trovi il <strong>QR CODE</strong> che ti servirà per accedere alla <strong>".$invito."</strong>. <strong>Conservalo e portalo</strong> con te perché <strong>ti verrà chiesto all'ingresso</strong>.";
        
        if ( USE_MAGNEWS == true ) { //MagNews
           
            $url_ch = MN_API_URL . "/v19/simplemessages/message";
            $values = array(
                "type" => "email",
                "fromemail" => FROM_MAIL,
                "fromname" => FROM_NAME,
                "replyto" => FROM_MAIL,
                "to" => $test_mode ? $test_email_addr : strtolower( $row_recipient[ 'mail' ] ),
            );
            if ( USE_SANDBOX == true ) {
                $options = array(
                    "usenewsletterastemplate" => "true",
                    "idnewsletter" => $template_MN,
                    "renderatsend" => "true",
                    "temp.nome" => "[TEST] " . ucfirst( strtolower( $row_recipient[ 'nome' ] ) ),
                    "temp.cognome" => ucfirst( strtolower( $row_recipient[ 'cognome' ] ) ),
                    "temp.mail" => strtolower( $row_recipient[ 'mail' ] ),
                    "temp.tel" => $row_recipient[ 'telefono' ],
                    "temp.tipo" => $row_recipient[ 'tipo' ],
                    //"temp.nota" => "Questo è il mo commentp!",
                    "temp.codice_s" => $secret,
                    "temp.codice_d" => $row_recipient[ 'Id_ticket' ],
                    "temp.testo_mail" => $frase_donazione,
                    "temp.imgqr" => $url_di_base . "/img/qr/'.$secret.'.jpg",
                    "temp.qr_MN" => $url_di_base . '/ticket.php?d=' . $row_recipient[ 'Id_ticket' ] . '&s=' . $secret. '&t=partner',
                    //"temp.personalcode" => $row_recipient['CodicePersonale'],

                );
            } else {
                $options = array(
                    "usenewsletterastemplate" => "true",
                    "idnewsletter" => $template_MN,
                    "renderatsend" => "true",
                    "temp.nome" => ucfirst( strtolower( $row_recipient[ 'nome' ] ) ),
                    "temp.cognome" => ucfirst( strtolower( $row_recipient[ 'cognome' ] ) ),
                    "temp.mail" => strtolower( $row_recipient[ 'mail' ] ),
                    "temp.tel" => $row_recipient[ 'telefono' ],
                    "temp.tipo" => $row_recipient[ 'tipo' ],
                    //"temp.nota" => "Questo è il mo commentp!",
                    "temp.codice_s" => $secret,
                    "temp.codice_d" => $row_recipient[ 'Id_ticket' ],
                    "temp.testo_mail" => $frase_donazione,
                    "temp.imgqr" => $url_di_base . "/img/qr/'.$secret.'.jpg",
                    "temp.qr_MN" => $url_di_base . '/ticket.php?d=' . $row_recipient[ 'Id_ticket' ] . '&s=' . $secret. '&t=partner',
                    //"temp.personalcode" => $row_recipient['CodicePersonale'],
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
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            $headers = array( "Content-Type: application/json", "Authorization: Bearer $access_token" );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
            $result = json_decode( curl_exec( $ch ), true );
            if ( DEBUG == true ) {
                error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito Send message Magnews: " . json_encode( $result ) . PHP_EOL, 3, LOG_FILE );
            }
            curl_close( $ch );
            if ( !$test_mode ) {
                // Update count remainder
                //$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
                if ( $conn->connect_errno ) {
                    trigger_error( "Connessione al server mySQL fallita: (" . $conn->connect_errno . ") " . $conn->connect_error, E_USER_ERROR );
                }
                // preparo lo statement
                if ( !( $stmt = $conn->prepare( "UPDATE `Ticket` SET `remainder` = ? WHERE `Ticket`.`Id_ticket` = ? AND `Ticket`.`Id_partner` = ?;" ) ) ) {
                    trigger_error( "Prepare failed: (" . $conn->errno . ") " . $conn->error, E_USER_ERROR );
                }
                // associo i parametri ai placeholder
                //$invio_n = INVIO_MAIL;
                if ( !$stmt->bind_param( 'iis', $invio_n, $row_recipient[ 'Id_ticket' ], $row_recipient[ 'Id_partner' ] ) ) {
                    trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
                }
                // eseguo la query e chiudo
                if ( !$stmt->execute() ) {
                    trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
                }
                $stmt->close();
                echo "Inviato remainder a (" . $row_recipient[ 'Id_ticket' ] . ") " . $row_recipient[ 'nome' ] . " " . $row_recipient[ 'cognome' ] . " [" . $row_recipient[ 'Nome' ] . "]<br>";
            } else {
                echo "<strong>[TEST]</strong> Mail inviata a <strong>" . htmlspecialchars( $test_email_addr ) . "</strong> con dati fittizi (DB non aggiornato)<br>";
            }
            ob_flush();
            $n_sent++;
            if ( $test_mode ) break;
        }
    } while ( $row_recipient = mysqli_fetch_assoc( $recipient ) );
    ob_end_clean();

} else {
    echo "Non ci sono promemoria da inviare";
}
// Invio Mail e aggiorno DB  - FINE