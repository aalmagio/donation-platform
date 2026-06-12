<?php
/*
* v 202506 - Security hardening
* - Replaced MD5 with password_hash/password_verify (bcrypt)
* - Added CSRF protection
* - Used prepared statements
* - Added rate limiting
* - Added security headers
*/
if ( !isset( $_SESSION ) ) {
	session_start();
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

require '../inc/config.inc.php';
require '../inc/data.inc.php';
require '../inc/csrf.php';

$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
if (!$conn) {
	error_log("DB connection failed in be/index.php");
	die("Errore interno del server.");
}

$actualURL = "https://" . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
if ( $_SERVER[ 'SERVER_PORT' ] != "443" ) {
	header( "Location: $actualURL" );
	exit;
}
// ** Logout the current user. **
$logoutAction = $_SERVER[ 'PHP_SELF' ] . "?doLogout=true";
if ( ( isset( $_SERVER[ 'QUERY_STRING' ] ) ) && ( $_SERVER[ 'QUERY_STRING' ] != "" ) ) {
	$logoutAction .= "&amp;" . htmlentities( $_SERVER[ 'QUERY_STRING' ] );
}
$loginFormAction = $_SERVER[ 'PHP_SELF' ];
if ( isset( $_GET[ 'accesscheck' ] ) ) {
	$_SESSION[ 'PrevUrl' ] = $_GET[ 'accesscheck' ];
}
if ( isset( $_POST[ 'mail' ] ) ) {

	// CSRF validation
	if (!csrf_validate($_POST['csrf_token'] ?? '')) {
		error_log( date( '[Y-m-d H:i:s e] ' ) . "CSRF token non valido per login attempt" . PHP_EOL, 3, LOG_FILE );
		header( "Location: index.php?e=2" );
		exit;
	}

	// Rate limiting: max 5 tentativi per IP in 15 minuti
	$ip = $_SERVER['REMOTE_ADDR'];
	$rate_key = 'login_attempts_' . md5($ip);
	if (!isset($_SESSION[$rate_key])) {
		$_SESSION[$rate_key] = ['count' => 0, 'first_attempt' => time()];
	}
	if (time() - $_SESSION[$rate_key]['first_attempt'] > 900) {
		$_SESSION[$rate_key] = ['count' => 0, 'first_attempt' => time()];
	}
	$_SESSION[$rate_key]['count']++;
	if ($_SESSION[$rate_key]['count'] > 5) {
		error_log( date( '[Y-m-d H:i:s e] ' ) . "Rate limit exceeded for IP: " . $ip . PHP_EOL, 3, LOG_FILE );
		header( "Location: index.php?e=3" );
		exit;
	}

	$loginUsername = $_POST[ 'mail' ];
	$password = $_POST[ 'Password' ];

	$MM_redirectLoginSuccess = "oneoff.php";
	$MM_redirectLoginFailed = "index.php?e=1";
	$MM_redirecttoReferrer = true;

	// Prepared statement per il login
	$stmt = $conn->prepare("SELECT Nominativo, mail, password, scadenza_pwd, gruppo FROM Utenti WHERE mail = ? AND attivo = 'Y'");
	$stmt->bind_param('s', $loginUsername);
	$stmt->execute();
	$LoginRS = $stmt->get_result();
	$loginFoundUser = $LoginRS->num_rows;
	$row_LoginRS = $LoginRS->fetch_assoc();
	$stmt->close();

	if ( $loginFoundUser && $row_LoginRS ) {
		$stored_password = $row_LoginRS['password'];
		$password_valid = false;

		// Supporto doppio: bcrypt (nuovo) e MD5 (legacy, per migrazione)
		if (password_get_info($stored_password)['algo'] !== null && password_get_info($stored_password)['algo'] !== 0) {
			// Password gia in formato bcrypt
			$password_valid = password_verify($password, $stored_password);
		} else {
			// Legacy MD5 - verifica e migra a bcrypt
			if ($stored_password === md5($password)) {
				$password_valid = true;
				// Migra la password a bcrypt
				$new_hash = password_hash($password, PASSWORD_BCRYPT);
				$update_stmt = $conn->prepare("UPDATE Utenti SET password = ? WHERE mail = ?");
				$update_stmt->bind_param('ss', $new_hash, $loginUsername);
				$update_stmt->execute();
				$update_stmt->close();
				error_log( date( '[Y-m-d H:i:s e] ' ) . "Password migrata a bcrypt per: " . $loginUsername . PHP_EOL, 3, LOG_FILE );
			}
		}

		if ( $password_valid ) {
			$loginStrGroup = $row_LoginRS['gruppo'];
			$_SESSION[ 'MM_Username' ] = $loginUsername;
			$_SESSION[ 'MM_UserGroup' ] = $loginStrGroup;
			$_SESSION[ 'MM_UserAuthorization' ] = $row_LoginRS[ 'gruppo' ];
			$_SESSION[ 'AA_Chi' ] = $row_LoginRS[ 'Nominativo' ];

			// Reset rate limiting on success
			unset($_SESSION[$rate_key]);

			// Rigenera session ID per prevenire session fixation
			session_regenerate_id(true);
			// Rigenera CSRF token
			csrf_regenerate();

			if ( isset( $_SESSION[ 'PrevUrl' ] ) && true ) {
				$MM_redirectLoginSuccess = $_SESSION[ 'PrevUrl' ];
			}
			$data = date( "d.m.y H.i.s" );
			$ip_host = $_SERVER[ "REMOTE_ADDR" ];
			$browser = $_SERVER[ "HTTP_USER_AGENT" ];
			error_log( date( '[Y-m-d H:i:s e] ' ) . "BED Login  " .$_SESSION[ 'MM_Username' ] ." IP ". $ip_host. PHP_EOL, 3, LOG_FILE );
			$messaggio = "Questo e' un messaggio automatico generato dal gestionale donazioni\r\n per garantire la sicurezza delle tue credenziali di accesso .\r\nQualcuno in data " . $data . " dall'IP " . $ip_host . " con il browser \r\n" . $browser . " \r\nha effettuato l'accesso al sistema utilizzando le tue credenziali\r\n\r\nSe non fossi stato tu ad accedere informa l'amministratore del sito e affrettati a cambiare la tua password.\r\n\r\nSe invece sei stato tu ad accedere al sito ignora pure questo messaggio.";
			$to = $row_LoginRS[ 'mail' ];
			$subject = "Accesso all'area riservata Donazioni ";
			$extra = "From: " . ORG_NOREPLY . "\r\nReply-To: " . ORG_NOREPLY . "\r\n";
			mail( $to, $subject, $messaggio, $extra );
			header( "Location: " . $MM_redirectLoginSuccess );
			exit;
		} else {
			// Password non valida
			$ip_host = $_SERVER[ "REMOTE_ADDR" ];
			error_log( date( '[Y-m-d H:i:s e] ' ) . "Failed login attempt for: " . $loginUsername . " from IP: " . $ip_host . PHP_EOL, 3, LOG_FILE );
			header( "Location: " . $MM_redirectLoginFailed );
			exit;
		}
	} else {
		$ip_host = $_SERVER[ "REMOTE_ADDR" ];
		error_log( date( '[Y-m-d H:i:s e] ' ) . "Failed login attempt (user not found): " . $loginUsername . " from IP: " . $ip_host . PHP_EOL, 3, LOG_FILE );
		header( "Location: " . $MM_redirectLoginFailed );
		exit;
	}
}
if ( ( isset( $_GET[ 'doLogout' ] ) ) && ( $_GET[ 'doLogout' ] == "true" ) ) {
	$_SESSION = [];
	session_destroy();
	session_start();
	$logoutGoTo = "index.php";
	header( "Location: $logoutGoTo" );
	exit;
}
$MM_authorizedUsers = "";
$MM_donotCheckaccess = "true";

function isAuthorized( $strUsers, $strGroups, $UserName, $UserGroup ) {
	$isValid = False;
	if ( !empty( $UserName ) ) {
		$arrUsers = Explode( ",", $strUsers );
		$arrGroups = Explode( ",", $strGroups );
		if ( in_array( $UserName, $arrUsers ) ) {
			$isValid = true;
		}
		if ( in_array( $UserGroup, $arrGroups ) ) {
			$isValid = true;
		}
		if ( ( $strUsers == "" ) && true ) {
			$isValid = true;
		}
	}
	return $isValid;
}
$MM_restrictGoTo = "index.php";
?>
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="">
<meta name="author" content="Donation Platform">
<title>BED - Back End Donazioni</title>

<!-- Bootstrap core CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>

<!-- Favicons -->
<link rel="apple-touch-icon" sizes="57x57" href="img/apple-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="img/apple-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="img/apple-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="img/apple-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="img/apple-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="img/apple-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="img/apple-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="img/apple-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="img/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192"  href="img/android-icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="img/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="img/favicon-16x16.png">
<!-- <link rel="manifest" href="img/manifest.json"> -->

<meta name="msapplication-TileImage" content="img/ms-icon-144x144.png">

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



<form class="form-signin" action="<?php echo htmlspecialchars($loginFormAction); ?>" method="post" name="Login" id="Login">
	<?php echo csrf_field(); ?>
	<img class="mb-4" src="../img/logo_wr.svg" alt="" width="72" height="72">
	<h1 class="h3 mb-3 font-weight-normal">BED - Back End Donazioni - Accedi: </h1>
	<label for="mail" class="sr-only">Email address</label>
	<input type="email" id="mail" name="mail" class="form-control" placeholder="Email address" required autofocus>
	<label for="Password" class="sr-only">Password</label>
	<input type="password" id="Password" name="Password" class="form-control" placeholder="Password" required>
	<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>


		<?php if (isset($_GET['e']) && $_GET['e'] == '1'){ ?>
<p class="mt-5 mb-3 text-muted"><a href="retrive.php">[Non ricordi la password? Clicca qui per recuperarla!]</a> </p>
		<?php } elseif (isset($_GET['e']) && $_GET['e'] == '3'){ ?>
<p class="mt-5 mb-3 text-danger">Troppi tentativi di accesso. Riprova fra 15 minuti.</p>
		<?php }?>
	<p class="mt-5 mb-3 text-muted">&copy; 2017-2026</p>
	</form>
</body></html>
<?php if (isset($conn)){mysqli_close($conn); }?>

