<?php

if ( !isset( $_SESSION ) ) {
    session_start();
}
require '../inc/config.inc.php';
require '../inc/data.inc.php';
require 'inc/auth-logout.php';
$today = date( "YmdHi" );
header( "Content-Type: application/xls" );
header( "Content-Disposition: attachment; filename=voucher_partner_" . $today . ".xls" );
header( "Pragma: no-cache" );
header( "Expires: 0" );
$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );

$output = "";
if ( $_SESSION[ 'MM_UserAuthorization' ] == "A" ) {
    $output .= "
		<table>
			<thead>
				<tr>
                    <th>Id</th>
					<th>Cognome</th>
					<th>Nome</th>
                    <th>Partner</th> 
                    <th>email</th>
                    <th>Tipo</th>
                    <th>secret</th>
				</tr>
			<tbody>
	";
} else {
    $output .= "
		<table>
			<thead>
				<tr>
					<th>Id</th>
					<th>Cognome</th>
					<th>Nome</th>
                    <th>Partner</th> 
                    <th>email</th>
                    <th>Tipo</th>
                    <th>secret</th>
				</tr>
			<tbody>
	";
}
if ( !isset( $_GET ) ) {
    $query = $conn->query( "SELECT Ticket.Id_ticket,  Ticket.nome, Ticket.cognome, Ticket.mail, Ticket.telefono, Ticket.Tipo, Partner.Nome AS RSPartner FROM `Ticket` LEFT JOIN Partner ON Ticket.Id_partner = Partner.Id_partner WHERE 1 ORDER BY Partner.Nome, Ticket.cognome ASC;" )or die( mysqli_errno() );
} else {
    $query = $conn->query( "SELECT Ticket.Id_ticket,  Ticket.nome, Ticket.cognome, Ticket.mail, Ticket.telefono, Ticket.Tipo, Partner.Nome AS RSPartner FROM `Ticket` LEFT JOIN Partner ON Ticket.Id_partner = Partner.Id_partner WHERE 1 ORDER BY Partner.Nome, Ticket.cognome ASC;" )or die( mysqli_errno() );

}
while ( $fetch = $query->fetch_array() ) {
    // $secret = md5( "partner" . $_REQUEST[ 'd' ] . SALT_MAIL );
    if ( $_SESSION[ 'MM_UserAuthorization' ] == "A" ) {
        $output .= "<tr>
                    <td>" . $fetch[ 'Id_ticket' ] . "</td>
					<td>" . $fetch[ 'cognome' ] . "</td>
					<td>" . $fetch[ 'nome' ] . "</td>
                    <td>" . $fetch[ 'RSPartner' ] . "</td>
					<td>" . $fetch[ 'mail' ] . "</td>
                    <td>" . $fetch[ 'Tipo' ] . "</td>
                    <td>" . md5( "partner" . $fetch[ 'Id_ticket' ] . SALT_MAIL ) . "</td>
				</tr>
                ";
    } else {
        $output .= "<tr>
                    <td>" . $fetch[ 'RSPartner' ] . "</td>
					<td>" . $fetch[ 'cognome' ] . "</td>
					<td>" . $fetch[ 'nome' ] . "</td>
					<td>" . $fetch[ 'mail' ] . "</td>
                    <td>" . $fetch[ 'Tipo' ] . "</td>
				</tr>
                ";
    }
}

$output .= "
			</tbody>
			
		</table>
	";

echo $output;


?>