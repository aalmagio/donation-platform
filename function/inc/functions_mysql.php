<?php //GENERIC mySQL 202506 - Security hardening
/*
 * Added CodicePersonale, CodiceReferral in ScriviAnagrafica_mysql
 * 2025-06: Converted LeggiDati_mysql to prepared statements
 * 2025-06: Replaced die(mysqli_error()) with safe error logging
 * 2025-06: Added null checks on query results
 * 2025-07: Singleton DB connections - one TCP conn per DB per request
 */

function getSharedConnection( string $dbname = '' ): ?mysqli {
    static $connections = [];
    $key = $dbname !== '' ? $dbname : DB_DBNAME;
    if ( !isset( $connections[ $key ] ) || !$connections[ $key ]->ping() ) {
        $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, $key );
        if ( !$conn ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "getSharedConnection failed for $key: " . mysqli_connect_error() . PHP_EOL, 3, LOG_FILE );
            return null;
        }
        $connections[ $key ] = $conn;
    }
    return $connections[ $key ];
}

function ScriviAnagrafica_mysql( $anagrafica ) {
    $connection = getSharedConnection();
    if ( !$connection ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviAnagrafica_mysql: connessione fallita: " . mysqli_connect_error() . PHP_EOL, 3, LOG_FILE );
        return array( "Errore di connessione al database", "" );
    }
    if ( !( $stmt = $connection->prepare( "INSERT INTO Anagrafica (nome, cognome, ragioneSociale, sesso, indirizzo, civico, cap, citta, provincia, stato, tel, mail, codFis, PIVA, datanascita, privacy, id_fonte, id_campagna, IP, tipo_ana, operazione,lang, CodicePersonale, CodiceReferral ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,? )" ) ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviAnagrafica_mysql prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
        return array( "Errore di scrittura in mysql", "" );
    }
    // Variabili intermedie: bind_param richiede riferimenti, non valori di ritorno di funzioni
    $provincia = strtoupper( $anagrafica->provincia ?? '' );
    if ( $anagrafica->sesso == "S" ) {
        $nome = "C.A. " . $anagrafica->nome . " " . $anagrafica->cognome;
        $cognome = "";
        $CodicePersonale = generateReferralCode( $cognome, $anagrafica->CodTrans );
        if ( !$stmt->bind_param( 'ssssssssssssssssisssssss', $nome, $cognome, $anagrafica->ragioneSociale, $anagrafica->sesso, $anagrafica->indirizzo, $anagrafica->civico, $anagrafica->cap, $anagrafica->citta, $provincia, $anagrafica->stato, $anagrafica->tel, $anagrafica->mail, $anagrafica->codFis, $anagrafica->partitaIVA, $anagrafica->mysqldatanascita, $anagrafica->privacy, $anagrafica->id_fonte, $anagrafica->id_campagna, $anagrafica->IP, $anagrafica->tipo_ana, $anagrafica->tipo_donazione, $anagrafica->lang, $CodicePersonale, $anagrafica->CodiceReferral ) ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviAnagrafica_mysql bind failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
            $stmt->close();
            return array( "Errore di scrittura in mysql", "" );
        }
    } else {
        $CodicePersonale = generateReferralCode( $anagrafica->cognome, $anagrafica->CodTrans );
        if ( !$stmt->bind_param( 'ssssssssssssssssisssssss', $anagrafica->nome, $anagrafica->cognome, $anagrafica->ragioneSociale, $anagrafica->sesso, $anagrafica->indirizzo, $anagrafica->civico, $anagrafica->cap, $anagrafica->citta, $provincia, $anagrafica->stato, $anagrafica->tel, $anagrafica->mail, $anagrafica->codFis, $anagrafica->partitaIVA, $anagrafica->mysqldatanascita, $anagrafica->privacy, $anagrafica->id_fonte, $anagrafica->id_campagna, $anagrafica->IP, $anagrafica->tipo_ana, $anagrafica->tipo_donazione, $anagrafica->lang, $CodicePersonale, $anagrafica->CodiceReferral ) ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviAnagrafica_mysql bind failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
            $stmt->close();
            return array( "Errore di scrittura in mysql", "" );
        }
    }
    if ( !$stmt->execute() ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviAnagrafica_mysql execute failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
        $stmt->close();
        return array( "Errore di scrittura in mysql", "" );
    }
    $codice_anagrafica = $stmt->insert_id;
    $stmt->close();
    if ( !is_numeric( $codice_anagrafica ) ) {
        return array( "Errore di scrittura in mysql", "" );
    } else {
        return array( $codice_anagrafica, $CodicePersonale );
    }

}

function ScriviDonazione_mysql( $donazione ) {
    $connection = getSharedConnection();
    if ( !$connection ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviDonazione_mysql: connessione fallita: " . mysqli_connect_error() . PHP_EOL, 3, LOG_FILE );
        return false;
    }
    if ( !( $stmt = $connection->prepare( "INSERT INTO Donazione (CodTrans,Id_a,importo,pay_method,causale,nota,tessera,tipotessera,esito,centro,tipo,codicePartner) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)" ) ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviDonazione_mysql prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
        return false;
    }
    // Strip 4-byte UTF-8 characters (emoji, symbols outside BMP) that MySQL utf8 (3-byte) can't store
    $donazione->nota = preg_replace( '/[\x{10000}-\x{10FFFF}]/u', '', (string) $donazione->nota );
    if ( !$stmt->bind_param( 'sidssssssiss', $donazione->CodTrans, $donazione->Id_a, $donazione->importo, $donazione->pay_method, $donazione->causale, $donazione->nota, $donazione->tessera, $donazione->tipoTessera, $donazione->esito, $donazione->centro, $donazione->tipo_donazione, $donazione->codicePartner ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviDonazione_mysql bind failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
        $stmt->close();
        return false;
    }
    if ( !$stmt->execute() ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviDonazione_mysql execute failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
        $stmt->close();
        return false;
    }
    $stmt->close();
    return $donazione->CodTrans;

}

//mySQL - Tessera In regalo - BEGIN
function ScriviDonati_mysql( $donati_arr ) {
    $connection = getSharedConnection( DB_DBNAME_TGIFT );
    if ( !$connection ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviDonati_mysql: connessione fallita: " . mysqli_connect_error() . PHP_EOL, 3, LOG_FILE );
        return array();
    }
    $GUID = guid();
    $campagna = ID_CAMPAGNA_DONATI_DEFAULT;
    if ( !( $stmt = $connection->prepare( "INSERT INTO Voucher (Id_donatore, CodTrans, GUID, nome_d, cognome_d, mail_d, campagna, campagna_donazione, data_invio_mail ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ? )" ) ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviDonati_mysql prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
        return array();
    }
    if ( !$stmt->bind_param( 'issssssss', $donati_arr->Id_a, $donati_arr->CodTrans, $GUID, $donati_arr->destinatari->d1->nomed, $donati_arr->destinatari->d1->cognomed, $donati_arr->destinatari->d1->maild, $campagna, $donati_arr->id_campagna, $donati_arr->destinatari->d1->data_inviod ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviDonati_mysql bind failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
        $stmt->close();
        return array();
    }
    if ( !$stmt->execute() ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviDonati_mysql execute failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
        $stmt->close();
        return array();
    }
    $codice_donato = $stmt->insert_id;
    $stmt->close();
    return array( $codice_donato );

}

function ScriviAnagraficaDonato_mysql( $anagrafica ) {
    $connection = getSharedConnection( DB_DBNAME_TGIFT );
    if ( !$connection ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviAnagraficaDonato_mysql: connessione fallita: " . mysqli_connect_error() . PHP_EOL, 3, LOG_FILE );
        return array( "Errore di connessione al database", "" );
    }
    if ( !( $stmt = $connection->prepare( "INSERT INTO Anagrafica (nome, cognome, ragioneSociale, sesso, indirizzo, civico, cap, citta, provincia, stato, tel, mail, codFis, datanascita, privacy, id_fonte, id_campagna, IP, tipo_ana, operazione,lang ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,? )" ) ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviAnagraficaDonato_mysql prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
        return array( "Errore di scrittura in mysql", "" );
    }
    if ( !$stmt->bind_param( 'sssssssssssssssisssss', $anagrafica->nome, $anagrafica->cognome, $anagrafica->ragioneSociale, $anagrafica->sesso, $anagrafica->indirizzo, $anagrafica->civico, $anagrafica->cap, $anagrafica->citta, strtoupper( $anagrafica->provincia ), $anagrafica->stato, $anagrafica->tel, $anagrafica->mail, $anagrafica->codFis, $anagrafica->mysqldatanascita, $anagrafica->privacy, $anagrafica->id_fonte, $anagrafica->id_campagna, $anagrafica->IP, $anagrafica->tipo_ana, $anagrafica->tipo_donazione, $anagrafica->lang ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviAnagraficaDonato_mysql bind failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
        $stmt->close();
        return array( "Errore di scrittura in mysql", "" );
    }
    if ( !$stmt->execute() ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviAnagraficaDonato_mysql execute failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
        $stmt->close();
        return array( "Errore di scrittura in mysql", "" );
    }
    $codice_anagrafica = $stmt->insert_id;
    $stmt->close();
    if ( !is_numeric( $codice_anagrafica ) ) {
        return array( "Errore di scrittura in mysql", "" );
    } else {
        return array( $codice_anagrafica, "" );
    }

}
//mySQL - Tessera In regalo - END

function ScriviMandato_mysql( $mandato ) {
    $connection = getSharedConnection();
    if ( !$connection ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviMandato_mysql: connessione fallita: " . mysqli_connect_error() . PHP_EOL, 3, LOG_FILE );
        return false;
    }
    if ( !( $stmt = $connection->prepare( "INSERT INTO Mandato (Id_a,codiceDonatore,codiceCampanga,codiceCentro,codiceCanale,importo,frequenza,metodo,IBAN,BIC,Token,meseToken,annoToken,nomeTitolare,codiceFiscaleTitolare,indirizzoTitolare,localitaTitolare,provinciaTitolare,cap,note) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)" ) ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviMandato_mysql prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
        return false;
    }
    if ( !$stmt->bind_param( 'issssiisssssssssssss', $mandato->Id_a, $mandato->codiceAnagraficaMentor, $mandato->id_campagna, $mandato->centro, $mandato->codiceCanale, $mandato->importo, $mandato->frequenza, $mandato->metodo, strtoupper( $mandato->IBAN ), $mandato->BIC, $mandato->Token, $mandato->meseToken, $mandato->annoToken, $mandato->titolare, $mandato->codiceFiscaleTitolare, $mandato->indirizzoTitolare, $mandato->localitaTitolare, $mandato->provinciaTitolare, $mandato->cap, $mandato->nota ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviMandato_mysql bind failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
        $stmt->close();
        return false;
    }
    if ( !$stmt->execute() ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ScriviMandato_mysql execute failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
        $stmt->close();
        return false;
    }
    $codice_mandato = $stmt->insert_id;
    $stmt->close();
    return $codice_mandato;

}

function aggiornaEsitoDonazione( $donazione ) {
    $connection = getSharedConnection();
    if ( !$connection ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "aggiornaEsitoDonazione: connessione fallita" . PHP_EOL, 3, LOG_FILE );
        return;
    }
    if ( !( $stmt = $connection->prepare( "UPDATE Donazione SET esito=? WHERE CodTrans=?;" ) ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "aggiornaEsitoDonazione prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
        return;
    }
    if ( isset( $donazione->PP_esito ) ) {
        $donazione->esito = $donazione->PP_esito;
    } elseif ( isset( $donazione->TransactionResult ) ) {
        $donazione->esito = $donazione->TransactionResult;
    }
    $stmt->bind_param( 'ss', $donazione->esito, $donazione->CodTrans );
    if ( !$stmt->execute() ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "aggiornaEsitoDonazione execute failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
    }
    $stmt->close();
}

function aggiornaDonati_mysql( $donati_arr ) {
    $connection = getSharedConnection( DB_DBNAME_TGIFT );
    if ( !$connection ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "aggiornaDonati_mysql: connessione fallita" . PHP_EOL, 3, LOG_FILE );
        return array();
    }

    // Leggi Id_donato?GUID per il valore di ritorno
    $stmt_select = $connection->prepare( "SELECT Id_donato, GUID FROM Voucher WHERE CodTrans = ?" );
    if ( !$stmt_select ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "aggiornaDonati_mysql prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
        return array();
    }
    $stmt_select->bind_param( 's', $donati_arr->CodTrans );
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $codici_donati = array();
    while ( $row = $result->fetch_assoc() ) {
        $codici_donati[ $row[ 'Id_donato' ] ] = $row[ 'GUID' ];
    }
    $stmt_select->close();

    // Singolo UPDATE per tutti i voucher della transazione (era N+1)
    if ( !empty( $codici_donati ) ) {
        $stmt_update = $connection->prepare(
            "UPDATE Voucher SET Esito_donazione=?, id_mentor_donatore=?, id_mentor_donazione=? WHERE CodTrans=?"
        );
        if ( !$stmt_update ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "aggiornaDonati_mysql update prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
        } else {
            $stmt_update->bind_param( 'ssss', $donati_arr->EsitoDonazione, $donati_arr->codiceAnagraficaMentor, $donati_arr->codiceDonazioneMentor, $donati_arr->CodTrans );
            if ( !$stmt_update->execute() ) {
                error_log( date( '[Y-m-d H:i:s e] ' ) . "aggiornaDonati_mysql execute failed: " . $stmt_update->error . PHP_EOL, 3, LOG_FILE );
            }
            $stmt_update->close();
        }
    }

    return $codici_donati;
}

function aggiornaVoucher_mysql( $donati_arr ) {
    $connection = getSharedConnection( DB_DBNAME_TGIFT );
    if ( !$connection ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "aggiornaVoucher_mysql: connessione fallita" . PHP_EOL, 3, LOG_FILE );
        return;
    }
    if ( !( $stmt = $connection->prepare( "UPDATE Voucher SET id_richiesta=? WHERE Id_donato=?;" ) ) ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "aggiornaVoucher_mysql prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE );
        return;
    }
    $stmt->bind_param( 'si', $donati_arr->Id_donatore, $donati_arr->Id_donato );
    if ( !$stmt->execute() ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "aggiornaVoucher_mysql execute failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE );
    }
    $stmt->close();
}

function LeggiDati_mysql( $richiesta ) {
    $answer_donazione = ( object )array();

    if ( isset( $richiesta->CodTrans ) && trim( $richiesta->CodTrans ) != "" && preg_match( "/^[A-Z]{1}-[0-9]{17}-[A-Z]{2}/", $richiesta->CodTrans ) ) {
        // Query su codice transazione - PREPARED STATEMENT
        $connection = getSharedConnection();
        if ( !$connection ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "LeggiDati_mysql: connessione fallita" . PHP_EOL, 3, LOG_FILE );
            return $answer_donazione;
        }
        $stmt = $connection->prepare( "SELECT Anagrafica.*, Donazione.CodTrans, Donazione.importo, Donazione.pay_method, Donazione.tessera, Donazione.centro, Donazione.tipotessera, Donazione.esito, Donazione.`data`, Donazione.tipo, Donazione.nota, Donazione.codicePartner FROM Donazione LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a WHERE Donazione.CodTrans = ?" );
        if ( !$stmt ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "LeggiDati_mysql prepare failed (CodTrans): " . $connection->error . PHP_EOL, 3, LOG_FILE );
            return $answer_donazione;
        }
        $stmt->bind_param( 's', $richiesta->CodTrans );
        $stmt->execute();
        $result = $stmt->get_result();
        $row_donazione = $result->fetch_assoc();
        $stmt->close();

        if ( !$row_donazione ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "LeggiDati_mysql: nessun risultato per CodTrans " . $richiesta->CodTrans . PHP_EOL, 3, LOG_FILE );
            return $answer_donazione;
        }
        foreach ( $row_donazione as $key => $value ) {
            if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                $answer_donazione->$key = $value;
            }
        }
        if ( isset( $row_donazione[ 'tipo' ] ) && $row_donazione[ 'tipo' ] == "regular" ) {
            $stmt2 = $connection->prepare( "SELECT frequenza, importo as importomandato FROM Mandato WHERE Id_a = ?" );
            if ( $stmt2 ) {
                $id_a = intval( $row_donazione[ 'Id_a' ] );
                $stmt2->bind_param( 'i', $id_a );
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                $row_mandato = $result2->fetch_assoc();
                $stmt2->close();
                if ( $row_mandato ) {
                    foreach ( $row_mandato as $key => $value ) {
                        if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                            $answer_donazione->$key = $value;
                        }
                    }
                }
            }
        }
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Query LeggiDati_mysql: " . json_encode( $answer_donazione ) . PHP_EOL, 3, LOG_FILE );
        return ( $answer_donazione );

    } elseif ( isset( $richiesta->Id_a ) && trim( $richiesta->Id_a ) != "" && is_numeric( $richiesta->Id_a ) ) {
        // Query su codice anagrafica - PREPARED STATEMENT
        $connection = getSharedConnection();
        if ( !$connection ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "LeggiDati_mysql: connessione fallita" . PHP_EOL, 3, LOG_FILE );
            return $answer_donazione;
        }
        $stmt = $connection->prepare( "SELECT * FROM Anagrafica WHERE Id_a = ?" );
        if ( !$stmt ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "LeggiDati_mysql prepare failed (Id_a): " . $connection->error . PHP_EOL, 3, LOG_FILE );
            return $answer_donazione;
        }
        $id_a = intval( $richiesta->Id_a );
        $stmt->bind_param( 'i', $id_a );
        $stmt->execute();
        $result = $stmt->get_result();
        $row_anagrafica = $result->fetch_assoc();
        $stmt->close();

        if ( !$row_anagrafica ) {
            return $answer_donazione;
        }
        foreach ( $row_anagrafica as $key => $value ) {
            if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                $answer_donazione->$key = $value;
            }
        }
        $stmt2 = $connection->prepare( "SELECT Donazione.CodTrans, Donazione.importo, Donazione.pay_method, Donazione.tessera, Donazione.centro, Donazione.tipotessera, Donazione.esito, Donazione.`data`, Donazione.tipo, Donazione.nota, Donazione.codicePartner FROM Donazione WHERE Donazione.Id_a = ?" );
        if ( $stmt2 ) {
            $stmt2->bind_param( 'i', $id_a );
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $row_donazione = $result2->fetch_assoc();
            $totalRows_donazione = $result2->num_rows;
            $stmt2->close();
            if ( $totalRows_donazione == 1 && $row_donazione ) {
                foreach ( $row_donazione as $key => $value ) {
                    if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                        $answer_donazione->$key = $value;
                    }
                }
            }
        }
        if ( isset( $row_anagrafica[ 'operazione' ] ) && $row_anagrafica[ 'operazione' ] == "regular" ) {
            $stmt3 = $connection->prepare( "SELECT frequenza, importo FROM Mandato WHERE Id_a = ?" );
            if ( $stmt3 ) {
                $stmt3->bind_param( 'i', $id_a );
                $stmt3->execute();
                $result3 = $stmt3->get_result();
                $row_mandato = $result3->fetch_assoc();
                $stmt3->close();
                if ( $row_mandato ) {
                    foreach ( $row_mandato as $key => $value ) {
                        if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                            $answer_donazione->$key = $value;
                        }
                    }
                }
            }
        }
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Query LeggiDati_mysql: " . json_encode( $answer_donazione ) . PHP_EOL, 3, LOG_FILE );
        return ( $answer_donazione );
    }
    elseif ( isset( $richiesta->paymentID ) && trim( $richiesta->paymentID ) != "" && is_numeric( $richiesta->paymentID ) ) {
        // Query su ID pagamento GestPay - PREPARED STATEMENT
        $connection = getSharedConnection();
        if ( !$connection ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "LeggiDati_mysql: connessione fallita" . PHP_EOL, 3, LOG_FILE );
            return $answer_donazione;
        }
        $stmt = $connection->prepare( "SELECT GestPayREST.shopTransactionID, Donazione.Id_a, Donazione.CodTrans, Donazione.importo, Donazione.pay_method, Donazione.tessera, Donazione.centro, Donazione.tipotessera, Donazione.esito, Donazione.`data`, Donazione.tipo, Donazione.nota, Donazione.codicePartner FROM `GestPayREST` LEFT JOIN Donazione ON GestPayREST.shopTransactionID = Donazione.CodTrans WHERE GestPayREST.paymentID = ?" );
        if ( !$stmt ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "LeggiDati_mysql prepare failed (paymentID): " . $connection->error . PHP_EOL, 3, LOG_FILE );
            return $answer_donazione;
        }
        $stmt->bind_param( 's', $richiesta->paymentID );
        $stmt->execute();
        $result = $stmt->get_result();
        $row_donazione = $result->fetch_assoc();
        $totalRows_donazione = $result->num_rows;
        $stmt->close();

        if ( $totalRows_donazione == 1 && $row_donazione ) {
            foreach ( $row_donazione as $key => $value ) {
                if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                    $answer_donazione->$key = $value;
                }
            }
        }
        if ( $row_donazione && isset( $row_donazione[ 'Id_a' ] ) ) {
            $stmt2 = $connection->prepare( "SELECT * FROM Anagrafica WHERE Id_a = ?" );
            if ( $stmt2 ) {
                $id_a = intval( $row_donazione[ 'Id_a' ] );
                $stmt2->bind_param( 'i', $id_a );
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                $row_anagrafica = $result2->fetch_assoc();
                $stmt2->close();
                if ( $row_anagrafica ) {
                    foreach ( $row_anagrafica as $key => $value ) {
                        if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                            $answer_donazione->$key = $value;
                        }
                    }
                    if ( isset( $row_anagrafica[ 'operazione' ] ) && $row_anagrafica[ 'operazione' ] == "regular" ) {
                        $stmt3 = $connection->prepare( "SELECT frequenza, importo FROM Mandato WHERE Id_a = ?" );
                        if ( $stmt3 ) {
                            $stmt3->bind_param( 'i', $id_a );
                            $stmt3->execute();
                            $result3 = $stmt3->get_result();
                            $row_mandato = $result3->fetch_assoc();
                            $stmt3->close();
                            if ( $row_mandato ) {
                                foreach ( $row_mandato as $key => $value ) {
                                    if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                                        $answer_donazione->$key = $value;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Query LeggiDati_mysql: " . json_encode( $answer_donazione ) . PHP_EOL, 3, LOG_FILE );
        return ( $answer_donazione );
    }
    elseif ( isset( $richiesta->g ) && trim( $richiesta->g ) != "" ) {
        // Query su GUID per tessera in regalo - PREPARED STATEMENT
        $connection = getSharedConnection( DB_DBNAME_TGIFT );
        if ( !$connection ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "LeggiDati_mysql: connessione fallita (TGIFT)" . PHP_EOL, 3, LOG_FILE );
            return $answer_donazione;
        }
        $stmt = $connection->prepare( "SELECT * FROM Voucher WHERE GUID = ?" );
        if ( !$stmt ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "LeggiDati_mysql prepare failed (GUID): " . $connection->error . PHP_EOL, 3, LOG_FILE );
            return $answer_donazione;
        }
        $stmt->bind_param( 's', $richiesta->g );
        $stmt->execute();
        $result = $stmt->get_result();
        $row_donato = $result->fetch_assoc();
        $totalRows_donato = $result->num_rows;
        $stmt->close();

        if ( 1 <> $totalRows_donato ) {
            $answer_donazione->Esito = "KO";
            $answer_donazione->record = $totalRows_donato;
        } else if ( $row_donato ) {
            foreach ( $row_donato as $key => $value ) {
                if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                    $answer_donazione->$key = $value;
                }
            }
        }
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Query LeggiDati_mysql: " . json_encode( $answer_donazione ) . PHP_EOL, 3, LOG_FILE );
        return ( $answer_donazione );
    }
    elseif ( isset( $richiesta->Id_ag ) && trim( $richiesta->Id_ag ) != "" ) {
        $connection = getSharedConnection( DB_DBNAME_TGIFT );
        if ( !$connection ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "LeggiDati_mysql: connessione fallita (TGIFT)" . PHP_EOL, 3, LOG_FILE );
            return $answer_donazione;
        }
        $stmt = $connection->prepare( "SELECT * FROM Voucher WHERE Id_donatore = ?" );
        if ( !$stmt ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "LeggiDati_mysql prepare failed (Id_ag): " . $connection->error . PHP_EOL, 3, LOG_FILE );
            return $answer_donazione;
        }
        $stmt->bind_param( 's', $richiesta->Id_ag );
        $stmt->execute();
        $result = $stmt->get_result();
        $totalRows_donati = $result->num_rows;

        if ( 0 == $totalRows_donati ) {
            $answer_donazione->Esito = "KO";
            $answer_donazione->record = $totalRows_donati;
        } else {
            while ( $row_donati = $result->fetch_assoc() ) {
                $indice = $row_donati[ 'Id_donato' ];
                $answer_donazione->$indice = ( object )array();
                foreach ( $row_donati as $key => $value ) {
                    if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                        $answer_donazione->$indice->$key = $value;
                    }
                }
            }
        }
        $stmt->close();
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Query LeggiDati_mysql: " . json_encode( $answer_donazione ) . PHP_EOL, 3, LOG_FILE );
        return ( $answer_donazione );
    }

    return $answer_donazione;
}
