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
if ( isset( $_POST[ "MM_update" ] ) && $_POST[ "MM_update" ] == "mod_ana" ) {
    $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );

    $updateSQL = sprintf( "UPDATE `Anagrafica` SET `nome`=%s,`cognome`=%s,`ragioneSociale`=%s,`sesso`=%s,`indirizzo`=%s,`civico`=%s,`cap`=%s,`citta`=%s,`stato`=%s,`tel`=%s,`mail`=%s,`codFis`=%s,`PIVA`=%s WHERE `Id_a`=%s ;",
        GetSQLValueString( strtoupper( $_POST[ 'nome' ] ), "text" ),
        GetSQLValueString( strtoupper( $_POST[ 'cognome' ] ), "text" ),
        GetSQLValueString( strtoupper( $_POST[ 'ragioneSociale' ] ), "text" ),
        GetSQLValueString( strtoupper( $_POST[ 'sesso' ] ), "text" ),
        GetSQLValueString( strtoupper( $_POST[ 'indirizzo' ] ), "text" ),
        GetSQLValueString( strtoupper( $_POST[ 'civico' ] ), "text" ),
        GetSQLValueString( strtoupper( $_POST[ 'cap' ] ), "text" ),
        GetSQLValueString( strtoupper( $_POST[ 'citta' ] ), "text" ),
        GetSQLValueString( strtoupper( $_POST[ 'stato' ] ), "text" ),
        GetSQLValueString( strtoupper( $_POST[ 'tel' ] ), "text" ),
        GetSQLValueString( strtoupper( $_POST[ 'mail' ] ), "text" ),
        GetSQLValueString( strtoupper( $_POST[ 'codFis' ] ), "text" ),
        GetSQLValueString( strtoupper( $_POST[ 'PIVA' ] ), "text" ),

        GetSQLValueString( $_POST[ 'Id_a' ], "int" ) );
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
                <h1 class="h2">Modifica Dati Anagrafica Donatore</h1>
            </div>
            <!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
            <?php

            if ( isset( $totalRows_donazione ) && $totalRows_donazione == 1 ) {
                //print_r( $row_donazione );
                if ( $row_donazione[ 'ID_Mentor' ] == "" ) {
                    ?>
            <div class="container-fluid mt-3">
                <p class="alert alert-warning">Attenzione stai per effettuare la modifica dell'anagrafica della donazione <strong>
                    <?= $row_donazione['CodTrans'] ?>
                    </strong></p>
                <p>Donazioni effettuata da
                    <?= $row_donazione['nome'] . " ". $row_donazione['cognome'] . " (". $row_donazione['importo'] . " &euro;) in data " . $row_donazione['data'];   ?>
                </p>
                <form action="<?php echo $editFormAction; ?>" method="post" name="mod_campaign" class = "form-horizontal" role = "form">
                    <div class="form-group">
                        <?php if ($row_donazione['nome'] !=""){ ?>
                        <label for="nome" class="control-label col-sm-2">Nome</label>
                        <div class="col-sm-6">
                            <input name="nome" type="text" value="<?php echo $row_donazione['nome']; ?>" size="20" maxlength="150" class="form-control" required/>
                        </div>
                        <?php
                        }
                        if ( $row_donazione[ 'cognome' ] != "" ) {
                            ?>
                        <label for="cognome" class="control-label col-sm-2">Cognome</label>
                        <div class="col-sm-6">
                            <input name="cognome" type="text" value="<?php echo $row_donazione['cognome']; ?>" size="20" maxlength="150" class="form-control" required/>
                        </div>
                        <?php
                        }
                        if ( $row_donazione[ 'ragioneSociale' ] != "" ) {
                            ?>
                        <label for="ragioneSociale" class="control-label col-sm-2">Ragione Sociale</label>
                        <div class="col-sm-6">
                            <input name="ragioneSociale" type="text" value="<?php echo $row_donazione['ragioneSociale']; ?>" size="20" maxlength="150" class="form-control" required/>
                        </div>
                        <?php } ?>
                        <label for="indirizzo" class="control-label col-sm-2">Indirizzo</label>
                        <div class="col-sm-6">
                            <input name="indirizzo" type="text" value="<?php echo $row_donazione['indirizzo']; ?>" size="20" maxlength="150" class="form-control" required/>
                        </div>
                        <label for="civico" class="control-label col-sm-2">Civico</label>
                        <div class="col-sm-6">
                            <input name="civico" type="text" value="<?php echo $row_donazione['civico']; ?>" size="20" maxlength="150" class="form-control" required/>
                        </div>
                        <label for="cap" class="control-label col-sm-2">CAP</label>
                        <div class="col-sm-6">
                            <input name="cap" type="text" value="<?php echo $row_donazione['cap']; ?>" size="20" maxlength="150" class="form-control" required/>
                        </div>
                        <label for="citta" class="control-label col-sm-2">Citt&agrave;</label>
                        <div class="col-sm-6">
                            <input name="citta" type="text" value="<?php echo $row_donazione['citta']; ?>" size="20" maxlength="150" class="form-control" required/>
                        </div>
                        <!--<label for="provincia" class="control-label col-sm-2">Provincia</label>
                        <div class="col-sm-6">
                            <input name="provincia" type="text" value="<?php echo $row_donazione['provincia']; ?>" size="20" maxlength="150" class="form-control" required/>
                        </div>-->
                        <label for="tel" class="control-label col-sm-2">Telefono</label>
                        <div class="col-sm-6">
                            <input name="tel" type="text" value="<?php echo $row_donazione['tel']; ?>" size="20" maxlength="150" class="form-control" required/>
                        </div>
                        <label for="mail" class="control-label col-sm-2">E-mail</label>
                        <div class="col-sm-6">
                            <input name="mail" type="text" value="<?php echo $row_donazione['mail']; ?>" size="20" maxlength="150" class="form-control" required/>
                        </div>
                        <label for="codFis" class="control-label col-sm-2">Codice Fiscale</label>
                        <div class="col-sm-6">
                            <input name="codFis" type="text" value="<?php echo $row_donazione['codFis']; ?>" size="20" maxlength="150" class="form-control" />
                        </div>
                        <?php if ($row_donazione['PIVA'] !=""){ ?>
                        <label for="PIVA" class="control-label col-sm-2">Partita IVA</label>
                        <div class="col-sm-6">
                            <input name="PIVA" type="text" value="<?php echo $row_donazione['PIVA']; ?>" size="20" maxlength="150" class="form-control" />
                        </div>
                        <?php } ?>
                        <div class="form-group">
                            <div class="col-sm-2">
                                <input name="insert" type="submit" id="insert" value="Modifica" class="form-control" />
                            </div>
                        </div>
                        <input type="hidden" name="MM_update" value="mod_ana" />
                        <input type="hidden" name="Id_a" value="<?=  $row_donazione['Id_a']; ?>">
                        <input type="hidden" name="sesso" value="<?=  $row_donazione['sesso']; ?>">
                        <input type="hidden" name="stato" value="<?=  $row_donazione['stato']; ?>">
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
                echo "<p class=\"alert alert-success \" role=\"alert\">L'anagrafica del donaore &egrave; stata modificata. </p>";

            }

            if ( !isset( $totalRows_donazione ) || ( isset( $totalRows_donazione ) && $totalRows_donazione != 1 ) ) {
                ?>
            <h3>Ricerca anagrafica</h3>
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