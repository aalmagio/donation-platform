<?php
if ( $_SERVER[ "SERVER_PORT" ] != "443" ) {
    header( 'Location: https://' . $_SERVER[ 'SERVER_NAME' ] . '/' );
    exit;
}
setlocale( LC_ALL, 'it_IT' );
require '../inc/config.inc.php';
require '../inc/data.inc.php';
//initialize the session
if ( !isset( $_SESSION ) ) {
    session_start();
}
$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error( $conn ), E_USER_ERROR );
setlocale( LC_ALL, 'it_IT' );
if ( isset( $_GET[ 'token' ] ) )$_SESSION[ 'token' ] = mysqli_real_escape_string( $conn, $_GET[ 'token' ] );
$errors = [];

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

if ( ( isset( $_POST[ "AA_newpasswd" ] ) ) && ( $_POST[ "AA_newpasswd" ] == "newpasswd" ) ) {

    $new_pass = mysqli_real_escape_string( $conn, $_POST[ 'new_pass' ] );
    $new_pass_c = mysqli_real_escape_string( $conn, $_POST[ 'new_pass_c' ] );
    $token = $_SESSION[ 'token' ];
    if ( empty( $new_pass ) || empty( $new_pass_c ) )array_push( $errors, "La password &egrave; obbligatoria" );
    if ( $new_pass !== $new_pass_c )array_push( $errors, "Le password digitate non coincidono." );
    if ( strlen( $new_pass ) < 8 )array_push( $errors, "Le password digitate sono pi&ugrave; corte di 8 caratteri." );
    if ( count( $errors ) == 0 ) {
        // select email address of user from the password_reset table 
        $sql = "SELECT * FROM password_reset WHERE token='$token' AND DATE_SUB(NOW(),INTERVAL 15 MINUTE)LIMIT 1";
        echo $sql;
        $results = mysqli_query( $conn, $sql );
        $row = mysqli_fetch_assoc( $results );
        $user = $row[ 'ID_utente' ];
        if ( $user ) {
            $new_pass = md5( $new_pass );
            $sql = "UPDATE Utenti SET password='$new_pass' WHERE ID_utente='$user'";
            $results = mysqli_query( $conn, $sql );
            $sql = "UPDATE password_reset SET token='' WHERE token='$token'";
            $results = mysqli_query( $conn, $sql );

            header( 'location: index.php?r=ok' );

        } else {
            array_push( $errors, "Il link per il cambio della password è scaduto. <br />Per favore ottieni un nuovo link <a href=\"retrive.php \">qui</a> " );
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
<title>BED - Back End Donazioni </title>
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
<?php
if ( count( $errors ) > 0 ) {
    echo "<div class=\"container\">";
    foreach ( $errors as $error ) {
        echo $error;
    }
    echo "<br><a href=\"new_password.php?token=".$_GET['token']."\">Torna alla reimpostazione della password</a></div>";
} else {
    ?>
<form action="<?php echo $editFormAction; ?>" method="POST" name="changepassword" id="changepassword" class="form-signin">
    <img class="mb-4" src="img/logo.svg" alt="" width="72" height="72">
    <h1 class="h3 mb-3 font-weight-normal">RDA - Reimposta la password </h1>
    <label for="new_pass" class="sr-only">Nuova password: </label>
    <input name="new_pass" type="password"  class="form-control"  pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="La password deve contenere almeno un numero una lettera maiuscola e una lettera minuscola. Deve esser lunga almeno 8 caratteri" required autofocus />
    <br />
    <label for="new_pass_c" class="sr-only">Ripeti la password:</label>
    <input name="new_pass_c" type="password" class="form-control" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="La password deve contenere almeno un numero una lettera maiuscola e una lettera minuscola. Deve esser lunga almeno 8 caratteri" required autofocus  />
    <input type="hidden" name="AA_newpasswd" value="newpasswd"/>
    <p align="center">
        <input name="Entra" type="submit" value="Entra"/>
    </p>
</form>
<?php } ?>
</body>
</html>
