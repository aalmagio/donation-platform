<?php
/*
 * v 202101051300
 * 
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
// Gestione caratteri spciali -INIO
function utf8ize( $d ) {
    /*if ( is_array( $d ) ) {
        foreach ( $d as $k => $v ) {
            $d[ $k ] = utf8ize( $v );
        }
    } else if ( is_string( $d ) ) {
        return utf8_encode( $d );
    }*/
    return $d;
}
// Gestione caratteri spciali -FINE
$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error( $conn ), E_USER_ERROR );
//mysql_select_db(DB_DBNAME, $Donazioni);
if ( isset( $_REQUEST[ 'CodTrans' ] ) ) {
    $q_donazione = TRUE;
    $query_donazione = sprintf( "SELECT * FROM `Donazione` WHERE CodTrans = %s;", GetSQLValueString( $_REQUEST[ 'CodTrans' ], 'text' ) );
    $singola_donazione = mysqli_query( $conn, $query_donazione )or die( mysqli_error( $conn ) );
    $row_singola_donazione = mysqli_fetch_assoc( $singola_donazione );
    $totalRows_singola_donazione = mysqli_num_rows( $singola_donazione );

    $query_anagrafica = sprintf( "SELECT Anagrafica.*,
    Form.nome_form
    FROM `Anagrafica`
    LEFT JOIN Form
    ON Anagrafica.id_fonte = Form.id_fonte 
    WHERE `Anagrafica`.`Id_a` = %s;", GetSQLValueString( $row_singola_donazione[ 'Id_a' ], 'int' ) );
    $singola_anagrafica = mysqli_query( $conn, $query_anagrafica )or die( mysqli_error( $conn ) );
    $row_singola_anagrafica = mysqli_fetch_assoc( $singola_anagrafica );
    $totalRows_singola_anagrafica = mysqli_num_rows( $singola_anagrafica );

    if ( "regular" == $row_singola_anagrafica[ 'operazione' ] ) {
        $query_mandato = sprintf( "SELECT * FROM `Mandato` WHERE Id_a = %s;", GetSQLValueString( $row_singola_donazione[ 'Id_a' ], 'int' ) );
        $singolo_mandato = mysqli_query( $conn, $query_mandato )or die( mysqli_error( $conn ) );
        $row_singolo_mandato = mysqli_fetch_assoc( $singolo_mandato );
        $totalRows_singolo_mandato = mysqli_num_rows( $singolo_mandato );
        if ( "K" == $row_singolo_mandato[ 'metodo' ] ) {
            $mandato = "CC";
        }
        $mandato = "SDD";
    } else {
        $mandato = "NO";
    }
    if ( "TG" == substr( $row_singola_donazione[ 'CodTrans' ], -2 ) ) {
        $conn_tes = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME_TGIFT )or trigger_error( mysqli_error( $conn_tes ), E_USER_ERROR );
        $query_tesserainregalo = sprintf( "SELECT * FROM `Voucher` WHERE Id_donatore = %s;", GetSQLValueString( $row_singola_donazione[ 'Id_a' ], 'int' ) );
        $singola_tesserainregalo = mysqli_query( $conn_tes, $query_tesserainregalo )or die( mysqli_error( $conn_tes ) );
        $row_singola_tesserainregalo = mysqli_fetch_assoc( $singola_tesserainregalo );
        $totalRows_singola_tesserainregalo = mysqli_num_rows( $singola_tesserainregalo );
    }

    if ( "SDD" != $mandato && "WA" != $row_singola_donazione[ 'esito' ] ) {
        switch ( $row_singola_donazione[ 'pay_method' ] ) {
            case 'PP':
                $pg_table = "PayPalCheckout";
                break;
            case 'CC':
                $pg_table = "GestPayREST";
                break;
            case 'SY':
                $pg_table = "Satispay";
                break;
            default:
                $pg_table = "";
                break;
        }
        if ( isset( $_REQUEST[ 'CodTrans' ] ) ) {
            $q_var = trim( $_REQUEST[ 'CodTrans' ] );
        } else {
            $q_var = $row_singola_donazione[ 'CodTrans' ];
        }
        if ( "GestPayREST" == $pg_table ) {
            $query_data_pg = sprintf( "SELECT * FROM %s WHERE shopTransactionID = '%s';",
                $pg_table,
                trim( $q_var ), 0, 20 );
        } else {
            $query_data_pg = sprintf( "SELECT * FROM %s WHERE CodTrans = '%s';",
                $pg_table,
                trim( $q_var ), 0, 20 );
        }
        if ( "" != $pg_table ) {
            $singola_data_pg = mysqli_query( $conn, $query_data_pg )or die( mysqli_error( $conn ) );
            $row_singola_data_pg = mysqli_fetch_assoc( $singola_data_pg );
            $totalRows_singola_data_pg = mysqli_num_rows( $singola_data_pg );
        }

    }
} elseif ( isset( $_REQUEST[ 'Id_a' ] ) ) {
    $query_anagrafica = sprintf( "SELECT Anagrafica.*,
    Form.nome_form
    FROM `Anagrafica`
    LEFT JOIN Form
    ON Anagrafica.id_fonte = Form.id_fonte 
    WHERE `Anagrafica`.`Id_a` = %s;", GetSQLValueString( $_REQUEST[ 'Id_a' ], 'int' ) );
    $singola_anagrafica = mysqli_query( $conn, $query_anagrafica )or die( mysqli_error( $conn ) );
    $row_singola_anagrafica = mysqli_fetch_assoc( $singola_anagrafica );
    $totalRows_singola_anagrafica = mysqli_num_rows( $singola_anagrafica );
    switch ( $row_singola_anagrafica[ 'operazione' ] ) {
        case 'oneoff': // Singol, Tessera, Tessera in regalo
            $q_donazione = TRUE;
            $q_data_pg = TRUE; //not for WA
            break;
        case 'regular': // Recolare con Carta o SDD
            $q_donazione = TRUE; // NOT FOR SDD
            $q_data_pg = TRUE; // NOT FOR SDD and WA
            break;
        case 'TGIFT': //Donato
            $q_donazione = FALSE;
            $q_data_pg = FALSE; //not for WA
            break;
        default:
            $q_donazione = FALSE;
            $q_data_pg = FALSE;
            break;
    }

    if ( "regular" == $row_singola_anagrafica[ 'operazione' ] ) {
        $query_mandato = sprintf( "SELECT * FROM `Mandato` WHERE Id_a = %s;", GetSQLValueString( $_REQUEST[ 'Id_a' ], 'int' ) );
        $singolo_mandato = mysqli_query( $conn, $query_mandato )or die( mysqli_error( $conn ) );
        $row_singolo_mandato = mysqli_fetch_assoc( $singolo_mandato );
        $totalRows_singolo_mandato = mysqli_num_rows( $singolo_mandato );
        if ( "K" == $row_singolo_mandato[ 'metodo' ] ) {
            $mandato = "CC";
        }
        $mandato = "SDD";
    } else {
        $mandato = "NO";
    }
    if ( $q_donazione == TRUE && "SDD" != $mandato ) {
        $query_donazione = sprintf( "SELECT * FROM `Donazione` WHERE Id_a = %s;", GetSQLValueString( $_REQUEST[ 'Id_a' ], 'int' ) );
        $singola_donazione = mysqli_query( $conn, $query_donazione )or die( mysqli_error( $conn ) );
        $row_singola_donazione = mysqli_fetch_assoc( $singola_donazione );
        $totalRows_singola_donazione = mysqli_num_rows( $singola_donazione );
    }
    if ( "TG" == substr( $row_singola_donazione[ 'CodTrans' ], -2 ) ) {
        $conn_tes = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME_TGIFT )or trigger_error( mysqli_error( $conn_tes ), E_USER_ERROR );
        $query_tesserainregalo = sprintf( "SELECT * FROM `Voucher` WHERE Id_donatore = %s;", GetSQLValueString( $_REQUEST[ 'Id_a' ], 'int' ) );
        $singola_tesserainregalo = mysqli_query( $conn_tes, $query_tesserainregalo )or die( mysqli_error( $conn_tes ) );
        $row_singola_tesserainregalo = mysqli_fetch_assoc( $singola_tesserainregalo );
        $totalRows_singola_tesserainregalo = mysqli_num_rows( $singola_tesserainregalo );
    }


    if ( $q_data_pg == TRUE && "SDD" != $mandato && "WA" != $row_singola_donazione[ 'esito' ] ) {
        switch ( $row_singola_donazione[ 'pay_method' ] ) {
            case 'PP':
                $pg_table = "PayPalCheckout";
                break;
            case 'CC':
                $pg_table = "GestPayREST";
                break;
            case 'SY':
                $pg_table = "Satispay";
                break;
            default:
                $pg_table = "";
                break;
        }
        if ( isset( $_REQUEST[ 'CodTrans' ] ) ) {
            $q_var = trim( $_REQUEST[ 'CodTrans' ] );
        } else {
            $q_var = $row_singola_donazione[ 'CodTrans' ];
        }
        if ( "GestPayREST" == $pg_table ) {
            $query_data_pg = sprintf( "SELECT * FROM %s WHERE shopTransactionID = '%s';",
                $pg_table,
                trim( $q_var ), 0, 20 );
        } else {
            $query_data_pg = sprintf( "SELECT * FROM %s WHERE CodTrans = '%s';",
                $pg_table,
                trim( $q_var ), 0, 20 );
        }
        if ( "" != $pg_table ) {
            $singola_data_pg = mysqli_query( $conn, $query_data_pg )or die( mysqli_error( $conn ) );
            $row_singola_data_pg = mysqli_fetch_assoc( $singola_data_pg );
            $totalRows_singola_data_pg = mysqli_num_rows( $singola_data_pg );
        }

    }
}
?>
<?php require('inc/head.inc.php'); ?>
<body>
<?php require('inc/nav_hor.inc.php'); ?>
<div class="container-fluid">
<!-- MODALE INIZIO-->
<div class="modal fade" id="empModal" role="dialog">
    <div class="modal-dialog"> 
        
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Dati Completi</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body"> </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>
<!-- MODALE FINE-->
<div class="row">
<?php require('inc/nav_ver.inc.php'); ?>
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
<?php if (isset($_SESSION) && "A" == $_SESSION['MM_UserGroup']) { ?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dati Completi</h1>
</div>
<?php if (isset($totalRows_singola_anagrafica) && $totalRows_singola_anagrafica >0) {?>
<!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
<h3>Anagrafica</h3>
<?php
foreach ( $row_singola_anagrafica as $key => $value ) {
    echo "<strong>" . str_replace( "_", " ", $key ) . "</strong>: " . $value . "<br>";
}
if ( isset( $totalRows_singola_donazione ) && $totalRows_singola_donazione > 0 ) {
    ?>
<hr>
<h3>Donazione</h3>
<?php
foreach ( $row_singola_donazione as $key => $value ) {
    echo "<strong>" . str_replace( "_", " ", $key ) . "</strong>: " . $value . "<br>";
}
}
if ( "TG" == substr( $row_singola_donazione[ 'CodTrans' ], -2 ) ) {
    ?>
<hr>
<h3>Tesssera in regalo (Voucher)</h3>
<?php
foreach ( $row_singola_tesserainregalo as $key => $value ) {
    echo "<strong>" . str_replace( "_", " ", $key ) . "</strong>: " . $value . "<br>";
}
}
if ( "regular" == $row_singola_anagrafica[ 'operazione' ] ) {
    ?>
<hr>
<h3>Mandato</h3>
<?php
foreach ( $row_singolo_mandato as $key => $value ) {
    echo "<strong>" . str_replace( "_", " ", $key ) . "</strong>: " . $value . "<br>";
}
}
if ( isset( $totalRows_singola_data_pg ) && $totalRows_singola_data_pg > 0 ) {
    ?>
<hr>
<h3>Dati Payment Gateway (
    <?= $pg_table; ?>
    )</h3>
<?php
foreach ( $row_singola_data_pg as $key => $value ) {
    echo "<strong>" . str_replace( "_", " ", $key ) . "</strong>: " . $value . "<br>";
}
}
} else {
    ?>
<h3>Visualizza i dati completi:</h3>
<p class="text-danger">I campi di ricerca sono ALTERNATIVI</p>
<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="Ricerca_codTrans">
    <div style="clear:both;">
    <fieldset>
        <legend> Cerca per codice di transazione</legend>
        <div class="col-8">
            <label for="CodTrans">Codice Transazione: </label>
            <input type="text" name="CodTrans">
        </div>
    </fieldset>
    <input type="submit" name="button" id="button1" value="Invia"/>
</form>
<hr>
<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="Ricerca_id_a">
    <fieldset>
        <legend>Cerca id donatore (NON ID Mentor)</legend>
        <div class="col-8">
            <label for="Id_a">Id_a: </label>
            <input type="text" name="Id_a">
        </div>
    </fieldset>
    </div>
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
</div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php
if ( isset( $conn ) ) {
    mysqli_close( $conn );
}
if ( isset( $conn_tes ) ) {
    mysqli_close( $conn_tes );
}
?>