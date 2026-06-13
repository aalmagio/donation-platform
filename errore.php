<?php
require 'inc/config.inc.php';
require 'inc/data.inc.php';
/*if($_GET){
	$TY = json_encode( $_GET );
	if(!isset($_GET['p']) || "" == $_GET['p']){
	error_log( date( '[Y-m-d H:i:s e] ' ) . "grazie.php parametro diverso da p: " . $TY . PHP_EOL, 3, LOG_FILE ); //DEBUG		
	}
}*/
//Backup per gestpay.php - INIZIO
if ( USE_GESTPAY == true ) { //GestPay
//Backup per gestpay.php - INIZIO
     if (isset($_GET['a']) && isset($_GET['paymentID']) && isset($_GET['paymentToken'])){// Solo per le chimate Gestpay
	$errore = "";
	if(GP_COD_ESE != $_GET['a']){
    	$errore .= "Non coincide il codice eserente<br>";
	}
	if (!isset( $_GET[ 'paymentID' ] ) ) {
    	$errore .= "Non è definito il codice del pagamento<br>";
	} 
	if ( trim( $errore ) == "" ) {
		$azione_data['TransactionResult'] = $_GET['Status'];
		$connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
		if (!$connection) { error_log(date('[Y-m-d H:i:s e] ') . "errore.php DB connection failed: " . mysqli_connect_error() . PHP_EOL, 3, LOG_FILE); }
		$stmt_err = $connection->prepare("SELECT GestPayREST.shopTransactionID, GestPayREST.paymentID, GestPayREST.transactionErrorCode, GestPayREST.transactionErrorDescription, Donazione.CodTrans, Donazione.Id_a, Donazione.importo, Donazione.centro, Donazione.pay_method, Donazione.nota, Donazione.tessera, Donazione.tipotessera, Donazione.esito, Donazione.`data`, Donazione.CodiceMentor, Donazione.tipo, Donazione.codicePartner, Anagrafica.* FROM GestPayREST LEFT JOIN Donazione ON GestPayREST.shopTransactionID = Donazione.CodTrans LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a WHERE GestPayREST.paymentID =?");
		$stmt_err->bind_param('s', $_GET['paymentID']);
		$stmt_err->execute();
		$donazione = $stmt_err->get_result();
		if (!$donazione) { error_log(date('[Y-m-d H:i:s e] ') . "errore.php query failed: " . $connection->error . PHP_EOL, 3, LOG_FILE); }
		$row_donazione = mysqli_fetch_assoc( $donazione );
		$totalRows_donazione = mysqli_num_rows( $donazione );
		error_log( date( '[Y-m-d H:i:s e] ' ) . "Query grazie.php: (" .$totalRows_donazione .") " .$query_donazione  . PHP_EOL, 3, LOG_FILE );// TEMP
		if($totalRows_donazione>0){
			foreach ( $row_donazione as $key => $value ) {
				if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
					$azione_data[ $key ] = $value;
				}
			}
		} else {
			  error_log( date( '[Y-m-d H:i:s e] ' ) . "Query grazie.php: (" .$totalRows_donazione .") " .$query_donazione  . PHP_EOL, 3, LOG_FILE );
		}
		if ("WA" == $row_donazione['esito'] ){
			if ( $row_donazione[ 'tipo' ] == "regular" ) {
				$query_mandato = sprintf( "SELECT Id_mandato, frequenza, Token, meseToken, annoToken, nomeTitolare FROM Mandato WHERE Id_a =%s", $row_donazione[ 'Id_a' ] );
				$mandato = mysqli_query( $connection, $query_mandato );
			if (!$mandato) { error_log(date('[Y-m-d H:i:s e] ') . "errore.php mandato query failed: " . mysqli_error($connection) . PHP_EOL, 3, LOG_FILE); }
				$row_mandato = mysqli_fetch_assoc( $mandato );
				$totalRows_mandato = mysqli_num_rows( $mandato );
				foreach ( $row_mandato as $key => $value ) {
					if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
						$azione_data[ $key ] = $value;
					}
				}
			}
			if ( DEBUG == true ) {
				error_log( date( '[Y-m-d H:i:s e] ' ) . "grazie.php ESITO 3D : " . $row_donazione['CodTrans'] ." - Status: " .$_GET['Status'] . PHP_EOL, 3, LOG_FILE );
			}
			if(isset($row_donazione['CodTrans']) && preg_match( "/^[A-Z]{1}-[0-9]{17}-[A-Z]{2}/", $row_donazione['CodTrans'])){
				if (empty ($row_donazione['CodiceMentor'])){	
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
				}
				else{
					error_log( date( '[Y-m-d H:i:s e] ' ) . "grazie.php WA con codice mentor : " . $row_donazione['CodTrans'] ." - CodTrans: " .$row_donazione['CodTrans']  . " - Codice Mentor: " .$_GET['CodiceMentor']  . PHP_EOL, 3, LOG_FILE );	
				}
			}
			// MAIL - INIZIO
			$secret = md5( $row_donazione[ 'Id_a' ] . SALT_MAIL );
			$redirect_url = $url_di_base . "/function/mail.php?d=" . $row_donazione[ 'Id_a' ] . "&s=" . $secret;
			// create a new cURL resource
			$ch_mail = curl_init();
			// set URL and other appropriate options
			curl_setopt($ch_mail, CURLOPT_URL, $redirect_url);
			curl_setopt($ch_mail, CURLOPT_HEADER, 0);
			// Cattura l'output di mail.php invece di riversarlo nella pagina
			curl_setopt($ch_mail, CURLOPT_RETURNTRANSFER, true);

			$result_mail = curl_exec($ch_mail);

			// close cURL resource, and free up system resources
			curl_close($ch_mail);

			// MAIL - FINE
		}
	}
}
//Backup per gestpay.php - FINE
}


?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>Errore | <?php echo htmlspecialchars(ORG_NAME); ?></title>
<meta name="description" content="Si è verificato un errore con la donazione">
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
  <div class="campaign-header center">
    <h1>&#9888;&#65039; Ops, qualcosa è andato storto</h1>
    <h2>La tua donazione non è andata a buon fine.</h2>
  </div>

  <div class="card center">
    <p>Nessun importo ti è stato addebitato.<br>
      Per favore, <a href="index.php">torna al form di donazione</a> e riprova.</p>
    <p>Se il problema persiste, scrivici a
      <a href="mailto:<?php echo ORG_EMAIL; ?>"><?php echo ORG_EMAIL; ?></a>: saremo pronti ad aiutarti!</p>
    <a class="btn" href="index.php" style="width:auto">Riprova a donare</a>
  </div>
</div>

<footer class="site-footer">
  &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(ORG_NAME); ?> &middot;
  <a href="<?php echo ORG_PRIVACY_URL; ?>">Privacy Policy</a>
</footer>

</body>
</html>

