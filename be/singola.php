<?php
/*
 * v 20250426945
 * add Referral
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
        /*    $query_checkCC = sprintf( "SELECT BankTransactionID FROM GestPayREST WHERE shopTransactionID = '%s';",
                trim( $_REQUEST[ 'CodTrans' ] ), 0, 20 );
            $checkCC = mysqli_query( $conn, $query_checkCC )or die( mysqli_error( $conn ) );
            $row_checkCC = mysqli_fetch_assoc( $checkCC );
            $totalRows_checkCC = mysqli_num_rows( $checkCC );*/
        $q_table = "GestPayREST";
    } elseif ( $row_singola_donazione[ 'pay_method' ] == "SY" ) {
        $q_table = "Satispay";
    }
    else {
        $q_table = "PayPalCheckout";
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
                <h1 class="h2">Verifica Donazione</h1>
            </div>
            
            <!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
            <?php //SE Risutano più donazioni con lo stesso codice di transazione  - INIZIO
            if ( isset( $totalRows_donazione ) && $totalRows_donazione >= 1 ) {
                ?>
            <?php echo "Numero righe: " . $totalRows_donazione ."<br>"; ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th scope="col">ID_A</th>
                            <th scope="col">Cod_Trans</th>
                            <th scope="col">Importo</th>
                            <th scope="col">Data</th>
                            <th scope="col">Metodo</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Anagrafica</th>
                            <th scope="col">Referral</th>
                            <?php if ( USE_MENTOR == true ) { ?>
                            <th scope="col">ID Mentor A</th>
                            <th scope="col">Codice Mentor D</th>
                            <th scope="col">Codice Mandato Mentor</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php do{ ?>
                        <tr>
                            <td><?php echo stripslashes($row_donazione['Id_a']); ?></td>
                            <?php if ( USE_MENTOR == true ) { ?>
                            <td><?php if ($row_donazione['ID_Mentor']==""){ ?>
                                <?php echo stripslashes($row_donazione['CodTrans']); ?>
                                <?} else {?>
                                <?php if($row_donazione['tipo'] =="oneoff"){ ?>
                                <a href="index.php?CodTrans=<?php echo stripslashes($row_donazione['CodTrans']); ?>"> <?php echo stripslashes($row_donazione['CodTrans']); ?> </a>
                                <?php } else{?>
                                <a href="index.php?Id_a=<?php echo stripslashes($row_donazione['Id_a']); ?>"> <?php echo stripslashes($row_donazione['CodTrans']); ?> </a>
                                <?php } ?>
                                <?php } ?></td>
                            <?php } else{ ?>
                            <td><?php if($row_donazione['tipo'] =="oneoff"){ ?>
                                <a href="index.php?CodTrans=<?php echo stripslashes($row_donazione['CodTrans']); ?>"> <?php echo stripslashes($row_donazione['CodTrans']); ?> </a>
                                <?php } else{?>
                                <a href="index.php?Id_a=<?php echo stripslashes($row_donazione['Id_a']); ?>"> <?php echo stripslashes($row_donazione['CodTrans']); ?> </a>
                                <?php } ?></td>
                            <?php } ?>
                            <td><?php echo $row_donazione['importo']; ?></td>
                            <td><?php echo stripslashes($row_donazione['data']); ?></td>
                            <td><?php echo $row_donazione['pay_method']; ?></td>
                            <td><?php echo $row_donazione['tipo']; ?></td>
                            <td><?php if ($row_donazione['ragioneSociale']==""){ echo $row_donazione['nome']. " " .$row_donazione['cognome']; } else{ $row_donazione['ragioneSociale']; } ?></td>
                            <td><?php
                            if ( $row_donazione[ 'CodiceReferral' ] == "" ) {
                                echo "NO";
                            } else {
                                $query_referral = sprintf( "SELECT nome, cognome FROM Anagrafica WHERE CodicePersonale = '%s'",
                                    $row_donazione[ 'CodiceReferral' ]
                                );

                                $referral = mysqli_query( $conn, $query_referral )or die( mysqli_error() );
                                $row_referral = mysqli_fetch_assoc( $referral );
                                $totalRows_referral = mysqli_num_rows( $referral );
                                if ( $totalRows_referral == 1 ) {
                                    echo $row_referral[ 'nome' ] . " " . $row_referral[ 'cognome' ];
                                } else {
                                    echo $row_donazione[ 'CodiceReferral' ];
                                }

                            }
                            ?></td>
                            <?php if ( USE_MENTOR == true ) { ?>
                            <td><?php
                            if ( $row_donazione[ 'ID_Mentor' ] == "" && ( $_SESSION[ 'MM_UserGroup' ] == "A" || $_SESSION[ 'MM_UserGroup' ] == "S" ) ) {
                                if ( $row_donazione[ 'tipo' ] == "oneoff" ) {
                                    ?>
                                <a href="index.php?CodTrans=<?php echo stripslashes($row_donazione['CodTrans']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php } else{?>
                                <a href="index.php?Id_a=<?php echo stripslashes($row_donazione['Id_a']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php } ?>
                                <?php
                                } elseif ( $row_donazione[ 'CodiceMentor' ] == "" && $_SESSION[ 'MM_UserGroup' ] == "U" ) {
                                    echo "<span style=\"color:#F00; font-weight: bold;\">Non importata </span>";
                                } else {
                                    echo $row_donazione[ 'ID_Mentor' ];
                                }
                                ?></td>
                            <td><?php if ($row_donazione['CodiceMentor']=="" &&  ($_SESSION['MM_UserGroup'] =="A" ||$_SESSION['MM_UserGroup'] =="S")){ ?>
                                <?php if($row_donazione['tipo'] =="oneoff"){ ?>
                                <a href="index.php?CodTrans=<?php echo stripslashes($row_donazione['CodTrans']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php } else{?>
                                <a href="index.php?Id_a=<?php echo stripslashes($row_donazione['Id_a']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php } ?>
                                <?php
                                } elseif ( $row_donazione[ 'CodiceMentor' ] == "" && $_SESSION[ 'MM_UserGroup' ] == "U" ) {
                                    echo "<span style=\"color:#F00; font-weight: bold;\">Non importata </span>";
                                }
                                else {
                                    echo $row_donazione[ 'CodiceMentor' ];
                                }
                                ?></td>
                            <td><?php
                            if ( $row_donazione[ 'tipo' ] == "regular" ) {
                                if ( $row_donazione[ 'CodiceMandatoMentor' ] != "" ) {
                                    echo $row_donazione[ 'CodiceMandatoMentor' ];
                                } elseif ( $row_donazione[ 'CodiceMandatoMentor' ] == "" && ( $_SESSION[ 'MM_UserGroup' ] == "A" || $_SESSION[ 'MM_UserGroup' ] == "S" ) ) {
                                        ?>
                                <a href="index.php?Id_a=<?php echo stripslashes($row_donazione['Id_a']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php
                                } else {
                                    echo "<span style=\"color:#F00; font-weight: bold;\">Non importata </span>";
                                }
                                } else echo "oneoff no mandato";
                                ?></td>
                            <?php } ?>
                        </tr>
                        <?php } while ($row_donazione = mysqli_fetch_assoc($donazione)); ?>
                        <tr>
                            <td colspan="10"><div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <tbody>
                                            <tr>
                                                <td style="text-align: center;"><?php if ($pageNum_donazione > 0) { // Show if not first page ?>
                                                    <a href="<?php printf(" %s?pageNum_donazione=%d%s ", $currentPage, max(0, $pageNum_donazione - 1), $queryString_donazione); ?>">indietro</a>
                                                    <?php
                                                    } // Show if not first page 
                                                    else {
                                                        echo "&nbsp;";
                                                    }
                                                    ?></td>
                                                <td style="text-align: center;"><?php if ($pageNum_donazione > 0) { // Show if not first page ?>
                                                    <p><a href="<?php printf(" %s?pageNum_donazione=%d%s ", $currentPage, 0, $queryString_donazione); ?>">inizio</a>
                                                        <?php
                                                        } // Show if not first page 
                                                        else {
                                                            echo "&nbsp;";
                                                        }
                                                        ?>
                                                </td>
                                                <td style="text-align: center;">&nbsp;</td>
                                                <td style="text-align: center;">pagina <?php echo $pageNum_donazione+1; ?> di <?php echo $totalPages_donazione+1;?></td>
                                                <td style="text-align: center;">&nbsp;</td>
                                                <td style="text-align: center;"><?php if ($pageNum_donazione < $totalPages_donazione) { // Show if not last page ?>
                                                    <a href="<?php printf(" %s?pageNum_donazione=%d%s ", $currentPage, $totalPages_donazione, $queryString_donazione); ?>">fine</a>
                                                    <?php
                                                    } // Show if not last page  
                                                    else {
                                                        echo "&nbsp;";
                                                    }
                                                    ?></td>
                                                <td style="text-align: center;"><?php if ($pageNum_donazione < $totalPages_donazione) { // Show if not last page ?>
                                                    <a href="<?php printf(" %s?pageNum_donazione=%d%s ", $currentPage, min($totalPages_donazione, $pageNum_donazione + 1), $queryString_donazione); ?>">avanti</a>
                                                    <?php
                                                    } // Show if not last page 
                                                    else {
                                                        echo "&nbsp;";
                                                    }
                                                    ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php
            }
            //SE Risutano più donazioni con lo stesso codice di transazione  - FINE ?>
            <?php if (isset($totalRows_singola_donazione) && $totalRows_singola_donazione >0){?>
            <?php do{?>
            <p>Donazione <?php echo $row_singola_donazione['CodTrans'];?> </p>
            <p>Nome: <?php echo $row_singola_donazione['nome'];?> <?php echo $row_singola_donazione['cognome'];?><br/>
                Indirizzo: <?php echo $row_singola_donazione['indirizzo'];?> <?php echo $row_singola_donazione['civico'];?> - <?php echo $row_singola_donazione['cap'];?> - <?php echo $row_singola_donazione['citta'];?> ( <?php echo $row_singola_donazione['provincia'];?>)<br/>
                mail: <?php echo $row_singola_donazione['mail'];?><br/>
                tel: <?php echo $row_singola_donazione['tel'];?> </p>
            <p>Referral:
                <?php
                if ( $row_singola_donazione[ 'CodiceReferral' ] == "" ) {
                    echo "NO";
                } else {
                    $query_referral = sprintf( "SELECT nome, cognome FROM Anagrafica WHERE CodicePersonale = '%s'",
                        $row_singola_donazione[ 'CodiceReferral' ]
                    );

                    $referral = mysqli_query( $conn, $query_referral )or die( mysqli_error() );
                    $row_referral = mysqli_fetch_assoc( $referral );
                    $totalRows_referral = mysqli_num_rows( $referral );
                    if ( $totalRows_referral == 1 ) {
                        echo $row_referral[ 'nome' ] . " " . $row_referral[ 'cognome' ];
                    } else {
                        echo $row_singola_donazione[ 'CodiceReferral' ];
                    }

                }
                ?>
            </p>
            <p>Data: <?php echo $row_singola_donazione['data'];?><br/>
                IP: <?php echo $row_singola_donazione['IP'];?><br/>
            </p>
            <?php if ( USE_MENTOR == true ) { ?>
            <p><strong>Codice Anagrafica in Mentor</strong>: <?php echo $row_singola_donazione['ID_Mentor'];?>
                <?php
                if ( $row_singola_donazione[ 'ID_Mentor' ] == "" ) {
                    //json Anagrafica - INIZIO
                    if ( $row_singola_donazione[ 'centro' ] == TESSERA_COD ) { //TESSERA X SE
                        if ( $row_singola_donazione[ 'tipotessera' ] == "Junior" ) {
                            $tipotessera = "1";
                        } elseif ( $row_singola_donazione[ 'tipotessera' ] == "Senior" ) {
                            $tipotessera = "3";
                        } else {
                            $tipotessera = "2";
                        }
                        $data_scad_tessera = DATA_SCAD_TESSERA;
                    } else {
                        $tipotessera = "";
                        $data_scad_tessera = "";
                    }
                    $anagrafica_mentor = array(
                        //"codiceWeb"=>$codice_anagrafica,
                        "codiceWeb" => $row_singola_donazione[ 'Id_a' ], //Test
                        "tipo" => $row_singola_donazione[ 'tipo_ana' ], // wsc_table (09 => Donatore, 10 =>Tesserato, 60 => Prospect)
                        //"sottotipo"=>$anagrafica->sottotipo,
                        "nome" => strtoupper( $row_singola_donazione[ 'nome' ] ),
                        "cognome" => strtoupper( $row_singola_donazione[ 'cognome' ] ),
                        "ragioneSociale" => strtoupper( $row_singola_donazione[ 'ragioneSociale' ] ),
                        "genere" => strtoupper( $row_singola_donazione[ 'sesso' ] ),
                        //"dataNascita"=>$row_singola_donazione['datanascita'],
                        //"luogoNascita"=> strtoupper($row_singola_donazione['luogoNascita']),
                        "codiceFiscale" => strtoupper( $row_singola_donazione[ 'codFis' ] ),
                        //"partitaIVA"=>$row_singola_donazione['partitaIVA'],
                        "email1" => strtoupper( $row_singola_donazione[ 'mail' ] ),
                        "cellulare1" => $row_singola_donazione[ 'tel' ],
                        "dug" => "",
                        "duf" => strtoupper( $row_singola_donazione[ 'indirizzo' ] ),
                        "civico" => strtoupper( $row_singola_donazione[ 'civico' ] ),
                        //"altroCivico"=>$anagrafica->altroCivico,
                        //"frazione"=>$anagrafica->frazione,
                        "localita" => strtoupper( $row_singola_donazione[ 'citta' ] ),
                        "provincia" => strtoupper( $row_singola_donazione[ 'provincia' ] ),
                        "cap" => $row_singola_donazione[ 'cap' ],
                        "codiceNazione" => strtoupper( $row_singola_donazione[ 'stato' ] ),
                        "codiceCampagna" => strtoupper( $row_singola_donazione[ 'id_campagna' ] ),
                        "tipoTessera" => $tipotessera,
                        //"codiceTessera"=>$anagrafica-> codiceTessera, 
                        "dataScadenzaTessera" => $data_scad_tessera,
                        //"dataEmissioneTessera"=>$anagrafica-> dataEmissioneTessera,
                        //"flagEmissioneTessera"=>$anagrafica-> flagEmissioneTessera
                    );
                    //$anagrafica_mentor = array_map('utf8_encode', $anagrafica_mentor); //Aggiunto per codifica accentate
                    $data = array(
                        "env" => ID_AMBIENTE,
                        "application" => ID_APP,
                        "operation" => "save",
                        "token" => TOKEN,
                        "user" => MENTOR_USER,
                        "param" => "",
                        "data" => $anagrafica_mentor
                    );
                    $data_string = json_encode( utf8ize( $data ), JSON_UNESCAPED_UNICODE );
                    error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor wsc_save_donor  " . $_SESSION[ 'MM_Username' ] . " chiamata dati " . $data_string . PHP_EOL, 3, LOG_FILE ); //DEBUG
                    $ch = curl_init( MENTOR_API_URL . "/wsc_save_donor.ashx" );
                    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
                    curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
                    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen( $data_string ) ) );
                    $result = json_decode( curl_exec( $ch ), true );
                    error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor wsc_save_donor  " . $_SESSION[ 'MM_Username' ] . " esito " . json_encode( $result ) . PHP_EOL, 3, LOG_FILE ); //DEBUG
                    //$result_ANA = json_decode( curl_exec( $ch ), true );
                    $result_ANA = $result;
                    $noe = explode( ";", $result_ANA[ 'message' ] );
                    $result_ANA[ 'NoE' ] = $noe[ 0 ]; //Anagriafica Nuova o Esistente 
                    error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor Debug 1 privacy result Tipo: " . $result_ANA[ 'NoE' ] . PHP_EOL, 3, LOG_FILE );
                    $result_ANA[ 'SessoMentor' ] = $noe[ 1 ];
                    curl_close( $ch );
                    if ( !$result_ANA ) {
                        echo "Si &egrave; verificato un errore: " . $data_string;
                    }
                    if ( $result_ANA[ 'result' ] == "KO" ) {
                        $codice_mentor_anagrafica = "";
                        echo "<br>" . $anagrafica_message = "Chiamata con errori: " . MENTOR_API_URL . "/wsc_save_donor.ashx<br />Esito:" . $result_ANA[ 'result' ] . "<br>ID:" . $result_ANA[ 'data' ] . "<br>Messaggio:" . $result_ANA[ 'message' ] . "<br>" . $data_string;
                    } else {
                        echo $codice_mentor_anagrafica = $result_ANA[ 'data' ];
                        echo "<br><strong>Tipo Anagrafica</strong>: " . $anagrafica_message = $result_ANA[ 'message' ];
                        echo "<p class='text-muted'>Chiamata Anagrafica:";
                        echo "<br>" . $data_string;
                        echo "<br>" . MENTOR_API_URL . "/wsc_save_donor.ashx";
                        echo "</p>-----------------------<br>";
                    }
                    //json Anagrafica - FINE
                    //json Privacy - INIZIO
                    error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor Debug 2 privacy result wsc_save_donor: " . $result_ANA[ 'result' ] . " Tipo: " . $result_ANA[ 'NoE' ] . PHP_EOL, 3, LOG_FILE ); //DEBUG
                    if ( $result_ANA[ 'result' ] == "OK" ) {
                        $tipoprivacy = "E,G,L,S,T,A,R";
                        $flagprivacy = "1,1,1,1,1,1,1";
                        if ( isset( $result_ANA[ 'NoE' ] ) && $result_ANA[ 'NoE' ] == "nuova" ) {
                            $noteprivacy = "Consenso per donazione online";
                            $tipoprivacy = "E,G,L,S,T,A,R";
                            $flagprivacy = "1,1,1,1,1,1,1";
                        } else {
                            $noteprivacy = "";
                            $tipoprivacy = "";
                            $flagprivacy = "";
                        }
                        if ( isset( $result_ANA[ 'NoE' ] ) && $result_ANA[ 'NoE' ] == "nuova" ) {
                            $privacy_mentor = array(
                                "codiceDonatore" => $result_ANA[ 'data' ],
                                "codicePrivacy" => $tipoprivacy,
                                "attiva" => $flagprivacy,
                                //"dataEntata"=>date("Ymd"),//"20170503",//, 
                                //"dataEntata"=> date("Ymd", strtotime($row_singola_donazione['data'])),//"20170503",//
                                "dataEntata" => date( "Ymd", strtotime( $row_singola_donazione[ 'data_ins' ] ) ), //"20170503",// Per data di inserimento uso quella dell'anagrafica visto che quella della donaizon ecambia in base all'update
                                "dataUscita" => "0",
                                "note" => $noteprivacy
                            );
                            $data = array(
                                "env" => ID_AMBIENTE,
                                "application" => ID_APP,
                                "operation" => "save",
                                "token" => TOKEN,
                                "user" => MENTOR_USER,
                                "param" => "",
                                "data" => $privacy_mentor
                            );
                            $data_string = json_encode( utf8ize( $data ), JSON_UNESCAPED_UNICODE );
                            error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor wsc_save_privacy  " . $_SESSION[ 'MM_Username' ] . " chiamata dati " . $data_string . PHP_EOL, 3, LOG_FILE ); //DEBUG
                            $ch = curl_init( MENTOR_API_URL . "/wsc_save_privacy.ashx" );
                            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
                            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
                            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                            curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                                'Content-Type: application/json',
                                'Content-Length: ' . strlen( $data_string ) ) );
                            error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor wsc_save_privacy  " . $_SESSION[ 'MM_Username' ] . " eisto " . $ch . PHP_EOL, 3, LOG_FILE ); //DEBUG
                            $result_PRIVACY = json_decode( curl_exec( $ch ), true );
                            curl_close( $ch );
                            if ( !$result_PRIVACY ) {
                                echo "Si &egrave; verificato un errore: " . $data_string;
                            }
                            if ( $result[ '$result_PRIVACY' ] == "KO" ) {
                                echo "<br>Chiamata con errore: " . MENTOR_API_URL . "/wsc_save_privacy.ashx<br />Esito:" . $result_PRIVACY[ 'result' ] . "<br />ID:" . $result_PRIVACY[ 'data' ] . "<br />Messaggio:" . $result_PRIVACY[ 'message' ] . "<br />" . $data_string;
                            } else {
                                echo "<br><strong>Privacy scritta in Mentor</strong>: " . $result_PRIVACY[ 'result' ];
                                echo "<p class='text-muted'>Chiamata Privacy";
                                echo "<br>" . $data_string;
                                echo "<br>" . MENTOR_API_URL . "/wsc_save_privacy.ashx";
                                echo "</p>-----------------------<br>";
                            }
                        }
                        // SPECIFICHE
                        $codiceCampo = array( "RIN", "TRIME", "RINCAR", "ATTCAR", "RINMAIL" );
                        if ( isset( $result_ANA[ 'NoE' ] ) && $result_ANA[ 'NoE' ] == "nuova" ) {
                            foreach ( $codiceCampo as $key => $value ) {
                                $specifiche_mentor = array(
                                    "codiceAnagrafica" => $result_ANA[ 'data' ],
                                    "codiceCampo" => $value,
                                    "valore" => "1"
                                );
                                $data = array(
                                    "env" => ID_AMBIENTE,
                                    "application" => ID_APP,
                                    "operation" => "save",
                                    "token" => TOKEN,
                                    "user" => MENTOR_USER,
                                    "param" => "",
                                    "data" => $specifiche_mentor
                                );
                                $data_string = json_encode( utf8ize( $data ), JSON_UNESCAPED_UNICODE );
                                error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor wsc_save_spec  " . $_SESSION[ 'MM_Username' ] . " chiamata dati " . $data_string . PHP_EOL, 3, LOG_FILE ); //DEBUG
                                $ch = curl_init( MENTOR_API_URL . "/wsc_set_spec.ashx" );
                                curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
                                curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
                                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                                curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                                    'Content-Type: application/json',
                                    'Content-Length: ' . strlen( $data_string ) ) );
                                error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor wsc_save_spec  " . $_SESSION[ 'MM_Username' ] . " esito " . $ch . PHP_EOL, 3, LOG_FILE ); //DEBUG
                                $result = json_decode( curl_exec( $ch ), true );
                                curl_close( $ch );
                                $message_specs .= $value . ": " . $result[ 'result' ] . " - ";
                            }
                        } else {
                            $message_specs = "Nessuna specifica scritta";
                        }
                        echo "<br><strong>Specifiche scritte in Mentor</strong>";
                        echo "<br>" . $message_specs;
                        echo "<p class='text-muted'>Chiamata Specifiche";
                        echo "<br>" . MENTOR_API_URL . "/wsc_set_spec.ashx";
                        echo "</p>-----------------------<br>";
                    }
                    //Scrivo in MENTOR - Fine
                    //json Privacy - FINE
                    if ( $result_ANA[ 'result' ] == "OK" ) {
                        //Aggiorno anagrafica in mysql - INIZIO
                        if ( $row_singola_donazione[ 'centro' ] == TESSERA_COD ) { //TESSERA X SE
                            $tipo_ana = "10";
                        } else {
                            $tipo_ana = "09";
                        }
                        $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
                        if ( $connection->connect_errno ) {
                            trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
                        }
                        // preparo lo statement
                        if ( !( $stmt = $connection->prepare( "UPDATE Anagrafica SET ID_Mentor=?, tipo_ana=? WHERE Id_a=?;" ) ) ) {
                            trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
                        }
                        // associo i parametri ai placeholder
                        if ( !$stmt->bind_param( 'ssi', $result_ANA[ 'data' ], $tipo_ana, $row_singola_donazione[ 'Id_a' ] ) ) {
                            trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
                        }
                        // eseguo la query e chiudo
                        if ( !$stmt->execute() ) {
                            trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
                        }
                        $stmt->close();
                        //Aggiorno anagrafica in mysql - Fine
                    }
                }
                }
                ?>
                <br/>
                <?php if ($row_singola_donazione['tipo'] =="regular"){?>
                <?php if ( USE_MENTOR == true ) { ?>
                <strong>Codice Mandato in Mentor</strong> : <?php echo $row_singola_donazione['CodiceMandatoMentor'];?>
                <?php
                if ( ( $result_ANA[ 'result' ] == "OK" || $row_singola_donazione[ 'ID_Mentor' ] != "" ) && $row_singola_donazione[ 'CodiceMandatoMentor' ] == "" ) { // gestione donazione regolare 
                    if ( $result_ANA[ 'result' ] == "OK" ) {
                        $codiceDonatore = $result_ANA[ 'data' ];
                    } else {
                        $codiceDonatore = $row_singola_donazione[ 'ID_Mentor' ];
                    }
                    $mandato_mentor = array(
                        //"codice"=>$mandato->codice,
                        "generaSostegno" => $row_singola_donazione[ 'generaSostegno' ],
                        "codiceDonatore" => $codiceDonatore,
                        "codiceCampagna" => strtoupper( $row_singola_donazione[ 'codiceCampanga' ] ),
                        "codiceCentro" => $row_singola_donazione[ 'codiceCentro' ],
                        "codiceCanale" => $row_singola_donazione[ 'codiceCanale' ],
                        "codiceProgetto" => "", //$row_singola_donazione['codiceProgetto'], 
                        "codiceTema" => "3", //
                        "importo" => number_format( $row_singola_donazione[ 'importo_mandato' ], 2, ',', '' ),
                        "frequenza" => $row_singola_donazione[ 'frequenza' ],
                        "metodo" => $row_singola_donazione[ 'metodo' ],
                        "IBAN" => strtoupper( $row_singola_donazione[ 'IBAN' ] ),

                        "BIC" => strtoupper( $row_singola_donazione[ 'BIC' ] ),
                        "Token" => $row_singola_donazione[ 'Token' ],
                        "meseToken" => $row_singola_donazione[ 'meseToken' ],
                        "annoToken" => "20" . $row_singola_donazione[ 'annoToken' ], //DACAMBIARE DOPO IL 2099
                        "providerIncasso" => $row_singola_donazione[ 'providerIncasso' ],
                        "nomeTitolare" => strtoupper( $row_singola_donazione[ 'nomeTitolare' ] ),
                        "codiceFiscaleTitolare" => strtoupper( $row_singola_donazione[ 'codiceFiscaleTitolare' ] ),
                        "indirizzoTitolare" => strtoupper( $row_singola_donazione[ 'indirizzoTitolare' ] ),
                        "localitaTitolare" => strtoupper( $row_singola_donazione[ 'localitaTitolare' ] ),
                        "provinciaTitolare" => strtoupper( $row_singola_donazione[ 'provinciaTitolare' ] ),
                        "cap" => $row_singola_donazione[ 'cap' ],
                        "urn" => $row_singola_donazione[ 'urn' ],
                        "lotto" => $row_singola_donazione[ 'lotto' ],
                        "note" => $row_singola_donazione[ 'note' ],
                        "codiceDialogatoreEsterno" => $row_singola_donazione[ 'codiceDialogatoreEsterno' ],
                        "nomeDialogatoreEsterno" => $row_singola_donazione[ 'nomeDialogatoreEsterno' ],
                        "locazione" => $row_singola_donazione[ 'locazione' ],
                        "cittaLocazione" => $row_singola_donazione[ 'cittaLocazione' ],
                        "dataRichiesta" => date( "Ymd", strtotime( $row_singola_donazione[ 'data_ins' ] ) ),
                        "dataDelega" => date( "Ymd", strtotime( $row_singola_donazione[ 'data_ins' ] ) ),
                        "dataPromessa" => date( "Ymd", strtotime( $row_singola_donazione[ 'data_ins' ] ) )
                    );
                    $data = array(
                        "env" => ID_AMBIENTE,
                        "application" => ID_APP,
                        "operation" => "save",
                        "token" => TOKEN,
                        "user" => MENTOR_USER,
                        "param" => "",
                        "data" => $mandato_mentor
                    );
                    $data_string = json_encode( utf8ize( $data ), JSON_UNESCAPED_UNICODE );
                    error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor wsc_save_regular  " . $_SESSION[ 'MM_Username' ] . " chiamata dati " . $data_string . PHP_EOL, 3, LOG_FILE ); //DEBUG
                    $ch = curl_init( MENTOR_API_URL . "/wsc_save_regular.ashx" );
                    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
                    curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
                    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen( $data_string ) ) );
                    $string = curl_exec( $ch );
                    $string_mod = str_replace( "\"{", "{", $string ); //rimuove i doppi apici di data
                    $string_mod = str_replace( "}\"", "}", $string_mod ); //rimuove i doppi apici di data
                    $result = json_decode( $string_mod, true );
                    error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor wsc_save_regular  " . $_SESSION[ 'MM_Username' ] . " esito " . $ch . PHP_EOL, 3, LOG_FILE ); //DEBUG
                    curl_close( $ch );
                    if ( $result[ 'result' ] == "KO" ) {
                        echo "Chiamata: " . MENTOR_API_URL . "/wsc_save_regular.ashx<br />Esito: " . $result[ 'result' ] . "<br />ID:" . $result[ 'data' ] . "<br />Messaggio: " . $result[ 'message' ] . "<br />" . $data_string;
                        error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor Chiamata: " . MENTOR_API_URL . "/wsc_save_regular.ashx Esito: " . $result[ 'result' ] . "ID: " . $result[ 'data' ] . " Messaggio: " . $result[ 'message' ] . " " . $data_string . PHP_EOL, 3, LOG_FILE ); //DEBUG
                    } else {
                        echo $codice_mandato = $result[ 'data' ][ 'idRegolare' ];
                    }
                    echo "<p class='text-muted'>Chimata Mandato";
                    echo "<br>" . $data_string;
                    echo "<br>" . MENTOR_API_URL . "/wsc_save_regular.ashx";
                    echo "</p>-----------------------<br>";
                    if ( $result[ 'result' ] == "OK" ) { //Aggiornamento DB e scrittura attività -  INZIO
                        // connetto al db
                        $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
                        if ( $connection->connect_errno ) {
                            trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
                        }
                        // preparo lo statement
                        if ( !( $stmt = $connection->prepare( "UPDATE Mandato SET CodiceMandatoMentor=?, codiceDonatore =?  WHERE Id_mandato=?;" ) ) ) {
                            trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
                        }
                        // associo i parametri ai placeholder
                        if ( !$stmt->bind_param( 'ssi', $codice_mandato, $codiceDonatore, $row_singola_donazione[ 'Id_mandato' ] ) ) {
                            trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
                        }
                        // eseguo la query e chiudo
                        if ( !$stmt->execute() ) {
                            trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
                        }
                        $stmt->close();
                        if ( $row_singola_donazione[ 'esito' ] == "KO" ) {
                            //Scrittura Attività - INIZO
                            $attivita_mentor = array(
                                "codiceDonatore" => $codiceDonatore,
                                "codiceCampagna" => strtoupper( $row_singola_donazione[ 'codiceCampanga' ] ),
                                "idRegolare" => $codice_mandato,
                                "tipo" => "12",
                                "sottotipo" => "1201",
                                //"stato" => "0",
                                "stato" => "2",
                                "dataAttivita" => date( "Ymd" ), //"20170503",//, 
                                "oggetto" => "DR Cca KO/WA web"
                                //"note"=> "Note sull'attività",
                                //"utenteAssegnatario"=> "johndoe",
                                //"gruppoUtentiAssegnatario"=> "Servizio Donatori",        
                            );
                            if ( isset( $nota_attivita ) ) { //Passa l'utetne solo se presente
                                $attivita_mentor[ 'note' ] = $nota_attivita;
                            }
                            if ( isset( $att_utenteAssegnatario ) ) { //Passa l'utetne solo se presente
                                $attivita_mentor[ 'utenteAssegnatario' ] = $att_utenteAssegnatario;
                            }
                            $data = array(
                                "env" => ID_AMBIENTE,
                                "application" => ID_APP,
                                "operation" => "save",
                                "token" => TOKEN,
                                "user" => MENTOR_USER,
                                "param" => "",
                                "data" => $attivita_mentor
                            );
                            $data_string = json_encode( utf8ize( $data ), JSON_UNESCAPED_UNICODE );
                            //$data_string = CleanMyJSON( $data_string );
                            error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor wsc_save_activity " . $_SESSION[ 'MM_Username' ] . " chiamata dati " . $data_string . PHP_EOL, 3, LOG_FILE ); //DEBUG
                            $ch = curl_init( MENTOR_API_URL . "/wsc_save_activity.ashx" );
                            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
                            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
                            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                            curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                                'Content-Type: application/json',
                                'Content-Length: ' . strlen( $data_string ) ) );

                            $result = json_decode( curl_exec( $ch ), true );
                            error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor wsc_save_activity " . $_SESSION[ 'MM_Username' ] . " esito " . $ch . PHP_EOL, 3, LOG_FILE ); //DEBUG
                            curl_close( $ch );
                            /*$string_act = curl_exec($ch);
                            $string_mod_act = str_replace("\"{","{",$string_act); //rimuove i doppi apici di data
                            $string_mod_act = str_replace("}\"","}",$string_mod_act); //rimuove i doppi apici di data
                            $result_act = json_decode($string_mod_act,true);*/

                            if ( $result[ 'result' ] == "KO" ) {
                                echo "Chiamata con errori: " . MENTOR_API_URL . "/wsc_save_activity.ashx<br />Esito: " . $result[ 'result' ] . "<br /><br />Messaggio: " . $result[ 'message' ] . "<br />" . $data_string;
                                error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor Chiamata con errori: " . MENTOR_API_URL . "/wsc_save_activity.ashx Esito: " . $result[ 'result' ] . " Messaggio: " . $result[ 'message' ] . " " . $data_string . PHP_EOL, 3, LOG_FILE );
                            } else {
                                echo "<br>Codice attivit&agrave;: " . $codice_attivita = $result[ 'data' ];
                            }
                            echo "<p class='text-muted'>Chimata Attivit&agrave;";
                            echo "<br>" . $data_string;
                            echo "<br>" . MENTOR_API_URL . "/wsc_save_activity.ashx";
                            echo "</p>-----------------------<br>";
                            //Scrittura Attività - FINE
                        } else {
                            echo "<br><strong>Nessuna Attivit&agrave; scritta (esito donazione: " . $row_singola_donazione[ 'esito' ] . ")</strong><br>";
                        }
                    } //Aggiornamento DB e scrittura attività - FINE
                } //donazione regolare fine?>
                <?php } ?>
                <br/>
                <?php } ?>
                <?php if ( USE_MENTOR == true ) { ?>
                <strong>Codice Donazione in Mentor</strong>: <?php echo $row_singola_donazione['CodiceMentor'];?>
                <?php } ?>
                <?php if ($row_singola_donazione['esito']=="OK"){ ?>
                <?php
                if ( USE_MENTOR == true ) {
                    if ( ( ( isset( $result_ANA[ 'result' ] ) && $result_ANA[ 'result' ] == "OK" ) || $row_singola_donazione[ 'ID_Mentor' ] != "" ) && $row_singola_donazione[ 'CodiceMentor' ] == "" ) {
                        if ( $row_singola_donazione[ 'pay_method' ] == "PP" ) {
                            $MM_metodo = "8";
                        } elseif ( $row_singola_donazione[ 'pay_method' ] == "SY" ) {
                            $MM_metodo = "22";
                        } else {
                            $MM_metodo = "9";
                        }
                        if ( $result_ANA[ 'result' ] == "OK" ) {
                            $codiceDonatore = $result_ANA[ 'data' ];
                        } else {
                            $codiceDonatore = $row_singola_donazione[ 'ID_Mentor' ];
                        }
                        if ( isset( $row_singola_donazione[ 'CodiceMandatoMentor' ] ) && $row_singola_donazione[ 'CodiceMandatoMentor' ] != "" ) {
                            $idRegolare = $row_singola_donazione[ 'CodiceMandatoMentor' ];
                            $MM_metodo = "K";
                        } elseif ( isset( $codice_mandato ) && $codice_mandato != "" ) {
                            $idRegolare = $codice_mandato;
                            $MM_metodo = "K";
                        }
                        else {
                            $idRegolare = "";
                        }
                        $donazione_mentor = array(
                            "idRegolare" => $idRegolare,
                            "codiceDonatore" => $codiceDonatore,
                            "codiceCampagna" => $row_singola_donazione[ 'id_campagna' ],
                            "codiceCentro" => $row_singola_donazione[ 'centro' ],
                            "codiceBambino" => "",
                            "codiceProgetto" => "", //$donazione->,
                            "codiceCanale" => "", // $donazione->,
                            "importo" => number_format( $row_singola_donazione[ 'importo' ], 2, ',', '' ),
                            "metodo" => $MM_metodo, //8 PayPal 5 Carta di credito
                            //"dataOperazione"=> date("Ymd"),//"20170503",//
                            //"dataOperazione"=> date("Ymd", strtotime($row_singola_donazione['data'])),//"20170503",//
                            "dataOperazione" => date( "Ymd", strtotime( $row_singola_donazione[ 'data_ins' ] ) ), //"20170503",// Per data di inserimento uso quella dell'anagrafica visto che quella della donaizon ecambia in base all'update
                            "dataValuta" => "",
                            "codiceTransazione" => $row_singola_donazione[ 'CodTrans' ],
                            "idWeb" => $row_singola_donazione[ 'Id_a' ],
                            //"note"=> $donazione->nota
                        );
                        $data = array(
                            "env" => ID_AMBIENTE,
                            "application" => ID_APP,
                            "operation" => "save",
                            "token" => TOKEN,
                            "user" => MENTOR_USER,
                            "param" => "",
                            "data" => $donazione_mentor
                        );
                        $data_string = json_encode( utf8ize( $data ), JSON_UNESCAPED_UNICODE );
                        error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor wsc_save_donation " . $_SESSION[ 'MM_Username' ] . " chiamata dati " . $data_string . PHP_EOL, 3, LOG_FILE );
                        $ch = curl_init( MENTOR_API_URL . "/wsc_save_donation.ashx" );
                        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
                        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
                        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen( $data_string ) ) );
                        error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor wsc_save_donation " . $_SESSION[ 'MM_Username' ] . " esito " . $ch . PHP_EOL, 3, LOG_FILE );
                        $resultDonazione = json_decode( curl_exec( $ch ), true );
                        if ( !$resultDonazione ) {
                            echo 'Sono avvenuti errori: ' . $data_string;
                        }
                        curl_close( $ch );
                        if ( $resultDonazione[ 'result' ] == "KO" ) {
                            echo "<br>" . "Chiamata cone errori: " . MENTOR_API_URL . "/wsc_save_donation.ashx<br />Esito:" . $resultDonazione[ 'result' ] . "<br />ID:" . $resultDonazione[ 'data' ] . "<br />Messaggio:" . $resultDonazione[ 'message' ] . "<br />" . $data_string;
                            error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor Chiamata cone errori: " . MENTOR_API_URL . "/wsc_save_donation.ashx Esito:" . $resultDonazione[ 'result' ] . " ID:" . $resultDonazione[ 'data' ] . " Messaggio:" . $resultDonazione[ 'message' ] . " " . $data_string . PHP_EOL, 3, LOG_FILE );
                        } else {
                            echo $resultDonazione[ 'data' ];
                        }
                        echo "<p class='text-muted'> Chimata Donazione";
                        echo "<br>" . $data_string;
                        echo "<br>" . MENTOR_API_URL . "/wsc_save_donation.ashx";
                        echo "</p>-----------------------<br>";
                        //Scrivo in MENTOR - Fine
                        //Aggiorno in MYSQL
                        $connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
                        if ( $connection->connect_errno ) {
                            trigger_error( "Connessione al server mySQL fallita: (" . $connection->connect_errno . ") " . $connection->connect_error, E_USER_ERROR );
                        }
                        // preparo lo statement
                        if ( !( $stmt = $connection->prepare( "UPDATE Donazione SET CodiceMentor=? WHERE CodTrans=?;" ) ) ) {
                            trigger_error( "Prepare failed: (" . $connection->errno . ") " . $connection->error, E_USER_ERROR );
                        }
                        // associo i parametri ai placeholder
                        if ( !$stmt->bind_param( 'ss', $resultDonazione[ 'data' ], $row_singola_donazione[ 'CodTrans' ] ) ) {
                            trigger_error( "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
                        }
                        // eseguo la query e chiudo
                        if ( !$stmt->execute() ) {
                            trigger_error( "Execute failed: (" . $stmt->errno . ") " . $stmt->error, E_USER_ERROR );
                        }
                        $stmt->close();
                    }
                }
                ?>
                <?php
                } else {
                    echo "<br><strong>La donazione non ha esito positivo</strong>: " . $row_singola_donazione[ 'esito' ];
                }
                ?>
            </p>
            <p>Metodo:
                <?php
                switch ( $row_singola_donazione[ 'pay_method' ] ) {
                    case "CC":
                        echo "Carta di credito";
                        break;
                    case "PP":
                        echo "Pay Pal";
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
                Esito: <?php echo $row_singola_donazione['esito'];?> <br/>
                Validità ticket: <?php echo $row_singola_donazione['valido'];?> </p>
            <p>Campagna: <?php echo $row_singola_donazione['id_campagna'];?> </p>
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
                <?php if ("0" == $row_tesserainregalo['invio_mail']) { ?>
                Data invio mail:
                <?= $row_tesserainregalo['data_invio_mail'];?>
                <br>
                <?php } ?>
                Invio mail:
                <?php if ("0" == $row_tesserainregalo['invio_mail']) echo "NON "; ?>
                effettuato
                <?php if ( $row_tesserainregalo['invio_mail']>0) echo "[". $row_tesserainregalo['invio_mail']. "]"; ?>
                .<br>
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
            <button data-id_a='<?php echo $row_singola_donazione['Id_a']?>' class='remail'>Rimanda la mail</button>
            <script type='text/javascript'>
            $(document).ready(function(){

                $('.remail').click(function(){
                    
                    var d = $(this).data('id_a');

                    // AJAX request
                    $.ajax({
                        url: 're-mail.php',
                        type: 'get',
                        data: {d: d},
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
            if ( $row_singola_donazione[ 'pay_method' ] == "SY" ) {
                ?>
            <button data-id='<?php echo $row_singola_donazione_ADD['id']?>' class='syveri'>Verifica la transazione su Satispay</button>
            <script type='text/javascript'>
            $(document).ready(function(){

                $('.syveri').click(function(){
                    
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
            <button data-id='<?php echo $row_singola_donazione_ADD['id']?>' class='refund'>Rimborsa la transazione su Satispay</button>
            <script type='text/javascript'>
            $(document).ready(function(){

                $('.refund').click(function(){
                    
                    var syid = $(this).data('id');

                    // AJAX request
                    $.ajax({
                        url: 'refund_satispay.php',
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
            <button data-id='<?php echo $row_singola_donazione_ADD['paymentID']?>' class='ccinfo'>Verifica la transazione su GestPay</button>
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
            <button data-qr='<?php echo $row_singola_donazione['Id_a'];?>' class='syinfo'>Genera QR</button>
            <script type='text/javascript'>
            $(document).ready(function(){

                $('.syinfo').click(function(){
                    
                    var qrid = $(this).data('qr');

                    // AJAX request
                    $.ajax({
                        url: 'generate_qr.php',
                        type: 'post',
                        data: {qrid: qrid},
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
            <?php } while ($row_singola_donazione = mysqli_fetch_assoc($singola_donazione));?>
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