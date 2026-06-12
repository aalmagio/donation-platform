<?php
// RDA v2 bootstrap - 20201116
if ( $_SERVER[ "SERVER_PORT" ] != "443" ) {
	header( 'Location: https://' . $_SERVER[ 'SERVER_NAME' ] . '/' );
	exit;
}
require '../inc/config.inc.php';
require '../inc/data.inc.php';
$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error( $conn ), E_USER_ERROR );
setlocale( LC_ALL, 'it_IT' );
//require_once( 'class/class.phpmailer.php' );
if ( !function_exists( "GetSQLValueString" ) ) {
  function GetSQLValueString( $theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "" ) {
    if ( PHP_VERSION < 6 ) {
      $theValue = get_magic_quotes_gpc() ? stripslashes( $theValue ) : $theValue;
    }
    
    $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error( $conn ), E_USER_ERROR );
    $theValue = $conn->real_escape_string( $theValue );
    if ( isset( $conn ) ) {
      mysqli_close( $conn );
    }
    switch ( $theType ) {
      case "text":
        $theValue = ( $theValue != "" ) ? "'" . $theValue . "'": "NULL";
        break;
      case "long":
      case "int":
        $theValue = ( $theValue != "" ) ? intval( $theValue ) : "NULL";
        break;
      case "double":
        $theValue = ( $theValue != "" ) ? doubleval( $theValue ) : "NULL";
        break;
      case "date":
        $theValue = ( $theValue != "" ) ? "'" . $theValue . "'": "NULL";
        break;
      case "defined":
        $theValue = ( $theValue != "" ) ? $theDefinedValue : $theNotDefinedValue;
        break;
    }

    return $theValue;
  }
}

$editFormAction = $_SERVER[ 'PHP_SELF' ];
if ( isset( $_SERVER[ 'QUERY_STRING' ] ) ) {
	$editFormAction .= "?" . htmlentities( $_SERVER[ 'QUERY_STRING' ] );
}

if ( ( isset( $_POST[ "AA_newmail" ] ) ) && ( $_POST[ "AA_newmail" ] == "newmail" ) ) {
    if ( isset( $_POST[ 'user' ] ) ) {
        $loginUsername = $_POST[ 'user' ];
        $LoginRS__query = sprintf( "SELECT
          `ID_utente`,
          `mail`,
          `attivo`
        FROM
          `Utenti` WHERE mail =%s ",
            GetSQLValueString( $loginUsername, "text" ) );
        //echo $LoginRS__query;
        //exit;
        $LoginRS = mysqli_query( $conn, $LoginRS__query )or die( mysqli_error( $conn ) );
        $loginFoundUser = mysqli_num_rows( $LoginRS );
        if ( $loginFoundUser ) {
            $row_LoginRS = mysqli_fetch_assoc( $LoginRS );
            $token = bin2hex( random_bytes( 50 ) );
            $email = $row_LoginRS[ 'mail' ];
            $ID_Utente = $row_LoginRS[ 'ID_utente' ];
            $sql = "INSERT INTO password_reset(mail, ID_utente, token) VALUES ('$email', '$ID_Utente', '$token')";
            //echo $sql;
            $results = mysqli_query( $conn, $sql );
            // Send email to user with the token in a link they can click on
            $subject = "Reset your password on " . $_SERVER[ 'HTTP_HOST' ];
            $msg = "Ciao per reimpostare la password per il sito " . $_SERVER[ 'HTTP_HOST' ] . ", puoi fare click su questo link\r\nhttps://" . $_SERVER[ 'HTTP_HOST' ] . "/new_password.php?token=" . $token . " \r\n Il link ha validita' per 15 minuti.";
            $msg = wordwrap( $msg, 70 );
            $headers = "From: " . ORG_NOREPLY . "\r\nReply-To:" . ORG_NOREPLY . "\r";
            $retval = mail( $email, $subject, $msg, $headers );
            if ( $retval == true ) {
                $text = "Ti abbiamo spedito via mail un link per aiutarti a recuperare il tuo account.<br>Controlla la tua mail e fai click sul link per reimpostare la tua password.";
            } else {
                $text = "KO";
            }
        } else {
            $text = "<span style=\"color:#F00;\">Si &egrave; verificato un errore:</span> <br />
            Ti prego di verificare il tuo utente o di contattare l'amministratore del sito<br />
            Grazie";
            $messaggio = "Per l'area RDA si e' verificato un tentativo di modifica password per un utente inesisitente [" . $loginUsername . "]";
            $to = ORG_EMAIL;
            $subject = "RDA Tentativo di modifica password utente non esistente";
            $extra = "From: " . ORG_NOREPLY . "\r\nReply-To:" . ORG_NOREPLY . " \r";
            mail( $to, $subject, $messaggio, $extra );

        }
    }
}

?>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="">
<meta name="author" content="Donation Platform">
<title>BE Mentor - Recupero password</title>
<link rel="canonical" href="retrive.php">

<!-- Bootstrap core CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>
<!-- Favicons -->
<link rel="apple-touch-icon" href="img/apple-touch-icon.png" sizes="180x180">
<link rel="icon" href="img/favicon-32x32.png" sizes="32x32" type="image/png">
<link rel="icon" href="imgfavicon-16x16.png" sizes="16x16" type="image/png">
<link rel="icon" href="favicon.ico">
<meta name="theme-color" content="#631719">
<style>
.bd-placeholder-img {
	font-size: 1.125rem;
	text-anchor: middle;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
}

@media (min-width: 768px) {
.bd-placeholder-img-lg {
	font-size: 3.5rem;
}
}
</style>
<!-- Custom styles for this template -->
<link href="css/signin.css" rel="stylesheet">
</head>
	<body>
		<?php if (!isset($text)){ ?>
		<form action="<?php echo $editFormAction; ?>" method="POST" name="Login" id="Login" class="form-signin">
			<img class="mb-4" src="img/logo.svg" alt="" width="72" height="72">
			<h1 class="h3 mb-3 font-weight-normal">RDA - Recupero password: </h1>
			<label for="user" class="sr-only">Email address</label>
			<input type="email" id="user" name="user" class="form-control" placeholder="Email address" required autofocus>
			<button class="btn btn-lg btn-primary btn-block" type="submit">Mandami la mail</button>
			<input type="hidden" name="AA_newmail" value="newmail" />
		</form>
		<?php
		} else {
			echo "<div class=\"container\">" . $text . "<br><a href=\"index.php\">Torna al login</a></div>";
		}
		?>
		<!--  <p> Manutenzione in corso</p> --> 
</body>
</html>


