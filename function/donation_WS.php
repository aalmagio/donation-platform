<?php
//202504260930
/*
 *  Added Refferal code
 *  Modify idWeb for ScriviDonazione_mentor: specific id for payment gateway to develo refund form Mentor 
 *  Added Corportate in ScriviAnagrafica_mysql and ScriviAnagrafica_mentor
 *  Added control for duplicated donation in Mentor
 *  Removed AggiornaDonazione3D -> aggiornaEsitoDonazione
 */
require '../inc/config.inc.php';
require '../inc/data.inc.php';

require_once( 'inc/functions_generic.php' );
require_once( 'inc/functions_mysql.php' );
require_once( 'inc/functions_validatedata.php' );

//Payment Methodo specific function - BEGIN
if ( USE_GESTPAY == true ) {
    require_once( 'inc/functions_gestpay.php' );
} elseif ( USE_STRIPE == true ) {
    require_once( 'inc/functions_stripe.php' );
}
if ( USE_PAYPAL == true ) {
    require_once( 'inc/functions_paypal.php' );
}
if ( USE_SATISPAY == true ) {
    require_once( 'inc/functions_satispay.php' );
}
//Payment Method specific function - END
//CRM specific function - BEGIN
if ( USE_MENTOR == true ) {
    require_once( 'inc/functions_mentor.php' );

}
if ( USE_MAGNEWS == true ) {
    require_once( 'inc/functions_magnews.php' );
}
//CRM specific function - END

//Leggo il JSON di richiesta - BEGIN
try {
	$query_json = file_get_contents("php://input");
	error_log( date( '[Y-m-d H:i:s e] ' ) . "DON_WS JSON chiamata : " . strip_log_CC( $query_json ) . PHP_EOL, 3, LOG_FILE ); //DEBUG
	$query_stream = json_decode( $query_json, true );
} catch (JsonException $e) {
    handleError('Errore nella decodifica del JSON: ' . $e->getMessage(), 400);
}

//Codice del WS - BEGIN

$query_action = ( object )array(); // Azione richiesta la WebService
foreach ( $query_stream as $key => $value ) {
    if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
        $query_action->$key = $value;
    }
}
$query_data = ( object )array();
if ( $query_action->data != NULL ) {
    $query_obj = json_decode( $query_json, false );
    $query_data = $query_obj->data;
} else {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "Query Action Data NULL" . PHP_EOL, 3, LOG_FILE ); //DEBUG
}
//Leggo il JSON di richiesta - FINE
if ( !isset( $query_data->req_fields ) || trim( $query_data->req_fields ) == "" ) {
    $query_data->req_fields = REQ_FIELDS_DEFAULT; // Se non sono imposti i campi richiesti li valorizza con quelli di defult
}
//METODI
//if ( in_array( $_SERVER[ 'REMOTE_ADDR' ], $authorized_IP ) ) {
if ( $query_action->operation == "do" && $query_action->param == "transaction" ) {
    // 1 Valido i dati del form
    $chk_data = call_user_func( 'ValidaDati' );
    if ( $chk_data[ 0 ] <> 0 ) {
        // 1 Valido i dati del form
        $risposta_verificadati[ 'Esito' ] = "KO";
        $i = 0;
        foreach ( $chk_data[ 2 ] as $k => $v ) {
            $i++;
            $risposta_verificadati[ 'Errori' ][ $i ][ 'Codice' ] = $k;
            $risposta_verificadati[ 'Errori' ][ $i ][ 'Messaggio' ] = $v;
        }
        if ( DEBUG == true ) {
            $dbgp = json_encode( $risposta_verificadati );
            error_log( date( '[Y-m-d H:i:s e] ' ) . "DON_WS Verifica DATI " . $dbgp . PHP_EOL, 3, LOG_FILE ); //DEBUG
        }
        $risposta[ 'Errori' ] = $risposta_verificadati; // Modifica 'Errori' in 'Validazione'
    } else {
        //2 Scrivo l'anagrafica in mysql
        $id_anagrafica = call_user_func_array( 'scriviAnagrafica_mysql', array( $query_data ) ); // Scrivo l'anagrafica in mysql
        if ( is_numeric( $id_anagrafica[ 0 ] ) ) {
            $risposta_ana[ 'Esito_mysql' ] = "OK";
            $risposta_ana[ 'Messaggio_mysql' ] = "&Egrave; stata scritta in MYSQL l'anagrafica " . $id_anagrafica[ 0 ];
            $risposta_ana[ 'id_anagrafica_mysql' ] = $id_anagrafica[ 0 ];
            $query_data->Id_a = $id_anagrafica[ 0 ]; // Aggiungo l'id mysql dell'anagrafia a query_data
            $query_data->CodicePersonale = $id_anagrafica[ 1 ]; // Aggiungo il Codice Personale a query_data
            //2 Scrivo l'anagrafica in mysql
            $id_donazione = call_user_func_array( 'ScriviDonazione_mysql', array( $query_data ) );
            if ( preg_match( "/^[A-Z]{1}-[0-9]{17}-[A-Z]{2}/", $id_donazione ) ) { //Verifico codice transazione (formato D-20170701608524665-DD)
                $risposta_don[ 'Esito_mysql' ] = "OK";
                $risposta_don[ 'Messaggio_mysql' ] = "&Egrave; stata scritta in MYSQL la donazione " . $id_donazione;
                $risposta_don[ 'CodTrans' ] = $id_donazione; // Aggiungo il codice di transazione a query_data
                    $query_data->CodTrans = $id_donazione; // Aggiungo il codice di transazione a query_data
                //Tessera in regalo inzio
                if ( $query_data->centro == TESSERA_GIFT ) {
                    //Tessera in regalo inzio
                    $donati = call_user_func_array( 'ScriviDonati_mysql', array( $query_data ) );
                }
                //Tessera in regalo fine
                if ( $query_data->pay_method === "CC" ) { //Pagamanto con carta di credito
                    if ( USE_GESTPAY == true ) {
                        $query_data->GPtransactionType = "oneoff";
                        //4 Creo Ordine GestPay
                        $GP_order = call_user_func_array( 'CreateOrderGestPay', array( $query_data ) ); // 
                        if ( 0 == $GP_order->error->code ) {
                            foreach ( $GP_order->payload as $k => $v ) {
                                if ( is_string( $k ) ) {
                                    $query_data->$k = $v;
                                } else {
                                    foreach ( $k->payload as $k1 => $v1 ) {
                                        $query_data->$k1 = $v1;
                                    }
                                }
                            }
                            if ( DEBUG == true ) {
                                error_log( date( '[Y-m-d H:i:s e] ' ) . "DON_WS GP_order [2426]: " . json_encode( $GP_order ) . PHP_EOL, 3, LOG_FILE );
                            }
                            //5 Scrivo l'ordine GestPay in mySQL
                            $id_order = call_user_func_array( 'ScriviOrderGestPay_mysql', array( $query_data ) ); // 
                            //exit;
                            //6 Invio Ordine GestPay
                            $GP_submit = call_user_func_array( 'SubmitOrderGestPay', array( $query_data ) ); // 
                            if ( DEBUG == true ) {
                                error_log( date( '[Y-m-d H:i:s e] ' ) . "DON_WS SubmitOrderGestPay : " . strip_log_CC( json_encode( $query_data ) ) . PHP_EOL, 3, LOG_FILE );
                            }
                            if ( 0 == $GP_submit->error->code ) {
                                foreach ( $GP_submit->payload as $k => $v ) {
                                    if ( is_string( $k ) ) {
                                        $query_data->$k = $v;
                                    } else {
                                        foreach ( $k->payload as $k1 => $v1 ) {
                                            $query_data->$k1 = $v1;
                                        }
                                    }
                                }
                                if ( DEBUG == true ) {
                                    error_log( date( '[Y-m-d H:i:s e] ' ) . "DON_WS ESITO GP_submit : " . strip_log_CC( json_encode( $query_data ) ) . PHP_EOL, 3, LOG_FILE );
                                }
                                //5 Scrivo l'ordine GestPay in mySQL
                                if ( is_numeric( $id_order ) ) {
                                    //print_r($query_data);
                                    $query_data->EsitoDonazione = $query_data->transactionResult;
                                    call_user_func_array( 'AggiornaGetPayREST', array( $query_data ) ); // 7 Aggiorno estio in Donazone
                                    if ( "8006" == $query_data->transactionErrorCode ) { //3D
                                        $risposta_trans[ 'Esito_trans' ] = "3D";
                                        $risposta_trans[ 'Messaggio_trans' ] = "&Egrave; necessaria l'autenticazione 3D Secure";
                                        $risposta_trans[ 'URL_trans' ] = $query_data->userRedirect->href;
                                        //$query_data->EsitoDonazione = $query_data->transactionResult;
                                    } else { //NO 3D
                                        // 7 Aggiorno estio in Donazone
                                        call_user_func( 'AggiornaDonazioneNo3DGP', $query_data->transactionResult, $query_data->CodTrans ); // 7 Aggiorno estio in Donazone
                                        if ( "OK" == $query_data->transactionResult ) {
                                            // transazione no 3D con esito positivo - INIZIO
                                            //Scrivo l'anagrafica in Mentor (se la transazione è andata a buon fine)
                                            if ( USE_MENTOR == true ) {
                                                $id_anagrafica_mentor = call_user_func_array( 'scriviAnagrafica_mentor', array( $query_data ) ); // Scrivo l'anagrafica in mentor
                                                if ( preg_match( "/^[0-9]+/", $id_anagrafica_mentor[ 1 ] ) ) {
                                                    $risposta_ana_mentor[ 'Esito_mentor' ] = "OK";
                                                    $risposta_ana_mentor[ 'Messaggio_mentor' ] = "&Egrave; stata scritta in Mentor l'anagrafica " . $id_anagrafica_mentor[ 1 ];
                                                    $risposta_ana_mentor[ 'id_anagrafica_mentor' ] = $id_anagrafica_mentor[ 1 ];
                                                    $query_data->codiceAnagraficaMentor = $id_anagrafica_mentor[ 1 ];
                                                    $noe = explode( ";", $id_anagrafica_mentor[ 0 ] );
                                                    $query_data->AnagraficaMentor_NoE = $noe[ 0 ]; //Anagriafica Nuova o Esistente 
                                                    $risposta_ana_mentor[ 'NoE' ] = $noe[ 0 ]; // Aggiungo NOE
                                                    $query_data->SessoMentor = $noe[ 1 ];
                                                    if ( "nuova" == $query_data->AnagraficaMentor_NoE ) {
                                                        $wr_privacy = call_user_func_array( 'ScriviPrivacy_mentor', array( $query_data ) );
                                                    }
                                                    $wr_specs = call_user_func_array( 'ScriviSpecifiche_mentor', array( $query_data ) );
                                                    if ( "nuova" == $query_data->AnagraficaMentor_NoE ) {
                                                        $wr_subscription = call_user_func_array( 'ScriviSubscription_mentor', array( $query_data ) );
                                                    }
                                                    //10 Aggiorno l'anagrafica in mysql (2) con il codice Mentor dell'anagrafica (6)  
                                                    $ud_anagrafica = call_user_func_array( 'aggiornaAnagrafica_mysql', array( $query_data ) ); // aggiorno l'anagrafica in mysql	
                                                } else { //Errore di scrittura anagrafica in mentor
                                                    $risposta_ana_mentor[ 'Esito_mentor' ] = "KO";
                                                    $risposta_ana_mentor[ 'Messaggio_mentor' ] = "Si &egrave; verificato un errore nella scrittura in Mentor " . $id_anagrafica_mentor[ 0 ];
                                                    $risposta_ana_mentor[ 'id_anagrafica_mentor' ] = $id_anagrafica_mentor[ 1 ];
                                                }
                                                $risposta[ 'AnagraficaMentor' ] = $risposta_ana_mentor;
                                            }
                                            // Passo CodTrans così grazie.php può recuperare la donazione (altrimenti rimbalza a errore)
                                            $redirect_url = FORM_THANK_YOU_PAGE . "?CodTrans=" . $query_data->CodTrans;
                                            // transazione no 3D con esito positivo - FINE
                                        } else { // transazione no 3D con esito negativo
                                            if ( $query_data->centro == TESSERA_GIFT ) {
                                                // transazione no 3D con esito positivo - FINE
                                                $ud_donati = call_user_func_array( 'aggiornaDonati_mysql', array( $query_data ) );
                                            }
                                            // faccio cose, vedo gente e poi rimando all'esito  
                                            //$redirect_url = FORM_ERROR_PAGE;
                                            $redirect_url = FORM_ERROR_PAGE . "?e=" . $query_data->transactionErrorCode . "&t=" . $query_data->CodTrans;
                                            // $redirect_url = FORM_ERROR_PAGE . "?e=" . $azione_data[ 'ErrorCode' ] . "&t=" . $azione_data[ 'CodTrans' ];
                                        }
                                        $risposta_trans[ 'Esito_trans' ] = $query_data->transactionResult;
                                        $risposta_trans[ 'Messaggio_trans' ] = $query_data->transactionErrorDescription;
                                        $risposta_trans[ 'URL_trans' ] = $redirect_url;
                                        $query_data->EsitoDonazione = $query_data->transactionResult;
                                    }

                                }
                                //print_r($GP_submit);  
                            } else { //Errore GP Submit Order
                                $risposta_trans[ 'Esito_trans' ] = "KO";
                                $risposta_trans[ 'GP_ErrorCode' ] = $GP_submit->error->code;
                                //echo $GP_submit->error->code;
                                $risposta_trans[ 'GP_ErrorDescription' ] = $GP_submit->error->description;
                                //echo $GP_submit->error->description;
                            }

                        } else { // Errore GP Create Order
                            $risposta_trans[ 'Esito_trans' ] = "KO";
                            $risposta_trans[ 'GP_ErrorCode' ] = $GP_order->error->code ?? '';
                            $risposta_trans[ 'GP_ErrorDescription' ] = $GP_order->error->description ?? '';
                            error_log( date( '[Y-m-d H:i:s e] ' ) . "GestPay CreateOrder errore: " . json_encode( $GP_order->error ?? null ) . PHP_EOL, 3, LOG_FILE );
                        }
                        //4 PP Creo token
                        //Gestione errore?
                        $risposta[ 'Transazione' ] = $risposta_trans;
                    } elseif ( USE_STRIPE == true ) {
                        $query_data->SPtransactionType = "oneoff";
                        $SP_order = call_user_func_array( 'ChargeOrderStripe', array( $query_data ) );
                    }
                    else {

                    }
                } elseif ( $query_data->pay_method === "PP" && USE_PAYPAL == true ) { //pagamento con PayPal
                    //4 PP Creo token
                    $OAuth = CLIENT_ID_PP . ":" . SECRET_ID_PP;
                    $TokenPayPal = call_user_func( 'TokenPayPal', $OAuth );
                    // Token non ottenuto (credenziali errate o API non raggiungibile): errore gestito
                    if ( !is_array( $TokenPayPal ) || empty( $TokenPayPal[ 'access_token' ] ) ) {
                        error_log( date( '[Y-m-d H:i:s e] ' ) . "PayPal: token non ottenuto - " . json_encode( $TokenPayPal ) . PHP_EOL, 3, LOG_FILE );
                        $risposta_trans[ 'Esito_trans' ] = "KO";
                        $risposta_trans[ 'Messaggio_trans' ] = "Pagamento PayPal non disponibile";
                        $risposta_trans[ 'URL_trans' ] = "";
                        $risposta[ 'Transazione' ] = $risposta_trans;
                        echo json_encode( $risposta );
                        exit;
                    }
                    foreach ( $TokenPayPal as $key => $value ) { //scope,  access_token, token_type , app_id, expires_in, nonce
                        $query_data->$key = $value;
                    }
                    //5 PP Creo Ordine
                    $Id_OrderPayPal = call_user_func_array( 'CreateOrderPayPal', array( $query_data ) );
                    // Ordine non creato: l'id PayPal ha formato tipo "5O190127TN364715T"
                    if ( empty( $Id_OrderPayPal ) || !preg_match( '/^[A-Z0-9]{8,}$/', (string) $Id_OrderPayPal ) ) {
                        error_log( date( '[Y-m-d H:i:s e] ' ) . "PayPal: creazione ordine fallita - " . json_encode( $Id_OrderPayPal ) . PHP_EOL, 3, LOG_FILE );
                        $risposta_trans[ 'Esito_trans' ] = "KO";
                        $risposta_trans[ 'Messaggio_trans' ] = "Creazione ordine PayPal fallita";
                        $risposta_trans[ 'URL_trans' ] = "";
                        $risposta[ 'Transazione' ] = $risposta_trans;
                        echo json_encode( $risposta );
                        exit;
                    }
                    $query_data->Id_OrderPayPal = $Id_OrderPayPal;
                    //6 Scrivo ordine nel DB
                    call_user_func_array( 'ScriviOrderPayPal_mysql', array( $query_data ) );
                    $redirect_URL = PP_REDIRECT . "/checkoutnow?token=" . $Id_OrderPayPal;
                    $risposta_trans[ 'Esito_trans' ] = "PayPal";
                    $risposta_trans[ 'Messaggio_trans' ] = "&Egrave; necessari l'autenticazione su Paypal ";
                    $risposta_trans[ 'URL_trans' ] = $redirect_URL;
                    $risposta[ 'Transazione' ] = $risposta_trans;
                    if ( DEBUG == true ) {
                        error_log( date( '[Y-m-d H:i:s e] ' ) . "URL autorizzazone PAYPAL: " . $risposta_trans[ 'URL_trans' ] . PHP_EOL, 3, LOG_FILE );
                    }
                }
                elseif ( $query_data->pay_method === "SY" ) { //Pagamento con Satispay
                    require_once( '../vendor/autoload.php' );
                    $Payment_Statispay = call_user_func_array( 'SatispayGetPayment', array( $query_data ) );
                    //if ( DEBUG == true ) {
                    error_log( date( '[Y-m-d H:i:s e] ' ) . "SATISPAY_WS JSON Payment : " . json_encode( $Payment_Statispay ) . PHP_EOL, 3, LOG_FILE ); //DEBUG
                    //}
                    if ( empty( $Payment_Statispay ) ) {
                        // Creazione pagamento Satispay fallita (credenziali mancanti o API non raggiungibile)
                        $risposta_trans[ 'Esito_trans' ] = "KO";
                        $risposta_trans[ 'Messaggio_trans' ] = "Pagamento Satispay non disponibile";
                        $risposta_trans[ 'URL_trans' ] = "";
                        $risposta[ 'Transazione' ] = $risposta_trans;
                        $risposta_string = json_encode( $risposta );
                        echo $risposta_string;
                        exit;
                    }
                    foreach ( $Payment_Statispay as $key => $value ) {
                        //$query_data->SY_ . $key = $value;
                        $query_data->$key = $value;
                    }
                    call_user_func_array( 'ScriviOrderSatisPay_mysql', array( $query_data ) );
                    //$redirect_URL = "https://staging.online.satispay.com/pay/" . $query_data->id;
                    //$redirect_URL = SY_REDIRECT . "/pay/" . $query_data->id; // Pre SY HD
                    $redirect_URL = $query_data->redirect_url;
                    $risposta_trans[ 'Esito_trans' ] = "Satispay";
                    $risposta_trans[ 'Messaggio_trans' ] = "&Egrave; necessaria la transazione su Satispay ";
                    $risposta_trans[ 'URL_trans' ] = $redirect_URL;
                    $risposta[ 'Transazione' ] = $risposta_trans;
                    if ( DEBUG == true ) {
                        error_log( date( '[Y-m-d H:i:s e] ' ) . "DON_WS URL autorizzazone Satispay: " . $risposta_trans[ 'URL_trans' ] . PHP_EOL, 3, LOG_FILE );
                    }

                }
                else {
                    $risposta_trans[ 'Esito_trans' ] = "Altro";
                    $risposta_trans[ 'Messaggio_trans' ] = "Il sistema di pagamnto non &grave; supportato ";
                    $risposta_trans[ 'URL_trans' ] = "";
                    $risposta[ 'Transazione' ] = $risposta_trans;
                }
            } else { //errore scrittura anagrafica in mysql
                $risposta_don[ 'Esito_mysql' ] = "KO";
                $risposta_don[ 'Messaggio_mysql' ] = "Si &egrave; verificato un errore nella scrittura in MYSQL " . $id_donazione;
                $risposta_don[ 'CodTrans' ] = $id_donazione;
            }
            $risposta[ 'Donazione' ] = $risposta_don;
        } else { //errore scrittura anagrafica in mysql
            $risposta_ana[ 'Esito_mysql' ] = "KO";
            $risposta_ana[ 'Messaggio_mysql' ] = "Si &egrave; verificato un errore nella scrittura in MYSQL " . $id_anagrafica[ 0 ];
            $risposta_ana[ 'id_anagrafica_mysql' ] = $id_anagrafica[ 0 ];
        }
        $risposta[ 'Anagrafica' ] = $risposta_ana;
    }
    $risposta_string = json_encode( $risposta );
    echo $risposta_string;
    //ESITO - FINE
} elseif ( $query_action->operation == "do" && $query_action->param == "regular" ) {
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Donazione REGOLARE" . PHP_EOL, 3, LOG_FILE ); //DEBUG
    }
    // 1 Valido i dati del form
    $chk_data = call_user_func( 'ValidaDati' );
    if ( $chk_data[ 0 ] <> 0 ) {
        //$risposta ['Errori'] = $chk_data;
        $risposta_verificadati[ 'Esito' ] = "KO";
        $i = 0;
        foreach ( $chk_data[ 2 ] as $k => $v ) {
            $i++;
            $risposta_verificadati[ 'Errori' ][ $i ][ 'Codice' ] = $k;
            $risposta_verificadati[ 'Errori' ][ $i ][ 'Messaggio' ] = $v;
        }
        $risposta[ 'Errori' ] = $risposta_verificadati; // Modifica 'Errori' in 'Validazione'
    } else {
        $query_data->cvvcode = $query_data->cvv;
        // 1 Valido i dati del form
        $id_anagrafica = call_user_func_array( 'scriviAnagrafica_mysql', array( $query_data ) ); // Scrivo l'anagrafica in mysql
        if ( is_numeric( $id_anagrafica[ 0 ] ) ) {
            $risposta_ana[ 'Esito_mysql' ] = "OK";
            $risposta_ana[ 'Messaggio_mysql' ] = "&Egrave; stata scritta in MYSQL l'anagrafica " . $id_anagrafica[ 0 ];
            $risposta_ana[ 'id_anagrafica_mysql' ] = $id_anagrafica[ 0 ];
            $query_data->Id_a = $id_anagrafica[ 0 ]; // Aggiungo l'id mysql dell'anagrafia a query_data
            $query_data->CodicePersonale = $id_anagrafica[ 1 ]; // Aggiungo il Codice Personale a query_data
            if ( $query_data->pay_method === "CC" ) { //Ricorrente  con carta di credito
                if ( USE_GESTPAY == true ) {
                    $query_data->GPtransactionType = "regular";
                    $query_data->metodo = "CC"; // mandato a carta (per il cron di addebito ricorrente)
                    //3 Scrivo donazione in mysql con id anagrfica (2) (Se ho scritto l'anagrafica)
                    $id_donazione = call_user_func_array( 'ScriviDonazione_mysql', array( $query_data ) ); //Scrivo la donazione in mysql
                    if ( preg_match( "/^[A-Z]{1}-[0-9]{17}-[A-Z]{2}/", $id_donazione ) ) { //Verifico codice transazione (formato D-20170701608524665-DD)
                        $risposta_don[ 'Esito_mysql' ] = "OK";
                        $risposta_don[ 'Messaggio_mysql' ] = "&Egrave; stata scritta in MYSQL la donazione " . $id_donazione;
                        $risposta_don[ 'CodTrans' ] = $id_donazione;
                        $query_data->CodTrans = $id_donazione; // Aggiungo il codice di transazione a query_data
                        //4 Scrivo il mandato in mysql
                        $id_mandato = call_user_func_array( 'ScriviMandato_mysql', array( $query_data ) ); //Scrivo il mandato in mysql
                        if ( is_numeric( $id_mandato ) ) {
                            $query_data->Id_mandato = $id_mandato; // Aggiungo l'id mysql del mandato a query_data
                            $risposta_mandato[ 'Esito_mandato_mysql' ] = "OK";
                            $risposta_mandato[ 'Messaggio_mandato_mysql' ] = "&Egrave; stato scritto in MYSQL il mandato " . $id_mandato;
                            $risposta_mandato[ 'id_mandato_mysql' ] = $id_mandato;
                            //5 Genero Ordine
                            $GP_order = call_user_func_array( 'CreateOrderGestPay', array( $query_data ) );
                            if ( 0 == $GP_order->error->code ) { // Se ho creato l'ordine -INZIO
                                foreach ( $GP_order->payload as $k => $v ) {
                                    if ( is_string( $k ) ) {
                                        $query_data->$k = $v;
                                    } else {
                                        foreach ( $k->payload as $k1 => $v1 ) {
                                            $query_data->$k1 = $v1;
                                        }
                                    }
                                }
                                $risposta_order[ 'Esito_GestPay' ] = "OK";
                                $risposta_order[ 'Messaggio_GestPay' ] = "Creazione dell'ordine di GestPay " . $query_data->paymentID;
                                $risposta_order[ 'CodOrd_GestPay' ] = $query_data->paymentToken;
                                // Il primo addebito (submit) addebita la prima rata e, grazie a requestToken,
                                // restituisce il token della carta per gli addebiti ricorrenti futuri.
                                if ( true ) {
                                    if ( USE_MENTOR == true ) {
                                        //Verifica API -INZIO
                                        $url_ch = MENTOR_API_URL . "/wsc_table.ashx";
                                        $rtest = verifyURL( $url_ch );
                                        if ( $rtest[ 'stauts' ] ) { //SE API DIPONIBILE -INZIO 
                                            // 8 Scrivo l'anagrafica in mentor
                                            $id_anagrafica_mentor = call_user_func_array( 'scriviAnagrafica_mentor', array( $query_data ) );
                                            if ( preg_match( "/^[0-9]+/", $id_anagrafica_mentor[ 1 ] ) ) { //Se ho scritto l'anagrfica in Menor - INIZIO
                                                $risposta_ana_mentor[ 'Esito_mentor' ] = "OK";
                                                $risposta_ana_mentor[ 'Messaggio_mentor' ] = "&Egrave; stata scritta in Mentor l'anagrafica " . $id_anagrafica_mentor[ 1 ];
                                                $risposta_ana_mentor[ 'id_anagrafica_mentor' ] = $id_anagrafica_mentor[ 1 ];
                                                $query_data->codiceAnagraficaMentor = $id_anagrafica_mentor[ 1 ];
                                                $noe = explode( ";", $id_anagrafica_mentor[ 0 ] );
                                                $query_data->AnagraficaMentor_NoE = $noe[ 0 ]; //Anagriafica Nuova o Esistente 
                                                $risposta_ana_mentor[ 'NoE' ] = $noe[ 0 ]; // Aggiungo NOE
                                                $query_data->SessoMentor = $noe[ 1 ];
                                                //9 Scrivo la privacy in Mentor 
                                                if ( "nuova" == $query_data->AnagraficaMentor_NoE ) {
                                                    $wr_privacy = call_user_func_array( 'ScriviPrivacy_mentor', array( $query_data ) );
                                                }
                                                //10 Scrivo le specifiche  
                                                $wr_specs = call_user_func_array( 'ScriviSpecifiche_mentor', array( $query_data ) );
                                                //11 Scrivo le sottoscrizioni
                                                if ( "nuova" == $query_data->AnagraficaMentor_NoE ) {
                                                    $wr_subscription = call_user_func_array( 'ScriviSubscription_mentor', array( $query_data ) );
                                                }
                                                //12 Aggiorno l'anagrafica in mysql (2) con il codice Mentor dell'anagrafica (8)
                                                $ud_anagrafica = call_user_func_array( 'aggiornaAnagrafica_mysql', array( $query_data ) );
                                                //13 Scrivo il mandato in Mentor
                                                $id_mandato_Mentor = call_user_func_array( 'ScriviMandato_mentor', array( $query_data ) ); //Scrivo il mandato in mysql
                                                if ( preg_match( "/^[0-9]+/", $id_mandato_Mentor[ 0 ] ) ) {
                                                    $risposta_mandato_mentor[ 'Esito_mandato_mentor' ] = "OK";
                                                    $risposta_mandato_mentor[ 'Messaggio_mandato_mentor' ] = "&Egrave; stato scritto in Mentor il mandato " . $id_mandato_Mentor[ 0 ];
                                                    $risposta_mandato_mentor[ 'id_mandato_mentor' ] = $id_mandato_Mentor[ 0 ];
                                                    $query_data->id_mandato_Mentor = $id_mandato_Mentor[ 0 ];
                                                    //14 Aggiorna mandato mysql con codice mentor
                                                    $ud_mandato_mysql = call_user_func_array( 'aggiornaMandatoCodMentor_mysql', array( $query_data ) );
                                                    //15 Effettuo la transazione su gestpay con il codice transazione (3) e il token del donatore (5) e 16 Aggiorno la donazione in mysql (3) con l'esito
                                                    //9 Scrivo la privacy in Mentor 
                                                    //10 Scrivo le specifiche  
                                                    //11 Scrivo le sottoscrizioni
                                                } else {
                                                    $risposta_mandato_mentor[ 'Esito_mandato_mentor' ] = "KO";
                                                    $risposta_mandato_mentor[ 'Messaggio_mandato_mentor' ] = "Si &egrave; verificato un errore nella scrittura in Mentor" . $id_mandato_Mentor[ 0 ];
                                                    $risposta_mandato_mentor[ 'id_mandato_mentor' ] = $id_mandato_Mentor[ 0 ];
                                                    $query_data->errore_mandato_Mentor = $id_mandato_Mentor[ 0 ];
                                                    //$ud_mandato_mysql = call_user_func_array( 'aggiornaMandatoCodMentor_mysql', array( $query_data ) );
                                                }
                                                $risposta[ 'MandatoMentor' ] = $risposta_mandato_mentor;
                                            } //SE API DIPONIBILE - FINE 
                                            else { //SE API NON DIPONIBILE
                                                $risposta_ana_mentor[ 'Esito_mentor' ] = "KO";
                                                $risposta_ana_mentor[ 'Messaggio_mentor' ] = "Si &egrave; verificato un errore nella scrittura in Mentor " . $id_anagrafica_mentor[ 1 ];
                                                $risposta_ana_mentor[ 'id_anagrafica_menotr' ] = $id_anagrafica_mentor[ 1 ];
                                            }
                                            $risposta[ 'AnagraficaMentor' ] = $risposta_ana_mentor;
                                        } //SE API DIPONIBILE - FINE 
                                        else { //SE API NON DIPONIBILE
                                        }
                                    }
                                    // 16 Effettuo l'incasso del TOKEN
                                    $GP_submit = call_user_func_array( 'SubmitOrderGestPay', array( $query_data ) ); // Scrivo l'anagrafica in mysql
                                    if ( 0 == $GP_submit->error->code ) {
                                        foreach ( $GP_submit->payload as $k => $v ) {
                                            if ( is_string( $k ) ) {
                                                $query_data->$k = $v;
                                            } else {
                                                foreach ( $k->payload as $k1 => $v1 ) {
                                                    $query_data->$k1 = $v1;
                                                }
                                            }
                                        }
                                        // Il token della carta NON è nella risposta del submit ma nel dettaglio
                                        // del pagamento: lo recupero con GetOrderGestPay e lo salvo nel mandato.
                                        $gp_detail = call_user_func_array( 'GetOrderGestPay', array( $query_data ) );
                                        $gp_token = ( is_object( $gp_detail ) && isset( $gp_detail->payload->token ) ) ? $gp_detail->payload->token : '';
                                        if ( !empty( $gp_token ) ) {
                                            $query_data->GP_token = $gp_token;
                                            $query_data->GP_tokenExpiryMonth = $gp_detail->payload->tokenExpiryMonth ?? '';
                                            $query_data->GP_tokenExpiryYear = $gp_detail->payload->tokenExpiryYear ?? '';
                                            $query_data->errore_mandato_Mentor = '';
                                            call_user_func_array( 'aggiornaMandatoToken_mysql', array( $query_data ) );
                                        }
                                        //17 Scrivo l'ordine GestPay in mySQL
                                        $id_order = call_user_func_array( 'ScriviOrderGestPay_mysql', array( $query_data ) ); // Scrivo l'anagrafica in mysql
                                        if ( is_numeric( $id_order ) ) {
                                            if ( "8006" == $query_data->transactionErrorCode ) { //3D
                                                $risposta_trans[ 'Esito_trans' ] = "3D";
                                                $risposta_trans[ 'Messaggio_trans' ] = "&Egrave; necessaria l'autenticazione 3D Secure";
                                                $risposta_trans[ 'URL_trans' ] = $query_data->userRedirect->href;
                                                $query_data->EsitoDonazione = $query_data->transactionResult;
                                                //if ( DEBUG == true ) {
                                                error_log( date( '[Y-m-d H:i:s e] ' ) . "donation_WS.php Regular CC with 3DS" . PHP_EOL, 3, LOG_FILE );
                                                //}
                                            } else { //NO 3D
                                                // 18 Aggiorno esito in Donazone
                                                call_user_func( 'AggiornaDonazioneNo3DGP', $query_data->transactionResult, $query_data->CodTrans );
                                                if ( "OK" == $query_data->transactionResult ) { // transazione no 3D con esito positivo - INIZIO
                                                    //if ( DEBUG == true ) {
                                                    if ( USE_MENTOR == true ) {
                                                        $id_donazione_mentor = call_user_func_array( 'ScriviDonazione_mentor', array( $query_data ) );
                                                        if ( !preg_match( "/^[0-9]+/", $id_donazione_mentor ) ) {
                                                            $risposta_dona_mentor[ 'Esito_mentor' ] = "KO";
                                                            $risposta_dona_mentor[ 'Messaggio_mentor' ] = "Si &egrave; verificato un errore nella scrittura in Mentor " . $id_donazione_mentor;
                                                            $risposta_dona_mentor[ 'id_donazione_mentor' ] = $id_donazione_mentor;
                                                        } else {
                                                            $risposta_dona_mentor[ 'Esito_mentor' ] = "OK";
                                                            $risposta_dona_mentor[ 'Messaggio_mentor' ] = "&Egrave; stata scritta in Mentor la donazione " . $id_donazione_mentor;
                                                            $risposta_dona_mentor[ 'id_donazione_mentor' ] = $id_donazione_mentor;
                                                            $query_data->codiceDonazioneMentor = $id_donazione_mentor;
                                                            //12 Aggiorno la donazione in mysql (3) con il codice Mentor della donazione (9)
                                                            $ud_donazionecodMentor = call_user_func_array( 'aggiornaDonazioneCodMentor_mysql', array( $query_data ) );
                                                        }
                                                        $risposta[ 'DonazioneMentor' ] = $risposta_dona_mentor;
                                                    }
                                                    // Primo addebito ricorrente OK: attivo il mandato e calcolo la prossima data
                                                    if ( isset( $query_data->Id_mandato ) ) {
                                                        call_user_func_array( 'attivaMandato_mysql', array( $query_data->Id_mandato, $query_data->frequenza ?? 1 ) );
                                                    }
                                                    $redirect_url = FORM_THANK_YOU_PAGE . "?CodTrans=" . $query_data->CodTrans;
                                                    // transazione no 3D con esito positivo - FINE
                                                } else { // transazione no 3D con esito negativo
                                                    if ( USE_MENTOR == true ) {
                                                        $query_data->att_tipo = "12";
                                                        $query_data->att_sottotipo = "1201";
                                                        $query_data->att_oggetto = "DR Cca KO/WA web";
                                                        $query_data->att_stato = "2";
                                                        $wr_attivitaMentor = call_user_func_array( 'ScriviAttivita_mentor', array( $query_data ) );
                                                        $risposta[ 'Attivita' ] = $wr_attivitaMentor;
                                                        if ( DEBUG == true ) {
                                                            error_log( date( '[Y-m-d H:i:s e] ' ) . "donation_WS.php scrivo attivita 1201 NO 3DS" . PHP_EOL, 3, LOG_FILE );
                                                        }
                                                    }
                                                    $redirect_url = FORM_ERROR_PAGE;
                                                }
                                                $risposta_trans[ 'Esito_trans' ] = $query_data->transactionResult;
                                                $risposta_trans[ 'Messaggio_trans' ] = $query_data->transactionErrorDescription;
                                                $risposta_trans[ 'URL_trans' ] = $redirect_url;
                                                $query_data->EsitoDonazione = $query_data->transactionResult;
                                            }
                                            $risposta[ 'Transazione' ] = $risposta_trans;
                                        }
                                        //print_r($GP_submit);
                                    } else { //Errore GP Submit Order
                                        $risposta_trans[ 'Esito_trans' ] = "KO";
                                        $risposta_trans[ 'GP_ErrorCode' ] = $GP_submit->error->code ?? '';
                                        $risposta_trans[ 'GP_ErrorDescription' ] = $GP_submit->error->description ?? '';
                                        $risposta[ 'Transazione' ] = $risposta_trans;
                                        error_log( date( '[Y-m-d H:i:s e] ' ) . "GestPay regular Submit errore: " . json_encode( $GP_submit->error ?? null ) . PHP_EOL, 3, LOG_FILE );
                                    }
                                } // fine primo addebito
                            } // Se ho creato l'ordine -FINE
                            else { // Errore GP Create Order
                                $risposta_order[ 'Esito_GestPay' ] = "KO";
                                $risposta_order[ 'Messaggio_GestPay' ] = "Si &egrave; verificato un errore nella creazione dell'ordine di GestPay: " . $GP_order->error->code;
                                $risposta_order[ 'CodOrd_GestPay' ] = "";
                            }
                        } else { // Errore Mandato
                            $risposta_mandato[ 'Esito_mandato_mysql' ] = "KO";
                            $risposta_mandato[ 'Messaggio_mandato_mysql' ] = "Si &egrave; verificato un errore nella scrittura del mandato in MYSQL " . $id_mandato;
                            $risposta_mandato[ 'id_mandato_mysql' ] = $id_mandato;
                        }
                        $risposta[ 'Mandato' ] = $risposta_mandato;
                    } else {
                        $risposta_don[ 'Esito_mysql' ] = "KO";
                        $risposta_don[ 'Messaggio_mysql' ] = "Si &egrave; verificato un errore nella scrittura della donazione in MYSQL " . $id_donazione;
                        $risposta_don[ 'CodTrans' ] = $id_donazione;
                    }
                    $risposta[ 'Donazione' ] = $risposta_don;
                } elseif ( USE_STRIPE == true ) { // Regolare con Stripe

                }
            } else { //SDD
                if ( DEBUG == true ) {
                    error_log( date( '[Y-m-d H:i:s e] ' ) . "Donazione SDD" . PHP_EOL, 3, LOG_FILE ); //DEBUG
                }
                //3 Scrivo il mandato in mysql con id anagrfica (2) (Se ho scritto l'anagrafica)	
                $id_mandato = call_user_func_array( 'ScriviMandato_mysql', array( $query_data ) ); //Scrivo il mandato in mysql
                if ( is_numeric( $id_mandato ) ) {
                    $risposta_mandato[ 'Esito_mandato_mysql' ] = "OK";
                    $risposta_mandato[ 'Messaggio_mandato_mysql' ] = "&Egrave; stato scritto in MYSQL il mandato " . $id_mandato;
                    $risposta_mandato[ 'id_mandato_mysql' ] = $id_mandato;
                    $query_data->Id_mandato = $id_mandato; // Aggiungo l'id mysql del mandato a query_data
                    if ( USE_MENTOR == true ) {
                        $id_anagrafica_mentor = call_user_func_array( 'scriviAnagrafica_mentor', array( $query_data ) ); // Scrivo l'anagrafica in mentor
                        if ( preg_match( "/^[0-9]+/", $id_anagrafica_mentor[ 1 ] ) ) {
                            $risposta_ana_mentor[ 'Esito_mentor' ] = "OK";
                            $risposta_ana_mentor[ 'Messaggio_mentor' ] = "&Egrave; stata scritta in Mentor l'anagrafica " . $id_anagrafica_mentor[ 1 ];
                            $risposta_ana_mentor[ 'id_anagrafica_mentor' ] = $id_anagrafica_mentor[ 1 ];
                            $query_data->codiceAnagraficaMentor = $id_anagrafica_mentor[ 1 ];
                            $noe = explode( ";", $id_anagrafica_mentor[ 0 ] );
                            $query_data->AnagraficaMentor_NoE = $noe[ 0 ]; //Anagriafica Nuova o Esistente 
                            $risposta_ana_mentor[ 'NoE' ] = $noe[ 0 ]; // Aggiungo NOE
                            $query_data->SessoMentor = $noe[ 1 ];
                            //6 Scrivo la privacy in Mentor 
                            if ( "nuova" == $query_data->AnagraficaMentor_NoE ) {
                                $wr_privacy = call_user_func_array( 'ScriviPrivacy_mentor', array( $query_data ) );
                            }
                            //7 Scrivo le specifiche in Mentor
                            $wr_specs = call_user_func_array( 'ScriviSpecifiche_mentor', array( $query_data ) );
                            //8 Scrivo le sottoscrizioni in Mentor
                            if ( "nuova" == $query_data->AnagraficaMentor_NoE ) {
                                $wr_subscription = call_user_func_array( 'ScriviSubscription_mentor', array( $query_data ) );
                            }
                            //9 Aggiorno l'anagrafica in mysql (1) con il codice Mentor dell'anagrafica (5)
                            $ud_anagrafica = call_user_func_array( 'aggiornaAnagrafica_mysql', array( $query_data ) ); // aggiorno l'anagrafica in mysql	
                            $id_mandato_Mentor = call_user_func_array( 'ScriviMandato_mentor', array( $query_data ) ); //Scrivo il mandato in mysql
                            if ( preg_match( "/^[0-9]+/", $id_mandato_Mentor[ 0 ] ) ) {
                                $risposta_mandato_mentor[ 'Esito_mandato_mentor' ] = "OK";
                                $risposta_mandato_mentor[ 'Messaggio_mandato_mentor' ] = "&Egrave; stato scritto in Mentor il mandato " . $id_mandato_Mentor[ 0 ];
                                $risposta_mandato_mentor[ 'id_mandato_mentor' ] = $id_mandato_Mentor[ 0 ];
                                $query_data->id_mandato_Mentor = $id_mandato_Mentor[ 0 ];
                                //6 Scrivo la privacy in Mentor 
                                $ud_mandato_mysql = call_user_func_array( 'aggiornaMandatoCodMentor_mysql', array( $query_data ) );
                            } else {
                                $risposta_mandato_mentor[ 'Esito_mandato_mentor' ] = "KO";
                                $risposta_mandato_mentor[ 'Messaggio_mandato_mentor' ] = "Si &egrave; verificato un errore nella scrittura in Mentor" . $id_mandato_Mentor[ 0 ];
                                $risposta_mandato_mentor[ 'id_mandato_mentor' ] = $id_mandato_Mentor[ 0 ];
                                $query_data->errore_mandato_Mentor = $id_mandato_Mentor[ 0 ];
                                $ud_mandato_mysql = call_user_func_array( 'aggiornaMandatoCodMentor_mysql', array( $query_data ) );
                            }
                            $risposta[ 'MandatoMentor' ] = $risposta_mandato_mentor;
                            $risposta_mandato[ 'Esito_mandato_mysql' ] = "OK";
                            $risposta_mandato[ 'Messaggio_mandato_mysql' ] = "&Egrave; stato scritto in MYSQL il mandato " . $id_mandato;
                            $risposta_mandato[ 'id_mandato_mysql' ] = $id_mandato;
                        } else { //Errore di scrittura anagrafica in mentor
                            $risposta_ana_mentor[ 'Esito_mentor' ] = "KO";
                            $risposta_ana_mentor[ 'Messaggio_mentor' ] = "Si &egrave; verificato un errore nella scrittura in Mentor " . $id_anagrafica_mentor[ 1 ];
                            $risposta_ana_mentor[ 'id_anagrafica_menotr' ] = $id_anagrafica_mentor[ 1 ];
                        }
                        $risposta[ 'AnagraficaMentor' ] = $risposta_ana_mentor;
                    }
                } else { // Errore Mandato
                    $risposta_mandato[ 'Esito_mandato_mysql' ] = "KO";
                    $risposta_mandato[ 'Messaggio_mandato_mysql' ] = "Si &egrave; verificato un errore nella scrittura in MYSQL del mandato " . $id_mandato;
                    $risposta_mandato[ 'id_mandato_mysql' ] = $id_mandato;
                }
                $risposta[ 'Mandato' ] = $risposta_mandato;
            }
        } else { //errore scrittura anagrafica in mysql
            $risposta_ana[ 'Esito_mysql' ] = "KO";
            $risposta_ana[ 'Messaggio_mysql' ] = "Si &egrave; verificato un errore nella scrittura in MYSQL " . $id_anagrafica[ 0 ];
            $risposta_ana[ 'id_anagrafica_mysql' ] = $id_anagrafica[ 0 ];
        }
        $risposta[ 'Anagrafica' ] = $risposta_ana;
    }
    $risposta_string = json_encode( $risposta );
    echo $risposta_string;
}
elseif ( $query_action->operation == "save" && $query_action->param == "GestPay3D" ) {
    if ( DEBUG == true ) {
        $debugWS = json_encode( $query_data ); //DEBUG
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Paramentri chiamata WS save 3d: " . strip_log_CC( $debugWS ) . PHP_EOL, 3, LOG_FILE ); //DEBUG
    }
    // Sanitizzare i dati prima di usarli
    //$query_data = sanitizeInput($query_data);
    //5 Aggiorno la donazione in mysql (3) con l'esito 
    call_user_func_array( 'aggiornaEsitoDonazione', array( $query_data ) ); // Scrivo l'anagrafica in mysql
    //call_user_func_array( 'AggiornaDonazione3D', array( $query_data ) ); // Scrivo l'anagrafica in mysql
    // UPDATE GetPayREST - INIZIO
    $GP_order_status = call_user_func_array( 'GetOrderGestPay', array( $query_data ) );
    //if ( DEBUG == true ) {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "DON_WS risposta GetOrderGestPay: " . json_encode( $GP_order_status ) . PHP_EOL, 3, LOG_FILE ); //DEBUG
    //}
    foreach ( $GP_order_status->payload as $k => $v ) {
        if ( is_string( $k ) ) {
            $query_data->$k = $v;
        } else {
            foreach ( $k->payload as $k1 => $v1 ) {
                $query_data->$k1 = $v1;
            }
        }
    }
    //if ( DEBUG == true ) {
    $debugWS = json_encode( $query_data ); //DEBUG
    error_log( date( '[Y-m-d H:i:s e] ' ) . "DON_WS Paramentri chiamata AggiornaGetPayREST: " . strip_log_CC( $debugWS ) . PHP_EOL, 3, LOG_FILE ); //DEBUG
    //}
    call_user_func_array( 'AggiornaGetPayREST', array( $query_data ) );
    // UPDATE GetPayREST - FINE
    if ( $query_data->TransactionResult == "OK" ) {
        $query_data->EsitoDonazione = $query_data->TransactionResult;
        // Se è una donazione regolare, attivo il mandato del donatore dopo il 3DS andato a buon fine
        // e salvo l'eventuale token della carta restituito da GetOrderGestPay (per gli addebiti ricorrenti).
        if ( isset( $query_data->tipo ) && $query_data->tipo == "regular" && isset( $query_data->Id_a ) ) {
            call_user_func_array( 'attivaMandatoDonatore_mysql', array(
                $query_data->Id_a,
                $query_data->token ?? '',
                $query_data->tokenExpiryMonth ?? '',
                $query_data->tokenExpiryYear ?? ''
            ) );
        }
        if ( USE_MENTOR == true ) {
            //6 Scrivo l'anagrafica in Mentor (se la transazione è andata a buon fine)
            $id_anagrafica_mentor = call_user_func_array( 'scriviAnagrafica_mentor', array( $query_data ) ); // Scrivo l'anagrafica in mentor
            if ( preg_match( "/^[0-9]+/", $id_anagrafica_mentor[ 1 ] ) ) {
                $risposta_ana_mentor[ 'Esito_mentor' ] = "OK";
                $risposta_ana_mentor[ 'Messaggio_mentor' ] = "&Egrave; stata scritta in Mentor l'anagrafica " . $id_anagrafica_mentor[ 1 ];
                $risposta_ana_mentor[ 'id_anagrafica_mentor' ] = $id_anagrafica_mentor[ 1 ];
                $query_data->codiceAnagraficaMentor = $id_anagrafica_mentor[ 1 ];
                $noe = explode( ";", $id_anagrafica_mentor[ 0 ] );
                $query_data->AnagraficaMentor_NoE = $noe[ 0 ]; //Anagriafica Nuova o Esistente
                $risposta_ana_mentor[ 'NoE' ] = $noe[ 0 ]; // Aggiungo NOE
                $query_data->SessoMentor = $noe[ 1 ];
                //7 Scrivo la privacy in Mentor 
                if ( "nuova" == $query_data->AnagraficaMentor_NoE ) {
                    $wr_privacy = call_user_func_array( 'ScriviPrivacy_mentor', array( $query_data ) );
                }
                //8 Scrivo le specifiche in Mentor 
                $wr_specs = call_user_func_array( 'ScriviSpecifiche_mentor', array( $query_data ) );
                //9 Scrivo le sottoscrizioni In Mentor
                if ( "nuova" == $query_data->AnagraficaMentor_NoE ) {
                    $wr_subscription = call_user_func_array( 'ScriviSubscription_mentor', array( $query_data ) );
                }
                //10 Aggiorno l'anagrafica in mysql (2) con il codice Mentor dell'anagrafica (6)
                $ud_anagrafica = call_user_func_array( 'aggiornaAnagrafica_mysql', array( $query_data ) ); // aggiorno l'anagrafica in mysql	
                //11 Scrivo la donazione in Mentor 
                $id_donazione_mentor = call_user_func_array( 'ScriviDonazione_mentor', array( $query_data ) );
                if ( !preg_match( "/^[0-9]+/", $id_donazione_mentor ) ) {
                    $risposta_dona_mentor[ 'Esito_mentor' ] = "KO";
                    $risposta_dona_mentor[ 'Messaggio_mentor' ] = "Si &egrave; verificato un errore nella scrittura in Mentor " . $id_donazione_mentor;
                    $risposta_dona_mentor[ 'id_donazione_mentor' ] = $id_donazione_mentor;
                } else {
                    $risposta_dona_mentor[ 'Esito_mentor' ] = "OK";
                    $risposta_dona_mentor[ 'Messaggio_mentor' ] = "&Egrave; stata scritta in Mentor la donazione " . $id_donazione_mentor;
                    $risposta_dona_mentor[ 'id_donazione_mentor' ] = $id_donazione_mentor;
                    $query_data->codiceDonazioneMentor = $id_donazione_mentor;
                    //12 Aggiorno la donaione in mysql (2) con il codice Mentor della donazione (4)
                    $ud_donazionecodMentor = call_user_func_array( 'aggiornaDonazioneCodMentor_mysql', array( $query_data ) );
                }
                $risposta[ 'DonazioneMentor' ] = $risposta_dona_mentor;
                //Tessera in regalo
                if ( $query_data->centro == TESSERA_GIFT ) {
                    // 13 Aggiorno i donati in mysql
                    $ud_donati = call_user_func_array( 'aggiornaDonati_mysql', array( $query_data ) );
                    $risposta[ 'Donati' ] = $ud_donati;
                    // 14 Scrivo l'attività per tesserain regalo
                    $query_data->att_tipo = "2";
                    $query_data->att_sottotipo = "0205";
                    $query_data->att_stato = "2";
                    $query_data->att_oggetto = TESSERA_DESC . " REGALATA";
                    //$query_data->att_note ="";
                    $wr_attivitaMentor = call_user_func_array( 'ScriviAttivita_mentor', array( $query_data ) );
                    $risposta[ 'Attivita' ] = $wr_attivitaMentor;
                }
                //
            } else { //Errore di scrittura anagrafica in mentor
                $risposta_ana_mentor[ 'Esito_mentor' ] = "KO";
                $risposta_ana_mentor[ 'Messaggio_mentor' ] = "Si &egrave; verificato un errore nella scrittura in Mentor " . $id_anagrafica_mentor[ 1 ];
                $risposta_ana_mentor[ 'id_anagrafica_menotr' ] = $id_anagrafica_mentor[ 1 ];
            }
            $risposta[ 'AnagraficaMentor' ] = $risposta_ana_mentor;
        } else { // Valutare cosa c'è da fare se non uso mentor 

        }
		/* // spostato su grazie.php
        //Magnews - Fine    
        if ( USE_MAGNEWS == true ) {
            //Magnews - Inizio
            $dataset = array(
                "email" => $query_data->mail,
                "nome" => $query_data->nome,
                "cognome" => $query_data->cognome,
                "tel" => $query_data->tel,
                "CodiceReferral"=>$query_data->CodiceReferral,
                "CodicePersonale"=>$query_data->CodicePersonale,
                "db" => 1,
                "Id_a" => $query_data->Id_a,
            );
            $mn_operation = call_user_func_array( 'Upsert_Magnews', array( $dataset ) ); // Scrivo il contatto in magnews
            if ( is_array( $mn_operation ) ) {
                $dataset += [
                    "id_donatore" => $mn_operation[ 'idcontact' ], // WR + Id_a
                    "codice_donazione" => $query_data->CodTrans, //CodTrans
                    "importo" => $query_data->importo, //Importo
                    "campagna" => $query_data->id_campagna,
                    "modalita_pagamento" => $query_data->pay_method, //pay_method
                    "piattaforma" => "Almabox"
                ];
                //$mn_update = call_user_func_array( 'Upsert_Magnews', array( $dataset ) ); // Scrivo il contatto in magnews
                //$mn_donation = call_user_func_array( 'AddDonation_Magnews', array( $dataset ) ); // Scrivo il contatto in magnews
            }


        }*/ // spostato su grazie.php
        //Magnews - Fine    
    } else { //transazione NON OK
        $query_data->EsitoDonazione = $query_data->TransactionResult;
        if ( $query_data->centro == TESSERA_GIFT ) {
            //Magnews - Fine    
            $ud_donati = call_user_func_array( 'aggiornaDonati_mysql', array( $query_data ) );
            $risposta[ 'Donati' ] = $ud_donati;
        }

        $risposta_trans[ 'Esito_transazione' ] = $query_data->TransactionResult;
        $risposta_trans[ 'Messaggio_transazionee' ] = "La transazione ha avuto esito " . $query_data->TransactionResult;
        $risposta_trans[ 'Transazione' ] = $risposta_trans;
    }
    if ( isset( $risposta ) )$risposta_string = json_encode( $risposta );
    else {
        $risposta_string = "OK";
    }
    echo $risposta_string;
}
elseif ( $query_action->operation == "save" && $query_action->param == "paypalcheckout" ) {

    $PPquery_stream = json_decode( $query_json );
    $datieme = $PPquery_stream->data->E;
    $datipp = $PPquery_stream->data->PP;
    $paymentpp = $datipp->purchase_units[ '0' ]->payments->captures[ '0' ];
    $seller_receivable_breakdown = $paymentpp->seller_receivable_breakdown;
    $query_data->Order = $datipp->id;
    $query_data->PP_esito = $datipp->PP_esito;
    $query_data->Status = $datipp->status;
    $query_data->PP_given_name = $datipp->payer->name->given_name;
    $query_data->PP_surname = $datipp->payer->name->surname;
    $query_data->PP_email_address = $datipp->payer->email_address;
    $query_data->payer_id = $datipp->payer->payer_id;
    $query_data->Payment = $paymentpp->id;
    $query_data->invoice_id = $paymentpp->invoice_id;
    $query_data->custom_id = $paymentpp->custom_id;
    $query_data->create_time = $paymentpp->create_time;
    $query_data->update_time = $paymentpp->update_time;
    $query_data->gross_amount_currency_code = $seller_receivable_breakdown->gross_amount->currency_code;
    $query_data->gross_amount_value = $seller_receivable_breakdown->gross_amount->value;
    $query_data->paypal_fee_currency_code = $seller_receivable_breakdown->paypal_fee->currency_code;
    $query_data->paypal_fee_value = $seller_receivable_breakdown->paypal_fee->value;
    $query_data->net_amount_currency_code = $seller_receivable_breakdown->net_amount->currency_code;
    $query_data->net_amount_value = $seller_receivable_breakdown->net_amount->value;
    foreach ( $datieme as $key => $value ) {
        $query_data->$key = trim( $value );
    }
    //echo "query data per pp" . json_encode($query_data);

    //10 - Aggiorno la donazione in mysql (3) con l'esito
    call_user_func_array( 'aggiornaEsitoDonazione', array( $query_data ) );

    //11 - Aggiorno l'ordine PP
    call_user_func_array( 'aggiornaOrdinePP', array( $query_data ) );
    $query_data->EsitoDonazione = $query_data->PP_esito;
    if ( "OK" == $query_data->PP_esito ) {
        if ( USE_MENTOR == true ) {
            //12 - Scrivo l'anagrafica in Mentor 
            $id_anagrafica_mentor = call_user_func_array( 'scriviAnagrafica_mentor', array( $query_data ) ); // Scrivo l'anagrafica in mentor
            if ( preg_match( "/^[0-9]+/", $id_anagrafica_mentor[ 1 ] ) ) {
                $risposta_ana_mentor[ 'Esito_mentor' ] = "OK";
                $risposta_ana_mentor[ 'Messaggio_mentor' ] = "&Egrave; stata scritta in Mentor l'anagrafica " . $id_anagrafica_mentor[ 1 ];
                $risposta_ana_mentor[ 'id_anagrafica_mentor' ] = $id_anagrafica_mentor[ 1 ];
                $query_data->codiceAnagraficaMentor = $id_anagrafica_mentor[ 1 ];
                $noe = explode( ";", $id_anagrafica_mentor[ 0 ] );
                $query_data->AnagraficaMentor_NoE = $noe[ 0 ]; //Anagriafica Nuova o Esistente 
                $risposta_ana_mentor[ 'NoE' ] = $noe[ 0 ]; // Aggiungo NOE
                $query_data->SessoMentor = $noe[ 1 ];
                //12 - Scrivo l'anagrafica in Mentor 
                if ( "nuova" == $query_data->AnagraficaMentor_NoE ) {
                    $wr_privacy = call_user_func_array( 'ScriviPrivacy_mentor', array( $query_data ) );
                }
                //14 Scrivo le specifiche in Mentor 
                $wr_specs = call_user_func_array( 'ScriviSpecifiche_mentor', array( $query_data ) );
                //15 Scrivo le sottoscrizioni in Mentor  
                if ( "nuova" == $query_data->AnagraficaMentor_NoE ) {
                    $wr_subscription = call_user_func_array( 'ScriviSubscription_mentor', array( $query_data ) );
                }
                //16 Aggiorno l'anagrafica in mysql (2) con il codice Mentor dell'anagrafica (6)  
                $ud_anagrafica = call_user_func_array( 'aggiornaAnagrafica_mysql', array( $query_data ) ); // aggiorno l'anagrafica in mysql	
                //17 Scrivo la donazione in Mentor 
                $id_donazione_mentor = call_user_func_array( 'ScriviDonazione_mentor', array( $query_data ) );
                if ( !preg_match( "/^[0-9]+/", $id_donazione_mentor ) ) {
                    $risposta_dona_mentor[ 'Esito_mentor' ] = "KO";
                    $risposta_dona_mentor[ 'Messaggio_mentor' ] = "Si &egrave; verificato un errore nella scrittura in Mentor " . $id_donazione_mentor;
                    $risposta_dona_mentor[ 'id_donazione_mentor' ] = $id_donazione_mentor;
                } else {
                    $risposta_dona_mentor[ 'Esito_mentor' ] = "OK";
                    $risposta_dona_mentor[ 'Messaggio_mentor' ] = "&Egrave; stata scritta in Mentor la donazione " . $id_donazione_mentor;
                    $risposta_dona_mentor[ 'id_donazione_mentor' ] = $id_donazione_mentor;
                    $query_data->codiceDonazioneMentor = $id_donazione_mentor;
                    //17 Aggiorno la donazione in mysql (3) con il codice Mentor della donazione (9)
                    $ud_donazionecodMentor = call_user_func_array( 'aggiornaDonazioneCodMentor_mysql', array( $query_data ) );
                }
                $risposta[ 'DonazioneMentor' ] = $risposta_dona_mentor;
                //17 Aggiorno la donazione in mysql (3) con il codice Mentor della donazione (9)
                if ( $query_data->centro == TESSERA_GIFT ) {
                    // 18 Aggiorno i donati in mysql
                    $ud_donati = call_user_func_array( 'aggiornaDonati_mysql', array( $query_data ) );
                    $risposta[ 'Donati' ] = $ud_donati;
                    // 19 Scrivo l'attività per tesserain regalo
                    $query_data->att_tipo = "2";
                    $query_data->att_sottotipo = "0205";
                    $query_data->att_stato = "2";
                    $query_data->att_oggetto = TESSERA_DESC . " REGALATA";
                    $wr_attivitaMentor = call_user_func_array( 'ScriviAttivita_mentor', array( $query_data ) );
                    $risposta[ 'Attivita' ] = $wr_attivitaMentor;
                }
            } else { //Errore di scrittura anagrafica in mentor
                $risposta_ana_mentor[ 'Esito_mentor' ] = "KO";
                $risposta_ana_mentor[ 'Messaggio_mentor' ] = "Si &egrave; verificato un errore nella scrittura in Mentor " . $id_anagrafica_mentor[ 0 ];
                $risposta_ana_mentor[ 'id_anagrafica_mentor' ] = $id_anagrafica_mentor[ 1 ];
            }
            $risposta[ 'AnagraficaMentor' ] = $risposta_ana_mentor;
        }
        $risposta_trans[ 'Esito_transazione' ] = $query_data->PP_esito;
        if ( USE_MAGNEWS == true ) {
            //Magnews - Inizio
            $dataset = array(
                "email" => $query_data->mail,
                "nome" => $query_data->nome,
                "cognome" => $query_data->cognome,
                "tel" => $query_data->tel,
                "CodiceReferral"=>$query_data->CodiceReferral,
                "CodicePersonale"=>$query_data->CodicePersonale,
                "db" => 1,
                "Id_a" => $query_data->Id_a,
            );
            $mn_operation = call_user_func_array( 'Upsert_Magnews', array( $dataset ) ); // Scrivo il contatto in magnews
            if ( is_array( $mn_operation ) ) {
                $dataset += [
                    "id_donatore" => $mn_operation[ 'idcontact' ], // WR + Id_a
                    "codice_donazione" => $query_data->CodTrans, //CodTrans
                    "importo" => $query_data->importo, //Importo
                    //"data_donazione" => "15/05/2024 10:05", //data
                    "campagna" => $query_data->id_campagna,
                    "modalita_pagamento" => $query_data->pay_method, //pay_method
                    "piattaforma" => "Almabox"
                ];
                //$mn_update = call_user_func_array( 'Upsert_Magnews', array( $dataset ) ); // Scrivo il contatto in magnews
                $mn_donation = call_user_func_array( 'AddDonation_Magnews', array( $dataset ) ); // Scrivo il contatto in magnews
            }


        }
        //Magnews - Fine
    } else { // transazione PAYPAL con esito negativo
        if ( $query_data->centro == TESSERA_GIFT ) {
            $ud_donati = call_user_func_array( 'aggiornaDonati_mysql', array( $query_data ) );
            $risposta[ 'Donati' ] = $ud_donati;
        }

        $risposta_trans[ 'Esito_transazione' ] = $query_data->PP_esito;
        $risposta_trans[ 'Messaggio_transazionee' ] = $query_data->PP->details->{0}->description;
        $risposta_trans[ 'Transazione' ] = $query_data->PP->details->{0}->issue;

    }

    $risposta[ 'Transazione' ] = $risposta_trans;
}
elseif ( $query_action->operation == "save" && $query_action->param == "satispay" ) {
    $SYquery_stream = json_decode( $query_json );
    $datieme = $SYquery_stream->data->E;
    $datisy = $SYquery_stream->data->SY;
    $query_data->SY_id = $datisy->id;
    $query_data->amount_unit = $datisy->amount_unit;
    $query_data->SY_status = $datisy->status;
    $query_data->sender_id = $datisy->sender->id;
    $query_data->sender_name = $datisy->sender->name;

    foreach ( $datieme as $key => $value ) {
        $query_data->$key = trim( $value );
    }
    //1 - Aggiorno la donazione in mysql (3) con l'esito
    if ( "ACCEPTED" == $datisy->status ) {
        $query_data->esito = "OK";
    } elseif ( "CANCELED" == $datisy->status ) {
        $query_data->esito = "KO";
    }
    else {
        $query_data->esito = "WA";
    }
    $query_data->EsitoDonazione = $query_data->esito;
    call_user_func_array( 'aggiornaEsitoDonazione', array( $query_data ) );
    //2 - Aggiorno l'ordine Satispay
    call_user_func_array( 'aggiornaOrdineSatyspay', array( $query_data ) );
    if ( "OK" == $query_data->esito ) {
        if ( USE_MENTOR == true ) {
            //3 - Scrivo l'anagrafica in Mentor 
            $id_anagrafica_mentor = call_user_func_array( 'scriviAnagrafica_mentor', array( $query_data ) ); // Scrivo l'anagrafica in mentor
            if ( preg_match( "/^[0-9]+/", $id_anagrafica_mentor[ 1 ] ) ) {
                $risposta_ana_mentor[ 'Esito_mentor' ] = "OK";
                $risposta_ana_mentor[ 'Messaggio_mentor' ] = "&Egrave; stata scritta in Mentor l'anagrafica " . $id_anagrafica_mentor[ 1 ];
                $risposta_ana_mentor[ 'id_anagrafica_mentor' ] = $id_anagrafica_mentor[ 1 ];
                $query_data->codiceAnagraficaMentor = $id_anagrafica_mentor[ 1 ];
                $noe = explode( ";", $id_anagrafica_mentor[ 0 ] );
                $query_data->AnagraficaMentor_NoE = $noe[ 0 ]; //Anagriafica Nuova o Esistente 
                $risposta_ana_mentor[ 'NoE' ] = $noe[ 0 ]; // Aggiungo NOE
                $query_data->SessoMentor = $noe[ 1 ];
                //3 - Scrivo l'anagrafica in Mentor 
                if ( "nuova" == $query_data->AnagraficaMentor_NoE ) {
                    $wr_privacy = call_user_func_array( 'ScriviPrivacy_mentor', array( $query_data ) );
                }
                //5 Scrivo le specifiche in Mentor 
                $wr_specs = call_user_func_array( 'ScriviSpecifiche_mentor', array( $query_data ) );
                //6 Scrivo le sottoscrizioni in Mentor  
                if ( "nuova" == $query_data->AnagraficaMentor_NoE ) {
                    $wr_subscription = call_user_func_array( 'ScriviSubscription_mentor', array( $query_data ) );
                }
                //7 Aggiorno l'anagrafica in mysql (2) con il codice Mentor dell'anagrafica (6)  
                $ud_anagrafica = call_user_func_array( 'aggiornaAnagrafica_mysql', array( $query_data ) ); // aggiorno l'anagrafica in mysql	
                //8 Scrivo la donazione in Mentor 
                $id_donazione_mentor = call_user_func_array( 'ScriviDonazione_mentor', array( $query_data ) );
                if ( !preg_match( "/^[0-9]+/", $id_donazione_mentor ) ) {
                    $risposta_dona_mentor[ 'Esito_mentor' ] = "KO";
                    $risposta_dona_mentor[ 'Messaggio_mentor' ] = "Si &egrave; verificato un errore nella scrittura in Mentor " . $id_donazione_mentor;
                    $risposta_dona_mentor[ 'id_donazione_mentor' ] = $id_donazione_mentor;
                } else {
                    $risposta_dona_mentor[ 'Esito_mentor' ] = "OK";
                    $risposta_dona_mentor[ 'Messaggio_mentor' ] = "&Egrave; stata scritta in Mentor la donazione " . $id_donazione_mentor;
                    $risposta_dona_mentor[ 'id_donazione_mentor' ] = $id_donazione_mentor;
                    $query_data->codiceDonazioneMentor = $id_donazione_mentor;
                    //8 Scrivo la donazione in Mentor 
                    $ud_donazionecodMentor = call_user_func_array( 'aggiornaDonazioneCodMentor_mysql', array( $query_data ) );
                }
                $risposta[ 'DonazioneMentor' ] = $risposta_dona_mentor;

                if ( $query_data->centro == TESSERA_GIFT ) {
                    // 18 Aggiorno i donati in mysql
                    $ud_donati = call_user_func_array( 'aggiornaDonati_mysql', array( $query_data ) );
                    $risposta[ 'Donati' ] = $ud_donati;
                    // 19 Scrivo l'attività per tesserain regalo
                    $query_data->att_tipo = "2";
                    $query_data->att_sottotipo = "0205";
                    $query_data->att_stato = "2";
                    $query_data->att_oggetto = TESSERA_DESC . " REGALATA";
                    $wr_attivitaMentor = call_user_func_array( 'ScriviAttivita_mentor', array( $query_data ) );
                    $risposta[ 'Attivita' ] = $wr_attivitaMentor;
                }
            } else { //Errore di scrittura anagrafica in mentor
                $risposta_ana_mentor[ 'Esito_mentor' ] = "KO";
                $risposta_ana_mentor[ 'Messaggio_mentor' ] = "Si &egrave; verificato un errore nella scrittura in Mentor " . $id_anagrafica_mentor[ 0 ];
                $risposta_ana_mentor[ 'id_anagrafica_mentor' ] = $id_anagrafica_mentor[ 1 ];
            }
            $risposta[ 'AnagraficaMentor' ] = $risposta_ana_mentor;
        }
        $risposta_trans[ 'Esito_transazione' ] = $query_data->esito;
        $risposta_trans[ 'Messaggio_transazionee' ] = "Satispay transazione OK";
        $risposta_trans[ 'Transazione' ] = "";
    } elseif ( "CANCELED" == $datisy->status ) {
        if ( $query_data->centro == TESSERA_GIFT ) {
            $ud_donati = call_user_func_array( 'aggiornaDonati_mysql', array( $query_data ) );
            $risposta[ 'Donati' ] = $ud_donati;
        }
        $risposta_trans[ 'Esito_transazione' ] = $query_data->esito;
        $risposta_trans[ 'Messaggio_transazionee' ] = "Satispay transazione cancellata";
        $risposta_trans[ 'Transazione' ] = "";
    }
    $risposta[ 'Transazione' ] = $risposta_trans;
    if ( USE_MAGNEWS == true ) {
        //Magnews - Inizio
      $dataset = array(
                "email" => $query_data->mail,
                "nome" => $query_data->nome,
                "cognome" => $query_data->cognome,
                "tel" => $query_data->tel,
                "CodiceReferral"=>$query_data->CodiceReferral,
                "CodicePersonale"=>$query_data->CodicePersonale,
                "db" => 1,
                "Id_a" => $query_data->Id_a,
            );
        $mn_operation = call_user_func_array( 'Upsert_Magnews', array( $dataset ) ); // Scrivo il contatto in magnews
        if ( is_array( $mn_operation ) ) {
            $dataset += [
                "id_donatore" => $mn_operation[ 'idcontact' ], // WR + Id_a
                "codice_donazione" => $query_data->CodTrans, //CodTrans
                "importo" => $query_data->importo, //Importo
                    //"data_donazione" => "15/05/2024 10:05", //data
                //"data_donazione" => "15/05/2024 10:05", //data
                "campagna" => $query_data->id_campagna,
                "modalita_pagamento" => $query_data->pay_method, //pay_method
                "piattaforma" => "Almabox"
            ];
            //$mn_update = call_user_func_array( 'Upsert_Magnews', array( $dataset ) ); // Scrivo il contatto in magnews
            $mn_donation = call_user_func_array( 'AddDonation_Magnews', array( $dataset ) ); // Scrivo il contatto in magnews
        }


    }
    //Magnews - Fine
}
elseif ( $query_action->operation == "save" && $query_action->param == "donato" ) {
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "JSON Destinatario tessera" . PHP_EOL, 3, LOG_FILE ); //DEBUG
    }
    //1 Leggo i dati da Voucher
    $voucher_data = call_user_func_array( 'LeggiDati_mysql', array( $query_data ) );
    foreach ( $voucher_data as $key => $value ) {
        $query_data->$key = $value;
    }
    // 2 Valido i dati del form
    $chk_data = call_user_func( 'ValidaDati' );
    if ( $chk_data[ 0 ] <> 0 ) {
        //$risposta ['Errori'] = $chk_data;
        $risposta_verificadati[ 'Esito' ] = "KO";
        $i = 0;
        foreach ( $chk_data[ 2 ] as $k => $v ) {
            $i++;
            $risposta_verificadati[ 'Errori' ][ $i ][ 'Codice' ] = $k;
            $risposta_verificadati[ 'Errori' ][ $i ][ 'Messaggio' ] = $v;
        }
        $risposta[ 'Errori' ] = $risposta_verificadati; // Modifica 'Errori' in 'Validazione'
    } else {
        //1 Leggo i dati da Voucher
        // 2 Valido i dati del form
        $id_anagrafica = call_user_func_array( 'scriviAnagrafica_mysql', array( $query_data ) ); // Scrivo l'anagrafica in mysql
        if ( is_numeric( $id_anagrafica[ 0 ] ) ) {
            $risposta_ana[ 'Esito_mysql' ] = "OK";
            $risposta_ana[ 'Messaggio_mysql' ] = "&Egrave; stata scritta in MYSQL l'anagrafica del destinatario" . $id_anagrafica[ 0 ];
            $risposta_ana[ 'id_anagrafica_mysql' ] = $id_anagrafica[ 0 ];
            $query_data->Id_a = $id_anagrafica[ 0 ]; // Aggiungo l'id mysql dell'anagrafia a query_data
            $query_data->CodicePersonale = $id_anagrafica[ 1 ]; // Agigungo il codice Personale a query_data
            //$risposta ['Errori'] = $chk_data;
            //4	Aggiorno l'id_richiesta nella tabella Voucher con l'id della anagrafia (3)
            $ud_anagrafica = call_user_func_array( 'aggiornaVoucher_mysql', array( $query_data ) ); // aggiorno l'anagrafica in mysql				
            //5 Scrivo l'anagrafica in Mentor 
            $id_anagrafica_mentor = call_user_func_array( 'scriviAnagrafica_mentor', array( $query_data ) ); // Scrivo l'anagrafica in mentor
            if ( preg_match( "/^[0-9]+/", $id_anagrafica_mentor[ 1 ] ) ) {
                $risposta_ana_mentor[ 'Esito_mentor' ] = "OK";
                $risposta_ana_mentor[ 'Messaggio_mentor' ] = "&Egrave; stata scritta in Mentor l'anagrafica del destinatario " . $id_anagrafica_mentor[ 1 ];
                $risposta_ana_mentor[ 'id_anagrafica_mentor' ] = $id_anagrafica_mentor[ 1 ];
                $query_data->codiceAnagraficaMentor = $id_anagrafica_mentor[ 1 ];
                $noe = explode( ";", $id_anagrafica_mentor[ 0 ] );
                $query_data->AnagraficaMentor_NoE = $noe[ 0 ]; //Anagriafica Nuova o Esistente
                $risposta_ana_mentor[ 'NoE' ] = $noe[ 0 ]; // Aggiungo NOE
                $query_data->SessoMentor = $noe[ 1 ];
                if ( isset( $query_data->AnagraficaMentor_NoE ) && $query_data->AnagraficaMentor_NoE == "nuova" ) {
                    //6 Scrivo la privacy in Mentor 
                    $wr_privacy = call_user_func_array( 'ScriviPrivacy_mentor', array( $query_data ) );
                    //7 Scrivo le specifiche in Mentor 
                    $wr_specs = call_user_func_array( 'ScriviSpecifiche_mentor', array( $query_data ) );
                    //$wr_subscription = call_user_func_array( 'ScriviSubscription_mentor', array( $query_data ) ); // non scrivo subscription per il donato
                }
                //8 Recupero i dati del donatore 
                //$donatore_mentor = call_user_func_array( 'LeggiDati_mentor', array( $query_data ) );
                //9 Scrivo l'attività per tessera in regalo con l'Id Mentor del donatore 
                $query_data->att_tipo = "2";
                $query_data->att_sottotipo = "0201";
                $query_data->att_stato = "0";
                $query_data->att_oggetto = TESSERA_DESC . "  IN REGALO DA ID " . $query_data->id_mentor_donatore;
                //$query_data->att_note = $donatore_mentor->data[ 'nome' ] . " " . $donatore_mentor->data[ 'cognome' ];
                $query_data->att_utenteAssegnatario = "imazzucchelli";
                //$query_data->att_note ="";
                $wr_attivitaMentor = call_user_func_array( 'ScriviAttivita_mentor', array( $query_data ) );
                $risposta[ 'Attivita' ] = $wr_attivitaMentor;
                //10 Aggiorno l'anagrafica in mysql (3) con il codice Mentor dell'anagrafica (5)
                $ud_anagrafica = call_user_func_array( 'aggiornaAnagrafica_mysql', array( $query_data ) ); // aggiorno l'anagrafica in mysql
            } else { //Errore di scrittura anagrafica in mentor
                $risposta_ana_mentor[ 'Esito_mentor' ] = "KO";
                $risposta_ana_mentor[ 'Messaggio_mentor' ] = "Si &egrave; verificato un errore nella scrittura in Mentor " . $id_anagrafica_mentor[ 0 ];
                $risposta_ana_mentor[ 'id_anagrafica_mentor' ] = $id_anagrafica_mentor[ 1 ];
            }
            $risposta[ 'AnagraficaMentor' ] = $risposta_ana_mentor;

        } else { //errore scrittura anagrafica in mysql
            $risposta_ana[ 'Esito_mysql' ] = "KO";
            $risposta_ana[ 'Messaggio_mysql' ] = "Si &egrave; verificato un errore nella scrittura in MYSQL " . $id_anagrafica[ 0 ];
            $risposta_ana[ 'id_anagrafica_mysql' ] = $id_anagrafica[ 0 ];
        }
        $risposta[ 'Anagrafica' ] = $risposta_ana;
    }
    $risposta_string = json_encode( $risposta );
    echo $risposta_string;
}
elseif ( $query_action->operation == "get" && $query_action->param == "data" ) {
    // Endpoint interno: restituisce PII del donatore. Richiede un token HMAC
    // (basato su SALT_MAIL) per impedire l'enumerazione dei donatori via Id_a/CodTrans (IDOR).
    $expected_token = hash_hmac( 'sha256', 'get_data', defined( 'SALT_MAIL' ) ? SALT_MAIL : '' );
    $provided_token = isset( $query_data->token ) ? (string) $query_data->token : '';
    if ( '' === $provided_token || !hash_equals( $expected_token, $provided_token ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "DON_WS get/data: token non valido da IP " . ( $_SERVER[ 'REMOTE_ADDR' ] ?? '?' ) . PHP_EOL, 3, LOG_FILE );
        http_response_code( 403 );
        echo json_encode( array( 'error' => 'Accesso negato' ) );
        exit;
    }
    $riga_mysql = call_user_func_array( 'LeggiDati_mysql', array( $query_data ) ); // Scrivo l'anagrafica in mysql
    //print_r($riga_mysql);
    $riga_mysql_array = ( array )$riga_mysql;
    $risposta_string = json_encode( $riga_mysql_array, JSON_UNESCAPED_UNICODE );
    echo $risposta_string;
}
else {
    $risposta[ 'ESITO' ] = "Si sono verificati degli errori";
    $risposta_string = json_encode( $risposta );
    echo $risposta_string;
}

/*} else {
    $risposta[ 'ESITO' ] = "Chiamata effettuata da IP non autorizzato";
    $risposta_string = json_encode( $risposta );
    echo $risposta_string;
    mail( ALERT_MAIL, "IP " . $_SERVER[ 'REMOTE_ADDR' ] . " non autorizzato", $risposta[ 'ESITO' ] . " su WS di " . $url_di_base . "\r\nLista IP: " . $authorized_IP );
}*/
if ( DEBUG == true ) {
    if ( isset( $risposta_string ) )error_log( date( '[Y-m-d H:i:s e] ' ) . "Risposta WS " . $risposta_string . PHP_EOL, 3, LOG_FILE ); //DEBUG
}