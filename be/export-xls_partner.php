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
$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );

$output = "";

if ( $_SESSION[ 'MM_UserAuthorization' ] == "A" ) {
    $output .= "
		<table>
			<thead>
				<tr>
                    <th>Cognome</th>
					<th>Nome</th>
					<th>Partner</th>
                    <th>Biglietto</th>
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
					<th>Partner</th>
                    <th>Biglietto</th>
				</tr>
			<tbody>
	";
}

if ( !isset( $_GET ) ) {
    if ( $_SESSION[ 'MM_UserAuthorization' ] == "A" ) {
        
        
        $query = $conn->query( "SELECT Partner.Nome AS Partner, Ticket.nome, Ticket.cognome, Ticket.Tipo  FROM `Ticket` left JOIN Partner on Ticket.Id_partner = Partner.Id_partner WHERE 1;" )or die( mysqli_errno() );
    } else {
        $query = $conn->query( "SELECT Partner.Nome AS Partner, Ticket.nome, Ticket.cognome, Ticket.Tipo  FROM `Ticket` left JOIN Partner on Ticket.Id_partner = Partner.Id_partner WHERE 1" )or die( mysqli_errno() );
    }
} else {
   if ( $_SESSION[ 'MM_UserAuthorization' ] == "A" ) {
        $query = $conn->query( "SELECT Partner.Nome AS Partner, Ticket.nome, Ticket.cognome, Ticket.Tipo  FROM `Ticket` left JOIN Partner on Ticket.Id_partner = Partner.Id_partner WHERE 1;" )or die( mysqli_errno() );
    } else {
        $query = $conn->query( "SELECT Partner.Nome AS Partner, Ticket.nome, Ticket.cognome, Ticket.Tipo  FROM `Ticket` left JOIN Partner on Ticket.Id_partner = Partner.Id_partner WHERE 1;" )or die( mysqli_errno() );
    }

}
while ( $fetch = $query->fetch_array() ) {
    //$secret = md5( Id_a . SALT_MAIL );
    if ( $_SESSION[ 'MM_UserAuthorization' ] == "A" ) {
        $output .= "
				<tr>
                   <td>" . $fetch[ 'cognome' ] . "</td>
					<td>" . $fetch[ 'nome' ] . "</td>
					
					<td>" . $fetch[ 'Partner' ] . "</td>
                    <td>" . $fetch[ 'Tipo' ] . "</td>
					

				</tr>
	";
    } else {
        $output .= "
				<tr>
					<td>" . $fetch[ 'cognome' ] . "</td>
					<td>" . $fetch[ 'nome' ] . "</td>
					
					<td>" . $fetch[ 'Partner' ] . "</td>
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


