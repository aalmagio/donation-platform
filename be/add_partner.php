<?php
if ( !isset( $_SESSION ) ) {
    session_start();
}
require '../inc/config.inc.php';
require '../inc/data.inc.php';
//require_once( 'vendor/autoload.php' );
require 'inc/auth-logout.php';
if ( !function_exists( "GetSQLValueString" ) ) {
    function GetSQLValueString( $theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "" ) {
        if ( PHP_VERSION < 6 ) {
            $theValue = get_magic_quotes_gpc() ? stripslashes( $theValue ) : $theValue;
        }
        //$theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);
        $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );
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

function utf8ize( $d ) {
    if ( is_array( $d ) ) {
        foreach ( $d as $k => $v ) {
            $d[ $k ] = utf8ize( $v );
        }
    } else if ( is_string( $d ) ) {
        return utf8_encode( $d );
    }
    return $d;
}
// Gestione caratteri spciali -FINE

?>
<?php require('inc/head.inc.php'); ?>
<body>
<?php require('inc/nav_hor.inc.php'); ?>
<div class="container-fluid">
    <div class="row">
        <?php require('inc/nav_ver.inc.php'); ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestisci i Partner</h1>
            </div>
            <!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
            <?php
            if ( isset( $_SESSION[ 'MM_UserGroup' ] ) && $_SESSION[ 'MM_UserGroup' ] == "A" ) {
                $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
                if ( !$conn ) {
                    error_log( date( '[Y-m-d H:i:s e] ' ) . "add_partner.php: DB connection failed" . PHP_EOL, 3, LOG_FILE );
                    die( "Errore interno del server." );
                }
                $editFormAction = $_SERVER[ 'PHP_SELF' ];
                if ( isset( $_SERVER[ 'QUERY_STRING' ] ) ) {
                    $editFormAction .= "?" . htmlentities( $_SERVER[ 'QUERY_STRING' ] );
                }
                if ( ( isset( $_POST[ "MM_insert" ] ) ) && ( $_POST[ "MM_insert" ] == "Addpartener" ) ) {
                    // Sanitizzazione semplice
                    $nome = trim( $_POST[ 'nome' ] );
                    $user = trim( $_POST[ 'user' ] );
                    $codice = trim( $_POST[ 'codice' ] );
                    $qt = intval( $_POST[ 'qt' ] );
                    $qtf = intval( $_POST[ 'qtf' ] );
                    $mail = trim( $_POST[ 'mail' ] );

                    // Controllo unicità di "User"
                    $check = $conn->prepare( "SELECT COUNT(*) FROM Partner WHERE `User` = ?" );
                    $check->bind_param( 's', $user );
                    $check->execute();
                    $check->bind_result( $cnt );
                    $check->fetch();
                    $check->close();

                    if ( $cnt > 0 ) {
                        $error = "Il codice utente «{$user}» è già in uso, scegli un altro User.";
                    } else {
                        // Prepared statement per l'inserimento
                        $sql = "
                              INSERT INTO Partner
                                (Nome, `User`, Codice, Qt, Qtf, mail)
                              VALUES
                                (?, ?, ?, ?, ?, ?)
                            ";
                        $stmt = $conn->prepare( $sql );
                        if ( !$stmt ) {
                            $error = 'Errore prepare: ' . $conn->error;
                        } else {
                            $stmt->bind_param( 'sssiis', $nome, $user, $codice, $qt, $qtf, $mail );
                            if ( $stmt->execute() ) {
                                $msg = "Partner aggiunto con successo (ID: {$stmt->insert_id})";
                                // Pulisci i valori per non ripopolare il form
                                unset( $nome, $user, $codice, $qt, $qtf, $mail );
                            } else {
                                // Nel raro caso passi il check ma scatti il vincolo UNIQUE sul DB
                                if ( $stmt->errno === 1062 ) {
                                    $error = "Errore: l'User «{$user}» esiste già.";
                                } else {
                                    $error = 'Errore execute: ' . $stmt->error;
                                }
                            }
                            $stmt->close();
                        }
                    }
                }

                // Carica la lista utenti esistenti per il datalist
                $stmt_users = $conn->prepare( "SELECT `User` FROM Partner ORDER BY `User`" );
                $userList = [];
                if ( $stmt_users ) {
                    $stmt_users->execute();
                    $result_users = $stmt_users->get_result();
                    while ( $row = $result_users->fetch_assoc() ) {
                        $userList[] = $row[ 'User' ];
                    }
                    $stmt_users->close();
                }

                $conn->close();
                $conn = null;

            ?>
            <div class="container-fluid mt-3">
                <?php if (!empty($msg)): ?>
                <p class="msg">
                    <?= htmlspecialchars($msg) ?>
                </p>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                <p class="error">
                    <?= htmlspecialchars($error) ?>
                </p>
                <?php endif; ?>
                <form action="<?php echo $editFormAction; ?>" method="post" name="Adduser" class = "form-horizontal" role = "form">
                    <div class="form-group">
                        <label for="nome" class="control-label col-sm-2">Nome: </label>
                        <div class="col-sm-6">
                            <input name="nome" type="text" value="<?= isset($nome) ? htmlspecialchars($nome) : '' ?>" size="20" maxlength="150" class="form-control" required/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mail" class="control-label col-sm-2">Mail: </label>
                        <div class="col-sm-6">
                            <input name="mail" type="email" size="20" maxlength="40" class="form-control" required maxlength="50" value="<?= isset($mail) ? htmlspecialchars($mail) : '' ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="user" class="control-label col-sm-2">User </label>
                        <div class="col-sm-6">
                            <input list="user-list" name="user" size="15" class="form-control" type="text" name="user" required maxlength="50"
             value="<?= isset($user) ? htmlspecialchars($user) : '' ?>" />
                            <datalist id="user-list">
                                <?php foreach ($userList as $u): ?>
                                <option value="<?= htmlspecialchars($u) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="codice" class="control-label col-sm-2">Codice </label>
                        <div class="col-sm-6">
                            <input name="codice" type="text" size="15" class="form-control" required maxlength="36" value="<?= isset($codice) ? htmlspecialchars($codice) : '' ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="qt" class="control-label col-sm-2">Qt: </label>
                        <div class="col-sm-6">
                            <input name="qt" type="number" size="20" maxlength="40" class="form-control" required min="0" value="<?= isset($qt) ? (int)$qt : 0 ?>" />
                        </div>
                    </div>
                     <div class="form-group">
                        <label for="qtf" class="control-label col-sm-2">Qtf: </label>
                        <div class="col-sm-6">
                            <input name="qtf" type="number" size="20" maxlength="40" class="form-control" required min="0" value="<?= isset($qtf) ? (int)$qtf : 0 ?>" />
                        </div>
                    </div>
            
             
                    <div class="form-group">
                        <div class="col-sm-2">
                            <input name="insert" type="submit" id="insert" value="Inserisci" class="form-control" />
                        </div>
                    </div>
                    <input type="hidden" name="scadenza_pwd" value="2007-01-01" />
                    <input type="hidden" name="MM_insert" value="Addpartener" />
                </form>
            </div>
            <?php }?>
        </main>
    </div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>
