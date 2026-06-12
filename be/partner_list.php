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
$maxRows_partner = 35;
$pageNum_partner = 0;
if ( isset( $_GET[ 'pageNumpartner' ] ) ) {
    $pageNum_partner = $_GET[ 'pageNum_partner' ];
}
$startRow_partner = $pageNum_partner * $maxRows_partner;
$query_partner =  "SELECT * FROM Partner ORDER BY Nome ASC";
$query_limit_partner = sprintf( "%s LIMIT %d, %d", $query_partner, $startRow_partner, $maxRows_partner );
$query_count_partner = "SELECT count(*) AS N_righe  FROM Partner ORDER BY Nome DESC";
$partner = mysqli_query( $conn, $query_limit_partner )or die( mysqli_error() );
$row_partner = mysqli_fetch_assoc( $partner );
$totalRows_partner = mysqli_num_rows( $partner );
if ( isset( $_GET[ 'totalRows_partner' ] ) ) {
    $totalRows_partner = $_GET[ 'totalRows_partner' ];
} else {
    $all_partner = mysqli_query( $conn, $query_count_partner )or die( mysqli_error() );
    $row_all_partner = mysqli_fetch_assoc( $all_partner );
    $totalRows_partner = $row_all_partner[ 'N_righe' ];
}
$totalPages_partner = ceil( $totalRows_partner / $maxRows_partner ) - 1;
$queryString_partner = "";
if ( !empty( $_SERVER[ 'QUERY_STRING' ] ) ) {
    $params = explode( "&", $_SERVER[ 'QUERY_STRING' ] );
    $newParams = array();
    foreach ( $params as $param ) {
        if ( stristr( $param, "pageNum_partner" ) == false &&
            stristr( $param, "totalRows_partner" ) == false ) {
            array_push( $newParams, $param );
        }
    }
    if ( count( $newParams ) != 0 ) {
        $queryString_partner = "&" . htmlentities( implode( "&", $newParams ) );
    }
}
$queryString_partner = sprintf( "&totalRows_partner=%d%s", $totalRows_partner, $queryString_partner );


?>
<?php require('inc/head.inc.php'); ?>
<body>
<?php require('inc/nav_hor.inc.php'); ?>
<div class="container-fluid">
    <div class="row">
        <?php require('inc/nav_ver.inc.php'); ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Elenco Partner</h1>
            </div>
            <!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
            <?php
            // Risultato ricerca - INIZO
            if ( isset( $totalRows_partner ) && $totalRows_partner >= 1 ) {
                ?>
            <?php echo "Numero righe: " . $totalRows_partner ."<br>"; ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                      
                            <th scope="col">ID</th>
                            <th scope="col">Nome</th>
                            <th scope="col">Mail</th>
                            <th scope="col">Qt</th>
                            <th scope="col">Qtf</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php do{ ?>
                        <tr <?php if ($row_partner['Qt'] == '0'){ echo' class="bg-secondary text-white"';}?> >
                            <td><?php echo stripslashes($row_partner['Id_partner']); ?></td>
                            <td><a href="mod_partner.php?id=<?php echo stripslashes($row_partner['Id_partner']); ?>" style="color: #68191c "> <?php echo stripslashes($row_partner['Nome']); ?> </a></td>
                            <td><?php echo $row_partner['mail']; ?></td>
                            <td><?php echo $row_partner['Qt']; ?></td>
                            <td><?php echo $row_partner['Qtf']; ?></td>
                        </tr>
                        <?php } while ($row_partner = mysqli_fetch_assoc($partner)); ?>
                        <tr>
                         
                            <td colspan="5">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <tbody>
                                            <tr>
                                                <td style="text-align: center;"><?php if ($pageNum_partner > 0) { // Show if not first page ?>
                                                        <a href="<?php printf(" %s?pageNum_partner=%d%s ", $currentPage, max(0, $pageNum_partner - 1), $queryString_partner); ?>">indietro</a>
                                                        <?php
                                                    } // Show if not first page 
                                                    else {
                                                        echo "&nbsp;";
                                                    }
                                                    ?></td>
                                                <td style="text-align: center;"><?php if ($pageNum_partner > 0) { // Show if not first page ?>
                                                        <p><a href="<?php printf(" %s?pageNum_partner=%d%s ", $currentPage, 0, $queryString_partner); ?>">inizio</a>
                                                            <?php
                                                        } // Show if not first page 
                                                        else {
                                                            echo "&nbsp;";
                                                        }
                                                        ?>
                                                </td>
                                                <td style="text-align: center;">&nbsp;</td>
                                                <td style="text-align: center;">pagina <?php echo $pageNum_partner+1; ?> di <?php echo $totalPages_partner+1;?></td>
                                                <td style="text-align: center;">&nbsp;</td>
                                                <td style="text-align: center;"><?php if ($pageNum_partner < $totalPages_partner) { // Show if not last page ?>
                                                    <a href="<?php printf(" %s?pageNum_partner=%d%s ", $currentPage, $totalPages_partner, $queryString_partner); ?>">fine</a>
                                                    <?php
                                                    } // Show if not last page  
                                                    else {
                                                        echo "&nbsp;";
                                                    }
                                                    ?></td>
                                                <td style="text-align: center;"><?php if ($pageNum_partner < $totalPages_partner) { // Show if not last page ?>
                                                        <a href="<?php printf(" %s?pageNum_partner=%d%s ", $currentPage, min($totalPages_partner, $pageNum_partner + 1), $queryString_partner); ?>">avanti</a>
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
            <hr>
            <?php
            }
            // Risultato ricerca - FINE
            ?>
        </main>
    </div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>