<?php
//Autenticazione e logout - INZIO
if ( !isset( $_SESSION ) ) {
    session_start();
}
require '../inc/config.inc.php';
require '../inc/data.inc.php';
require 'inc/auth-logout.php';
$today = date( "YmdHi" );
header( "Content-Type: application/xls" );
header( "Content-Disposition: attachment; filename=donazioni_" . $today . ".xls" );
header( "Pragma: no-cache" );
header( "Expires: 0" );
require_once 'inc/db_helpers.php';
$conn = safe_db_connect();
if ( !$conn ) { die( 'Errore di connessione al database.' ); }

$output = "";

if ( $_SESSION[ 'MM_UserAuthorization' ] == "A" ) {
    $output .= "
		<table>
			<thead>
				<tr>
                    <th>Id</th>
					<th>Cognome</th>
					<th>Nome</th>
                    <th>Email</th>
                    <th>Tel</th>
					<th>Importo</th>
					<th>Metodo</th>
                    <th>Data</th>
                    <th>Ora</th>
                    <th>CodTrans</th>
                    <th>Referral</th>
                    <th>Personal</th>
                    <th>secret</th>
				</tr>
			<tbody>
	";


} else {
    $output .= "
		<table>
			<thead>
				<tr>
					<th>Cognome</th>
					<th>Nome</th>
					<th>Importo</th>
                    <th>Data</th>
                    <th>Ora</th>
                    <th>Referral</th>
                    <th>Personal</th>
				</tr>
			<tbody>
	";
}

if ( !isset( $_GET ) ) {
    if ( $_SESSION[ 'MM_UserAuthorization' ] == "A" ) {
        $query = $conn->query( "SELECT Anagrafica.Id_a AS ID, Anagrafica.cognome, Anagrafica.nome, Anagrafica.mail, Anagrafica.tel, Anagrafica.data_ins, Anagrafica.CodiceReferral, Donazione.importo, Donazione.pay_method, Donazione.CodTrans, (SELECT COUNT(*) FROM Anagrafica AS A2 LEFT JOIN Donazione as D2 ON A2.Id_a = D2.Id_A WHERE A2.CodiceReferral = Anagrafica.CodicePersonale AND D2.Esito ='OK') AS NumeroUtilizziCodicePersonale FROM Donazione LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a WHERE Donazione.esito ='OK' and data >'2026-04-29' ORDER BY Anagrafica.cognome, Anagrafica.nome ASC;" );
        if (!$query) { error_log(date('[Y-m-d H:i:s e] ') . 'export-xls.php query error: ' . mysqli_error($conn) . PHP_EOL, 3, LOG_FILE); die('Errore interno.'); }
        $query = $query;
    } else {
        $query = $conn->query( "SELECT Anagrafica.cognome, Anagrafica.nome, Anagrafica.data_ins, Anagrafica.CodiceReferral, Donazione.importo, (SELECT COUNT(*) FROM Anagrafica AS A2 LEFT JOIN Donazione as D2 ON A2.Id_a = D2.Id_A WHERE A2.CodiceReferral = Anagrafica.CodicePersonale AND D2.Esito ='OK') AS NumeroUtilizziCodicePersonale FROM Donazione LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a WHERE Donazione.esito ='OK' and data >'2026-04-29' ORDER BY Anagrafica.cognome, Anagrafica.nome ASC;" );
        if (!$query) { error_log(date('[Y-m-d H:i:s e] ') . 'export-xls.php query error: ' . mysqli_error($conn) . PHP_EOL, 3, LOG_FILE); die('Errore interno.'); }
        $query = $query;
    }
} else {
    if ( $_SESSION[ 'MM_UserAuthorization' ] == "A" ) {
        $query = $conn->query( "SELECT Anagrafica.Id_a AS ID, Anagrafica.cognome, Anagrafica.nome, Anagrafica.mail, Anagrafica.tel, Anagrafica.data_ins, Anagrafica.CodiceReferral, Donazione.importo, Donazione.pay_method, Donazione.CodTrans, (SELECT COUNT(*) FROM Anagrafica AS A2 LEFT JOIN Donazione as D2 ON A2.Id_a = D2.Id_A WHERE A2.CodiceReferral = Anagrafica.CodicePersonale AND D2.Esito ='OK') AS NumeroUtilizziCodicePersonale FROM Donazione LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a WHERE Donazione.esito ='OK' and data >'2026-04-29' ORDER BY Anagrafica.cognome, Anagrafica.nome ASC;" );
        if (!$query) { error_log(date('[Y-m-d H:i:s e] ') . 'export-xls.php query error: ' . mysqli_error($conn) . PHP_EOL, 3, LOG_FILE); die('Errore interno.'); }
        $query = $query;
    } else {
        $query = $conn->query( "SELECT Anagrafica.cognome, Anagrafica.nome, Anagrafica.data_ins, Anagrafica.CodiceReferral, Donazione.importo, (SELECT COUNT(*) FROM Anagrafica AS A2 LEFT JOIN Donazione as D2 ON A2.Id_a = D2.Id_A WHERE A2.CodiceReferral = Anagrafica.CodicePersonale AND D2.Esito ='OK') AS NumeroUtilizziCodicePersonale FROM Donazione LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a WHERE Donazione.esito ='OK' and data >'2026-04-29' ORDER BY Anagrafica.cognome, Anagrafica.nome ASC;" );
        if (!$query) { error_log(date('[Y-m-d H:i:s e] ') . 'export-xls.php query error: ' . mysqli_error($conn) . PHP_EOL, 3, LOG_FILE); die('Errore interno.'); }
        $query = $query;
    }

}
while ( $fetch = $query->fetch_array() ) {
    //$secret = md5( Id_a . SALT_MAIL );
    if ( $fetch[ 'CodiceReferral' ] == "" ) {
        $CodiceReferral = "";
    } else {
        $stmt_ref = $conn->prepare( "SELECT nome, cognome FROM Anagrafica WHERE CodicePersonale = ?" );
        if (!$stmt_ref) { error_log(date('[Y-m-d H:i:s e] ') . 'export-xls.php referral prepare error: ' . $conn->error . PHP_EOL, 3, LOG_FILE); continue; }
        $ref_code = $fetch[ 'CodiceReferral' ];
        $stmt_ref->bind_param( 's', $ref_code );
        $stmt_ref->execute();
        $referral = $stmt_ref->get_result();
        $row_referral = mysqli_fetch_assoc( $referral );
        $totalRows_referral = mysqli_num_rows( $referral );
        if ( $totalRows_referral == 1 ) {
            $CodiceReferral = "Verificato: " . $row_referral[ 'nome' ] . " " . $row_referral[ 'cognome' ];
        } else {
            $CodiceReferral = "Da verificare: " . $fetch[ 'CodiceReferral' ];
        }
        $stmt_ref->close();

    }
    if ($fetch[ 'NumeroUtilizziCodicePersonale' ]>0) {$CodicePersonale = "Y";} else {$CodicePersonale =  "N";}
    if ( $_SESSION[ 'MM_UserAuthorization' ] == "A" ) {
        $output .= "
				<tr>
                    <td>" . $fetch[ 'ID' ] . "</td>
					<td>" . $fetch[ 'cognome' ] . "</td>
					<td>" . $fetch[ 'nome' ] . "</td>
                    <td>" . $fetch[ 'mail' ] . "</td>
                    <td>" . $fetch[ 'tel' ] . "</td>
					<td>" . $fetch[ 'importo' ] . "</td>
					<td>" . $fetch[ 'pay_method' ] . "</td>
                    <td>" . date( "d/m/Y", strtotime( $fetch[ 'data_ins' ] ) ) . "</td>
                    <td>" . date( "H:i", strtotime( $fetch[ 'data_ins' ] ) ) . "</td>
                    <td>" . $fetch[ 'CodTrans' ] . "</td>
                    <td>" . $CodiceReferral . "</td>
                    <td>" .  $CodicePersonale  . "</td>
                    <td>" . md5( $fetch[ 'ID' ] . SALT_MAIL ) . "</td>
				</tr>
	";
    } else {
        $output .= "
				<tr>
					<td>" . $fetch[ 'cognome' ] . "</td>
					<td>" . $fetch[ 'nome' ] . "</td>
					
					<td>" . $fetch[ 'importo' ] . "</td>
                    <td>" . date( "d/m/Y", strtotime( $fetch[ 'data_ins' ] ) ) . "</td>
                    <td>" . date( "H:i", strtotime( $fetch[ 'data_ins' ] ) ) . "</td>
                     <td>" . $CodiceReferral . "</td>
                     <td>" . $CodicePersonale   . "</td>
					
				</tr>
	";
    }
}

$output .= "
			</tbody>
			
		</table>
	";

echo $output;