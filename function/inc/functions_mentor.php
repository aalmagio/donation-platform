<?php // Mentor

function ScriviAnagrafica_mentor( $anagrafica ) {
    //Scrivo in MENTOR - Inizio
    $url_ch = MENTOR_API_URL . "/wsc_save_donor.ashx";
    $rtest = verifyURL( $url_ch );
    if ( $anagrafica->centro == TESSERA_COD ) {
        $anagrafica->dataScadenzaTessera = DATA_SCAD_TESSERA; //Per tesser 2018
        $anagrafica->dataEmissioneTessera = "";
        $anagrafica->flagEmissioneTessera = "0";
        if ( isset( $anagrafica->tipotessera ) ) {
            if ( $anagrafica->tipotessera == TESSERA_COST_JUNIOR || $anagrafica->tipotessera == "Junior" ) {
                $anagrafica->tipoTessera = "Junior";
                $anagrafica->codiceTesseraMentor = "1";

            } elseif ( $anagrafica->tipotessera == TESSERA_COST_SENIOR || $anagrafica->tipotessera == "Senior" ) {
                $anagrafica->tipoTessera = "Senior";
                $anagrafica->codiceTesseraMentor = "3";
            }
            else {
                $anagrafica->tipoTessera = "Standard";
                $anagrafica->codiceTesseraMentor = "2";
            }
        } else {
            $anagrafica->tipoTessera = "Standard";
            $anagrafica->codiceTesseraMentor = "2";
        }
    }
    // Telefono per 3d non entra (cellualre/telefono)
    if ( isset( $anagrafica->tel ) && !isset( $anagrafica->tipotel ) ) {
        if ( preg_match( '/^3\d{8,9}$/', $anagrafica->tel ) ) {
            $anagrafica->tipotel = "cellulare";
            $anagrafica->cellulare1 = $anagrafica->tel;
        } elseif ( preg_match( '/^0\d{7,8}$/', $anagrafica->tel ) ) {
            $anagrafica->tipotel = "fisso";
            $anagrafica->telefono1 = $anagrafica->tel;
        }
        else {
            $anagrafica->tipotel = "ND";
            $anagrafica->cellulare1 = $anagrafica->tel;
        }
    }
    if ( $rtest[ 'stauts' ] ) {
        if ( $anagrafica->sesso == "S" ) {
            $anagrafica_mentor = array(
                "codice" => $anagrafica->codice,
                "codiceOrigine" => $anagrafica->codiceOrigine,
                //"codiceWeb"=>$codice_anagrafica,
                "codiceWeb" => $anagrafica->Id_a, //Test
                "tipo" => $anagrafica->tipo_ana, // wsc_table (09 => Donatore, 10 =>Tesserato, 60 => Prospect)
                "sottotipo" => $anagrafica->sottotipo,
                //"nome" => "C.A. " . $anagrafica->nome ." ". $anagrafica->cognome ,
                "nome" => $anagrafica->nome,
                //"cognome" => $anagrafica->cognome,
                "ragioneSociale" => $anagrafica->ragioneSociale,
                "genere" => $anagrafica->sesso,
                //"dataNascita" => $anagrafica->mentordatanascita,
                //"luogoNascita" => $anagrafica->luogoNascita,
                "codiceFiscale" => $anagrafica->codFis,
                "partitaIVA" => $anagrafica->partitaIVA,
                "email1" => $anagrafica->mail,
                "email2" => $anagrafica->mail2,
                "telefono1" => $anagrafica->telefono1,
                //"telefono2" => $anagrafica->telefono2,
                "cellulare1" => $anagrafica->cellulare1,
                //"cellulare2" => $anagrafica->cellulare2,
                "presso" => $anagrafica->presso,
                "dug" => "",
                "duf" => $anagrafica->indirizzo,
                "civico" => $anagrafica->civico,
                "altroCivico" => $anagrafica->altroCivico,
                "frazione" => $anagrafica->frazione,
                "localita" => $anagrafica->citta,
                "provincia" => $anagrafica->provincia,
                "cap" => $anagrafica->cap,
                "codiceNazione" => $anagrafica->stato,
                "codiceCampagna" => $anagrafica->id_campagna,
                "tipoTessera" => $anagrafica->codiceTesseraMentor,
                "codiceTessera" => $anagrafica->codiceTessera,
                "dataScadenzaTessera" => $anagrafica->dataScadenzaTessera,
                "dataEmissioneTessera" => $anagrafica->dataEmissioneTessera,
                "flagEmissioneTessera" => $anagrafica->flagEmissioneTessera
            );
        } else {
            $anagrafica_mentor = array(
                "codice" => $anagrafica->codice,
                "codiceOrigine" => $anagrafica->codiceOrigine,
                //"codiceWeb"=>$codice_anagrafica,
                "codiceWeb" => $anagrafica->Id_a, //Test
                "tipo" => $anagrafica->tipo_ana, // wsc_table (09 => Donatore, 10 =>Tesserato, 60 => Prospect)
                "sottotipo" => $anagrafica->sottotipo,
                "nome" => $anagrafica->nome,
                "cognome" => $anagrafica->cognome,
                "ragioneSociale" => $anagrafica->ragioneSociale,
                "genere" => $anagrafica->sesso,
                "dataNascita" => $anagrafica->mentordatanascita,
                "luogoNascita" => $anagrafica->luogoNascita,
                "codiceFiscale" => $anagrafica->codFis,
                "partitaIVA" => $anagrafica->partitaIVA,
                "email1" => $anagrafica->mail,
                "email2" => $anagrafica->mail2,
                "telefono1" => $anagrafica->telefono1,
                //"telefono2" => $anagrafica->telefono2,
                "cellulare1" => $anagrafica->cellulare1,
                //"cellulare2" => $anagrafica->cellulare2,
                "presso" => $anagrafica->presso,
                "dug" => "",
                "duf" => $anagrafica->indirizzo,
                "civico" => $anagrafica->civico,
                "altroCivico" => $anagrafica->altroCivico,
                "frazione" => $anagrafica->frazione,
                "localita" => $anagrafica->citta,
                "provincia" => $anagrafica->provincia,
                "cap" => $anagrafica->cap,
                "codiceNazione" => $anagrafica->stato,
                "codiceCampagna" => $anagrafica->id_campagna,
                "tipoTessera" => $anagrafica->codiceTesseraMentor,
                "codiceTessera" => $anagrafica->codiceTessera,
                "dataScadenzaTessera" => $anagrafica->dataScadenzaTessera,
                "dataEmissioneTessera" => $anagrafica->dataEmissioneTessera,
                "flagEmissioneTessera" => $anagrafica->flagEmissioneTessera
            );

        }

        if ( isset( $anagrafica->tipo_donazione ) && "TGIFT" == $anagrafica->tipo_donazione ) { //Passa il codice del presentatore
            if ( isset( $anagrafica->id_mentor_donatore ) ) { //Passa il codice del presentatore
                $anagrafica_mentor[ 'codicePresentatore' ] = $anagrafica->id_mentor_donatore;
            }
            $anagrafica_mentor[ 'tipo' ] = "60"; // defualt tipo anagrafica prospect per tessera in omaggio 
            // Forzo i dati per la tesser in regalo
            $anagrafica_mentor[ 'dataScadenzaTessera' ] = DATA_SCAD_TESSERA;
            $anagrafica_mentor[ 'dataEmissioneTessera' ] = "";
            $anagrafica_mentor[ 'flagEmissioneTessera' ] = "0";
            $anagrafica_mentor[ 'tipoTessera' ] = "12";
        }

        //$anagrafica_mentor = array_map('utf8_encode', $anagrafica_mentor); //Aggiunto per codifica accentate
        $data = array(
            "env" => ID_AMBIENTE,
            "application" => ID_APP,
            "operation" => "save",
            "token" => TOKEN,
            "user" => MENTOR_USER,
            "param" => "",
            "data" => $anagrafica_mentor
        );
        $data_string = json_encode( $data, JSON_UNESCAPED_UNICODE );
        $data_string = CleanMyJSON( $data_string );
        //if ( DEBUG == true ) {
        //LOG non Debug	
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata wsc_save_donor Mentor: " . $data_string . PHP_EOL, 3, LOG_FILE );
        //}
        $ch = curl_init( $url_ch );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen( $data_string ) ) );
        $result = json_decode( curl_exec( $ch ), true );
        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito wsc_save_donor Mentor: " . json_encode( $result ) . PHP_EOL, 3, LOG_FILE );
        }
        curl_close( $ch );
    } else {
        $result[ 'result' ] = "KO";
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito wsc_save_donor Mentor: " . $result[ 'result' ] . " Chiamata non effettuata codice http : " . $rtest[ 'code' ] . PHP_EOL, 3, LOG_FILE );
    }
    if ( $result[ 'result' ] == "KO" ) {
        return array( $codice_anagrafica, "Chiamata: " . $url_ch . "<br />Esito:" . $result[ 'result' ] . "<br />ID:" . $result[ 'data' ] . "<br />Messaggio:" . $result[ 'message' ] . "<br />" . $data_string );
    } else {
        return array( $result[ 'message' ], $result[ 'data' ] );
    }
    //Scrivo in MENTOR - Fine
}

function ScriviDonazione_mentor( $donazione ) {
    //Scrivo in MENTOR - Inizio
    $idWeb = $donazione->Id_a;
    if ( $donazione->pay_method == "PP" ) {
        $MM_metodo = "8";
        $importo_donazione = $donazione->importo;
        $idWeb = $donazione->Id_OrderPayPal;
    } elseif ( $donazione->pay_method == "SY" ) {
        $MM_metodo = "22";
        $importo_donazione = $donazione->importo;
        $idWeb = $donazione->id;
    }
    else {
        $MM_metodo = "9"; // 
        if ( isset( $donazione->id_mandato_Mentor ) && trim( $donazione->id_mandato_Mentor ) != "" ) {
            $MM_metodo = "K";
        } else {
            $MM_metodo = "9";
        }
        $importo_donazione = RemoveDecimal( $donazione->importo );
        $idWeb = $donazione->bankTransactionID;
    }
    if ( !isset( $idWeb ) || $idWeb == "" ) {
        $idWeb = "E-" . $donazione->Id_a;
    }
    $url_ch = MENTOR_API_URL . "/wsc_save_donation.ashx";
    $rtest = verifyURL( $url_ch );
    if ( $rtest[ 'stauts' ] ) {
        if ( isset( $donazione->data_ins ) ) {
            $dataoperazione = date( "Ymd", strtotime( $donazione->data_ins ) );
        } elseif ( isset( $donazione->data ) ) {
            $dataoperazione = date( "Ymd", strtotime( $donazione->data ) );
        } else {
            $dataoperazione = date( "Ymd" );
        }
        $donazione_mentor = array(
            "idRegolare" => $donazione->id_mandato_Mentor,
            "codiceDonatore" => $donazione->codiceAnagraficaMentor,
            "codiceCampagna" => $donazione->id_campagna,
            "codiceCentro" => $donazione->centro,
            //"codiceBambino" => "",
            //"codiceProgetto" => "", //$donazione->, // usare se diverso da quello della campagna 
            //"codiceCanale" => "", // $donazione->, // usare se diverso da quello della campagna 
            //"codiceConto" =>"",
            //"codiceRiferimento" => "", //Codice dell'anagrafica da impostare come riferimento nella donazione (es. per donazioni in memoria).
            //"codiceSoggettoVersante" => "",  //Codice dell'anagrafica da impostare come soggetto versante (se diverso dal donatore).
            "codicePartner" => $donazione->codicePartner,
            "importo" => $importo_donazione . ",00",
            "metodo" => $MM_metodo, //8 PayPal, 5 Carta di credito, 9 Online, K Carta di Credito Automatica
            //"dataOperazione" => date( "Ymd" ), //"20170503",//
            "dataOperazione" => $dataoperazione, //"20170503",//
            //"dataValuta" => "",
            "codiceTransazione" => $donazione->CodTrans,
            //GestPay  -> bankTransactionID
            //PayPal -> Id_OrderPayPal
            //Satispay -> id
            "idWeb" => $idWeb,
            //"idWeb" => $donazione->Id_a,
            "note" => $donazione->nota
        );
        $data = array(
            "env" => ID_AMBIENTE,
            "application" => ID_APP,
            "operation" => "save",
            "token" => TOKEN,
            "user" => MENTOR_USER,
            "param" => "",
            "data" => $donazione_mentor
        );
        $data_string = json_encode( $data, JSON_UNESCAPED_UNICODE );
        $data_string = CleanMyJSON( $data_string );
        //Log chimata
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata wsc_save_donation Mentor: " . $data_string . PHP_EOL, 3, LOG_FILE );
        $ch = curl_init( $url_ch );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen( $data_string ) ) );
        $result = json_decode( curl_exec( $ch ), true );

        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito wsc_save_donation Mentor: " . json_encode( $result ) . PHP_EOL, 3, LOG_FILE );
        }
        curl_close( $ch );
    } else {
        $result[ 'result' ] = "KO";
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito wsc_save_donation Mentor: " . $result[ 'result' ] . " Chiamata non effettuata codice http : " . $rtest[ 'code' ] . PHP_EOL, 3, LOG_FILE );
    }
    if ( $result[ 'result' ] == "KO" ) {
        //{"result":"KO","message":"Individuata donazione doppia per id web 921","data":null}
        if ( strpos( $result[ 'message' ], "donazione doppia" ) !== false ) {
            return ( $result[ 'message' ] );
        } else {
            return ( "Chiamata: " . $url_ch . "<br />Esito:" . $result[ 'result' ] . "<br />ID:" . $result[ 'data' ] . "<br />Messaggio:" . $result[ 'message' ] . "<br />" . $data_string );
        }
    } else {
        return ( $result[ 'data' ] );
    }
    //Scrivo in MENTOR - Fine
}

function ScriviPrivacy_mentor( $anagrafica ) {
    $tipoprivacy = "E,G,L,S,T,A,R";
    $flagprivacy = "1,1,1,1,1,1,1";
    if ( isset( $anagrafica->AnagraficaMentor_NoE ) && $anagrafica->AnagraficaMentor_NoE == "nuova" ) {
        $noteprivacy = "Consenso per donazione online";
        $tipoprivacy = "E,G,L,S,T,A,R";
        $flagprivacy = "1,1,1,1,1,1,1";
    } else {
        $noteprivacy = "";
        $tipoprivacy = "";
        $flagprivacy = "";
    }
    $url_ch = MENTOR_API_URL . "/wsc_save_privacy.ashx";
    $rtest = verifyURL( $url_ch );
    if ( $rtest[ 'stauts' ] ) {
        $privacy_mentor = array(
            "codiceDonatore" => $anagrafica->codiceAnagraficaMentor,
            "codicePrivacy" => $tipoprivacy,
            "attiva" => $flagprivacy,
            "dataEntata" => date( "Ymd" ), //"20170503",//, 
            "dataUscita" => "0",
            "note" => $noteprivacy
        );
        $data = array(
            "env" => ID_AMBIENTE,
            "application" => ID_APP,
            "operation" => "save",
            "token" => TOKEN,
            "user" => MENTOR_USER,
            "param" => "",
            "data" => $privacy_mentor
        );
        $data_string = json_encode( $data, JSON_UNESCAPED_UNICODE );
        $data_string = CleanMyJSON( $data_string );
        $ch = curl_init( $url_ch );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen( $data_string ) ) );
        $result = json_decode( curl_exec( $ch ), true );
        curl_close( $ch );
    } else {
        $result[ 'result' ] = "KO";
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito wsc_save_privacy Mentor: " . $result[ 'result' ] . " Chiamata non effettuata codice http : " . $rtest[ 'code' ] . PHP_EOL, 3, LOG_FILE );
    }
    if ( $result[ 'result' ] == "KO" ) {
        return ( "Chiamata: " . $url_ch . "<br />Esito:" . $result[ 'result' ] . "<br />ID:" . $result[ 'data' ] . "<br />Messaggio:" . $result[ 'message' ] . "<br />" . $data_string );
    } else {
        return ( $result[ 'data' ] );
    }
    //Scrivo in MENTOR - Fine
}

function ScriviSubscription_mentor( $anagrafica ) {
    //01 Allistante 
    $tiposubscription = "01";
    $flagsubscription = "1";
    if ( isset( $anagrafica->AnagraficaMentor_NoE ) && $anagrafica->AnagraficaMentor_NoE == "nuova" ) {
        $tiposubscription = "01";
        $flagsubscription = "1";
    } else {
        $tiposubscription = "";
        $flagsubscription = "";
    }
    if ( isset( $anagrafica->AnagraficaMentor_NoE ) && $anagrafica->AnagraficaMentor_NoE == "nuova" ) { // Effettuo la chimata solo per anagrfiche nuove [paracadute]     
        $url_ch = MENTOR_API_URL . "/wsc_save_subscription.ashx";
        $rtest = verifyURL( $url_ch );
        if ( $rtest[ 'stauts' ] ) {
            $subscription_mentor = array(
                "codiceAnagrafica" => $anagrafica->codiceAnagraficaMentor,
                "codiceSubscription" => $tiposubscription,
                "attiva" => $flagsubscription,
                "dataEntata" => date( "Ymd" ), //"20170503",//, 
                "dataUscita" => "",
            );
            $data = array(
                "env" => ID_AMBIENTE,
                "application" => ID_APP,
                "operation" => "save",
                "token" => TOKEN,
                "user" => MENTOR_USER,
                "param" => "",
                "data" => $subscription_mentor
            );
            $data_string = json_encode( $data, JSON_UNESCAPED_UNICODE );
            $data_string = CleanMyJSON( $data_string );
            $ch = curl_init( $url_ch );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen( $data_string ) ) );
            $result = json_decode( curl_exec( $ch ), true );
            curl_close( $ch );
        } else {
            $result[ 'result' ] = "KO";
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito wsc_save_subscription Mentor: " . $result[ 'result' ] . " Chiamata non effettuata codice http : " . $rtest[ 'code' ] . PHP_EOL, 3, LOG_FILE );
        }
    }
    if ( $result[ 'result' ] == "KO" ) {
        return ( "Chiamata: " . $url_ch . "<br />Esito:" . $result[ 'result' ] . "<br />ID:" . $result[ 'data' ] . "<br />Messaggio:" . $result[ 'message' ] . "<br />" . $data_string );
    } else {
        return ( $result[ 'data' ] );
    }
    //Scrivo in MENTOR - Fine
}

function ScriviSpecifiche_mentor( $anagrafica ) {
    $codiceCampo = array( "RIN", "TRIME", "RINCAR", "ATTCAR", "RINMAIL" );
    //foreach ($_SESSION as $key => $value){echo "varibaile SESSIONE ". $key ." = ". $value ."<br>";}
    if ( isset( $anagrafica->AnagraficaMentor_NoE ) && $anagrafica->AnagraficaMentor_NoE == "nuova" ) {
        $url_ch = MENTOR_API_URL . "/wsc_set_spec.ashx";
        $rtest = verifyURL( $url_ch );
        if ( $rtest[ 'stauts' ] ) {
            foreach ( $codiceCampo as $key => $value ) {
                $specifiche_mentor = array(
                    "codiceAnagrafica" => $anagrafica->codiceAnagraficaMentor,
                    "codiceCampo" => $value,
                    "valore" => "1"
                );
                $data = array(
                    "env" => ID_AMBIENTE,
                    "application" => ID_APP,
                    "operation" => "save",
                    "token" => TOKEN,
                    "user" => MENTOR_USER,
                    "param" => "",
                    "data" => $specifiche_mentor
                );
                $data_string = json_encode( $data, JSON_UNESCAPED_UNICODE );
                $data_string = CleanMyJSON( $data_string );
                $ch = curl_init( $url_ch );
                curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen( $data_string ) ) );
                $result = json_decode( curl_exec( $ch ), true );
                curl_close( $ch );
                $message_specs .= $value . ": " . $result[ 'result' ];
            }
        } else {
            $message_specs = "Nessuna specifica scritta";
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito wsc_set_spec Mentor: " . $message_specs . " Chiamata non effettuata codice http : " . $rtest[ 'code' ] . PHP_EOL, 3, LOG_FILE );
        }
    } else {
        $message_specs = "Nessuna specifica scritta";
    }
    return ( $message_specs );
    //Scrivo in MENTOR - Fine
}

function ScriviMandato_mentor( $mandato ) {
    //Scrivo in MENTOR - Inizio
    $url_ch = MENTOR_API_URL . "/wsc_save_regular.ashx";
    $rtest = verifyURL( $url_ch );
    if ( $rtest[ 'stauts' ] ) {
        $mandato_mentor = array(
            //"codice"=>$mandato->codice,
            "generaSostegno" => $mandato->generaSostegno,
            "codiceDonatore" => $mandato->codiceAnagraficaMentor,
            //"codiceCampanga" => $mandato->id_campagna,
            "codiceCampagna" => $mandato->id_campagna,
            "codiceCentro" => $mandato->centro,
            "codiceCanale" => $mandato->codiceCanale,
            "codiceProgetto" => "",
            "codiceTema" => "3", // Tema della Regolare 
            "importo" => $mandato->importo,
            "frequenza" => $mandato->frequenza,
            "metodo" => $mandato->metodo,
            "IBAN" => strtoupper( $mandato->IBAN ),
            "BIC" => $mandato->BIC,
            "Token" => $mandato->GP_token,
            "meseToken" => $mandato->GP_tokenExpiryMonth,
            "annoToken" => "20" . $mandato->GP_tokenExpiryYear, //DACAMBIARE DOPO IL 2099
            "providerIncasso" => "",
            "nomeTitolare" => $mandato->titolare,
            "codiceFiscaleTitolare" => $mandato->codiceFiscaleTitolare,
            "indirizzoTitolare" => $mandato->indirizzoTitolare,
            "localitaTitolare" => $mandato->localitaTitolare,
            "provinciaTitolare" => $mandato->provinciaTitolare,
            "cap" => $mandato->capTitolare,
            "urn" => $mandato->urn,
            "lotto" => $mandato->lotto,
            "note" => $mandato->nota,
            "codiceDialogatoreEsterno" => $mandato->codiceDialogatoreEsterno,
            "nomeDialogatoreEsterno" => $mandato->nomeDialogatoreEsterno,
            "locazione" => $mandato->locazione,
            "cittaLocazione" => $mandato->cittaLocazione
        );
        //$mandato_mentor = array_map('utf8_encode', $mandato_mentor); //Aggiunto per codifica accentate
        $data = array(
            "env" => ID_AMBIENTE,
            "application" => ID_APP,
            "operation" => "save",
            "token" => TOKEN,
            "user" => MENTOR_USER,
            "param" => "",
            "data" => $mandato_mentor
        );
        $data_string = json_encode( $data, JSON_UNESCAPED_UNICODE );
        $data_string = CleanMyJSON( $data_string );
        //$data_string = json_encode($data, JSON_UNESCAPED_UNICODE);   
        //echo $data_string;
        $ch = curl_init( $url_ch );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen( $data_string ) ) );
        //LOG
        error_log( date( '[Y-m-d H:i:s e] ' ) . "JSON Donazione REGOLARE" . $data_string . PHP_EOL, 3, LOG_FILE ); //DEBUG
        $string = curl_exec( $ch );
        $string_mod = str_replace( "\"{", "{", $string ); //rimuove i doppi apici di data
        $string_mod = str_replace( "}\"", "}", $string_mod ); //rimuove i doppi apici di data
        $result = json_decode( $string_mod, true );
        curl_close( $ch );
    } else {
        $result[ 'result' ] = "KO";
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito wsc_save_regular Mentor: " . $result[ 'result' ] . " Chiamata non effettuata codice http : " . $rtest[ 'code' ] . PHP_EOL, 3, LOG_FILE );
    }
    if ( $result[ 'result' ] == "KO" ) {
        return array( "Esito: " . $result[ 'result' ] . ": " . $result[ 'message' ], "Chiamata: " . $url_ch . "<br />Esito:" . $result[ 'result' ] . "<br />ID:" . $result[ 'data' ] . "<br />Messaggio:" . $result[ 'message' ] . "<br />" . $data_string );
    } else {
        $codice_mandato = $result[ 'data' ];
        return array( $codice_mandato[ 'idRegolare' ], $codice_mandato[ 'codiceBambino' ] );
    }
    //Scrivo in MENTOR - Fine
}

function ScriviAttivita_mentor( $anagrafica ) {
    if ( !isset( $anagrafica->id_campagna ) || trim( $anagrafica->id_campagna ) == "" )$anagrafica->id_campagna = ID_CAMPAGNA_DEFAULT;
    $url_ch = MENTOR_API_URL . "/wsc_save_activity.ashx";
    $rtest = verifyURL( $url_ch );
    if ( $rtest[ 'stauts' ] ) {
        $attivita_mentor = array(
            "codiceDonatore" => $anagrafica->codiceAnagraficaMentor,
            "codiceCampagna" => $anagrafica->id_campagna,
            //"codiceProgetto" =>$anagrafica->,
            //"codiceCanale"=> $anagrafica->,
            "idRegolare" => $anagrafica->id_mandato_Mentor,
            "tipo" => $anagrafica->att_tipo,
            "sottotipo" => $anagrafica->att_sottotipo,
            //"stato" => "0",
            "stato" => $anagrafica->att_stato,
            "dataAttivita" => date( "Ymd" ), //"20170503",//, 
            "oggetto" => $anagrafica->att_oggetto
            //"note"=> "Note sull'attività",
            //"utenteAssegnatario"=> "johndoe",
            //"gruppoUtentiAssegnatario"=> "Servizio Donatori",        
        );
        if ( isset( $anagrafica->att_note ) ) { //Passa l'utetne solo se presente
            $attivita_mentor[ 'note' ] = $anagrafica->att_note;
        }
        if ( isset( $anagrafica->att_utenteAssegnatario ) ) { //Passa l'utetne solo se presente
            $attivita_mentor[ 'utenteAssegnatario' ] = $anagrafica->att_utenteAssegnatario;
        }

        $data = array(
            "env" => ID_AMBIENTE,
            "application" => ID_APP,
            "operation" => "save",
            "token" => TOKEN,
            "user" => MENTOR_USER,
            "param" => "",
            "data" => $attivita_mentor
        );
        $data_string = json_encode( $data, JSON_UNESCAPED_UNICODE );
        $data_string = CleanMyJSON( $data_string );
        $ch = curl_init( $url_ch );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen( $data_string ) ) );
        $result = json_decode( curl_exec( $ch ), true );
        curl_close( $ch );

    } else {
        $result[ 'result' ] = "KO";
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito wsc_save_activity Mentor: " . $result[ 'result' ] . " Chiamata non effettuata codice http : " . $rtest[ 'code' ] . PHP_EOL, 3, LOG_FILE );
    }
    $message_attivita = "Esito Attivita: " . $result[ 'result' ];
    // $message_attivita = $value . ": " . $result[ 'result' ];
    return ( $message_attivita );

}

function LeggiDati_mentor( $richiesta ) {
    $url_ch = MENTOR_API_URL . "/wsc_get_donor.ashx";
    $rtest = verifyURL( $url_ch );
    if ( $rtest[ 'stauts' ] ) {
        $data = array(
            "env" => ID_AMBIENTE,
            "application" => ID_APP,
            "operation" => "get",
            "token" => TOKEN,
            "user" => MENTOR_USER,
            "param" => $richiesta->id_mentor_donatore,
            "data" => null
        );
        $data_string = json_encode( $data, JSON_UNESCAPED_UNICODE );
        $data_string = CleanMyJSON( $data_string );
        //LOG
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata wsc_save_donor Mentor: " . $data_string . PHP_EOL, 3, LOG_FILE );
        $ch = curl_init( $url_ch );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen( $data_string ) ) );
        $result = json_decode( curl_exec( $ch ), true );
        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito wsc_save_donor Mentor: " . $result . PHP_EOL, 3, LOG_FILE );
        }
        curl_close( $ch );
        foreach ( $result as $key => $value ) {
            if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                $answer_donatore->$key = $value;
            }
        }
        return ( $answer_donatore );
    }
}

function aggiornaAnagrafica_mysql( $anagrafica ) {
    //connetto al db
    if ( $anagrafica->centro == TESSERA_COD ) { //TESSERA X SE
        $anagrafica->tipo_ana = "10";
    } else {
        $anagrafica->tipo_ana = "09";
    }
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( $connection->connect_errno ) {
        trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
    }
    // preparo lo statement
    if ( !( $stmt = $connection->prepare( "UPDATE Anagrafica SET ID_Mentor=?, tipo_ana=?, sesso=? WHERE Id_a=?;" ) ) ) {
        trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
    }
    // associo i parametri ai placeholder
    if ( !$stmt->bind_param( 'sssi', $anagrafica->codiceAnagraficaMentor, $anagrafica->tipo_ana, $anagrafica->SessoMentor, $anagrafica->Id_a ) ) {
        trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    // eseguo la query e chiudo
    if ( !$stmt->execute() ) {
        trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    $stmt->close();
    $connection->close();
}

function aggiornaDonazioneCodMentor_mysql( $donazione ) {
    // connetto al db
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( $connection->connect_errno ) {
        trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
    }
    // preparo lo statement
    if ( !( $stmt = $connection->prepare( "UPDATE Donazione SET CodiceMentor=? WHERE CodTrans=?;" ) ) ) {
        trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
    }
    // associo i parametri ai placeholder
    if ( !$stmt->bind_param( 'ss', $donazione->codiceDonazioneMentor, $donazione->CodTrans ) ) {
        trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    // eseguo la query e chiudo
    if ( !$stmt->execute() ) {
        trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    $stmt->close();
    $connection->close();
}

function aggiornaMandatoCodMentor_mysql( $mandato ) {
    // connetto al db
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( $connection->connect_errno ) {
        trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
    }
    // preparo lo statement
    if ( !( $stmt = $connection->prepare( "UPDATE Mandato SET CodiceMandatoMentor=?, codiceDonatore =?, Errore =? WHERE Id_mandato=?;" ) ) ) {
        trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
    }
    // associo i parametri ai placeholder
    if ( !$stmt->bind_param( 'sssi', $mandato->id_mandato_Mentor, $mandato->codiceAnagraficaMentor, $mandato->errore_mandato_Mentor, $mandato->Id_mandato ) ) {
        trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    // eseguo la query e chiudo
    if ( !$stmt->execute() ) {
        trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
    }
    $stmt->close();
    $connection->close();
}