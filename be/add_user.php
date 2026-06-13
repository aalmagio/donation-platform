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
                if ( ( isset( $_POST[ "MM_insert" ] ) ) && ( $_POST[ "MM_insert" ] == "Adduser" ) ) {
                    if ( isset( $_POST[ 'password' ] ) && $_POST[ 'password' ] === $_POST[ 'password2' ] && strlen( $_POST[ 'password' ] ) >= 8 ) {
                        $colname_user_chk = "-1";
                        if ( isset( $_POST[ 'mail' ] ) ) {
                            $colname_user_chk = $_POST[ 'mail' ] ;
                        }
                        $query_user_chk = sprintf( "SELECT mail FROM Utenti WHERE mail = %s", GetSQLValueString( $colname_user_chk, "text" ) );
                        $user_chk = mysqli_query( $conn, $query_user_chk )or die( mysqli_error( $conn ) );
                        $row_user_chk = mysqli_fetch_assoc( $user_chk );
                        $totalRows_user_chk = mysqli_num_rows( $user_chk );
                        if ( $totalRows_user_chk > 0 ) { // Show if recordset not empty  
                            echo "L'utente esiste già. <br />
  <a href=\"users.php\">Reinserisci i dati</a>
  <p style=\"font-size:9px; color:#FF0000\">";

                            if ( $_SESSION[ 'MM_Username' ] == SUPERADMIN_EMAIL ) {
                                echo "<br />debug list SESSION: <br/>";
                                foreach ( $_SESSION as $key => $value ) {
                                    echo $key . "= " . $_SESSION[ $key ] . "<br/>";
                                }
                            }
                            echo "			</p>
	</div>
	
</div>

	  
</body>
</html>
  
  ";
                            mysqli_free_result( $user_chk );
                            exit;
                        } // Show if recordset not empty 

                        mysqli_free_result( $user_chk );


                        $insertSQL = sprintf( "INSERT INTO `Utenti` ( Nominativo, password, mail, tel, scadenza_pwd, attivo, gruppo) VALUES ( %s, %s, %s, %s, %s, %s, %s)",
                            GetSQLValueString( $_POST[ 'Nominativo' ], "text" ),
                            GetSQLValueString( md5( $_POST[ 'password' ] ), "text" ),
                            GetSQLValueString( $_POST[ 'mail' ], "text" ),
                            GetSQLValueString( $_POST[ 'tel' ], "text" ),
                            GetSQLValueString( $_POST[ 'scadenza_pwd' ], "text" ),
                            GetSQLValueString( $_POST[ 'attivo' ], "text" ),
                            GetSQLValueString( $_POST[ 'gruppo' ], "text" ) );
                        //echo $insertSQL;
                        //mysqli_select_db( $database_su_orvieto, $rda );
                        $Result1 = mysqli_query( $conn, $insertSQL )or die( mysqli_error( $conn ) );

                        $insertGoTo = "users.php";
                        if ( isset( $_SERVER[ 'QUERY_STRING' ] ) ) {
                            $insertGoTo .= ( strpos( $insertGoTo, '?' ) ) ? "&" : "?";
                            $insertGoTo .= $_SERVER[ 'QUERY_STRING' ];
                        }
                        //header(sprintf("Location: %s", $insertGoTo));
                        echo "l'utente " . htmlspecialchars( $_POST[ 'Nominativo' ] ?? '', ENT_QUOTES, 'UTF-8' ) . " (" . htmlspecialchars( $_POST[ 'mail' ] ?? '', ENT_QUOTES, 'UTF-8' ) . ") &egrave; stato creato. <br />
  <a href=\"users.php\">Torna alla gesione utenti</a>
  <p style=\"font-size:9px; color:#FF0000\">";

                        if ( $_SESSION[ 'MM_Username' ] == SUPERADMIN_EMAIL ) {
                            echo "<br />debug list SESSION: <br/>";
                            foreach ( $_SESSION as $key => $value ) {
                                echo $key . "= " . $_SESSION[ $key ] . "<br/>";
                            }
                        }
                        echo "			</p>
	</div>
	
</div>

	  
</body>
</html>
  
  ";
                        exit;
                    } else {
                        echo "Le password digitate non coincidono o sono pi&ugrave; corte di 8 caratteri. <br /> 
  <a href=\"users.php\">Reinserisci i dati</a>
  <p style=\"font-size:9px; color:#FF0000\">";

                        if ( $_SESSION[ 'MM_Username' ] == SUPERADMIN_EMAIL ) {
                            echo "<br />debug list SESSION: <br/>";
                            foreach ( $_SESSION as $key => $value ) {
                                echo $key . "= " . $_SESSION[ $key ] . "<br/>";
                            }
                        }
                        echo "			</p>
	</div>
	
</div>

	  
</body>
</html>
  
  ";
                        exit;
                    }
                }
                

                ?>
            <div class="container-fluid mt-3">
                <form action="<?php echo $editFormAction; ?>" method="post" name="Adduser" class = "form-horizontal" role = "form">
                   
                    <div class="form-group">
                        <label for="Nominativo" class="control-label col-sm-2">Nome: </label>
                        <div class="col-sm-6">
                            <input name="Nominativo" type="text" value="<?php echo $row_load_user['Nominativo']; ?>" size="20" maxlength="150" class="form-control" required/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mail" class="control-label col-sm-2">Mail (utente): </label>
                        <div class="col-sm-6">
                            <input name="mail" type="email" size="20" maxlength="40" class="form-control" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password" class="control-label col-sm-2">Password </label>
                        <div class="col-sm-6">
                            <input name="password" type="password" size="15" class="form-control" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="La password deve contenere almeno un numero una lettera maiuscola e una lettera minuscola. Deve esser lunga almeno 8 caratteri" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password2" class="control-label col-sm-2">Ripeti la password: </label>
                        <div class="col-sm-6">
                            <input name="password2" type="password" size="15" class="form-control" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="La password deve contenere almeno un numero una lettera maiuscola e una lettera minuscola. Deve esser lunga almeno 8 caratteri" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="tel" class="control-label col-sm-2">Telefono: </label>
                        <div class="col-sm-6">
                            <input name="tel" type="text" size="20" maxlength="40" class="form-control" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="gruppo" class="control-label col-sm-2">Livello: </label>
                        <div class="col-sm-6">
                            <select name="gruppo" class="form-control">
                                <option value="A">Amministratore</option>
                                <option value="S">Superutente</option>
                                <option value="U" selected>Utente</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="attivo" class="control-label col-sm-2">Attivo: </label>
                        <div class="col-sm-6">
                            <select name="attivo" class="form-control">
                                <option value="Y">S&igrave;</option>
                                <option value="N">No </option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-2">
                            <input name="insert" type="submit" id="insert" value="Inserisci" class="form-control" />
                        </div>
                    </div>
                    <input type="hidden" name="scadenza_pwd" value="2007-01-01" />
                    <input type="hidden" name="MM_insert" value="Adduser" />
                </form>
            </div>
            <?php }?>
        </main>
    </div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>
