<?php
// Lanciato da CRON: i percorsi sono risolti rispetto alla posizione del file.
$site_folder = dirname(__DIR__) . '/';
$log_folder = $site_folder . 'log/';
require $site_folder.'inc/config.inc.php';
require $site_folder.'inc/data.inc.php';
//echo "db: ". DB_DBNAME ." Sandbox: ".USE_SANDBOX."<br>";
require_once $site_folder.'inc/security.php';
// GetSQLValueString centralizzata
require_once $site_folder.'be/inc/db_helpers.php';
$connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
if ( !$connection ) {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "cron_SY: connessione DB fallita: " . mysqli_connect_error() . PHP_EOL, 3, $log_folder . 'cron_SY.log' );
    exit(1);
}
$query_donazioni = "SELECT Anagrafica.Id_a, Anagrafica.nome, Anagrafica.cognome, Anagrafica.mail, Anagrafica.tel, Donazione.CodTrans, Donazione.importo, Donazione.`data`, Donazione.esito, Satispay.id, Satispay.code_identifier, Satispay.status FROM Donazione LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a LEFT Join Satispay ON Donazione.CodTrans = Satispay.CodTrans WHERE Donazione.pay_method ='SY' AND Donazione.esito = 'WA';";
$donazioni = mysqli_query( $connection, $query_donazioni );
if ( !$donazioni ) {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "cron_SY: query fallita: " . mysqli_error( $connection ) . PHP_EOL, 3, $log_folder . 'cron_SY.log' );
    $connection->close();
    exit(1);
}
$row_donazioni = mysqli_fetch_assoc( $donazioni );
$totalRows_donazioni = mysqli_num_rows( $donazioni );
$emailuserdata =new stdClass();
$now = new DateTime( 'now', new DateTimeZone( 'Europe/Rome' ) );
if($totalRows_donazioni>0){
    require_once( $site_folder.'vendor/autoload.php' );
	if ( USE_SANDBOX == true ) { 
     \SatispayGBusiness\Api::setSandbox( true );
    }
	$authData = json_decode( SY_AUTH );
	\SatispayGBusiness\Api::setPublicKey($authData->public_key);
    \SatispayGBusiness\Api::setPrivateKey($authData->private_key);
    \SatispayGBusiness\Api::setKeyId($authData->key_id);
    $message ="";
    do{
        foreach($row_donazioni as $k => $v){
            $emailuserdata->$k = $v;
        }
        $date2 = date_create( $row_donazioni['data'], new DateTimeZone( 'Europe/Rome' ) ); // orario - apertura cancelli
        $diff = date_diff( $now, $date2 );
        if ($diff->i >=5){
        $payment = \SatispayGBusiness\Payment::get( $row_donazioni['id']);
        if (!empty($payment->status)){
            if ( 'ACCEPTED' == $payment->status ) {
                $chk_satispay[ 'Esito ' ] = "OK";
            } else {
                $chk_satispay[ 'Esito ' ] = "KO";
            }
        }
        //echo "Status SY:" . $chk_satispay[ 'Esito ' ] ." Status DB: ". $row_donazioni['status'] ." Esito: ". $row_donazioni['esito'] ."<br>";
        if($chk_satispay[ 'Esito ' ] != $row_donazioni['esito'] ) {
            // connetto al db con transazione per evitare race condition
            $conn_upd = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
            if ( !$conn_upd ) {
                error_log( date( '[Y-m-d H:i:s e] ' ) . "cron_SY update esito: connessione fallita" . PHP_EOL, 3, $log_folder . 'cron_SY.log' );
                continue;
            }
            $conn_upd->begin_transaction();
            try {
                $stmt = $conn_upd->prepare( "UPDATE `Donazione` SET `esito` = ? WHERE `Donazione`.`Id_a` = ? AND `Donazione`.`CodTrans` = ? AND `esito` = 'WA';" );
                if ( !$stmt ) { throw new Exception( "Prepare failed: " . $conn_upd->error ); }
                $stmt->bind_param( 'sis', $chk_satispay[ 'Esito ' ], $row_donazioni[ 'Id_a' ] , $row_donazioni[ 'CodTrans' ] );
                if ( !$stmt->execute() ) { throw new Exception( "Execute failed: " . $stmt->error ); }
                if ( $stmt->affected_rows == 0 ) {
                    // Gia aggiornato da altro processo
                    $stmt->close();
                    $conn_upd->rollback();
                    $conn_upd->close();
                    continue;
                }
                $stmt->close();
                $conn_upd->commit();
            } catch ( Exception $e ) {
                $conn_upd->rollback();
                error_log( date( '[Y-m-d H:i:s e] ' ) . "cron_SY update esito: " . $e->getMessage() . PHP_EOL, 3, $log_folder . 'cron_SY.log' );
                $conn_upd->close();
                continue;
            }
            $conn_upd->close();
            $message .="Aggiorno esito Donazione " . $row_donazioni[ 'CodTrans' ]  ."\r\n";
            if('ACCEPTED' == $payment->status){ //Se esito OK 
                if ( USE_MAGNEWS == true ) { //MagNews
                        $pay_method = "Satispay";
                $secret = generate_signature( $row_donazioni[ 'Id_a' ] );
                // Testo personalizzabile della mail di conferma donazione
                $frase_donazione = "<strong>Grazie di cuore per la tua donazione a " . ORG_NAME . "!</strong><br><br>
                Il tuo contributo è prezioso e ci aiuterà a portare avanti i nostri progetti.";
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
                            //"idnewsletter"=> 81,
                            "idnewsletter"=> MN_TNX_EMAIL_ID,
                            "renderatsend"=> "true",
                            "temp.nome" => "[SY] " . ucfirst(strtolower($emailuserdata->nome)),
                            "temp.cognome" => ucfirst(strtolower($emailuserdata->cognome)),
                            "temp.mail" => strtolower($emailuserdata->mail),
                            "temp.tel" => $emailuserdata->tel,		
                            "temp.nota" => "Questo è il mo commentp!",
                            "temp.importo" => $emailuserdata->importo,
                            "temp.pay_method" => $pay_method,
                            "temp.CodTrans" => $emailuserdata->CodTrans,
                            "temp.codice_s" => $secret,
                            //"temp.codice_d" => $query_data->Id_a,
                            "temp.testo_mail" => $frase_donazione,
                            "temp.imgqr" => $url_di_base."/img/qr/'.$secret.'.jpg",
                            "temp.qr_MN" => $url_di_base.'/ticket.php?d='.$row_donazioni[ 'Id_a' ].'&s='.$secret,
                            "temp.personalcode" => $emailuserdata->CodicePersonale,
                        );
                    }
                    else{
                        $options =array(
                            "usenewsletterastemplate"=> "true",
                            //"idnewsletter"=> 8,
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
                            //"temp.codice_d" => $query_data->Id_a,
                            "temp.testo_mail" => $frase_donazione,
                            "temp.imgqr" => $url_di_base."/img/qr/'.$secret.'.jpg",
                            "temp.qr_MN" => $url_di_base.'/ticket.php?d='.$row_donazioni[ 'Id_a' ].'&s='.$secret,
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
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $headers = array("Content-Type: application/json", "Authorization: Bearer $access_token");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                    $result = json_decode( curl_exec( $ch ), true );
                    if ( DEBUG == true ) {
                        error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito Send message Magnews: " . json_encode( $result ) . PHP_EOL, 3, LOG_FILE );
                    }
                    curl_close( $ch );
                          $conn_mail = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
                    if ( !$conn_mail ) {
                        error_log( date( '[Y-m-d H:i:s e] ' ) . "cron_SY ringraziata: connessione fallita" . PHP_EOL, 3, $log_folder . 'cron_SY.log' );
                    } else {
                        $stmt = $conn_mail->prepare( "UPDATE `Donazione` SET `ringraziata` = 'Y' WHERE `Donazione`.`Id_a` = ? AND `Donazione`.`CodTrans` = ? ;" );
                        if ( $stmt ) {
                            $stmt->bind_param( 'is', $row_donazioni[ 'Id_a' ] , $row_donazioni[ 'CodTrans' ] );
                            if ( !$stmt->execute() ) {
                                error_log( date( '[Y-m-d H:i:s e] ' ) . "cron_SY ringraziata execute failed: " . $stmt->error . PHP_EOL, 3, $log_folder . 'cron_SY.log' );
                            }
                            $stmt->close();
                        }
                        $conn_mail->close();
                    }
    }
    
	

		}
	}
        if( isset($payment) && $payment->status != $row_donazioni['status'] ) {
            $conn_sy = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
            if ( !$conn_sy ) {
                error_log( date( '[Y-m-d H:i:s e] ' ) . "cron_SY update Satispay: connessione fallita" . PHP_EOL, 3, $log_folder . 'cron_SY.log' );
            } else {
                $stmt = $conn_sy->prepare( "UPDATE `Satispay` SET `status` = ? WHERE `Satispay`.`code_identifier` = ? AND `Satispay`.`id` = ?;" );
                if ( $stmt ) {
                    $stmt->bind_param( 'sss', $payment->status, $row_donazioni[ 'code_identifier' ] , $row_donazioni[ 'id' ] );
                    if ( !$stmt->execute() ) {
                        error_log( date( '[Y-m-d H:i:s e] ' ) . "cron_SY update Satispay execute failed: " . $stmt->error . PHP_EOL, 3, $log_folder . 'cron_SY.log' );
                    }
                    $stmt->close();
                } else {
                    error_log( date( '[Y-m-d H:i:s e] ' ) . "cron_SY update Satispay prepare failed: " . $conn_sy->error . PHP_EOL, 3, $log_folder . 'cron_SY.log' );
                }
                $conn_sy->close();
                $message .="Aggiorno status Satispay " . $row_donazioni[ 'CodTrans' ]  ."\r\n";
            }
	}
        
       //echo $message;
        }
    } while($row_donazioni = mysqli_fetch_assoc($donazioni)); 
    mail(ALERT_MAIL, '[SandBox]Controllo SY', $message);
}
