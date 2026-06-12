<?php // validate data
function ValidaDati() {
    global $query_data; // To modify directly $query_data after validation
    $req_fields = explode( ",", $query_data->req_fields );
    $errore = 0;
    //$codice_errore = "";
    $messaggio_errore = "";
    $arr_errore = array();
    //Mandatory variable management 
    //Any fields NOT present in the form are set to default value - START
    //Personal Data
    if ( !isset( $query_data->sesso ) || trim( $query_data->sesso ) == "" )$query_data->sesso = "X"; //X =Daverificare 
    if ( !isset( $query_data->id_campagna ) || trim( $query_data->id_campagna ) == "" )$query_data->id_campagna = ID_CAMPAGNA_DEFAULT;
    if ( !isset( $query_data->id_fonte ) || trim( $query_data->id_fonte ) == "" )$query_data->id_fonte = ID_FONTE_DEFAULT;
    if ( isset($query_data->IP)) $tentativi = call_user_func( 'CheckIPAttempts', $query_data->IP );
    if ( !isset( $query_data->tipo_ana ) || trim( $query_data->tipo_ana ) == "" )$query_data->tipo_ana = "09";
    if ( !isset( $query_data->lang ) || trim( $query_data->lang ) == "" )$query_data->lang = "it";
    //Se Member card
    if ( isset($query_data->centro) && $query_data->centro == TESSERA_COD )$query_data->tipo_ana = "10"; //TESSERA X SE
    //Donation
    if ( !isset( $query_data->CodTrans ) || trim( $query_data->CodTrans ) == "" ) {
        $micro_date = microtime();
        $date_array = explode( " ", $micro_date );
        $date = date( "YmdwHis", $date_array[ 1 ] );
        // Insert code with specific endings // TO DEVELOP
        if ( isset($query_data->centro) && $query_data->centro == TESSERA_COD ) { //TESSERA X SE
            $query_data->CodTrans = "D-" . $date . substr( $date_array[ 0 ], 2, 2 ) . "-DT";
            $query_data->tessera = "Y";
        } elseif ( isset($query_data->centro) && $query_data->centro == TESSERA_GIFT ) { //TESSERA X SE
            $query_data->CodTrans = "D-" . $date . substr( $date_array[ 0 ], 2, 2 ) . "-TG";
            $query_data->tessera = "Y";
        } else {
            if ( USE_SANDBOX == true )
                $query_data->CodTrans = "D-" . $date . substr( $date_array[ 0 ], 2, 2 ) . "-SB";
            else{
                $query_data->CodTrans = "D-" . $date . substr( $date_array[ 0 ], 2, 2 ) . "-DD";
            }
        }
    }
    if ( isset($query_data->centro) && $query_data->centro == TESSERA_COD ) {
        $query_data->dataScadenzaTessera = DATA_SCAD_TESSERA;
        $query_data->dataEmissioneTessera = "";
        $query_data->flagEmissioneTessera = "0";
        if ( isset( $query_data->tipotessera ) ) {
            if ( $query_data->tipotessera == TESSERA_COST_JUNIOR || $query_data->tipotessera == "Junior" ) {
                $query_data->tipoTessera = "Junior";
                $query_data->codiceTesseraMentor = "1";
            } elseif ( $query_data->tipotessera == TESSERA_COST_SENIOR || $query_data->tipotessera == "Senior" ) {
                $query_data->tipoTessera = "Senior";
                $query_data->codiceTesseraMentor = "3";
            }
            else {
                $query_data->tipoTessera = "Standard";
                $query_data->codiceTesseraMentor = "2";
            }
        } else {
            $query_data->tipoTessera = "Standard";
            $query_data->codiceTesseraMentor = "2";
        }
        if ( !isset( $query_data->importo ) || trim( $query_data->importo ) == "" || $query_data->importo == 0 ) {
            if ( isset( $query_data->tipotessera ) && is_numeric( $query_data->tipotessera) && $query_data->tipotessera >= TESSERA_COST_JUNIOR ) {
                $query_data->importo = $query_data->tipotessera;
                if ( is_numeric( $query_data->donazione_aggiuntiva ) ) {    
                    $query_data->importo = $query_data->tipotessera + $query_data->donazione_aggiuntiva;
                }
            }           
        }
    }
    if ( isset($query_data->centro) && $query_data->centro == TESSERA_GIFT ) {
        /*$query_data->dataScadenzaTessera = DATA_SCAD_TESSERA;
        $query_data->dataEmissioneTessera = "";
        $query_data->flagEmissioneTessera = "0";
        $query_data->tipoTessera = "Standard";
        $query_data->codiceTesseraMentor = "12";*/
        /*
        // Solo se devo calcolare l'importo
        if ( !isset( $query_data->importo ) || trim( $query_data->importo ) == "" || $query_data->importo == 0 ) {
        	$query_data->importo = $query_data->tipotessera;
        	if ( is_numeric( $query_data->donazione_aggiuntiva ) ) {
        		$query_data->importo = $query_data->tipotessera + $query_data->donazione_aggiuntiva;
        	}
        }*/
    }
    if ( !in_array( "causale", $req_fields ) && ( !isset( $query_data->causale ) || trim( $query_data->causale ) == "" ) )$query_data->causale = ID_CAMPAGNA_DEFAULT; 
    if ( !isset( $query_data->tessera ) || trim( $query_data->tessera ) == "" )$query_data->tessera = "N";
    if ( !isset( $query_data->esito ) || trim( $query_data->esito ) == "" )$query_data->esito = "WA";
    if ( !in_array( "centro", $req_fields ) && ( !isset( $query_data->centro ) || trim( $query_data->centro ) == "" ) )$query_data->centro = CENTRO_DEFAULT;
    //Mandato
    if ( !in_array( "codiceCanale", $req_fields ) && ( !isset( $query_data->codiceCanale ) || trim( $query_data->codiceCanale ) == "" ) )$query_data->codiceCanale = CANALE_DEFAULT; // 10 =WEB - SITO
    // Set any fields NOT present in the form to default value - END
    //$query_data->nome = strtoupper( $query_data->nome ); //Name
    $query_data->nome = mb_strtoupper( $query_data->nome, 'UTF-8' ); //Name
    if ( $query_data->sesso != "S" && ( !isset( $query_data->nome ) || trim( $query_data->nome ) == "" ) ) {
        $errore++;
        $messaggio_errore .= "M001|";
        $arr_errore[ 'M001' ] = 0; //"Manca il nome";
    } 
    //$query_data->cognome = strtoupper( $query_data->cognome ); //Surname
    $query_data->cognome = mb_strtoupper( $query_data->cognome, 'UTF-8' ); //Surname
    if ( $query_data->sesso != "S" && ( !isset( $query_data->cognome ) || trim( $query_data->cognome ) == "" ) ) {
        $errore++;
        $messaggio_errore .= "M002|";
        $arr_errore[ 'M002' ] = 0; //"Manca il cognome";
    } 
    //$query_data->ragioneSociale = strtoupper( $query_data->ragioneSociale ); //RAGIONE SOCIALE
    if (isset($query_data->ragioneSociale)) $query_data->ragioneSociale = mb_strtoupper( $query_data->ragioneSociale, 'UTF-8' ); //RAGIONE SOCIALE
    if ( $query_data->sesso == "S" && ( !isset( $query_data->ragioneSociale ) || trim( $query_data->ragioneSociale ) == "" ) ) {
        $errore++;
        $messaggio_errore .= "M003|";
        $arr_errore[ 'M003' ] = 0; //"Manca la ragione sociale";
    } 
    $query_data->mail = strtolower( $query_data->mail ); //MAIL
    if ( in_array( "mail", $req_fields ) && ( !isset( $query_data->mail ) || trim( $query_data->mail ) == "" ) ) {
        $errore++;
        $messaggio_errore .= "M005|";
        $arr_errore[ 'M005' ] = 0; //"Manca la mail";
    } 
    elseif ( in_array( "mail", $req_fields ) && !filter_var( $query_data->mail, FILTER_VALIDATE_EMAIL ) ) {
        $errore++;
        $messaggio_errore .= "E005|";
        $arr_errore[ 'E005' ] = 0; //"La mail risulta errata";
    }
    if ( in_array( "sesso", $req_fields ) && ( !isset( $query_data->sesso ) || trim( $query_data->sesso ) == "" ) ) {
        $errore++;
        $messaggio_errore .= "M004|";
        $arr_errore[ 'M004' ] = 0; //"Manca il sesso";
    }
    //$query_data->indirizzo = strtoupper( $query_data->indirizzo ); //INDIRIZZO
    if(isset($query_data->indirizzo)) $query_data->indirizzo = mb_strtoupper( $query_data->indirizzo, 'UTF-8' ); //INDIRIZZO
    if ( in_array( "indirizzo", $req_fields ) && ( !isset( $query_data->indirizzo ) || trim( $query_data->indirizzo ) == "" ) ) {
        $errore++;
        $messaggio_errore .= "M007|";
        $arr_errore[ 'M007' ] = 0; //"Manca l'indirizzo postale'";
    }
    if ( in_array( "civico", $req_fields ) && ( !isset( $query_data->civico ) || trim( $query_data->civico ) == "" ) ) {
        $errore++;
        $messaggio_errore .= "M008|";
        $arr_errore[ 'M008' ] = 0; //"Manca il numero civico'";
    }
    if ( in_array( "cap", $req_fields ) && ( !isset( $query_data->cap ) || trim( $query_data->cap ) == "" ) ) {
        $errore++;
        $messaggio_errore .= "M009|";
        $arr_errore[ 'M009' ] = 0; //"Manca il CAP";
    }
    if ( in_array( "cap", $req_fields ) && $query_data->stato == "I" && !preg_match( "/^[0-9]{5}$/", $query_data->cap ) ) {
        $errore++;
        $messaggio_errore .= "E009|";
        $arr_errore[ 'E009' ] = 0; //"Il CAP risulta errato";
    }
    //$query_data->citta = strtoupper( $query_data->citta ); // CITTA
    if(isset($query_data->citta)) $query_data->citta = mb_strtoupper( $query_data->citta, 'UTF-8' ); // CITTA
    if ( in_array( "citta", $req_fields ) && ( !isset( $query_data->citta ) || trim( $query_data->citta ) == "" ) ) {
        $errore++;
        $messaggio_errore .= "M010|";
        $arr_errore[ 'M010' ] = 0; //"Manca la citt&agrave;";
    }
    if (isset($query_data->stato) && $query_data->stato == "I" ) {
        //$query_data->provincia = strtoupper( $query_data->provincia );
        $query_data->provincia = mb_strtoupper( $query_data->provincia, 'UTF-8' );
        if ( in_array( "provincia", $req_fields ) && ( !isset( $query_data->provincia ) || trim( $query_data->provincia ) == "" ) ) {
            $errore++;
            $messaggio_errore .= "M011|";
            $arr_errore[ 'M011' ] = 0; //"Manca la provincia";
        }
        $province = array( "AG", "AL", "AN", "AO", "AQ", "AR", "AP", "AT", "AV", "BA", "BT", "BL", "BN", "BG", "BI", "BO", "BZ", "BS", "BR", "CA", "CL", "CB", "CI", "CE", "CT", "CZ", "CH", "CO", "CS", "CR", "KR", "CN", "EN", "FM", "FE", "FI", "FG", "FC", "FR", "GE", "GO", "GR", "IM", "IS", "SP", "LT", "LE", "LC", "LI", "LO", "LU", "MC", "MN", "MS", "MT", "VS", "ME", "MI", "MO", "MB", "NA", "NO", "NU", "OG", "OT", "OR", "PD", "PA", "PR", "PV", "PG", "PU", "PE", "PC", "PI", "PT", "PN", "PZ", "PO", "RG", "RA", "RC", "RE", "RI", "RN", "RM", "RO", "SA", "SS", "SV", "SI", "SR", "SO", "TA", "TE", "TR", "TO", "TP", "TN", "TV", "TS", "UD", "VA", "VE", "VB", "VC", "VR", "VV", "VI", "VT" );
        if ( !in_array( strtoupper( $query_data->provincia ), $province ) && trim( $query_data->provincia ) != "" && $query_data->stato == "I" ) {
            $errore++;
            $messaggio_errore .= "E011|";
            $arr_errore[ 'M011' ] = 0; //"La provincia risulta errata";
        }
    } else{
        $query_data->provincia ="";   
    }
    if ( in_array( "stato", $req_fields ) && ( !isset( $query_data->stato ) || trim( $query_data->stato ) == "" ) ) {
        $errore++;
        $messaggio_errore .= "M012|";
        $arr_errore[ 'M012' ] = 0; //"Manca lo stato";
    }
    $stati = array( "AFG", "AX", "AR", "DZ", "AS", "AND", "AN", "AI", "AQ", "AG", "SA", "RA", "ARM", "AW", "AUS", "A", "AZ", "BS", "BRN", "BD", "BDS", "BY", "B", "BH", "BJ", "BM", "BHT", "BOL", "BQ", "BIH", "RB", "BV", "BR", "IO", "BRU", "BG", "RHV", "BU", "K", "CAM", "CDN", "CV", "CYM", "RCA", "TCH", "CN", "CX", "RCH", "CY", "CC", "CO", "COM", "CD", "ZRE", "CK", "CR", "CI", "HR", "C", "CW", "DK", "DJI", "WD", "EC", "ET", "ES", "AE", "GQ", "ERI", "EE", "EST", "ETH", "FK", "FR", "RUS", "FJI", "RP", "FIN", "F", "GF", "PF", "TF", "GAB", "WAG", "GE", "D", "GH", "J", "GBZ", "GR", "GRL", "WG", "GP", "GUA", "GCA", "GG", "RG", "GW", "GUY", "RH", "HM", "SCV", "HN", "HK", "IND", "RI", "IR", "IRQ", "IRL", "IS", "IM", "VG", "IL", "I", "JA", "JE", "HKJ", "KZ", "EAK", "KI", "146", "ROK", "KWT", "KG", "LAO", "LS", "LR", "RL", "LB", "LAR", "FL", "LT", "L", "123", "MK", "RM", "MW", "ML", "MAL", "RMM", "M", "MA", "MH", "141", "RIM", "MU", "YT", "MEX", "FM", "MD", "MC", "MGL", "MNE", "MS", "MOC", "BUR", "SWA", "NR", "NEP", "NIC", "RN", "WAN", "NU", "NF", "MP", "N", "114", "NZ", "139", "NL", "PK", "PW", "PS", "PA", "PNG", "PY", "PE", "PN", "PL", "P", "PR", "Q", "GB", "CZ", "DOM", "RE", "RO", "RWA", "BL", "SH", "KN", "LC", "MF", "PM", "VC", "WS", "RSM", "ST", "SN", "SRB", "SY", "WAL", "SGP", "SX", "SK", "SLO", "SB", "SP", "GS", "SS", "E", "CL", "USA", "ZA", "SUD", "SME", "SJ", "S", "CH", "SD", "SYR", "RC", "TGK", "EAT", "T", "301", "TG", "TK", "TO", "TT", "TN", "TR", "TM", "TC", "TV", "UA", "EAU", "H", "UM", "ROU", "UZ", "VU", "YV", "VN", "VI", "WF", "EH", "YAR", "Z", "ZW" );
    if ( isset($query_data->stato) && !in_array( strtoupper( $query_data->stato ), $stati ) && trim( $query_data->stato ) != "" ) {
        $errore++;
        $messaggio_errore .= "E012|";
        $arr_errore[ 'E012' ] = 0; //"Lo Stato risulta errato";
    }
    if ( in_array( "tel", $req_fields ) && ( !isset( $query_data->tel ) || trim( $query_data->tel ) == "" ) ) {
        $errore++;
        $messaggio_errore .= "M006|";
        $arr_errore[ 'M006' ] = 0; //"Manca il telefono";
    } 
    else {
        if ( preg_match( '/^3\d{8,9}$/', $query_data->tel ) ) {
            $query_data->tipotel = "cellulare";
            $query_data->cellulare1 = $query_data->tel;
        } 
        elseif ( preg_match( '/^0\d{7,8}$/', $query_data->tel ) ) {
            $query_data->tipotel = "fisso";
            $query_data->telefono1 = $query_data->tel;
        }
        else {
            $query_data->tipotel = "ND";
            $query_data->cellulare1 = $query_data->tel;
        }
    }
    if ( in_array( "codFis", $req_fields ) && ( !isset( $query_data->codFis ) || trim( $query_data->codFis ) == "" ) ) {
        $query_data->codFis = strtoupper( $query_data->codFis );
        $errore++;
        $messaggio_errore .= "M014|";
        $arr_errore[ 'M014' ] = 0; //"Manca il Codice Fiscale";
    }
    if ( ( $query_data->pay_method == "SP" || $query_data->pay_method == "SD" ) && ( !isset( $query_data->codFis ) || trim( $query_data->codFis ) == "" ) ) {
        $errore++;
        $messaggio_errore .= "M014|";
        $arr_errore[ 'M014' ] = 0; //"Manca il Codice Fiscale";
    } //Togliere se CF non obbligatorio per SDD
    if ( isset( $query_data->codFis ) && trim( $query_data->codFis ) != "" ) {
        $cf = new CodiceFiscale();
        if ( isset($query_data->stato) && $query_data->stato == "I" && !$cf->ValidaCodiceFiscale( $query_data->codFis ) ) {
            $errore++;
            $messaggio_errore .= "E014|";
            $arr_errore[ 'E014' ] = 0; //"Il Codice Fiscale risulta errato";
        }
    }
    if ( in_array( "PIVA", $req_fields ) && ( !isset( $query_data->PIVA ) || trim( $query_data->PIVA ) == "" ) ) {
        $errore++;
        $messaggio_errore .= "M013|";
        $arr_errore[ 'M013' ] = 0; //"Manca la Partita IVA";
    }
    if ( in_array( "PIVA", $req_fields ) && isset($query_data->stato)  && $query_data->stato == "I" && !preg_match( "/^[0-9]{11}$/", $query_data->PIVA ) ) {
        $errore++;
        $messaggio_errore .= "E013|";
        $arr_errore[ 'E013' ] = 0; //"La Partita IVA risulta errata";
    }

    if ( !isset( $query_data->privacy ) || trim( $query_data->privacy ) == "" ) {
        $errore++;
        $messaggio_errore .= "M036|";
        $arr_errore[ 'M036' ] = 0; //"Manca il consenso al trattamento dati";
    }
    //DONAZIONE
    if ( !isset( $query_data->tipo_donazione ) || trim( $query_data->tipo_donazione ) == "" ) {
        $errore++;
        $messaggio_errore .= "M069|";
        $arr_errore[ 'M069' ] = 0; //"Manca il tipo di donazione";

    }
    if ( "oneoff" == $query_data->tipo_donazione || "regular" == $query_data->tipo_donazione ) {
        if ( !isset( $query_data->importo ) || trim( $query_data->importo ) == "" ) {
            $errore++;
            $messaggio_errore .= "M021|";
            $arr_errore[ 'M021' ] = "Manca l'importo";
        } elseif ( !preg_match( "/^[0-9]*$/", $query_data->importo ) ) {
            $errore++;
            $messaggio_errore .= "E021|";
            $arr_errore[ 'E021' ] = 0; //"L'importo deve esser un numero intero";
        }
        if ( !isset( $query_data->importo ) || trim( $query_data->importo ) == "" || $query_data->importo == 0 ) {
            if ( isset( $query_data->importo_libero ) &&  is_numeric( $query_data->importo_libero) && $query_data->importo_libero >= IMPORTO_MINIMO_ONE ) {
                $query_data->importo = $query_data->importo_libero;    
            }
        }
        if ( $query_data->tipo_donazione == "oneoff" && defined( 'IMPORTO_MINIMO_ONE' ) && $query_data->importo < IMPORTO_MINIMO_ONE ) {
            $errore++;
            $messaggio_errore .= "E021|";
            $arr_errore[ 'E021' ] = 0; //"L'importo deve essere maggiore di " . IMPORTO_MINIMO_ONE . " euro";
        }
        //Id_a e importo sono sempre obbligatori per la doanzione per cui non verifico nemmeno se sono in reqfield
        if ( in_array( "pay_method", $req_fields ) && ( !isset( $query_data->pay_method ) || trim( $query_data->pay_method ) == "" ) ) {
            $errore++;
            $messaggio_errore .= "M056|";
            $arr_errore[ 'M056' ] = 0; //"Manca il metodo di pagamento";
        }
        // Carta di credito 
        if ( isset( $query_data->pay_method ) && $query_data->pay_method == "CC" ) { //Verifico la carta di credito
            $verifyCard = checkCC( $query_data->cartan );
            if ( !isset( $query_data->cartan ) || trim( $query_data->cartan ) == "" ) {
                $errore++;
                $messaggio_errore .= "M027|";
                $arr_errore[ 'M027' ] = 0; //"Manca il numero della carta di credito";
            } elseif ( $verifyCard[ 0 ] != "" ) {
                    $errore++;
                    $messaggio_errore .= "E027|";
                    $arr_errore[ 'E027' ] = 0; //"Il numero della carta di credito risulta errato";
                }
                //if ($verifyCard[0]=="" && $verifyCard[2] =="KO") {$errore++; $messaggio_errore .= "Circuito NON supportato";}
            if ( !isset( $query_data->exp_mm ) || trim( $query_data->exp_mm ) == "" ) {
                $errore++;
                $messaggio_errore .= "M028|";
                $arr_errore[ 'M028' ] = 0; //"Manca il mese di scadenza della carta di credito";

            } elseif ( !preg_match( "/^(0[0-9]|1[012])$/", $query_data->exp_mm ) ) {

                $errore++;
                $messaggio_errore .= "E028|";
                $arr_errore[ 'E028' ] = 0; //"Il mese di scadenza della carta di credito risulta errato";
            }
            if ( !isset( $query_data->exp_yy ) || trim( $query_data->exp_yy ) == "" ) {
                $errore++;
                $messaggio_errore .= "M029|";
                $arr_errore[ 'M029' ] = 0; //"Manca l'anno di scadenza della carta di credito";
            } elseif ( !preg_match( "/^[0-9]{2}$/", $query_data->exp_yy ) ) {
                $errore++;
                $messaggio_errore .= "E029|";
                $arr_errore[ 'E029' ] = 0; //"L'anno di scadenza della carta di credito risulta errato";
            }

            if ( !isset( $query_data->cvv ) || trim( $query_data->cvv ) == "" ) {
                $errore++;
                $messaggio_errore .= "M030|";
                $arr_errore[ 'M030' ] = 0; //"Manca il CVV carta di credito";

            } elseif ( !preg_match( "/^([0-9]{3,4})$/", $query_data->cvv ) ) {
                $errore++;
                $messaggio_errore .= "M030|";
                $arr_errore[ 'M030' ] = 0; //"Il CVV della carta di credito risulta errato";
            }
            if ( in_array( "titolare", $req_fields ) && ( !isset( $query_data->titolare ) || trim( $query_data->titolare ) == "" ) ) {
                $errore++;
                $messaggio_errore .= "M031|";
                $arr_errore[ 'M031' ] = 0; //"Manca il titolare carta di credito";
            }

        }
        if ( in_array( "causale", $req_fields ) && ( !isset( $query_data->causale ) || trim( $query_data->causale ) == "" ) ) {
            $errore++;
            $messaggio_errore .= "M026|";
            $arr_errore[ 'M026' ] = 0; //"Manca la casuale della donazione";
        }
        $query_data->nota = strip_tags( (string)$query_data->nota );

        if ( in_array( "nota", $req_fields ) && ( !isset( $query_data->nota ) || trim( $query_data->nota ) == "" ) ) {
            $errore++;
            $messaggio_errore .= "M022|";
            $arr_errore[ 'M022' ] = 0; //"Manca la nota della donazione";
        }
        if ( in_array( "centro", $req_fields ) && ( !isset( $query_data->centro ) || trim( $query_data->centro ) == "" ) ) {
            $errore++;
            $messaggio_errore .= "M024";
            $arr_errore[ 'M024' ] = 0; //"Manca la destinazione della donazione";
        }
    }
    //MANDATO
    if ( "regular" == $query_data->tipo_donazione ) {
        if ( !isset( $query_data->generaSostegno ) || trim( $query_data->generaSostegno ) == "" )$query_data->generaSostegno = "0"; // 0= NO SAD; 1 = SAD
        if ( !isset( $query_data->frequenza ) || trim( $query_data->frequenza ) == "" ) {
            $errore++;
            $messaggio_errore .= "M033|";
            $arr_errore[ 'M033' ] = 0; //"Manca la frequenza della donazione";
        }
        $importo_annuo = 12 / $query_data->frequenza * $query_data->importo; //Importo Annuo
        if ( defined( 'IMPORTO_MINIMO_REG' ) && $importo_annuo < IMPORTO_MINIMO_REG ) {
            $errore++;
            $messaggio_errore .= "E021|";
            $arr_errore[ 'E021' ] = 0; //"L'importo deve essere maggiore di " . IMPORTO_MINIMO_REG . " euro";
        }
        switch ( $query_data->pay_method ) {
            case "CC":
                $query_data->metodo = "K";
                break;
            case "PP":
                $query_data->metodo = "Y";
                break;
            case "SD":
                $query_data->metodo = "R";
                break;
            case "SP":
                $query_data->metodo = "P";
                break;
        }
        if ( ( $query_data->pay_method == "SP" || $query_data->pay_method == "SD" ) && ( !isset( $query_data->IBAN ) || trim( $query_data->IBAN ) == "" ) ) {
            $errore++;
            $messaggio_errore .= "M032|";
            $arr_errore[ 'M033' ] = 0; //"Manca l'IBAN";
        }
        if ( ( $query_data->pay_method == "SP" || $query_data->pay_method == "SD" ) && ( checkIBAN( $query_data->IBAN ) == "E032|" ) ) {
            $errore++;
            $messaggio_errore .= "E032|";
            $arr_errore[ 'M032' ] = 0; //"L'IBAN risulta errato";
        }
        if ( ( $query_data->pay_method == "SP" || $query_data->pay_method == "SD" ) && strtoupper( substr( $query_data->IBAN, 0, 2 ) ) != "IT" && ( !isset( $query_data->BIC ) || trim( $query_data->BIC ) == "" ) ) {
            $errore++;
            $messaggio_errore .= "M064|";
            $arr_errore[ 'M064' ] = 0; //"Manca Il BIC";
        }
        //Manca validazione formale BIC
        if ( $query_data->pay_method == "SD" ) {
            //"P" solo se l'ABI è quello delle poste (07601) 
            //$iban="IT 02L 54231 12345 123456789012";
            $chk_posta = substr( str_replace( " ", "", $query_data->IBAN ), 5, 5 );
            if ( $chk_posta == "07601" || $chk_posta == "36081" ) { //Poste o  PostePay
                $query_data->metodo = "P";
            } else {
                $query_data->metodo = "R";
            }
        }
    }
    // Tessera in regalo
    if ( "TGIFT" == $query_data->tipo_donazione ) { //Lead 

    }
    if ( $query_data->centro == TESSERA_GIFT ) {
        if ( !isset( $query_data->destinatari ) || !is_object( $query_data->destinatari ) ) {
            $errore++;
            $messaggio_errore .= "M086";
            $arr_errore[ 'M086' ] = 0; //"Mancano i dati dei/l destnatari/io";
        } else {
            if ( !array_key_exists( "d", $query_data->destinatari ) ) {
                $errore++;
                $messaggio_errore .= "M087";
                $arr_errore[ 'M087' ] = 0; //"Manca il numero dei destinatari";
            } else {
                //Nome destinatario
                $query_data->destinatari->d1->nomed = mb_strtoupper( $query_data->destinatari->d1->nomed, 'UTF-8' ); //NOME
                if ( !isset( $query_data->destinatari->d1->nomed ) || trim( $query_data->destinatari->d1->nomed ) == "" ) {
                    $errore++;
                    $messaggio_errore .= "M089|";
                    $arr_errore[ 'M089' ] .= $i . ",";
                }
                //Cognome destinatario
                $query_data->destinatari->d1->cognomed = mb_strtoupper( $query_data->destinatari->d1->cognomed, 'UTF-8' ); //NOME
                if ( !isset( $query_data->destinatari->d1->cognomed ) || trim( $query_data->destinatari->d1->cognomed ) == "" ) {
                    $errore++;
                    $messaggio_errore .= "M090|";
                    $arr_errore[ 'M090' ] .= $i . ",";
                }

                //Mail destinatario
                $query_data->destinatari->d1->maild = strtolower( $query_data->destinatari->d1->maild ); //MAIL
                if ( !isset( $query_data->destinatari->d1->maild ) || trim( $query_data->destinatari->d1->maild ) == "" ) {
                    $errore++;
                    $messaggio_errore .= "M091|";
                    $arr_errore[ 'M091' ] .= $i . ",";
                } elseif ( !filter_var( $query_data->destinatari->d1->maild, FILTER_VALIDATE_EMAIL ) ) {
                        $errore++;
                        $messaggio_errore .= "E091|";
                        $arr_errore[ 'E091' ] .= $i . ",";
                    }
                    //Data invio
                if ( "" == trim( $query_data->destinatari->d1->data_inviod ) ) {
                    $query_data->destinatari->d1->data_inviod = date( "Y-m-d" );
                }
                //DATA_MAX_GIFT controllo su data massima
                else {
                    $data_inviod = trim( $query_data->destinatari->d1->data_inviod );
                    $dateObj = DateTime::createFromFormat( 'Y-m-d', $data_inviod );
                    if ( !$dateObj || $dateObj->format( 'Y-m-d' ) !== $data_inviod ) {
                        $errore++;
                        $messaggio_errore .= "E092|";
                        $arr_errore[ 'E092' ] .= $i . ",";
                    }
                }
                // Gestione multidestinatario da sistemare
                /*for ( $i = 1; $i <= $query_data->destinatari[ 'd' ]; $i++ ) {
                	//Nome destinatario
                	$query_data->destinatari[ 'd' . $i ][ 'nomed' ] = mb_strtoupper( $query_data->destinatari[ 'd' . $i ][ 'nomed' ] , 'UTF-8' ); //NOME
                	//$query_data->destinatari[ 'd' . $i ][ 'nomed' ] = strtoupper( $query_data->destinatari[ 'd' . $i ][ 'nomed' ] ); //NOME
                	if ( !isset( $query_data->destinatari[ 'd' . $i ][ 'nomed' ] ) || trim( $query_data->destinatari[ 'd' . $i ][ 'nomed' ] ) == "" ) {
                		$errore++;
                		$messaggio_errore .= "M089|";
                		$arr_errore[ 'M089' ] .= $i . ",";
                	}
                	//Cognome destinatario
                	$query_data->destinatari[ 'd' . $i ][ 'cognomed' ] = mb_strtoupper( $query_data->destinatari[ 'd' . $i ][ 'cognomed' ] , 'UTF-8' ); //NOME
                	//$query_data->destinatari[ 'd' . $i ][ 'cognomed' ] = strtoupper( $query_data->destinatari[ 'd' . $i ][ 'cognomed' ] ); //NOME
                	if ( !isset( $query_data->destinatari[ 'd' . $i ][ 'cognomed' ] ) || trim( $query_data->destinatari[ 'd' . $i ][ 'cognomed' ] ) == "" ) {
                		$errore++;
                		$messaggio_errore .= "M090|";
                		$arr_errore[ 'M090' ] .= $i . ",";
                	}
                	//Mail destinatario
                	$query_data->destinatari[ 'd' . $i ][ 'maild' ] = strtolower( $query_data->destinatari[ 'd' . $i ][ 'maild' ] ); //MAIL
                	if ( !isset( $query_data->destinatari[ 'd' . $i ][ 'maild' ] ) || trim( $query_data->destinatari[ 'd' . $i ][ 'maild' ] ) == "" ) {
                		$errore++;
                		$messaggio_errore .= "M091|";
                		$arr_errore[ 'M091' ] .= $i . ",";
                	} elseif ( !filter_var( $query_data->destinatari[ 'd' . $i ][ 'maild' ], FILTER_VALIDATE_EMAIL ) ) {
                			$errore++;
                			$messaggio_errore .= "E091|";
                			$arr_errore[ 'E091' ] .= $i . ",";
                		}
                		//Data invio
                	if ( "" == trim( $query_data->destinatari[ 'd' . $i ][ 'data_inviod' ] ) ) {
                		$query_data->destinatari[ 'd' . $i ][ 'data_inviod' ] = date( "Y-m-d" );
                	}

                	//DATA_MAX_GIFT controllo su data massima
                	else {

                		if ( !preg_match( "/^(2019|2020)(-)(0[1-9]|1[012])(-)([0-2][0-9]|(3)[0-1])$/", trim( $query_data->destinatari[ 'd' . $i ][ 'data_inviod' ] ) ) ) {
                			$errore++;
                			$messaggio_errore .= "E092|";
                			$arr_errore[ 'E092' ] .= $i . ","; // Ipotesi gestione errore
                		}
                	}

                }*/
            }
        }
    }
    //	
    return array( $errore, $messaggio_errore, /*$codice_errore, $arr_codici_errore, $arr_messagigo_errore,*/ $arr_errore );
}