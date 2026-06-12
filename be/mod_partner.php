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
            if ( $_SESSION[ 'MM_UserGroup' ] = "A" ) {
                $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );
                if ( $conn->connect_error ) {
                    die( 'Connessione fallita: ' . $conn->connect_error );
                }
                $editFormAction = $_SERVER[ 'PHP_SELF' ];
                if ( isset( $_SERVER[ 'QUERY_STRING' ] ) ) {
                    $editFormAction .= "?" . htmlentities( $_SERVER[ 'QUERY_STRING' ] );
                }
                if ( ( isset( $_POST[ "MM_insert" ] ) ) && ( $_POST[ "MM_insert" ] == "Modpartner" ) ) {
                    // Sanitizzazione semplice
                    $nome = trim( $_POST[ 'nome' ] );
                    $user = trim( $_POST[ 'user' ] );
                    $codice = trim( $_POST[ 'codice' ] );
                    $qt = intval( $_POST[ 'qt' ] );
                    $qtf = intval( $_POST[ 'qtf' ] );
                    $mail = trim( $_POST[ 'mail' ] );
                    $id = trim( $_POST[ 'id' ] );
                    
                    // Prepared statement per l'inserimento
                    $sql = "UPDATE Partner SET Nome = ?, `User`= ?, Codice = ?, Qt = ?, Qtf = ?, mail = ? WHERE Id_partner = ?";
                    $stmt = $conn->prepare( $sql );
                    if ( !$stmt ) { $error = 'Errore prepare: ' . $conn->error; } 
                    else {
                        $stmt->bind_param( 'sssiisi', $nome, $user, $codice, $qt, $qtf, $mail, $id );
                        if ( $stmt->execute() ) {
                            $msg = "Dati del Partner aggiornati con successo - <a href ='partner_list.php'>torna all'elenco</a>";
                            // Pulisci i valori per non ripopolare il form
                            unset( $nome, $user, $codice, $qt, $qtf, $mail, $id );
                        } else {
                            $error = 'Errore execute: ' . $stmt->error;
                        }
                    }
                $stmt->close();
            }
                $query_partner =  sprintf("SELECT * FROM Partner WHERE Id_partner = %s ORDER BY Nome ASC", $_REQUEST['id']);
                $partner = mysqli_query( $conn, $query_partner )or die( mysqli_error() );
                $row_partner = mysqli_fetch_assoc( $partner );
                $totalRows_partner = mysqli_num_rows( $partner );
                
                do{
                    $nome = trim( $row_partner[ 'Nome' ] );
                    $user = trim( $row_partner[ 'User' ] );
                    $codice = trim( $row_partner[ 'Codice' ] );
                    $qt = intval( $row_partner[ 'Qt' ] );
                    $qtf = intval( $row_partner[ 'Qtf' ] );
                    $mail = trim( $row_partner[ 'mail' ] );
                    $id = trim( $row_partner[ 'Id_partner' ] );
                    
                } while ($row_partner = mysqli_fetch_assoc($partner));
                
                //$conn->close();
                ?>
            <div class="container-fluid mt-3">
                <?php if (!empty($msg)): ?>
                <p class="msg">
                    <?= $msg?>
                </p>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                <p class="error">
                    <?= htmlspecialchars($error) ?>
                </p>
                <?php endif; ?>
                <form action="<?php echo $editFormAction; ?>" method="post" name="Modpartner" class = "form-horizontal" role = "form">
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
                            <input name="user" size="15" class="form-control" type="text" name="user" required maxlength="50" value="<?= isset($user) ? htmlspecialchars($user) : '' ?>" />
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
                            <input name="insert" type="submit" id="insert" value="Aggiorna" class="form-control" />
                        </div>
                    </div>
                    <input type="hidden" name="id" value="<?= isset($id) ? (int)$id : 0 ?>" />
                    <input type="hidden" name="MM_insert" value="Modpartner" />
                </form>
            </div>
            <?php }?>
        </main>
    </div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>
