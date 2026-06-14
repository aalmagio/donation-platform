<?php
/*
 * Cron addebiti ricorrenti su carta (GestPay/Axerve, modalità MIT con token).
 * Seleziona i mandati attivi a carta con prossima_data scaduta e addebita la rata
 * usando il Token salvato (nessun 3DS: è una Merchant Initiated Transaction).
 *
 * Esecuzione: php function/cron_regular.php   (oppure via HTTP con ?key=CRON_KEY)
 * Protezione: in esecuzione HTTP richiede il parametro key == CRON_REGULAR_KEY (config/.env).
 */

// Lanciabile da CLI o HTTP: percorsi relativi al file.
$site_folder = dirname(__DIR__) . '/';
require $site_folder . 'inc/config.inc.php';
require_once $site_folder . 'function/inc/functions_generic.php';
require_once $site_folder . 'function/inc/functions_mysql.php';
if ( USE_GESTPAY == true ) {
    require_once $site_folder . 'function/inc/functions_gestpay.php';
}

// --- Protezione accesso HTTP ---
$is_cli = ( php_sapi_name() === 'cli' );
if ( !$is_cli ) {
    $key = $_GET['key'] ?? '';
    $expected = defined('CRON_REGULAR_KEY') ? CRON_REGULAR_KEY : '';
    if ( $expected === '' || !hash_equals( (string)$expected, (string)$key ) ) {
        http_response_code( 403 );
        echo "Accesso negato";
        exit;
    }
}

if ( !defined('USE_GESTPAY') || USE_GESTPAY != true ) {
    echo "GestPay non attivo: nessun addebito ricorrente a carta.\n";
    exit;
}

$log_prefix = date( '[Y-m-d H:i:s e] ' ) . "cron_regular: ";
$connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
if ( !$connection ) {
    error_log( $log_prefix . "connessione DB fallita" . PHP_EOL, 3, LOG_FILE );
    exit( 1 );
}

// Mandati a carta attivi, con token, la cui prossima data di addebito è arrivata
$limite = ( defined('USE_SANDBOX') && USE_SANDBOX == true ) ? 5 : 200;
$sql = "SELECT m.Id_mandato, m.Id_a, m.importo, m.frequenza, m.Token, m.n_addebiti, m.codiceCentro,
               a.nome, a.cognome, a.mail, a.IP, a.id_campagna
        FROM Mandato m
        LEFT JOIN Anagrafica a ON m.Id_a = a.Id_a
        WHERE m.stato = 'attivo' AND m.metodo = 'CC'
          AND m.Token IS NOT NULL AND m.Token <> ''
          AND m.prossima_data IS NOT NULL AND m.prossima_data <= CURDATE()
        LIMIT $limite";
$res = mysqli_query( $connection, $sql );
if ( !$res ) {
    error_log( $log_prefix . "query mandati fallita: " . mysqli_error( $connection ) . PHP_EOL, 3, LOG_FILE );
    exit( 1 );
}

$totali = mysqli_num_rows( $res );
$ok = 0; $ko = 0;
echo "Mandati da addebitare: $totali\n";

// Registra l'esito di un addebito sul mandato. Su KO sposta comunque la prossima data
// alla rata successiva (niente ritentativi a raffica). NB: una logica di dunning/retry
// più sofisticata può essere aggiunta qui (es. sospendere dopo N fallimenti).
$segnaEsitoMandato = function ( $conn, $id_mandato, $frequenza, $esito ) {
    $mesi = ( (int) $frequenza === 12 ) ? 12 : 1;
    $prossima = date( 'Y-m-d', strtotime( "+$mesi month" ) );
    $inc = ( $esito === 'OK' ) ? ', n_addebiti=n_addebiti+1' : '';
    if ( $stmt = $conn->prepare( "UPDATE Mandato SET prossima_data=?, ultimo_addebito=NOW(), ultimo_esito=?$inc WHERE Id_mandato=?" ) ) {
        $idm = (int) $id_mandato;
        $stmt->bind_param( 'ssi', $prossima, $esito, $idm );
        $stmt->execute();
        $stmt->close();
    }
};

while ( $m = mysqli_fetch_assoc( $res ) ) {
    // Nuovo codice transazione per questa rata
    $micro = explode( ' ', microtime() );
    $codtrans = 'D-' . date( 'YmdwHis', $micro[1] ) . substr( $micro[0], 2, 2 ) . '-RC'; // RC = Recurring Charge

    // 1. Scrivo la donazione della rata (esito WA)
    $don = (object) array(
        'CodTrans'       => $codtrans,
        'Id_a'           => $m['Id_a'],
        'importo'        => $m['importo'],
        'pay_method'     => 'CC',
        'causale'        => $m['id_campagna'] ?? ID_CAMPAGNA_DEFAULT,
        'nota'           => '',
        'tessera'        => 'N',
        'tipoTessera'    => '',
        'esito'          => 'WA',
        'centro'         => ( !empty($m['codiceCentro']) ? $m['codiceCentro'] : CENTRO_DEFAULT ),
        'tipo_donazione' => 'regular',
        'codicePartner'  => '',
    );
    $id_don = ScriviDonazione_mysql( $don );
    if ( !preg_match( "/^[A-Z]{1}-[0-9]{17}-[A-Z]{2}/", (string)$id_don ) ) {
        error_log( $log_prefix . "scrittura donazione fallita per mandato " . $m['Id_mandato'] . PHP_EOL, 3, LOG_FILE );
        $ko++;
        continue;
    }

    // 2. Dati per la transazione MIT con token
    $dati = (object) array(
        'CodTrans'          => $codtrans,
        'importo'           => $m['importo'],
        'IP'                => $m['IP'] ?: '127.0.0.1',
        'GPtransactionType' => 'regular',   // SubmitOrderGestPay usa il token quando != oneoff
        'token'             => $m['Token'],
        'mail'              => $m['mail'],
        'titolare'          => trim( ($m['nome'] ?? '') . ' ' . ($m['cognome'] ?? '') ),
    );

    // 3. CreateOrder + SubmitOrder (con token, nessun CVV/3DS)
    $order = CreateOrderGestPay( $dati );
    if ( !is_object( $order ) || !isset( $order->error ) || $order->error->code != 0 ) {
        error_log( $log_prefix . "CreateOrder fallito ($codtrans): " . json_encode( $order->error ?? null ) . PHP_EOL, 3, LOG_FILE );
        AggiornaDonazioneNo3DGP( 'KO', $codtrans );
        $segnaEsitoMandato( $connection, $m['Id_mandato'], $m['frequenza'], 'KO' );
        $ko++;
        continue;
    }
    foreach ( $order->payload as $k => $v ) { if ( is_string( $k ) ) { $dati->$k = $v; } }

    $submit = SubmitOrderGestPay( $dati );
    if ( !is_object( $submit ) || !isset( $submit->error ) || $submit->error->code != 0 ) {
        error_log( $log_prefix . "Submit fallito ($codtrans): " . json_encode( $submit->error ?? null ) . PHP_EOL, 3, LOG_FILE );
        AggiornaDonazioneNo3DGP( 'KO', $codtrans );
        $segnaEsitoMandato( $connection, $m['Id_mandato'], $m['frequenza'], 'KO' );
        $ko++;
        continue;
    }
    foreach ( $submit->payload as $k => $v ) { if ( is_string( $k ) ) { $dati->$k = $v; } }

    $esito = ( isset( $dati->transactionResult ) && $dati->transactionResult === 'OK' ) ? 'OK' : 'KO';

    // 4. Salvo l'ordine GestPay + aggiorno esito donazione
    ScriviOrderGestPay_mysql( $dati );
    AggiornaDonazioneNo3DGP( $esito, $codtrans );

    // 5. Aggiorno il mandato: prossima data, contatori, ultimo esito
    $segnaEsitoMandato( $connection, $m['Id_mandato'], $m['frequenza'], $esito );

    if ( $esito === 'OK' ) {
        $ok++;
        // Mail di ringraziamento (best effort, non blocca il cron)
        $secret = function_exists('generate_signature') ? generate_signature( $m['Id_a'] ) : md5( $m['Id_a'] . SALT_MAIL );
        @file_get_contents( URL_DI_BASE . "/function/mail.php?d=" . $m['Id_a'] . "&s=" . $secret );
    } else {
        $ko++;
    }
    echo "Mandato {$m['Id_mandato']} ($codtrans): $esito\n";
}

mysqli_close( $connection );
echo "Completato. OK: $ok, KO: $ko\n";
error_log( $log_prefix . "completato. Totali: $totali, OK: $ok, KO: $ko" . PHP_EOL, 3, LOG_FILE );
