<?php
/*
 * V 20211227
 * First version
 */
if ( !isset( $_SESSION ) ) {
    session_start();
}
require '../inc/config.inc.php';
require '../inc/data.inc.php';
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

// Gestione caratteri spciali -INIO
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
if ( isset( $_GET[ 'ricerca' ] ) && "transazioni" == $_GET[ 'ricerca' ] ) {
    $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );

    $query_donazione = sprintf( "SELECT Donazione.Id_a, Donazione.CodTrans, Donazione.importo, Donazione.data, Donazione.pay_method, Donazione.tipo, Donazione.CodiceMentor, Donazione.esito,
                Anagrafica.*
                FROM Donazione
                LEFT JOIN Anagrafica
                ON Donazione.Id_a = Anagrafica.Id_A
                WHERE Donazione.CodTrans =
                %s
                ORDER BY Anagrafica.Id_a DESC",
        GetSQLValueString( $_GET[ 'CodTrans' ], "text" ) );
    $donazione = mysqli_query( $conn, $query_donazione )or die( mysqli_error( $conn ) );
    $row_donazione = mysqli_fetch_assoc( $donazione );
    $totalRows_donazione = mysqli_num_rows( $donazione );


}
if ( isset( $_POST[ "MM_update" ] ) && $_POST[ "MM_update" ] == "mod_campaign" ) {
    $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );

    $updateSQL = sprintf( "UPDATE `Anagrafica` SET `id_campagna` = %s WHERE `Anagrafica`.`Id_a` = %s AND `Anagrafica`.`Id_campagna` = %s ;",
        GetSQLValueString( strtoupper( $_POST[ 'id_campagna' ] ), "text" ),
        GetSQLValueString( $_POST[ 'Id_a' ], "int" ),
        GetSQLValueString( $_POST[ 'old_id_campagna' ], "text" ) );
    $Result1 = mysqli_query( $conn, $updateSQL )or die( mysqli_error( $conn ) );
}
?>
<?php require('inc/head.inc.php'); ?>
<body>
<?php
require( 'inc/nav_hor.inc.php' );
$editFormAction = $_SERVER[ 'PHP_SELF' ];
?>
<div class="container-fluid">
    <div class="row">
        <?php require('inc/nav_ver.inc.php'); ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <?php if (isset($_SESSION) && "A" == $_SESSION['MM_UserGroup']) { ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Modifica Campagna</h1>
            </div>
            <!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
            <?php

            if ( isset( $totalRows_donazione ) && $totalRows_donazione == 1 ) {
                //print_r( $row_donazione );
                if ( $row_donazione[ 'ID_Mentor' ] == "" ) {
                    ?>
            <div class="container-fluid mt-3">
                <p class="alert alert-warning">Attenzione stai per effettuare la modifica della transazione <strong>
                    <?= $row_donazione['CodTrans'] ?>
                    </strong></p>
                <p>Donazioni effettuata da
                    <?= $row_donazione['nome'] . " ". $row_donazione['cognome'] . " (". $row_donazione['importo'] . " &euro;) in data " . $row_donazione['data'];   ?>
                </p>
                <form action="<?php echo $editFormAction; ?>" method="post" name="mod_campaign" class = "form-horizontal" role = "form">
                    <div class="form-group">
                        <label for="id_campagna" class="control-label col-sm-2">Codice campagna: </label>
                        <div class="col-sm-6">
                            <input name="id_campagna" type="text" value="<?php echo $row_donazione['id_campagna']; ?>" size="20" maxlength="150" class="form-control" required/>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-2">
                                <input name="insert" type="submit" id="insert" value="Modifica" class="form-control" />
                            </div>
                        </div>
                        <input type="hidden" name="MM_update" value="mod_campaign" />
                        <input type="hidden" name="Id_a" value="<?=  $row_donazione['Id_a']; ?>">
                        <input type="hidden" name="old_id_campagna" value="<?=  $row_donazione['id_campagna']; ?>">
                    </div>
                </form>
            </div>
            <hr>
            <?php
            } else {
                echo "<p class=\"alert alert-danger\" role=\"alert\">L'anagrafica &egrave gi&agrave; scritta in Mentor, non è possibile modificare la campagna</p>";
            }
            } elseif ( isset( $totalRows_donazione ) && $totalRows_donazione > 1 ) { // Diverso da 1
                echo "<p class=\"alert alert-danger\" role=\"alert\">Impossibile procedere perch&eacute; ci sono " . $totalRows_donazione . " donazioni con lo stesso codice di transazione</p>";
            }
            else {
                if ( isset( $donazione ) ) {
                    echo "<p class=\"alert alert-danger\" role=\"alert\">La ricerca non ha dato risultati</p>";
                }
            }

            if ( isset( $Result1 ) ) {
                echo "<p class=\"alert alert-success \" role=\"alert\">La campagna della donazione &egrave; stata modificata. </p>";

            }

            if ( !isset( $totalRows_donazione ) || ( isset( $totalRows_donazione ) && $totalRows_donazione != 1 ) ) {
                ?>
            <h3>Ricerca donazione</h3>
            <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="Ricerca_donazione">
                <div style="clear:both;">
                    <fieldset>
                        <legend>Cerca per codice transazione: </legend>
                        <div class="col-8">
                            <label for="CodTrans">Codice Transazione: </label>
                            <input type="text" name="CodTrans">
                        </div>
                    </fieldset>
                </div>
                <input type="hidden" name="ricerca" value="transazioni"/>
                <input type="submit" name="button" id="button1" value="Invia"/>
            </form>
            <?php
            }
            } else {
                ?>
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Accesso non autorizzato</h1>
            </div>
            <?php
            }
            ?>
        </main>
    </div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>