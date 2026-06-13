<?php
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
$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );
//mysql_select_db(DB_DBNAME, $Donazioni);
$currentPage = $_SERVER[ "PHP_SELF" ];
$maxRows_pticket = 35;
$pageNum_pticket = 0;
if ( isset( $_GET[ 'pageNum_pticket' ] ) ) {
    $pageNum_pticket = $_GET[ 'pageNum_pticket' ];
}
$startRow_pticket = $pageNum_pticket * $maxRows_pticket;
$query_pticket =  "SELECT Ticket.Id_ticket,  Ticket.nome, Ticket.cognome, Ticket.mail, Ticket.valido, Ticket.telefono, Partner.Nome AS RSPartner FROM `Ticket` LEFT JOIN Partner ON Ticket.Id_partner = Partner.Id_partner WHERE 1";
if (isset($_GET['ricerca']) &&$_GET['ricerca'] =="partner"){
    $query_pticket =  "SELECT Ticket.Id_ticket,  Ticket.nome, Ticket.cognome, Ticket.mail, Ticket.valido, Ticket.telefono, Partner.Nome AS RSPartner FROM `Ticket` LEFT JOIN Partner ON Ticket.Id_partner = Partner.Id_partner WHERE ";
    if ($_GET['partner'] !="ALL"){
        $query_pticket = sprintf( "%s Ticket.Id_partner=%d", $query_pticket, (int) $_GET['partner'] );
    } else {
        $query_pticket = sprintf( "%s %s", $query_pticket, "Ticket.Id_partner > 0" );
    }
    if (trim($_GET['cognome']) !=""){
        $cognome_like = $conn->real_escape_string( $_GET['cognome'] );
        $query_pticket = sprintf( "%s %s", $query_pticket, " AND Ticket.cognome LIKE '%" .$cognome_like."%'"  );
    }
   if (trim($_GET['mail']) !=""){
        $mail_like = $conn->real_escape_string( $_GET['mail'] );
        $query_pticket = sprintf( "%s %s", $query_pticket, " AND Ticket.mail LIKE '%" .$mail_like."%'"  );
    }
}

$query_limit_pticket = sprintf( "%s LIMIT %d, %d", $query_pticket, $startRow_pticket, $maxRows_pticket );
$query_count_pticket = "SELECT count(Ticket.Id_ticket) AS N_righe FROM `Ticket`  WHERE 1";
$pticket = mysqli_query( $conn, $query_limit_pticket )or die( mysqli_error() );
$row_pticket = mysqli_fetch_assoc( $pticket );
$totalRows_pticket = mysqli_num_rows( $pticket );
if ( isset( $_GET[ 'totalRows_pticket' ] ) ) {
    $totalRows_pticket = $_GET[ 'totalRows_pticket' ];
} else {
    $all_pticket = mysqli_query( $conn, $query_count_pticket )or die( mysqli_error() );
    $row_all_pticket = mysqli_fetch_assoc( $all_pticket );
    $totalRows_pticket = $row_all_pticket[ 'N_righe' ];
}
$totalPages_pticket = ceil( $totalRows_pticket / $maxRows_pticket ) - 1;
$queryString_pticket = "";
if ( !empty( $_SERVER[ 'QUERY_STRING' ] ) ) {
    $params = explode( "&", $_SERVER[ 'QUERY_STRING' ] );
    $newParams = array();
    foreach ( $params as $param ) {
        if ( stristr( $param, "pageNum_pticket" ) == false &&
            stristr( $param, "totalRows_pticket" ) == false ) {
            array_push( $newParams, $param );
        }
    }
    if ( count( $newParams ) != 0 ) {
        $queryString_pticket = "&" . htmlentities( implode( "&", $newParams ) );
    }
}
$queryString_pticket = sprintf( "&totalRows_pticket=%d%s", $totalRows_pticket, $queryString_pticket );


$query_partner =  "SELECT * FROM `Partner` WHERE 1";
$partner = mysqli_query( $conn, $query_partner )or die( mysqli_error() );
$row_partner = mysqli_fetch_assoc( $partner );
$totalRows_partner = mysqli_num_rows( $partner );



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
                    <h4 class="modal-title">Biglietto Partner</h4>
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
<div class="container-fluid">
    <div class="row">
        <?php require('inc/nav_ver.inc.php'); ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Verifica Donazioni ONEOFF</h1>
            </div>
            <!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
            <?php
            // Risultato ricerca - INIZO
            if ( isset( $totalRows_pticket ) && $totalRows_pticket >= 1 ) {
                ?>
            <?php echo "Numero righe: " . $totalRows_pticket ."<br>"; ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
    
                            <th scope="col">ID</th>
                            <th scope="col">Nome</th>
                            <th scope="col">Cognome</th>
                            <th scope="col">Mail</th>
                            <th scope="col">Tel</th>
                            <th scope="col">Partner</th>
                            <th scope="col">Ticket</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php do{ ?>
                        <tr <?php if ($row_pticket['valido'] == 'N'){ echo' class="bg-secondary text-white"';}?>>

                            <td <?php if ($row_pticket['valido'] == 'N'){ echo' class="bg-white text-dark"';}?>><?php echo stripslashes($row_pticket['Id_ticket']); ?></td>
                            <td><?php echo stripslashes($row_pticket['nome']); ?></td>
                            <td><?php echo stripslashes($row_pticket['cognome']); ?></td>
                            <td><?php echo stripslashes($row_pticket['mail']); ?></td>
                            <td><?php echo $row_pticket['telefono']; ?></td>
                            <td><?php echo $row_pticket['RSPartner'];?></td>
                            <td><button data-qr='<?php echo $row_pticket['Id_ticket'];?>' class='syinfo-<?php echo $row_pticket['Id_ticket'];?>'>Genera QR</button>
            <script type='text/javascript'>
            $(document).ready(function(){

                $('.syinfo-<?php echo $row_pticket['Id_ticket'];?>').click(function(){
                    
                    var qrid = $(this).data('qr');

                    // AJAX request
                    $.ajax({
                        url: 'generate_qr_partner.php',
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
            </script></td>
                  
                        </tr>
                        <?php } while ($row_pticket = mysqli_fetch_assoc($pticket)); ?>
                        <tr>
                        
                            <td colspan="6">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <tbody>
                                            <tr>
                                                <td style="text-align: center;"><?php if ($pageNum_pticket > 0) { // Show if not first page ?>
                                                        <a href="<?php printf(" %s?pageNum_pticket=%d%s ", $currentPage, max(0, $pageNum_pticket - 1), $queryString_pticket); ?>">indietro</a>
                                                        <?php
                                                    } // Show if not first page 
                                                    else {
                                                        echo "&nbsp;";
                                                    }
                                                    ?></td>
                                                <td style="text-align: center;"><?php if ($pageNum_pticket > 0) { // Show if not first page ?>
                                                        <p><a href="<?php printf(" %s?pageNum_pticket=%d%s ", $currentPage, 0, $queryString_pticket); ?>">inizio</a>
                                                            <?php
                                                        } // Show if not first page 
                                                        else {
                                                            echo "&nbsp;";
                                                        }
                                                        ?></td>
                                                <td style="text-align: center;">&nbsp;</td>
                                                <td style="text-align: center;">pagina <?php echo $pageNum_pticket+1; ?> di <?php echo $totalPages_pticket+1;?></td>
                                                <td style="text-align: center;">&nbsp;</td>
                                                <td style="text-align: center;"><?php if ($pageNum_pticket < $totalPages_pticket) { // Show if not last page ?>
                                                    <a href="<?php printf(" %s?pageNum_pticket=%d%s ", $currentPage, $totalPages_pticket, $queryString_pticket); ?>">fine</a>
                                                    <?php
                                                    } // Show if not last page  
                                                    else {
                                                        echo "&nbsp;";
                                                    }
                                                    ?></td>
                                                <td style="text-align: center;"><?php if ($pageNum_pticket < $totalPages_pticket) { // Show if not last page ?>
                                                        <a href="<?php printf(" %s?pageNum_pticket=%d%s ", $currentPage, min($totalPages_pticket, $pageNum_pticket + 1), $queryString_pticket); ?>">avanti</a>
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
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td><a href="partner_export-xls.php" target="_blank">Scarica tutti i voucher</a>
                                
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <hr>
            <?php
            }
            // Risultato ricerca - FINE
            ?>
            <h3>Cerca partner</h3>
            <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="Ricerca_partner">
                <div style="clear:both;">
                <label for="partner">Partner:</label>
                        <select name="partner">
                            <option value="ALL" selected="selected" >Tutti</option>
                            <?php do { echo "<option value=\"".$row_partner['Id_partner']."\">".$row_partner['Nome']."</option>";
                             } while ($row_partner = mysqli_fetch_assoc($partner)); ?>
                        </select> AND
                <label for "cognome"> Cognome:</label>
                <input type="text" name="cognome"> AND
                <label for "mail"> email:</label>
                <input type="text" name="mail">

                </div>
                <input type="hidden" name="ricerca" value="partner"/>
                <input type="submit" name="button" id="button1" value="cerca"/>
            </form>
        </main>
    </div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>