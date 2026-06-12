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
                if ( ( isset( $_POST[ "MM_update" ] ) ) && ( $_POST[ "MM_update" ] == "chg_pwd" ) ) {
                    if ( isset( $_POST[ 'password' ] ) && $_POST[ 'password' ] === $_POST[ 'password2' ] && strlen( $_POST[ 'password' ] ) >= 6 ) {
                        $updateSQL = sprintf( "UPDATE `Utenti` SET password=%s, scadenza_pwd=%s WHERE ID_utente=%s",
                            GetSQLValueString( md5( $_POST[ 'password' ] ), "text" ),
                            GetSQLValueString( $_POST[ 'scadenza_pwd' ], "date" ),
                            GetSQLValueString( $_POST[ 'ID_utente' ], "int" ) );
                        $Result1 = mysqli_query( $conn, $updateSQL )or die( mysqli_error( $conn ) );
                        $updateGoTo = "users.php"; //da cambiare
                        if ( isset( $_SERVER[ 'QUERY_STRING' ] ) ) {
                            $updateGoTo .= ( strpos( $updateGoTo, '?' ) ) ? "&" : "?";
                        }
                        echo "la password &egrave; stata modificato. <br />
  Effettua il logout e ricollegati con la nuova password.

	</div>
	
</div>

	  
</body>
</html>
  
  ";
                        exit;
                    } else {
                        echo "Le password digitate non coincidono o sono pi&ugrave; corte di 8 caratteri. <br /> 
  <a href=\"change_pwd.php?user=".$_GET[ 'user' ]."\">Reinserisci i dati</a>
  
	</div>
	
</div>

	  
</body>
</html>
  
  ";
                        exit;
                    }
                }

                $colname_User_tocg = "-1"; //-1
                if ( isset( $_GET[ 'user' ] ) ) {
                    $colname_User_tocg = $_GET[ 'user' ] ;
                } elseif ( isset( $_SESSION[ 'MM_Username' ] ) ) {
                    $colname_User_tocg =  $_SESSION[ 'MM_Username' ];
                }
                $query_User_tocg = sprintf( "SELECT ID_utente, Nominativo, mail, password, scadenza_pwd FROM `Utenti` WHERE mail = %s", GetSQLValueString( $colname_User_tocg, "text" ) );
                //echo $query_User_tocg;
                $User_tocg = mysqli_query( $conn, $query_User_tocg )or die( mysqli_error( $conn ) );
                $row_User_tocg = mysqli_fetch_assoc( $User_tocg );
                //$totalRows_User_tocg = mysqli_num_rows($User_tocg);
                ?>
            <?php
            $last_change = strtotime( $row_User_tocg[ 'scadenza_pwd' ] );
            if ( $last_change != 1167606000 ) { // time stmap di 2007-01-01
                if ( time() - $last_change > 7776000 ) {
                    echo "ATTENZIONE la password &egrave; pi&ugrave; vecchia di 3 mesi.<br/>Affrettati a cambiarla o dal prossimo login non sari pi&ugrave; abilitato all'utilizzo del portale";
                    $updateSQL = sprintf( "UPDATE `Utenti` SET scadenza_pwd='2007-01-01' WHERE ID_utente=%s",
                        GetSQLValueString( $row_User_tocg[ 'ID_utente' ], "int" ) );
                    $Result1 = mysqli_query( $conn, $updateSQL )or die( mysqli_error( $conn ) );
                }
            }
            ?>
            <?php if (isset($_GET['user'])) {?>
            <p class="testo">Cambia la password di <?php echo $row_User_tocg['Nominativo'] . "</p>"; } else {?>
            <p class="testo">Ciao <?php echo $row_User_tocg['Nominativo']; ?>, inserisci una password personale facile da ricordare ma di almeno 8 caratteri. <br />
                Ad ogni accesso riceverai un messaggio automatico via mail a garanzia delle tue credenziali di accesso.</p>
            <?php }?>
            <div class="container-fluid mt-3">
                <form action="<?php echo $editFormAction; ?>" method="POST" name="chg_pwd">
                    <div class="form-group">
                        <label for="password" class="control-label col-sm-2" >Nuova password:</label>
                        <div class="col-sm-6">
                            <input name="password" type="password" size="15" maxlength="15" class="form-control" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="La password deve contenere almeno un numero una lettera maiuscola e una lettera minuscola. Deve esser lunga almeno 8 caratteri" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password2" class="control-label col-sm-2" >Ripeti la nuova password: </label>
                        <div class="col-sm-6">
                            <input name="password2" type="password" size="15" maxlength="15" class="form-control" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="La password deve contenere almeno un numero una lettera maiuscola e una lettera minuscola. Deve esser lunga almeno 8 caratteri" required />
                        </div>
                    </div>
                    <input type="hidden" name="vecchia_pwd" value="<?php //echo $row_User_tocg['password']; ?>" />
                    <input type="hidden" name="scadenza_pwd" value="<?php echo gmdate('Y-m-d'); ?>" />
                    <input type="hidden" name="ID_utente" value="<?php echo $row_User_tocg['ID_utente'] ?>" />
                    <div class="col-sm-2">
                        <input name="insert" type="submit" id="insert" value="Modifica" class="form-control"  />
                    </div>
                    <input type="hidden" name="MM_update" value="chg_pwd">
                </form>
            </div>
            <?php
mysqli_free_result($User_tocg);
              ?>
            <?php }?>
        </main>
    </div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>
