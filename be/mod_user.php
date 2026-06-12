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
                <h1 class="h2">Gestisci gli utenti</h1>
            </div>
            <!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
            <?php
            if ( $_SESSION[ 'MM_UserGroup' ] = "A" ) {
                $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );
                $editFormAction = $_SERVER[ 'PHP_SELF' ];
                if ( isset( $_SERVER[ 'QUERY_STRING' ] ) ) {
                    $editFormAction .= "?" . htmlentities( $_SERVER[ 'QUERY_STRING' ] );
                }
                if ( ( isset( $_POST[ "MM_update" ] ) ) && ( $_POST[ "MM_update" ] == "moduser" ) ) {
                    $updateSQL = sprintf( "UPDATE `Utenti` SET Nominativo=%s, tel=%s, gruppo=%s, attivo=%s  WHERE ID_utente=%s",
                        GetSQLValueString( $_POST[ 'Nominativo' ], "text" ),
                        GetSQLValueString( $_POST[ 'tel' ], "text" ),
                        GetSQLValueString( $_POST[ 'gruppo' ], "text" ),
                        GetSQLValueString( $_POST[ 'attivo' ], "text" ),
                        GetSQLValueString( $_POST[ 'ID_utente' ], "int" ) );
                    $Result1 = mysqli_query( $conn, $updateSQL )or die( mysqli_error( $conn ) );
                  
                    echo "l'utente " . $_POST[ 'Nominativo' ] . " (" . $_POST[ 'mail' ] . ") &egrave; stato modificato. <br />
                    <a href=\"users.php\">Torna alla gesione utenti</a>";
                    exit;
                } else {
                    $colname_load_user = "-1";
                    if ( isset( $_GET[ 'user' ] ) ) {
                        $colname_load_user = $_GET[ 'user' ] ;
                    }
                    //mysqli_select_db( $database_rda, $rda );
                    $query_load_user = sprintf( "SELECT * FROM Utenti WHERE ID_utente = %s", GetSQLValueString( $colname_load_user, "int" ) );
                    $load_user = mysqli_query( $conn, $query_load_user )or die( mysqli_error( $conn ) );
                    $row_load_user = mysqli_fetch_assoc( $load_user );
                    $totalRows_load_user = mysqli_num_rows( $load_user );

                    ?>
            <div class="container-fluid mt-3">
                <form action="<?php echo $editFormAction; ?>" method="POST" name="moduser" class = "form-horizontal" role = "form" >
                    <input name="ID_utente" type="hidden" value="<?php echo $row_load_user['ID_utente']; ?>" />
                    <div class="form-group">
                        <label for="Nominativo" class="control-label col-sm-2">Nome: </label>
                        <div class="col-sm-6">
                            <input name="Nominativo" type="text" value="<?php echo $row_load_user['Nominativo']; ?>" size="20" maxlength="150" class="form-control" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="tel" class="control-label col-sm-2">Telefono: </label>
                        <div class="col-sm-6">
                            <input name="tel" type="text" value="<?php echo $row_load_user['tel']; ?>" class="form-control" size="20" maxlength="150" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mail" class="control-label col-sm-2">Utente: </label>
                        <div class="col-sm-6">
                            <input name="mail" type="hidden" value="<?php echo $row_load_user['mail'];  ?>" class="form-control" size="20" maxlength="20" />
                            <?php echo $row_load_user['mail']; ?> </div>
                    </div>
                    <div class="form-group">
                        <label for="gruppo" class="control-label col-sm-2">Livello: </label>
                        <div class="col-sm-6">
                            <select name="gruppo" class="form-control">
                                <option value="A" <?php if ($row_load_user['gruppo'] =="A") echo "selected=\"selected\""; ?>>Amministratore</option>
                                <option value="S" <?php if ($row_load_user['gruppo'] =="S") echo "selected=\"selected\""; ?>>Superutente</option>
                                <option value="U" <?php if ($row_load_user['gruppo'] =="U") echo "selected=\"selected\""; ?>>Utente</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="gruppo" class="control-label col-sm-2">Attivo:</label>
                        <div class="col-sm-6">
                            <select name="attivo" class="form-control">
                                <option value="Y" <?php if ($row_load_user['attivo'] =="Y") echo "selected=\"selected\""; ?>>S&igrave;</option>
                                <option value="N" <?php if ($row_load_user['attivo'] =="N") echo "selected=\"selected\""; ?>>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-2">
                            <input name="insert" type="submit" id="insert" value="Modifica" class="form-control"  />
                        </div>
                    </div>
                    <input type="hidden" name="MM_update" value="moduser">
                </form>
            </div>
            <?php
                mysqli_free_result($load_user);
             }
            }?>
        </main>
    </div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>
