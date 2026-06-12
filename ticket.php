<?php
require 'inc/config.inc.php';
require 'inc/data.inc.php';
require 'inc/security.php';

send_security_headers();

function LeggiDati_mysql( $richiesta ) {
    $answer_donazione = ( object )array();
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( !$connection ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: DB connection failed in LeggiDati_mysql" . PHP_EOL, 3, LOG_FILE );
        die( "Errore interno del server." );
    }
    if ( isset( $_REQUEST[ 't' ] ) && $_REQUEST[ 't' ] == "partner" ) {
        $sql_anagrafica = "SELECT Ticket.*, Partner.Nome AS RSPartner FROM Ticket LEFT JOIN Partner ON Ticket.Id_partner = Partner.Id_partner WHERE Id_ticket = ?";
        $stmt_ana = $connection->prepare( $sql_anagrafica );
        if ( !$stmt_ana ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: prepare anagrafica failed - " . $connection->error . PHP_EOL, 3, LOG_FILE );
            die( "Errore interno del server." );
        }
        $id_ticket_param = intval( $richiesta->Id_ticket );
        $stmt_ana->bind_param( 'i', $id_ticket_param );
        $stmt_ana->execute();
        $result_ana = $stmt_ana->get_result();
        $row_anagrafica = $result_ana->fetch_assoc();
        if ( $row_anagrafica ) {
            foreach ( $row_anagrafica as $key => $value ) {
                if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                    $answer_donazione->$key = $value;
                }
            }
        }
        $stmt_ana->close();
    } else {

        $sql_ticket = "SELECT Anagrafica.Id_a AS ID, Anagrafica.cognome, Anagrafica.nome, Anagrafica.mail, Anagrafica.tel, Anagrafica.data_ins, Anagrafica.CodiceReferral, Donazione.importo, Donazione.data, Donazione.pay_method, Donazione.CodTrans, Donazione.esito, Donazione.valido, Donazione.gadget, (SELECT COUNT(*) FROM Anagrafica AS A2 LEFT JOIN Donazione as D2 ON A2.Id_a = D2.Id_A WHERE A2.CodiceReferral = Anagrafica.CodicePersonale AND D2.Esito ='OK') AS NumeroUtilizziCodicePersonale FROM Donazione LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a WHERE Anagrafica.Id_a = ?";
        $stmt_ticket = $connection->prepare( $sql_ticket );
        if ( !$stmt_ticket ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: prepare ticket failed - " . $connection->error . PHP_EOL, 3, LOG_FILE );
            die( "Errore interno del server." );
        }
        $id_a_param = intval( $richiesta->Id_a );
        $stmt_ticket->bind_param( 'i', $id_a_param );
        $stmt_ticket->execute();
        $result_ticket = $stmt_ticket->get_result();
        $row_ticket = $result_ticket->fetch_assoc();
        if ( $row_ticket ) {
            foreach ( $row_ticket as $key => $value ) {
                if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                    $answer_donazione->$key = $value;
                }
            }
        }
        $stmt_ticket->close();

        /*
        
        $query_anagrafica = sprintf( "SELECT * FROM Anagrafica WHERE Id_a = %s", $richiesta->Id_a );
        $anagrafica = mysqli_query( $connection, $query_anagrafica )or die( mysqli_error( $connection ) );
        $row_anagrafica = mysqli_fetch_assoc( $anagrafica );
        $totalRows_anagrafica = mysqli_num_rows( $anagrafica );
        foreach ( $row_anagrafica as $key => $value ) {
            if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                $answer_donazione->$key = $value;
            }
        }
        $query_donazione = sprintf( "SELECT Donazione.CodTrans, Donazione.importo, Donazione.esito, Donazione.pay_method, Donazione.valido, Donazione.gadget FROM Donazione WHERE Donazione.Id_a = '%s'", $row_anagrafica[ 'Id_a' ] );
        $donazione = mysqli_query( $connection, $query_donazione )or die( mysqli_error( $connection ) );
        $row_donazione = mysqli_fetch_assoc( $donazione );
        $totalRows_donazione = mysqli_num_rows( $donazione );
        if ( $totalRows_donazione == 1 ) {
            foreach ( $row_donazione as $key => $value ) {
                if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                    $answer_donazione->$key = $value;
                }
            }
        }*/

    }
    $connection->close();
    return ( $answer_donazione );
    }

    //function biglietti() {
    $q_ticket = "SELECT
      SUM(esito = 'OK')                       AS n_ok,
      SUM(esito = 'OK' AND valido = 'Y')      AS n_ok_valid,
      SUM(esito = 'OK' AND valido = 'N')      AS n_ok_invalid
    FROM Donazione
    WHERE causale = ?";

    $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( !$conn ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: DB connection failed for ticket count" . PHP_EOL, 3, LOG_FILE );
        die( "Errore interno del server." );
    }
    $stmt_q = $conn->prepare( $q_ticket );
    if ( !$stmt_q ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: prepare ticket count failed - " . $conn->error . PHP_EOL, 3, LOG_FILE );
        die( "Errore interno del server." );
    }
    $campagna_default = ID_CAMPAGNA_DEFAULT;
    $stmt_q->bind_param( 's', $campagna_default );
    $stmt_q->execute();
    $ticket = $stmt_q->get_result();
    $row_ticket = mysqli_fetch_assoc( $ticket );
    $totalRows_ticket = mysqli_num_rows( $ticket );
    /*foreach ( $row_ticket as $key => $value ) {
            if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
                $answer_ticket->$key = $value;
            }
        }
    */
    $conn->close();
    //    return ( $answer_ticket );
    //}
    if ( DEBUG == true ) {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php query stirng: " . $_SERVER[ 'QUERY_STRING' ] . PHP_EOL, 3, LOG_FILE ); //DEBUG
    }
    // controllo il secret (HMAC-SHA256 con fallback MD5)
    if ( isset( $_REQUEST[ 't' ] ) && $_REQUEST[ 't' ] == "partner" ) {
    $sig_data = "partner" . $_REQUEST[ 'd' ];
    define( 'INGRESSO', "partner" );
    } else {
    $sig_data = $_REQUEST[ 'd' ];
    define( 'INGRESSO', "donor" );
    }
    if ( !verify_signature($sig_data, $_REQUEST[ 's' ]) ) {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata a ticket.php con [s] non valido" . PHP_EOL, 3, LOG_FILE );
    $redirect_url = FORM_ERROR_PAGE;
    if ( true == DEBUG ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata a ticket.php con [s] non valido -> redirect a $redirect_url" . PHP_EOL, 3, EM_DEBUG_LOG_FILE );
    }
    header( "Location: " . $redirect_url );
    exit;
    }
    if ( INGRESSO == "partner" ) {
    $query_data = ( object )array();
    $query_data->Id_ticket = $_REQUEST[ 'd' ];
    // controllo i dati
    if ( isset( $_POST[ 'registra_accesso' ] ) && "strappa" == $_POST[ 'registra_accesso' ] ) {
        // connetto al db
        $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
        if ( $connection->connect_errno ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: DB connection failed: " . $connection->connect_error . PHP_EOL, 3, LOG_FILE ); die( "Errore interno del server." );
        }
        // preparo lo statement
        if ( !( $stmt = $connection->prepare( "UPDATE Ticket SET valido='N' WHERE Id_ticket=? AND Id_partner =?;" ) ) ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: Prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE ); die( "Errore interno del server." );
        }

        if ( !$stmt->bind_param( 'ii', $_POST[ 'd' ], $_POST[ 'Id_partner' ] ) ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: Binding failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE ); die( "Errore interno del server." );
        }
        // eseguo la query e chiudo
        if ( !$stmt->execute() ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: Execute failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE ); die( "Errore interno del server." );
        }
        $stmt->close();
        $connection->close();
    }
    $ticketuserdata = call_user_func_array( 'LeggiDati_mysql', array( $query_data ) );
    $ticketuserdata->importo = 0;
    $ticketuserdata->gadget = 'N';
    if ( $ticketuserdata->RSPartner == "" ) {
        $redirect_url = $url_di_base . "/noticket.php";
        header( "Location: " . $redirect_url );
        exit;
    }

    } else {
    $query_data = ( object )array();
    $query_data->Id_a = $_REQUEST[ 'd' ];
    // controllo i dati
    if ( isset( $_POST[ 'registra_accesso' ] ) && "strappa" == $_POST[ 'registra_accesso' ] ) {
        // connetto al db
        $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
        if ( $connection->connect_errno ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: DB connection failed: " . $connection->connect_error . PHP_EOL, 3, LOG_FILE ); die( "Errore interno del server." );
        }
        // preparo lo statement
        if ( !( $stmt = $connection->prepare( "UPDATE Donazione SET valido='N' WHERE CodTrans=? AND Id_a =?;" ) ) ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: Prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE ); die( "Errore interno del server." );
        }

        if ( !$stmt->bind_param( 'si', $_POST[ 'CodTrans' ], $_POST[ 'd' ] ) ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: Binding failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE ); die( "Errore interno del server." );
        }
        // eseguo la query e chiudo
        if ( !$stmt->execute() ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: Execute failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE ); die( "Errore interno del server." );
        }
        $stmt->close();
        $connection->close();
    }
    if ( isset( $_POST[ 'registra_gadget' ] ) && "ritira" == $_POST[ 'registra_gadget' ] ) {
        // connetto al db
        $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
        if ( $connection->connect_errno ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: DB connection failed: " . $connection->connect_error . PHP_EOL, 3, LOG_FILE ); die( "Errore interno del server." );
        }
        // preparo lo statement
        if ( !( $stmt = $connection->prepare( "UPDATE Donazione SET gadget='Y' WHERE CodTrans=? AND Id_a =?;" ) ) ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: Prepare failed: " . $connection->error . PHP_EOL, 3, LOG_FILE ); die( "Errore interno del server." );
        }

        if ( !$stmt->bind_param( 'si', $_POST[ 'CodTrans' ], $_POST[ 'd' ] ) ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: Binding failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE ); die( "Errore interno del server." );
        }
        // eseguo la query e chiudo
        if ( !$stmt->execute() ) {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: Execute failed: " . $stmt->error . PHP_EOL, 3, LOG_FILE ); die( "Errore interno del server." );
        }
        $stmt->close();
        $connection->close();
    }
    $ticketuserdata = call_user_func_array( 'LeggiDati_mysql', array( $query_data ) );

    if ( !isset( $ticketuserdata->importo ) || $ticketuserdata->importo == "" || $ticketuserdata->importo == 0 || $ticketuserdata->esito != "OK" ) {
        $redirect_url = $url_di_base . "/noticket.php";
        header( "Location: " . $redirect_url );
        exit;
    }
    }

    ?>
<!DOCTYPE html><!--  This site was created in Webflow. https://webflow.com  --><!--  Last Published: Fri May 16 2025 16:15:52 GMT+0000 (Coordinated Universal Time)  -->
<html data-wf-page="6814b3b8ef8eed3b94a583d2" data-wf-site="6814b3b8ef8eed3b94a583d3">
<head>
<meta charset="utf-8">
<title>Ticket | <?php echo htmlspecialchars(ORG_NAME); ?></title>
<meta content="Il tuo ticket di donazione per <?php echo htmlspecialchars(ORG_NAME); ?>" name="description">
<meta content="Ticket" property="og:title">
<meta content="Il tuo ticket di donazione per <?php echo htmlspecialchars(ORG_NAME); ?>" property="og:description">
<meta content="Ticket" property="twitter:title">
<meta content="Il tuo ticket di donazione per <?php echo htmlspecialchars(ORG_NAME); ?>" property="twitter:description">
<meta property="og:type" content="website">
<meta content="summary_large_image" name="twitter:card">
<meta content="width=device-width, initial-scale=1" name="viewport">
<meta content="Webflow" name="generator">
<link href="css/normalize.css" rel="stylesheet" type="text/css">
<link href="css/donation.css" rel="stylesheet" type="text/css">
<script type="text/javascript">!function(o,c){var n=c.documentElement,t=" w-mod-";n.className+=t+"js",("ontouchstart"in o||o.DocumentTouch&&c instanceof DocumentTouch)&&(n.className+=t+"touch")}(window,document);</script>
<link href="images/favicon.jpg" rel="shortcut icon" type="image/x-icon">
<link href="images/webclip.jpg" rel="apple-touch-icon">
<script>
document.addEventListener("DOMContentLoaded", function() {
    const button = document.getElementById('tostep2');
    const fieldsToCheck = [
        document.getElementById('name'),
        document.getElementById('surname')
    ];
     function checkFields() {
        let allFieldsFilled = true;
        fieldsToCheck.forEach(function(field) {
            if (field.value.trim() === '') {
                allFieldsFilled = false;
            }
        });
        button.disabled = !allFieldsFilled;
    }
    // Check fields on input change
    fieldsToCheck.forEach(function(field) {
        field.addEventListener('input', checkFields);
    });
    // Initial check
    checkFields();
});
</script>
</head>
<body>
<div data-w-id="85c016fa-7300-b8f3-69c3-fb75b7f867b7" data-animation="default" data-collapse="medium" data-duration="400" data-easing="ease" data-easing2="ease" role="banner" class="uui-navbar05_component w-nav">
    <div class="uui-navbar05_container"> <a href="#" class="uui-navbar05_logo-link w-nav-brand">
        <div class="uui-logo_component">
            <div class="uui-logo_logomark"><img src="images/logo.jpeg" loading="lazy" width="50" sizes="50px" alt="" srcset="images/logo-p-500.jpeg 500w, images/logo-p-800.jpeg 800w, images/logo-p-1080.jpeg 1080w, images/logo-p-1600.jpeg 1600w, images/logo-p-2000.jpeg 2000w, images/logo.jpeg 2560w" class="image"></div>
            <img src="images/untitled-ui-logo.png" loading="lazy" alt="Logo" class="uui-logo_image"> </div>
      </a>
      <div class="uui-navbar05_menu-button w-nav-button">
        <div class="menu-icon_component">
          <div class="menu-icon_line-top"></div>
          <div class="menu-icon_line-middle">
            <div class="menu-icon_line-middle-inner"></div>
          </div>
          <div class="menu-icon_line-bottom"></div>
        </div>
      </div>
    </div>
</div>
<header class="uui-section_heroheader21">
    <div class="w-layout-grid uui-heroheader21_component head-onlydesk thankserror">
      <div id="w-node-_5219f522-1165-edce-863c-52a4147ff315-94a583d2" class="uui-heroheader21_content">
            <?php
            if ( $ticketuserdata->importo >= IMPORTO_MINIMO_ONE || INGRESSO == "partner" ) { // Da inserire Partner ?>
            <h1 class="uui-heading-xlarge"><span class="text-span"><?php echo ucfirst(strtolower($ticketuserdata->nome)); ?>, <br>
                </span>questo è il tuo lasciapassare d&#x27;accesso al <strong>Party</strong><br>
            </h1>
            <?php
            }else {?>
            <h1 class="uui-heading-xlarge"><span class="text-span"><?php echo ucfirst(strtolower($ticketuserdata->nome)); ?><br>
                </span>Questa è la tua ricevuta di <strong>donazione</strong><br>
            </h1>
            <?php }?>
            <div class="uui-space-small"></div>
            <?php if($ticketuserdata->importo>= IMPORTO_MINIMO_ONE || INGRESSO =="partner") { // Da inserire Partner ?>
            
        
            <?php
            $date1 = new DateTime( 'now', new DateTimeZone( 'Europe/Rome' ) );

            //$date2 = APERTURA_CANCELLI; //date_create( "2023-06-01 13:45:00", new DateTimeZone( 'Europe/Rome' ) ); // orario - apertura cancelli
            $date2 = date_create( APERTURA_CANCELLI, new DateTimeZone( TIMEZONE ) );
            if ( $ticketuserdata->importo >= IMPORTO_MINIMO_ONE ) {
                $date3 = date_create( INIZIO_FESTA, new DateTimeZone( TIMEZONE ) );
            } elseif ( INGRESSO == "partner" ) {
                $date3 = date_create( INIZIO_CENA, new DateTimeZone( TIMEZONE ) );
            }
            $diff = date_diff( $date1, $date3 );
            //if($diff->d <=0 && $diff->h <=0 && $diff->i<=30 ){
            //print_r($date1);
            if ( $date1 > $date2 ) { //Apertura Cancelli      ?>
            <?php
            if ( $ticketuserdata->valido == 'Y' ) { // Bilgietto Valido ?>
            <div>Ingressi previsti: <?php echo $row_ticket['n_ok']; ?> </div>
            <div>Ingressi avvenuti: <?php echo $row_ticket['n_ok_invalid']; ?> </div>
            <div>Ingressi attesi: <?php echo $row_ticket['n_ok_valid']; ?> </div>
            <br>
            <div id="biglietto_valido">
                <?php
                if ( !isset( $_GET[ 't' ] ) || $_GET[ 't' ] != "partner" ) { //Donatore 
                   /* if ( $ticketuserdata->importo < 20 ) {
                        $frase_donazione = "<strong>Scendi dalla nave!</strong>";
                    } elseif ( $ticketuserdata->importo < 35 ) {
                        $frase_donazione = "<strong>MOZZO</strong>";
                    }
                    elseif ( $ticketuserdata->importo < 50 ) {
                        $frase_donazione = "<strong>NOSTROMO</strong>";
                    }
                    elseif ( $ticketuserdata->importo < 100 ) {
                        $frase_donazione = "<strong>SPUGNA</strong>";
                    }
                    else {
                        $frase_donazione = "<strong>CAPITAN UNCINO</strong>";
}*/
                     
                    if ($ticketuserdata->CodiceReferral  == "" ) {
                        $CodiceReferral = "";
                         $chckReferral ="KO";
                    } else {
                        $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
                        if ( !$conn ) {
                            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: DB connection failed for referral" . PHP_EOL, 3, LOG_FILE );
                            die( "Errore interno del server." );
                        }
                        $sql_referral = "SELECT nome, cognome FROM Anagrafica WHERE CodicePersonale = ?";
                        $stmt_ref = $conn->prepare( $sql_referral );
                        if ( !$stmt_ref ) {
                            error_log( date( '[Y-m-d H:i:s e] ' ) . "ticket.php: prepare referral failed - " . $conn->error . PHP_EOL, 3, LOG_FILE );
                            die( "Errore interno del server." );
                        }
                        $codice_ref = $ticketuserdata->CodiceReferral;
                        $stmt_ref->bind_param( 's', $codice_ref );
                        $stmt_ref->execute();
                        $result_ref = $stmt_ref->get_result();
                        $row_referral = $result_ref->fetch_assoc();
                        $totalRows_referral = $result_ref->num_rows;
                        $stmt_ref->close();
                        if ( $totalRows_referral == 1 ) {
                            $CodiceReferral = ": " . $row_referral[ 'nome' ] . " " . $row_referral[ 'cognome' ];
                            $chckReferral ="OK";
                        } else {
                            $CodiceReferral = " da verificare: " . $ticketuserdata->CodiceReferral ;
                             $chckReferral ="KO";
                    }
                         $conn->close();
                    }
                    
                    ?> 
                <!--<p class="text-h3 text-center" style="margin-top: -0.5em;" ><strong class="text-red"><?php //echo $frase_donazione; ?> </strong></p>-->
                <p class="text-h3 text-center" style="margin-top: -0.1em; font-size: 2em !important;"><strong class="text-red">REFERRAL: <?php if ($ticketuserdata->NumeroUtilizziCodicePersonale >0 || $chckReferral =="OK"){echo " SI";} else {echo " NO";} ?> </strong></p>
                <p class="text-h3 text-center" style="margin-top: -0.1em; font-size: 2em !important;"><strong class="text-red">EARLY BIRD/VIP: <?php IF ($_REQUEST[ 't' ] == "partner" || $ticketuserdata->data <"2025-05-30"){echo " SI";} else {echo " NO";} ?> </strong></p>
                <p class="text-h3 text-center" style="margin-top: -0.5em;" ><strong class="text-red">Codice personale: <?php if ($ticketuserdata->NumeroUtilizziCodicePersonale >0){echo " Utilizzato";} else {echo " Non utilizzato";} ?> </strong></p> 
                <p class="text-h3 text-center" style="margin-top: -0.5em;" ><strong class="text-red">Codice referral <?php echo $CodiceReferral; ?></strong></p>
                <?php } else{ //Partner  ?>
                <p class="text-h3 text-center" style="margin-top: -0.5em;" ><strong class="text-red"><strong>PARTNER</strong> [<?php echo $ticketuserdata->RSPartner; ?>] </strong></p>
                <?php } ?>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="registra_accesso" name="registra_accesso">
                    <fieldset>
                        <div class="col-12 form-group align-content-center">
                            <input type="hidden" name="IP" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>">
                            <input type="hidden" name="registra_accesso" value="strappa">
                            <?php if(INGRESSO =="donor"){ ?>
                            <input type="hidden" name="CodTrans" value="<?php echo $ticketuserdata->CodTrans; ?>">
                            <?php } else {?>
                            <input type="hidden" name="Id_partner" value="<?php echo $ticketuserdata->Id_partner; ?>">
                            <?php }?>
                            <input type="hidden" name="d" value="<?php echo $_REQUEST [ 'd' ];?>">
                            <input type="hidden" name="s" value="<?php echo $_REQUEST [ 's' ];?>">
                            <input type="hidden" name="t" value="<?php echo $_REQUEST [ 't' ];?>">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-outline-warning">Registra accesso</button>
                            </div>
                        </div>
                    </fieldset>
                </form>
                <p class="text-center text-bg-warning" style="margin-top: 1em;" ><strong class="text-red">Attenzione premendo il pulsante annulli l'ingresso.</strong></p>
            </div>
            <?php } else{// Bilgietto NON Valido?>
            <div id="biglietto_strappato">
                <p>L'acesso è già stato effettuato.</p>
                <?php  if($ticketuserdata->gadget =='N' && INGRESSO =="donor"){ // Gadget ?>
                <!--
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="registra_gadget" name="registra_gadget">
                    <fieldset>
                        <div class="col-12 form-group align-content-center">
                            <input type="hidden" name="IP" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>">
                            <input type="hidden" name="registra_gadget" value="ritira">
                            <input type="hidden" name="CodTrans" value="<?php echo $ticketuserdata->CodTrans; ?>">
                            <input type="hidden" name="d" value="<?php echo $_REQUEST [ 'd' ];?>">
                            <input type="hidden" name="s" value="<?php echo $_REQUEST [ 's' ];?>">
                            <input type="hidden" name="t" value="<?php echo $_REQUEST [ 't' ];?>">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-outline-warning">Ritira gadget</button>
                            </div>
                        </div>
                    </fieldset>
                </form>
                <p class="text-center text-bg-warning" style="margin-top: 1em;" ><strong class="text-red">Attenzione premendo il pulsante annulli il ritiro dei gadget.</strong></p>
                -->
                <p class="text-center text-bg-warning" style="margin-top: 1em;" ><strong class="text-red">Ricordati di ritirare i gadget.</strong></p>
                <?php } else{?>
                <?php if($ticketuserdata->importo>= IMPORTO_MINIMO_ONE || INGRESSO =="partner") { // Da inserire Partner
                 echo "<p>Ricordati di ritirare la tua spilla.</p>";
                } else{
                  echo "<p>I gadget sono stati ritirati.</p> ";
                }?>
                <?php } ?>
            </div>
            <?php
            }
            } else { //Cancelli ancora chiusi
                echo "<span class=\"text-red\"><strong>Mancano " . $diff->d . " giorni,  " . $diff->h . " ore, " . $diff->i . " minuti all'inizio della Festa</strong><span><br>
<br>
";
            }

            ?>
            <div class="row d-block d-lg-none">
                <div class="col-12">
                    <div style="height:30px;"></div>
                </div>
            </div>
            <?php
            if ( $date1 < $date2 ) {
                if ( isset( $_REQUEST[ 't' ] ) && $_REQUEST[ 't' ] == "partner" ) {?>
            Questo lasciapassare ti è stato offerto da <?php echo  $ticketuserdata->RSPartner;?>: il giorno dell'evento, questa pagina ci permetterà di farti accedere.<br>
            <br>
            <?php } else { ?>
            Per il momento qui trovi solo le informazioni sulla tua donazione ma, il giorno dell'evento, questa pagina ci permetterà di farti accedere.<br>
            <br>
            <?php
                }
            }
        }?>      
        </div>
      <div class="uui-heroheader21_image-wrapper grey">
        <div class="uui-text-size-xlarge"><!--Inizo-->
                <?php
        echo ucfirst( strtolower( $ticketuserdata->nome ) ) . " " . ucfirst( strtolower( $ticketuserdata->cognome ) );
        echo "<br>" . $ticketuserdata->mail;
        if ( isset( $_REQUEST[ 't' ] ) && $_REQUEST[ 't' ] == "partner" ) {
            echo "<br>" . $ticketuserdata->telefono ;
        } else {
             switch ( $ticketuserdata->pay_method ) {
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
            echo "<br>" . $ticketuserdata->tel ;
            echo "<br><strong>Donazione</strong> " . number_format( $ticketuserdata->importo, 2, ",", "." ) . " €";
            echo "<br><strong>Metodo</strong> " . $pay_method;
            echo "<br><strong>Codice donazione</strong> " . $ticketuserdata->CodTrans . "<br>";
            echo "<br><strong>Codice donazione</strong> " . $ticketuserdata->data . "<br>";
        }
        ?>
                
                <!--Fine--> </div>
      </div>
    </div>
</header>
<script src="https://d3e54v103j8qbb.cloudfront.net/js/jquery-3.5.1.min.dc5e7f18c8.js?site=6814b3b8ef8eed3b94a583d3" type="text/javascript" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script> 
<script src="js/webflow.js" type="text/javascript"></script>
<?php if ( USE_SANDBOX == true ) { ?>

<!-- DeBug -->
<?php
            echo "<br>------<br>Debug: <br>";
            //foreach ($_SESSION as $key => $value){echo "variabile SESSIONE ". $key ." = ". $value ."<br>";}
            foreach ( $_POST as $key => $value ) {
                echo "variabile POST " . $key . " = " . $value . "<br>";
            }
            foreach ( $_GET as $key => $value ) {
                echo "variabile GET " . $key . " = " . $value . "<br>";
            }
            if ( isset( $result ) ) {
                $result_data = json_decode( $result, true );
                echo "Risposta WS: <br>";
                foreach ( $result_data as $key => $value ) {
                    if ( is_array( $value ) ) {
                        echo "variabile result_data " . $key . " = <br>";
                        foreach ( $value as $k => $v ) {
                            if ( is_array( $v ) ) {
                                foreach ( $v as $kd => $vd ) {
                                    if ( is_array( $vd ) ) {
                                        foreach ( $vd as $kt => $vt ) {
                                            echo "variabile " . $value . "[" . $k . "] [" . $kd . "] [" . $kt . "]= " . $vt . "<br>";
                                        }
                                    } else {
                                        echo "variabile " . $value . "[" . $k . "] [" . $kd . "] = " . $vd . "<br>";
                                    }
                                }
                            } else {
                                echo "variabile " . $value . "[" . $k . "] = " . $v . "<br>";
                            }
                        }
                    } else {
                        echo "variabile result_data " . $key . " = " . $value . "<br>";
                    }
                }
            }
            echo "<br>Url di base: " . $url_di_base;
            echo "<br>Url di Webservice: " . DON_WS;
			echo "<br>Date1: "; print_r($date1);
			echo "<br>Date2: "; print_r($date2);
			echo "<br>Date3: "; print_r($date3);
		
	
            ?>
<!-- DeBug -->

<?php } ?>
</body>
</html>
