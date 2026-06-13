<?php
require 'inc/config.inc.php';
require 'inc/data.inc.php';
if ( $_GET ) {
  $TY = json_encode( $_GET );
  error_log( date( '[Y-m-d H:i:s e] ' ) . "grazie.php parametri GET: " . $TY . PHP_EOL, 3, LOG_FILE ); //DEBUG		
}
//Backup per gestpay.php - INIZIO
if ( USE_GESTPAY == true ) { //GestPay
  //Backup per gestpay.php - INIZIO
  if ( isset( $_GET[ 'a' ] ) && isset( $_GET[ 'paymentID' ] ) && isset( $_GET[ 'paymentToken' ] ) ) { // Solo per le chimate Gestpay
    $errore = "";
    if ( GP_COD_ESE != $_GET[ 'a' ] ) {
      $errore .= "Non coincide il codice eserente<br>";
    }
    if ( !isset( $_GET[ 'paymentID' ] ) ) {
      $errore .= "Non è definito il codice del pagamento<br>";
    }
    if ( trim( $errore ) == "" ) {
      $azione_data[ 'TransactionResult' ] = $_GET[ 'Status' ];
      $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );
      $query_donazione = sprintf( "SELECT GestPayREST.shopTransactionID, GestPayREST.paymentID, GestPayREST.transactionErrorCode, GestPayREST.transactionErrorDescription, Donazione.CodTrans, Donazione.Id_a, Donazione.importo, Donazione.centro, Donazione.pay_method, Donazione.nota, Donazione.tessera, Donazione.tipotessera, Donazione.esito, Donazione.`data`, Donazione.CodiceMentor, Donazione.tipo, Donazione.codicePartner, Anagrafica.* FROM GestPayREST LEFT JOIN Donazione ON GestPayREST.shopTransactionID = Donazione.CodTrans LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a WHERE GestPayREST.paymentID ='%s'", $_GET[ 'paymentID' ] );
      $donazione = mysqli_query( $connection, $query_donazione )or die( mysqli_error( $connection ) );
      $row_donazione = mysqli_fetch_assoc( $donazione );
      $totalRows_donazione = mysqli_num_rows( $donazione );
      error_log( date( '[Y-m-d H:i:s e] ' ) . "Query grazie.php: (" . $totalRows_donazione . ") " . $query_donazione . PHP_EOL, 3, LOG_FILE ); // TEMP
      if ( $totalRows_donazione > 0 ) {
        foreach ( $row_donazione as $key => $value ) {
          if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
            $azione_data[ $key ] = $value;
          }
        }
      } else {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Query grazie.php: (" . $totalRows_donazione . ") " . $query_donazione . PHP_EOL, 3, LOG_FILE );
      }
      if ( "WA" == $row_donazione[ 'esito' ] ) {
        if ( $row_donazione[ 'tipo' ] == "regular" ) {
          $query_mandato = sprintf( "SELECT Id_mandato, frequenza, Token, meseToken, annoToken, nomeTitolare FROM Mandato WHERE Id_a =%s", $row_donazione[ 'Id_a' ] );
          $mandato = mysqli_query( $connection, $query_mandato )or die( mysqli_error() );
          $row_mandato = mysqli_fetch_assoc( $mandato );
          $totalRows_mandato = mysqli_num_rows( $mandato );
          foreach ( $row_mandato as $key => $value ) {
            if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
              $azione_data[ $key ] = $value;
            }
          }
        }
        if ( DEBUG == true ) {
          error_log( date( '[Y-m-d H:i:s e] ' ) . "grazie.php ESITO 3D : " . $row_donazione[ 'CodTrans' ] . " - Status: " . $_GET[ 'Status' ] . PHP_EOL, 3, LOG_FILE );
        }
        if ( isset( $row_donazione[ 'CodTrans' ] ) && preg_match( "/^[A-Z]{1}-[0-9]{17}-[A-Z]{2}/", $row_donazione[ 'CodTrans' ] ) ) {
          if ( empty( $row_donazione[ 'CodiceMentor' ] ) ) {
            $azione = array( "operation" => "save",
              "param" => "GestPay3D",
              "data" => $azione_data
            );
            $azione_string = json_encode( $azione );
            $ch = curl_init( DON_WS );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $azione_string );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
              'Content-Type: application/json',
              'Content-Length: ' . strlen( $azione_string ) ) );
            $result = curl_exec( $ch );
          } else {
            error_log( date( '[Y-m-d H:i:s e] ' ) . "grazie.php WA con codice mentor : " . $row_donazione[ 'CodTrans' ] . " - CodTrans: " . $row_donazione[ 'CodTrans' ] . " - Codice Mentor: " . $_GET[ 'CodiceMentor' ] . PHP_EOL, 3, LOG_FILE );
          }
        }
        // MAIL - INIZIO
        $secret = md5( $row_donazione[ 'Id_a' ] . SALT_MAIL );
        $redirect_url = $url_di_base . "/function/mail.php?d=" . $row_donazione[ 'Id_a' ] . "&s=" . $secret;
        // create a new cURL resource
        $ch_mail = curl_init();
        // set URL and other appropriate options
        curl_setopt( $ch_mail, CURLOPT_URL, $redirect_url );
        curl_setopt( $ch_mail, CURLOPT_HEADER, 0 );
        // Cattura l'output di mail.php invece di riversarlo nella pagina del donatore
        curl_setopt( $ch_mail, CURLOPT_RETURNTRANSFER, true );

        $result_mail = curl_exec( $ch_mail );

        // close cURL resource, and free up system resources
        curl_close( $ch_mail );

        // MAIL - FINE
      }
    }

    if ( USE_MAGNEWS == true ) {
      //Magnews - Inizio
      if ( !function_exists( 'mb_ucfirst' ) ) {
        function mb_ucfirst( $string ) {
          return mb_strtoupper( mb_substr( $string, 0, 1 ) ) . mb_strtolower( mb_substr( $string, 1 ) );
        }
      }

      function CleanMyJSON( $json ) {
        return ( preg_replace( '/("[a-zA-Z0-9_\-]+"\s*+:\s*(null|"\s*"|NULL),\s*)*/', '', $json ) );
      }
      require_once( 'function/inc/functions_magnews.php' );
      $dataset = array(
        "email" => $row_donazione[ 'mail' ],
        "nome" => $row_donazione[ 'nome' ],
        "cognome" => $row_donazione[ 'cognome' ],
        "tel" => $row_donazione[ 'tel' ],
        "CodiceReferral" => $row_donazione[ 'CodiceReferral' ],
        "CodicePersonale" => $row_donazione[ 'CodicePersonale' ],
        "db" => 1,
        "Id_a" => $row_donazione[ 'Id_a' ],
      );
      $mn_operation = call_user_func_array( 'Upsert_Magnews', array( $dataset ) ); // Scrivo il contatto in magnews
      if ( is_array( $mn_operation ) ) {
        $dataset += [
          "id_donatore" => $mn_operation[ 'idcontact' ], // WR + Id_a
          "codice_donazione" => $row_donazione[ 'CodTrans' ], //CodTrans
          "importo" => $row_donazione[ 'importo' ], //Importo
          //"data_donazione" => "15/05/2026 10:05", //data
          "campagna" => $row_donazione[ 'id_campagna' ],
          "modalita_pagamento" => $row_donazione[ 'pay_method' ], //pay_method
          "piattaforma" => "Almabox"
        ];
        // Rimosso secondo Upsert_Magnews ($mn_update) — era una chiamata duplicata al contatto.
        $mn_donation = call_user_func_array( 'AddDonation_Magnews', array( $dataset ) ); // Scrivo la donazione in magnews
      }
    }

  }
  //Backup per gestpay.php - FINE
}
if ( isset( $_GET[ 'p' ] ) ) {
  // donazione con Satyspay
  $azione_data = array( 'Id_a' => $_GET[ 'p' ] );
} elseif ( isset( $_POST[ 'invoice' ] ) && preg_match( "/^[A-Z]{1}-[0-9]{17}-[A-Z]{2}/", trim( $_POST[ 'invoice' ] ) ) ) {
  // ?
  $azione_data = array( 'CodTrans' => $_POST[ 'invoice' ] );
} elseif ( isset( $_GET[ 'paymentID' ] ) ) {
  // paypal
  $azione_data = array( 'paymentID' => $_GET[ 'paymentID' ] );
} elseif ( isset( $_GET[ 'CodTrans' ] ) ) {
  // ?
  $azione_data = array( 'CodTrans' => $_GET[ 'CodTrans' ] );
} elseif ( isset( $_GET[ 'd' ] ) ) {
  // paypal
  $secret = md5( $_GET[ 'd' ] . SALT_MAIL );
  if ( $secret != $_GET[ 's' ] ) {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata a grazie.php con [s] non valido" . PHP_EOL, 3, LOG_FILE );
    $redirect_url = FORM_ERROR_PAGE;
    if ( true == DEBUG ) {
      error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata a grazie.php con [s] non valido -> redirect a $redirect_url" . PHP_EOL, 3, EM_DEBUG_LOG_FILE );
    }
    header( "Location: " . $redirect_url );
    exit;
  } else {
    $azione_data = array( 'Id_a' => $_GET[ 'd' ] );
  }
} elseif ( isset( $_POST[ 'noted' ] ) ) {
  //Lascia un commento
  $secret = md5( $_POST[ 'noted' ] . SALT_MAIL );
  if ( $secret != $_POST[ 'notes' ] ) {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata a grazie.php con [notes] non valido" . PHP_EOL, 3, LOG_FILE );
    $redirect_url = FORM_ERROR_PAGE;
    if ( true == DEBUG ) {
      error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata a grazie.php con [notes] non valido -> redirect a $redirect_url" . PHP_EOL, 3, EM_DEBUG_LOG_FILE );
    }
    header( "Location: " . $redirect_url );
    exit;
  } else {
    $azione_data = array( 'Id_a' => $_POST[ 'noted' ] );
  }
} else {
  $azione_data = '';
}
if ( !empty( $azione_data ) ) {
  // Token interno: prova al WS che la richiesta arriva dal server (anti-IDOR su get/data)
  $azione_data['token'] = hash_hmac( 'sha256', 'get_data', defined('SALT_MAIL') ? SALT_MAIL : '' );
  $azione = array( "operation" => 'get', "param" => 'data', "data" => $azione_data );
  $azione_string = json_encode( $azione, JSON_UNESCAPED_UNICODE );
  // DEBUG
  //echo $azione_string; exit;
  // FINE DEBUG
  $ch = curl_init( DON_WS );
  curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
  curl_setopt( $ch, CURLOPT_POSTFIELDS, $azione_string );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen( $azione_string ) ) );
  $donazione = curl_exec( $ch );
  curl_close( $ch );
  $donazione_data = json_decode( $donazione, true );


  $pay_method = 'SDD';
  switch ( $donazione_data[ 'pay_method' ] ) {
    case 'CC':
      $pay_method = 'Carta di credito';
      //$pay_method = isset($auth) && true == $auth ? 'Carta di credito (con autenticazione)' : 'Carta di credito (senza autenticazione)';
      break;
    case 'PP':
      $pay_method = 'PayPal';
      break;
    case 'SY':
      $pay_method = 'Satispay';
      break;
  }
  switch ( $donazione_data[ 'operazione' ] ) {
    case 'regular':
      $tipodonazione = 'Donazione regolare'; // eventCategory
      $esito_donazione_contestuale = $donazione_data[ 'esito' ];
      if ( '1' == $donazione_data[ 'frequenza' ] ) {
        $frequenza = 'Donazione mensile';
        $importo = preg_replace( '/([.,]00)$/', '', $donazione_data[ 'importo' ] * 12 ); // eventValue
      } elseif ( '12' == $donazione_data[ 'frequenza' ] ) {
          $frequenza = 'Donazione annuale';
          $importo = preg_replace( '/([.,]00)$/', '', $donazione_data[ 'importo' ] ); // eventValue
        } else {
          $frequenza = 'Donazione regolare con frequenza indefinita';
          $importo = ''; // eventValue
        } // eventLabel
      break;
    case 'oneoff':
      $tipodonazione = 'Donazione singola'; // eventCategory
      $frequenza = 'Donazione liberale'; // eventLabel
      $importo = preg_replace( '/([.,]00)$/', '', $donazione_data[ 'importo' ] ); // eventValue
      break;
  }
}
if ( "OK" != $donazione_data[ 'esito' ] ) {
  error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata a grazie.php con esito non OK" . PHP_EOL, 3, LOG_FILE );
  $redirect_url = FORM_ERROR_PAGE;
  header( "Location: " . $redirect_url );
  exit;
}
// mb_ucfirst()
// gestione delle stringhe per stampa a schermo / email
if ( !function_exists( 'mb_ucfirst' ) ) {
  function mb_ucfirst( $string ) {
    return mb_strtoupper( mb_substr( $string, 0, 1 ) ) . mb_strtolower( mb_substr( $string, 1 ) );
  }
}
// Verifica firma: prova che la richiesta arriva dal legittimo donatore (conosce SALT_MAIL).
// noted = Id_a, notes = md5(Id_a . SALT_MAIL). Senza questo chiunque potrebbe scrivere su Id_a altrui.
function grazie_firma_valida() {
  $id_a = $_POST[ 'noted' ] ?? '';
  $sig  = $_POST[ 'notes' ] ?? '';
  return ( $id_a !== '' && is_string( $sig ) && hash_equals( md5( $id_a . SALT_MAIL ), $sig ) );
}

if ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'add_comment' ) {
  if ( grazie_firma_valida() ) {
    $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
    if ( !$connection || $connection->connect_errno ) {
      error_log( date( '[Y-m-d H:i:s e] ' ) . "grazie.php add_comment: connessione DB fallita" . PHP_EOL, 3, LOG_FILE );
    } elseif ( $stmt = $connection->prepare( "UPDATE Donazione SET nota=? WHERE CodTrans=?;" ) ) {
      $nota = mb_substr( trim( (string)( $_POST[ 'new_comment' ] ?? '' ) ), 0, 200 );
      $stmt->bind_param( 'ss', $nota, $_POST[ 'CodTrans' ] );
      $stmt->execute();
      $stmt->close();
      $connection->close();
    }
  } else {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "grazie.php add_comment: firma non valida (Id_a " . ( $_POST[ 'noted' ] ?? '?' ) . ")" . PHP_EOL, 3, LOG_FILE );
  }
}

// Arricchimento anagrafica: aggiorna solo i campi compilati, mai sovrascrive con valori vuoti.
$enrich_done = false;
if ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'enrich' ) {
  if ( grazie_firma_valida() ) {
    $allowed = array( 'indirizzo', 'civico', 'cap', 'citta', 'provincia', 'codFis', 'datanascita' );
    $sets = array(); $vals = array(); $types = '';
    foreach ( $allowed as $f ) {
      $v = trim( (string)( $_POST[ $f ] ?? '' ) );
      if ( $v === '' ) { continue; }
      if ( $f === 'provincia' ) { $v = strtoupper( substr( $v, 0, 4 ) ); }
      if ( $f === 'codFis' )    { $v = strtoupper( $v ); }
      if ( $f === 'datanascita' && !preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ) { continue; }
      $sets[] = "`$f`=?"; $vals[] = $v; $types .= 's';
    }
    if ( !empty( $sets ) ) {
      $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
      if ( !$connection || $connection->connect_errno ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "grazie.php enrich: connessione DB fallita" . PHP_EOL, 3, LOG_FILE );
      } else {
        $sql = "UPDATE Anagrafica SET " . implode( ', ', $sets ) . " WHERE Id_a=?";
        $types .= 'i'; $vals[] = (int) $_POST[ 'noted' ];
        if ( $stmt = $connection->prepare( $sql ) ) {
          $stmt->bind_param( $types, ...$vals );
          if ( $stmt->execute() ) { $enrich_done = true; }
          $stmt->close();
        }
        $connection->close();
      }
    }
  } else {
    error_log( date( '[Y-m-d H:i:s e] ' ) . "grazie.php enrich: firma non valida (Id_a " . ( $_POST[ 'noted' ] ?? '?' ) . ")" . PHP_EOL, 3, LOG_FILE );
  }
}

$connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error( $connection ), E_USER_ERROR );
//mysql_select_db(DB_DBNAME, $connection);
$query_campagna = "SELECT count('Id_a') as sostenitori, SUM(`importo`) as totale FROM Donazione where esito ='OK';";
$campagna = mysqli_query( $connection, $query_campagna )or die( mysqli_error( $connection ) );
$row_campagna = mysqli_fetch_assoc( $campagna );


$connection->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>Grazie | <?php echo htmlspecialchars(ORG_NAME); ?></title>
<meta name="description" content="Grazie per la tua donazione a <?php echo htmlspecialchars(ORG_NAME); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="css/normalize.css" rel="stylesheet" type="text/css">
<link href="css/donation.css" rel="stylesheet" type="text/css">
</head>
<body>

<header class="site-header">
  <img class="logo" src="images/logo.png" alt="Logo <?php echo htmlspecialchars(ORG_NAME); ?>" onerror="this.style.display='none'">
  <span class="org-name"><?php echo htmlspecialchars(ORG_NAME); ?></span>
</header>

<div class="page-wrap">
  <div class="campaign-header">
    <h1>Grazie<?php if (isset($donazione_data['nome'])) { echo ' ' . htmlspecialchars(mb_ucfirst($donazione_data['nome'])); } ?>!</h1>
    <h2>La tua donazione è andata a buon fine. Controlla la tua email.</h2>
  </div>

  <?php if ($enrich_done) { ?>
  <div class="card" style="border-color:#3a8a4f;background:#f1faf3">
    <strong>✓ Grazie, i tuoi dati sono stati aggiornati.</strong>
  </div>
  <?php } ?>

  <div class="card">
    <p><strong>Riepilogo della donazione</strong></p>
    <ul>
      <?php if (isset($tipodonazione)) { ?><li>Tipo: <?php echo htmlspecialchars($tipodonazione); ?></li><?php } ?>
      <?php if (isset($importo) && $importo !== '') { ?><li>Importo: <?php echo htmlspecialchars($importo); ?> &euro;</li><?php } ?>
      <?php if (isset($pay_method)) { ?><li>Metodo di pagamento: <?php echo htmlspecialchars($pay_method); ?></li><?php } ?>
    </ul>
    <?php if (isset($row_campagna['sostenitori']) && $row_campagna['sostenitori'] > 0) { ?>
    <p>Insieme a te, <strong><?php echo (int)$row_campagna['sostenitori']; ?> sostenitori</strong> hanno già donato
      <strong><?php echo preg_replace('/([.,]00)$/', '', $row_campagna['totale']); ?> &euro;</strong>. Grazie!</p>
    <?php } ?>
  </div>

  <?php if (isset($donazione_data['Id_a'])) {
        $sig = md5($donazione_data['Id_a'] . SALT_MAIL);
        // valori già presenti (per pre-compilare il form di arricchimento)
        $val = function($k) use ($donazione_data) { return htmlspecialchars($donazione_data[$k] ?? '', ENT_QUOTES, 'UTF-8'); };
  ?>
  <?php if (!$enrich_done) { ?>
  <div class="card">
    <p><strong>Completa i tuoi dati</strong></p>
    <p style="color:var(--brand-muted);font-size:.9rem">Facoltativo: utile per inviarti la ricevuta della donazione e gli aggiornamenti. Puoi anche saltare questo passaggio.</p>
    <form method="post" action="grazie.php">
      <input type="text" name="indirizzo" placeholder="Indirizzo" class="text-field" value="<?php echo $val('indirizzo'); ?>">
      <input type="text" name="civico" placeholder="N. civico" class="text-field" value="<?php echo $val('civico'); ?>">
      <input type="text" name="cap" placeholder="CAP" class="text-field" maxlength="10" value="<?php echo $val('cap'); ?>">
      <input type="text" name="citta" placeholder="Città" class="text-field" value="<?php echo $val('citta'); ?>">
      <input type="text" name="provincia" placeholder="Provincia (es. MI)" class="text-field" maxlength="4" value="<?php echo $val('provincia'); ?>">
      <input type="text" name="codFis" placeholder="Codice Fiscale" class="text-field" maxlength="16" value="<?php echo $val('codFis'); ?>">
      <label style="display:block;font-size:.85rem;color:var(--brand-muted);margin:6px 0 2px">Data di nascita</label>
      <input type="date" name="datanascita" class="text-field" value="<?php echo $val('datanascita'); ?>">
      <input type="hidden" name="action" value="enrich">
      <input type="hidden" name="noted" value="<?php echo htmlspecialchars($donazione_data['Id_a'], ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="notes" value="<?php echo $sig; ?>">
      <button type="submit" class="btn">Salva i miei dati</button>
    </form>
  </div>
  <?php } ?>

  <?php if (isset($donazione_data['CodTrans']) && trim($donazione_data['nota'] ?? '') === '') { ?>
  <div class="card">
    <p><strong>Vuoi lasciare un commento?</strong></p>
    <form method="post" action="grazie.php">
      <input type="text" name="new_comment" maxlength="200" placeholder="Il tuo commento" class="text-field">
      <input type="hidden" name="action" value="add_comment">
      <input type="hidden" name="CodTrans" value="<?php echo htmlspecialchars($donazione_data['CodTrans'], ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="noted" value="<?php echo htmlspecialchars($donazione_data['Id_a'], ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="notes" value="<?php echo $sig; ?>">
      <button type="submit" class="btn">Invia commento</button>
    </form>
  </div>
  <?php } ?>
  <?php } ?>

  <div class="center" style="margin-top:24px">
    <a class="btn" href="<?php echo ORG_WEBSITE; ?>" style="width:auto">Torna al sito di <?php echo htmlspecialchars(ORG_NAME); ?></a>
  </div>
</div>

<footer class="site-footer">
  &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(ORG_NAME); ?> &middot;
  <a href="<?php echo ORG_PRIVACY_URL; ?>">Privacy Policy</a><br>
  Per qualsiasi domanda sulla tua donazione scrivici a <a href="mailto:<?php echo ORG_EMAIL; ?>"><?php echo ORG_EMAIL; ?></a>
</footer>

</body>
</html>

