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
                <h1 class="h2">Stats</h1>
                <?php
                //$q_ticket = "SELECT `esito`, `valido`, count(*) AS N_ticket FROM Donazione GROUP BY esito, valido;";
                $q_ticket = "SELECT
                  SUM(esito = 'OK')                       AS n_ok,
                  SUM(esito = 'OK' AND valido = 'Y')      AS n_ok_valid,
                  SUM(esito = 'OK' AND valido = 'N')      AS n_ok_invalid,
                  SUM(esito = 'KO')                       AS n_ko,
                  COUNT(*)                                AS n_total
                FROM Donazione
                WHERE causale = '".ID_CAMPAGNA_DEFAULT."';";
                $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );
                $ticket = mysqli_query( $conn, $q_ticket )or die( mysqli_error() );
                $row_ticket = mysqli_fetch_assoc( $ticket );
                $totalRows_ticket = mysqli_num_rows( $ticket );
                
                
                
                /*
                $n_ticket_tot =0;
                $n_ticket_valid =0;
                $n_ticket_notvalid =0;
                $n_transaction_KO =0;
                $n_trasnaction =0;
                
                 do{
                     if($row_ticket['esito']=="OK") {
                        $n_ticket_tot .= $row_ticket['N_ticket'];
                         $n_trasnaction.= $row_ticket['N_ticket'];
                         if($row_ticket['valido']== "Y"){ $n_ticket_valid .= $row_ticket['N_ticket'];}
                         else{$n_ticket_notvalid .= $row_ticket['N_ticket'];}   
                     } elseif($row_ticket['esito']=="KO") {
                         $n_transaction_KO .= $row_ticket['N_ticket'];
                         $n_trasnaction.= $row_ticket['N_ticket'];
                     }
                     
                 } while ($row_ticket = mysqli_fetch_assoc($ticket));
                 
                 */

                /*$q_partner = "SELECT Partner.Nome, Ticket.valido, count(*) AS N_voucher FROM Ticket Left JOIN Partner ON Ticket.Id_partner = Partner.Id_partner GROUP BY Nome, valido;";
                $voucher = mysqli_query( $conn, $q_partner )or die( mysqli_error() );
                $row_voucher = mysqli_fetch_assoc( $voucher );
                $totalRows_voucher = mysqli_num_rows( $voucher );
                */
                $sql = "
                  SELECT
                    p.Nome,
                    COUNT(*)            AS N_voucher,
                    SUM(t.valido = 'Y') AS N_valid
                  FROM Ticket AS t
                  LEFT JOIN Partner AS p
                    ON t.Id_partner = p.Id_partner
                  GROUP BY
                    p.Id_partner, p.Nome
                    ORDER BY  p.Nome;
                ";
                $res = mysqli_query( $conn, $sql ) or die( "Query error: " . mysqli_error( $conn ) );

                

                ?>
            </div>
            <!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>--> 
            <h3>Biglietti</h3>
            <div>Biglietti emessi: <?php echo $row_ticket['n_ok']; ?> </div>
            <div>Biglietti emessi - già strappati: <?php echo $row_ticket['n_ok_invalid']; ?> </div>
            <div>Biglietti emessi - ancora validi: <?php echo $row_ticket['n_ok_valid']; ?> </div>
            <div>Trasnsazione KO: <?php echo $row_ticket['n_ko']; ?>  </div>
            <h3>Partner</h3>
            <?php
            
              while ( $row = mysqli_fetch_assoc( $res ) )  {
                    echo "<div><strong>{$row['Nome']}</strong>: <ul><li>totali=<strong>{$row['N_voucher']}</strong></li><li>validi=<strong>{$row['N_valid']}</strong></li></ul></div>";
                }
            
            ?>
            
        </main>
    </div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>
