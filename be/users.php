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
                $currentPage = $_SERVER[ "PHP_SELF" ];

                $maxRows_Recordset1 = 15;
                $pageNum_Recordset1 = 0;
                if ( isset( $_GET[ 'pageNum_Recordset1' ] ) ) {
                    $pageNum_Recordset1 = $_GET[ 'pageNum_Recordset1' ];
                }
                $startRow_Recordset1 = $pageNum_Recordset1 * $maxRows_Recordset1;

                $query_Recordset1 = "SELECT ID_utente, Nominativo, mail, gruppo, attivo FROM Utenti ORDER BY Nominativo ASC";
                $query_limit_Recordset1 = sprintf( "%s LIMIT %d, %d", $query_Recordset1, $startRow_Recordset1, $maxRows_Recordset1 );
                $Recordset1 = mysqli_query( $conn, $query_limit_Recordset1 )or die( mysqli_error( $conn ) );
                $row_Recordset1 = mysqli_fetch_assoc( $Recordset1 );

                if ( isset( $_GET[ 'totalRows_Recordset1' ] ) ) {
                    $totalRows_Recordset1 = $_GET[ 'totalRows_Recordset1' ];
                } else {
                    $all_Recordset1 = mysqli_query( $conn, $query_Recordset1 );
                    $totalRows_Recordset1 = mysqli_num_rows( $all_Recordset1 );
                }
                $totalPages_Recordset1 = ceil( $totalRows_Recordset1 / $maxRows_Recordset1 ) - 1;

                $queryString_Recordset1 = "";
                if ( !empty( $_SERVER[ 'QUERY_STRING' ] ) ) {
                    $params = explode( "&", $_SERVER[ 'QUERY_STRING' ] );
                    $newParams = array();
                    foreach ( $params as $param ) {
                        if ( stristr( $param, "pageNum_Recordset1" ) == false &&
                            stristr( $param, "totalRows_Recordset1" ) == false ) {
                            array_push( $newParams, $param );
                        }
                    }
                    if ( count( $newParams ) != 0 ) {
                        $queryString_Recordset1 = "&" . htmlentities( implode( "&", $newParams ) );
                    }
                }
                $queryString_Recordset1 = sprintf( "&totalRows_Recordset1=%d%s", $totalRows_Recordset1, $queryString_Recordset1 );
                ?>
            <div class="container-fluid mt-3">
                <table class="table table-hover">
                    <caption>
                    Elenco Utenti (<?php echo $totalRows_Recordset1 ?>)
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Nome</th>
                            <th scope="col">Username</th>
                            <th scope="col">Livello</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php do { ?>
                        <tr class="<?php if ("N"==$row_Recordset1['attivo']) {echo 'table-danger';} else{ echo "table-success"; } ?>">
                            <td><a href="mod_user.php?user=<?php echo $row_Recordset1['ID_utente']; ?>"><?php echo $row_Recordset1['Nominativo']; ?></a></td>
                            <td><?php echo $row_Recordset1['mail']; ?></td>
                            <td><?php echo $row_Recordset1['gruppo']; ?></td>
                            <td><!--<a href="del_user.php?ID_utente=<?php echo $row_Recordset1['ID_utente']; ?>">del</a> - --> <a href="change_pwd.php?user=<?php echo $row_Recordset1['mail']; ?>">pwd</a></td>
                        </tr>
                        <?php } while ($row_Recordset1 = mysqli_fetch_assoc($Recordset1)); ?>
                        <tr>
                            <td class="text-center"><?php if ($pageNum_Recordset1 > 0) { // Show if not first page ?>
                                <a href="<?php printf("%s?pageNum_Recordset1=%d%s", $currentPage, max(0, $pageNum_Recordset1 - 1), $queryString_Recordset1); ?>">Indietro</a>
                                <?php } 
			else { echo "&nbsp;";}
			// Show if not first page ?></td>
                            <td class="text-center">&nbsp;</td>
                            <td class="text-center"><?php if ($pageNum_Recordset1 < $totalPages_Recordset1) { // Show if not last page ?>
                                    <a href="<?php printf("%s?pageNum_Recordset1=%d%s", $currentPage, min($totalPages_Recordset1, $pageNum_Recordset1 + 1), $queryString_Recordset1); ?>">Avanti</a>
                                    <?php } // Show if not last page 
		  else { echo "&nbsp;";}?></td>
                            <td>&nbsp;</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php
mysqli_free_result($Recordset1);
?>
            <?php }?>
        </main>
    </div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>
