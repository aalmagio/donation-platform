<?php
if ( !isset( $_SESSION ) ) session_start();
require '../inc/config.inc.php';
require '../inc/data.inc.php';
require 'inc/auth-logout.php';

header( 'Content-Type: application/json; charset=utf-8' );

if ( !isset( $_GET['date1'], $_GET['date2'], $_GET['destinatari'] ) ) {
    echo json_encode( ['error' => 'Parametri mancanti'] );
    exit;
}

$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
if ( !$conn ) {
    echo json_encode( ['error' => 'Connessione DB fallita'] );
    exit;
}

$page     = isset( $_GET['page'] ) && is_numeric( $_GET['page'] ) ? max( 1, (int)$_GET['page'] ) : 1;
$per_page = 50;
$offset   = ( $page - 1 ) * $per_page;
$invio_n  = isset( $_GET['n'] ) && is_numeric( $_GET['n'] ) ? (int)$_GET['n'] : 1;
$date1    = $conn->real_escape_string( $_GET['date1'] );
$date2    = $conn->real_escape_string( $_GET['date2'] );

if ( $_GET['destinatari'] === 'partner' ) {

    $where     = sprintf( "WHERE remainder < %d", $invio_n );
    $count_sql = "SELECT COUNT(*) AS tot
                  FROM Ticket
                  LEFT JOIN Partner ON Ticket.Id_partner = Partner.Id_partner
                  $where";
    $data_sql  = "SELECT Ticket.Id_ticket, Ticket.nome, Ticket.cognome, Ticket.mail,
                         Ticket.tipo, Ticket.remainder, Partner.Nome AS NomePartner
                  FROM Ticket
                  LEFT JOIN Partner ON Ticket.Id_partner = Partner.Id_partner
                  $where
                  ORDER BY Id_ticket ASC
                  LIMIT $per_page OFFSET $offset";

} else { // donor

    if ( isset( $_GET['ID_Mentor'] ) && $_GET['ID_Mentor'] === 'NOTNULL' ) {
        // Query MENTOR (with pay_method filter)
        if ( isset( $_GET['pay_method'] ) && $_GET['pay_method'] === 'ALL' ) {
            $pay_clause = "AND pay_method IS NOT NULL";
        } else {
            $pm         = $conn->real_escape_string( $_GET['pay_method'] ?? '' );
            $pay_clause = "AND pay_method = '$pm'";
        }
        $mentor_clause = "AND Anagrafica.ID_Mentor IS NOT NULL AND Donazione.CodiceMentor IS NOT NULL";
    } else {
        // Query NON-MENTOR (matches simplified active query — no pay_method filter)
        $mentor_clause = '';
        $pay_clause    = '';
    }

    $where = sprintf(
        "WHERE Donazione.Esito = 'OK'
         AND Donazione.tipo = 'oneoff'
         %s %s
         AND Donazione.Data >= '%s'
         AND Donazione.Data <= '%s'
         AND Donazione.remainder < %d",
        $mentor_clause,
        $pay_clause,
        $date1 . ' 00:00:00',
        $date2 . ' 23:59:59',
        $invio_n
    );

    $join      = "FROM Donazione LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a";
    $count_sql = "SELECT COUNT(*) AS tot $join $where";
    $data_sql  = "SELECT Anagrafica.Id_a, Anagrafica.nome, Anagrafica.cognome,
                         Anagrafica.mail, Donazione.importo, Donazione.pay_method,
                         Donazione.remainder
                  $join $where
                  ORDER BY Anagrafica.Id_a ASC
                  LIMIT $per_page OFFSET $offset";
}

$count_res = mysqli_query( $conn, $count_sql );
if ( !$count_res ) {
    echo json_encode( ['error' => mysqli_error( $conn )] );
    mysqli_close( $conn );
    exit;
}
$total       = (int)mysqli_fetch_assoc( $count_res )['tot'];
$total_pages = $total > 0 ? (int)ceil( $total / $per_page ) : 1;

$data_res = mysqli_query( $conn, $data_sql );
if ( !$data_res ) {
    echo json_encode( ['error' => mysqli_error( $conn )] );
    mysqli_close( $conn );
    exit;
}

$contacts = [];
while ( $row = mysqli_fetch_assoc( $data_res ) ) {
    $contacts[] = $row;
}

mysqli_close( $conn );

echo json_encode( [
    'total'       => $total,
    'page'        => $page,
    'pages'       => $total_pages,
    'per_page'    => $per_page,
    'destinatari' => $_GET['destinatari'],
    'contacts'    => $contacts,
], JSON_UNESCAPED_UNICODE );
