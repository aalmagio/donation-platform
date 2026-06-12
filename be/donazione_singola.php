<?php
/*
 * v 202012221040
 * add modal check pos (GP,SP,PP)
 * add log
 * add query for Satispay
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
    $query_singola_donazione = sprintf( "SELECT Donazione.*,
        Anagrafica.*
        FROM Donazione
        LEFT JOIN Anagrafica
        ON Donazione.Id_a =Anagrafica.Id_a   
        WHERE Donazione.CodTrans = '%s';",
        trim( $_REQUEST[ 'CodTrans' ] ), 0, 20 );
    //echo $query_singola_donazione;
    $singola_donazione = mysqli_query( $conn, $query_singola_donazione )or die( mysqli_error( $conn ) );
    $row_singola_donazione = mysqli_fetch_assoc( $singola_donazione );
    //var_dump($row_singola_donazione);
    $totalRows_singola_donazione = mysqli_num_rows( $singola_donazione );
    //echo $totalRows_singola_donazione;
    if ( $row_singola_donazione[ 'pay_method' ] == "CC" ) {
         $q_table = "GestPayREST" ;
    } elseif ( $row_singola_donazione[ 'pay_method' ] == "SY" ) {
        $q_table = "Satispay";
    }
    else {
        $row_singola_donazione[ 'data' ] >= '2020-06-10' ? $q_table = "PayPalCheckout" : $q_table = "PayPal";
    }
    if ( "GestPayREST" == $q_table ) {
        $query_singola_donazione_ADD = sprintf( "SELECT * FROM %s WHERE shopTransactionID = '%s';",
            $q_table,
            trim( $_REQUEST[ 'CodTrans' ] ), 0, 20 );
    } else {
        $query_singola_donazione_ADD = sprintf( "SELECT * FROM %s WHERE CodTrans = '%s';",
            $q_table,
            trim( $_REQUEST[ 'CodTrans' ] ), 0, 20 );
    }
    $singola_donazione_ADD = mysqli_query( $conn, $query_singola_donazione_ADD )or die( mysqli_error( $conn ) );
    $row_singola_donazione_ADD = mysqli_fetch_assoc( $singola_donazione_ADD );
    $totalRows_singola_donazione_ADD = mysqli_num_rows( $singola_donazione_ADD );
    if ( "TG" == substr( $row_singola_donazione[ 'CodTrans' ], -2 ) ) {
        $conn_tes = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME_TGIFT )or trigger_error( mysqli_error( $conn_tes ), E_USER_ERROR );
        $query_tesserainregalo = sprintf( "SELECT *
        FROM Voucher
        WHERE CodTrans = '%s';",
            $_REQUEST[ 'CodTrans' ] );
        $tesserainregalo = mysqli_query( $conn_tes, $query_tesserainregalo )or die( mysqli_error( $conn ) );
        $row_tesserainregalo = mysqli_fetch_assoc( $tesserainregalo );
        $totalRows_tesserainregalo = mysqli_num_rows( $tesserainregalo );
    }
} elseif ( isset( $_REQUEST[ 'Id_a' ] ) ) {
    $query_singola_donazione = sprintf( "SELECT
    `Anagrafica`.*,
    `Mandato`.`Id_mandato`,
    `Mandato`.`codiceDonatore`,
    `Mandato`.`codiceCampanga`,
    `Mandato`.`codiceCentro`,
    `Mandato`.`codiceCanale`,
    `Mandato`.`codiceProgetto`,
    `Mandato`.`CodiceMandatoMentor`,
    `Mandato`.`codiceFiscaleTitolare`,
    `Mandato`.`indirizzoTitolare`,
    `Mandato`.`nomeTitolare`,
    `Mandato`.`providerIncasso`,
    `Mandato`.`annoToken`,
    `Mandato`.`meseToken`,
    `Mandato`.`Token`,
    `Mandato`.`BIC`,
    `Mandato`.`IBAN`,
    `Mandato`.`metodo`,
    `Mandato`.`frequenza`,
    `Mandato`.`importo` AS `importo_mandato`,
    `Mandato`.`cittaLocazione`,
    `Mandato`.`localitaTitolare`,
    `Mandato`.`provinciaTitolare`,
    `Mandato`.`cap` AS `capTitolare`,
    `Mandato`.`codiceDialogatoreEsterno`,
    `Mandato`.`nomeDialogatoreEsterno`,
    `Mandato`.`urn`,
    `Mandato`.`lotto`,
    `Mandato`.`note`,
    `Mandato`.`locazione`,
    `Mandato`.`Errore`,
    `Mandato`.`generaSostegno`,
    `Donazione`.`CodTrans`,
    `Donazione`.`importo`,
    `Donazione`.`pay_method`,
    `Donazione`.`causale`,
    `Donazione`.`nota`,
    `Donazione`.`tessera`,
    `Donazione`.`tipotessera`,
    `Donazione`.`esito`,
    `Donazione`.`centro`,
    `Donazione`.`data`,
    `Donazione`.`CodiceMentor`,
    `Donazione`.`tipo`
    FROM
    `Anagrafica`
    LEFT JOIN `Donazione` ON `Donazione`.`Id_a` = `Anagrafica`.`Id_a`
    LEFT JOIN `Mandato` ON `Mandato`.`Id_a` = `Donazione`.`Id_a`
    WHERE
    `Anagrafica`.`Id_a` = %s;",
        $_REQUEST[ 'Id_a' ] );
    $singola_donazione = mysqli_query( $conn, $query_singola_donazione )or die( mysqli_error( $conn ) );
    $row_singola_donazione = mysqli_fetch_assoc( $singola_donazione );
    $totalRows_singola_donazione = mysqli_num_rows( $singola_donazione );
    if ( $row_singola_donazione[ 'pay_method' ] == "CC" ) {
        //$q_table = "GestPay";
        $query_checkCC = sprintf( "SELECT BankTransactionID FROM GestPay WHERE CodTrans = '%s';",
            trim( $_REQUEST[ 'CodTrans' ] ), 0, 20 );
        $checkCC = mysqli_query( $conn, $query_checkCC )or die( mysqli_error( $conn ) );
        $row_checkCC = mysqli_fetch_assoc( $checkCC );
        $totalRows_checkCC = mysqli_num_rows( $checkCC );
        $totalRows_checkCC == 0 ? $q_table = "GestPayREST" : $q_table = "GestPay";
    } elseif ( $row_singola_donazione[ 'pay_method' ] == "SY" ) {
        $q_table = "Satispay";
    } else {
        $row_singola_donazione[ 'data' ] >= '2020-06-10' ? $q_table = "PayPalCheckout" : $q_table = "PayPal";
    }
    if ( isset( $_REQUEST[ 'CodTrans' ] ) ) {
        $q_var = trim( $_REQUEST[ 'CodTrans' ] );
    } else {
        $q_var = $row_singola_donazione[ 'CodTrans' ];
    }
    if ( "GestPayREST" == $q_table ) {
        $query_singola_donazione_ADD = sprintf( "SELECT * FROM %s WHERE shopTransactionID = '%s';",
            $q_table,
            trim( $q_var ), 0, 20 );
    } else {
        $query_singola_donazione_ADD = sprintf( "SELECT * FROM %s WHERE CodTrans = '%s';",
            $q_table,
            trim( $q_var ), 0, 20 );
    }
    $singola_donazione_ADD = mysqli_query( $conn, $query_singola_donazione_ADD )or die( mysqli_error( $conn ) );
    $row_singola_donazione_ADD = mysqli_fetch_assoc( $singola_donazione_ADD );
    $totalRows_singola_donazione_ADD = mysqli_num_rows( $singola_donazione_ADD );
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
                    <h4 class="modal-title">Informazione sulla donazione </h4>
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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Transazione NON OK</h1>
            </div>
            
            <!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
            <?php
            if ( isset( $totalRows_singola_donazione ) && $totalRows_singola_donazione == 1 ) {
                ?>
            <p>Donazione <?php echo $row_singola_donazione['CodTrans'];?> </p>
            <p>Nome: <?php echo $row_singola_donazione['nome'];?> <?php echo $row_singola_donazione['cognome'];?><br/>
                Indirizzo: <?php echo $row_singola_donazione['indirizzo'];?> <?php echo $row_singola_donazione['civico'];?> - <?php echo $row_singola_donazione['cap'];?> - <?php echo $row_singola_donazione['citta'];?> ( <?php echo $row_singola_donazione['provincia'];?>)<br/>
                mail: <?php echo $row_singola_donazione['mail'];?><br/>
                tel: <?php echo $row_singola_donazione['tel'];?> </p>
            <p>Data: <?php echo $row_singola_donazione['data'];?><br/>
                IP: <?php echo $row_singola_donazione['IP'];?><br/>
            </p>
            <p><strong>Codice Anagrafica in Mentor</strong>: <?php echo $row_singola_donazione['ID_Mentor'];?> <br/>
                <?php if ($row_singola_donazione['tipo'] =="regular"){?>
                <strong>Codice Mandato in Mentor</strong> : <?php echo $row_singola_donazione['CodiceMandatoMentor'];?> <br/>
                <?php } ?>
                <strong>Codice Donazione in Mentor</strong>: <?php echo $row_singola_donazione['CodiceMentor'];?>
                <?php  echo "<br><strong>L'esito della donazione &egrave;</strong>: " . $row_singola_donazione['esito'];?>
            </p>
            <p>Metodo:
                <?php
                switch ( $row_singola_donazione[ 'pay_method' ] ) {
                    case "CC":
                        echo "Carta di credito";
                        break;
                    case "PP":
                        echo "PayPal";
                        break;
                    case "SD":
                        echo "SDD Banca";
                        break;
                    case "SP":
                        echo "SDD Posta";
                        break;
                    case "SY":
                        echo "Satispay";
                        break;
                }
                ?>
                <br/>
                Tipo: <?php echo $row_singola_donazione['tipo'];?> </p>
            <p>Importo: <?php echo $row_singola_donazione['importo'];?> &euro;
                <?php if ($row_singola_donazione['tipo'] =="regular"){ echo "<br />Importo mandato: " .$row_singola_donazione['importo_mandato'] . "&euro; ogni " . $row_singola_donazione['frequenza'] ." mese/i" ; }?>
                <br/>
                Esito: <?php echo $row_singola_donazione['esito'];?> </p>
            <p>Campagna:<?php echo $row_singola_donazione['id_campagna'];?></p>
            <?php if("TG"== substr($row_singola_donazione['CodTrans'], -2)){// Tessera in regalo - INZIO ?>
            <p><strong>Tessera in regalo</strong><br>
                <em>Destinatario</em><br>
                Nome:
                <?= $row_tesserainregalo['nome_d'];?>
                <br>
                Cognome:
                <?= $row_tesserainregalo['cognome_d'];?>
                <br>
                mail
                <?= $row_tesserainregalo['mail_d'];?>
            </p>
            <p>Esito:
                <?= $row_tesserainregalo['Esito_donazione'];?>
                <?php if ($row_tesserainregalo['Esito_donazione'] != $row_singola_donazione['esito']) {echo "<strong><em>Attenzione, l'esito della donazione (".$row_singola_donazione['esito'].") non &egrave; stato riportato correttmante nella tabella Vaucher</em></strong>"; }  ?>
                <br>
                GUID:
                <?= $row_tesserainregalo['GUID']?>
            </p>
            <p>
                <?php if ("0" == $row_tesserainregalo['inviomail']) { ?>
                Data invio mail:
                <?= $row_tesserainregalo['data_invio_mail'];?>
                <br>
                <?php } ?>
                Invio mail:
                <?php if ("0" == $row_tesserainregalo['inviomail']) echo "NON "; ?>
                effettuato.<br>
                Richiesta tessera:
                <?php if ("0" == $row_tesserainregalo['id_richiesta']) echo "NON "; ?>
                effettuata.<br>
                <?php } // Tessera in regalo - FINE ?>
                <?php if($row_singola_donazione['tipo'] =="regular"){ ?>
            <p><strong>Dati Mandato</strong><br/>
                Codice Donatore: <?php echo $row_singola_donazione['codiceDonatore'];?><br/>
                Codice Campanga: <?php echo $row_singola_donazione['codiceCampanga'];?><br/>
                Codice Centro: <?php echo $row_singola_donazione['codiceCentro'];?><br/>
                Codice Mandato Mentor: <?php echo $row_singola_donazione['CodiceMandatoMentor'];?><br/>
                Codice Fiscale Titolare: <?php echo $row_singola_donazione['codFis'];?><br/>
                Nome Titolare: <?php echo $row_singola_donazione['nomeTitolare'];?><br/>
                <?php if ($row_singola_donazione['metodo'] =="K"){?>
                Token: <?php echo $row_singola_donazione['Token'];?> - <?php echo $row_singola_donazione['meseToken'];?>/ <?php echo $row_singola_donazione['annoToken'];?><br/>
                <?php } else{ ?>
                IBAN: <?php echo $row_singola_donazione['IBAN'];?> (BIC: <?php echo $row_singola_donazione['BIC'];?>)<br/>
                <?php }?>
                Metodo: <?php echo $row_singola_donazione['metodo'];?><br/>
            </p>
            <?php } ?>
            <?php
            if ( $row_singola_donazione[ 'pay_method' ] == "SY" ) {
                ?>
            <button data-id='<?php echo $row_singola_donazione_ADD['id']?>' class='syinfo'>Verifica la transazione su Satispay</button>
            <script type='text/javascript'>
            $(document).ready(function(){

                $('.syinfo').click(function(){
                    
                    var syid = $(this).data('id');

                    // AJAX request
                    $.ajax({
                        url: 'checkpos.php',
                        type: 'post',
                        data: {syid: syid},
                        success: function(response){ 
                            // Add response in Modal body
                            $('.modal-body').html(response); 

                            // Display Modal
                            $('#empModal').modal('show'); 
                        }
                    });
                });
            });
            </script>
            <?php
            } elseif ( $row_singola_donazione[ 'pay_method' ] == "CC" ) {
                    ?>
            <button data-id='<?php echo $row_singola_donazione_ADD['paymentID']?>' class='ccinfo'>Verifica la transazione  su GestPay</button>
            <script type='text/javascript'>
            $(document).ready(function(){

                $('.ccinfo').click(function(){
                    
                    var ccid = $(this).data('id');

                    // AJAX request
                    $.ajax({
                        url: 'checkpos.php',
                        type: 'post',
                        data: {ccid: ccid},
                        success: function(response){ 
                            // Add response in Modal body
                            $('.modal-body').html(response); 

                            // Display Modal
                            $('#empModal').modal('show'); 
                        }
                    });
                });
            });
            </script>
            <?php
            } elseif ( $row_singola_donazione[ 'pay_method' ] == "PP" ) {
                    ?>
            <button data-id='<?php echo $row_singola_donazione_ADD['Id_OrderPayPal']?>' class='ppinfo'>Verifica la transazione su PayPal</button>
            <script type='text/javascript'>
            $(document).ready(function(){

                $('.ppinfo').click(function(){
                    
                    var ppid = $(this).data('id');

                    // AJAX request
                    $.ajax({
                        url: 'checkpos.php',
                        type: 'post',
                        data: {ppid: ppid},
                        success: function(response){ 
                            // Add response in Modal body
                            $('.modal-body').html(response); 

                            // Display Modal
                            $('#empModal').modal('show'); 
                        }
                    });
                });
            });
            </script>
            <?php
            }
            ?>
            <?php if ($row_singola_donazione['pay_method'] =="CC" || $row_singola_donazione['pay_method'] =="PP" || $row_singola_donazione['pay_method'] =="SY" ){?>
            <hr>
            <p><strong>Dati di transazione specifici del POS</strong><br/>
                <?php
                foreach ( $row_singola_donazione_ADD as $key => $value ) {
                    echo $key . ": " . $value . "<br />";
                }
                ?>
            </p>
            <?php } ?>
            <?php }?>
        </main>
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