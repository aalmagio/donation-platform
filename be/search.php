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
if ( isset( $_GET[ 'date3' ] ) && isset( $_GET[ 'date4' ] ) ) {
    if ( $_GET[ 'date3' ] > date( "Y-m-d" ) ) {
        echo "<p>La data iniziale del periodo &egrave; nel futuro. Non &egrave; possibile effettuare la query.</p>";
    } else {
        if (isset($_GET['pay_method'])&& "ALL"==$_GET['pay_method'] ){
            $pay_method_query =" IS NOT NULL  ";
        }
        else{
            $pay_method_query =" = '".$_GET['pay_method']."'";   
        }
        
        
        if ( isset( $_GET[ 'tipo_donazioni' ] ) && "OOC" == $_GET[ 'tipo_donazioni' ] ) {
            //echo "<h1>Donazioni One OFF</h1>";
            $h1 = "Donazioni One OFF";
            $currentPage = $_SERVER[ "PHP_SELF" ];
            $maxRows_donazione = 35;
            $pageNum_donazione = 0;
            if ( isset( $_GET[ 'pageNum_donazione' ] ) ) {
                $pageNum_donazione = $_GET[ 'pageNum_donazione' ];
            }
            $startRow_donazione = $pageNum_donazione * $maxRows_donazione;
            if ( isset( $_GET[ 'esito' ] ) && "OK" == $_GET[ 'esito' ] ) {
                if ( isset( $_GET[ 'ID_Mentor' ] ) && "NOTNULL" == $_GET[ 'ID_Mentor' ] ) { //Esito OK e SCRITTE IN MENTOR
                    $query_mentor = "IS NOT NULL";
                    $query_donazione = sprintf( "SELECT Donazione.Id_a, Donazione.CodTrans, Donazione.importo, Donazione.Esito, Donazione.data, Donazione.pay_method, Donazione.tipo, Donazione.CodiceMentor,
                            Anagrafica.ID_Mentor, Anagrafica.nome, Anagrafica.cognome, Anagrafica.ragioneSociale
                            FROM Donazione
                            LEFT JOIN Anagrafica
                            ON Donazione.Id_a = Anagrafica.Id_A
                            WHERE Donazione.Esito ='OK'
                            AND Donazione.tipo  = 'oneoff'
                            AND Anagrafica.ID_Mentor %s
                            AND Donazione.CodiceMentor %s
                            AND  Donazione.Data >= '%s'
                            AND Donazione.Data <= '%s'
                            AND pay_method %s
                            ORDER BY Id_a DESC",
                        $query_mentor,
                        $query_mentor,
                        $_GET[ 'date3' ] . " 00:00:00",
                        $_GET[ 'date4' ] . " 23:59:59",
                        $pay_method_query);
                    $query_limit_donazione = sprintf( "%s LIMIT %d, %d", $query_donazione, $startRow_donazione, $maxRows_donazione );
                    //Query conteggio
                    $query_count_donazione = sprintf( "SELECT count(Donazione.Id_a) AS N_righe
                            FROM Donazione
                            LEFT JOIN Anagrafica
                            ON Donazione.Id_a = Anagrafica.Id_A
                            WHERE Donazione.Esito ='OK'
                            AND Donazione.tipo  = 'oneoff'
                            AND  Donazione.tipo = 'oneoff'
                            AND Anagrafica.ID_Mentor %s
                            AND Donazione.CodiceMentor %s
                            AND  Donazione.Data >= '%s'
                            AND Donazione.Data <= '%s'
                            AND pay_method %s
                            ",
                        $query_mentor,
                        $query_mentor,
                        $_GET[ 'date3' ] . " 00:00:00",
                        $_GET[ 'date4' ] . " 23:59:59",
                        $pay_method_query );
                } else { // NON SCRITTE IN MENTOR
                    $query_mentor = "IS NULL";
                    $query_donazione = sprintf( "SELECT 
                        Donazione.Id_a, Donazione.CodTrans, Donazione.importo, Donazione.Esito, Donazione.data, Donazione.pay_method, Donazione.tipo, Donazione.CodiceMentor,
                        Anagrafica.ID_Mentor, Anagrafica.nome, Anagrafica.cognome, Anagrafica.ragioneSociale
                        FROM Donazione 
                        LEFT JOIN Anagrafica 
                        ON Donazione.Id_a = Anagrafica.Id_A 
                        WHERE Donazione.Esito ='OK'
                        AND  Donazione.tipo = 'oneoff'
                        AND (Anagrafica.ID_Mentor %s
                            OR Donazione.CodiceMentor %s)
                        AND Donazione.Data >= '%s' 
                        AND Donazione.Data <= '%s'  
                        AND pay_method %s
                        ORDER BY Id_a DESC",
                        $query_mentor,
                        $query_mentor,
                        $_GET[ 'date3' ] . " 00:00:00",
                        $_GET[ 'date4' ] . " 23:59:59",
                        $pay_method_query );
                    $query_limit_donazione = sprintf( "%s LIMIT %d, %d", $query_donazione, $startRow_donazione, $maxRows_donazione );
                    //Query conteggio
                    $query_count_donazione = sprintf( "SELECT count(Donazione.Id_a) AS N_righe 
                        FROM Donazione
                        LEFT JOIN Anagrafica
                        ON Donazione.Id_a = Anagrafica.Id_A
                        WHERE Donazione.Esito ='OK'
                        AND  Donazione.tipo = 'oneoff'
                        AND (Anagrafica.ID_Mentor %s
                            OR Donazione.CodiceMentor %s)
                        AND  Donazione.Data >= '%s'
                        AND Donazione.Data <= '%s'
                        AND pay_method %s",
                        $query_mentor,
                        $query_mentor,
                        $_GET[ 'date3' ] . " 00:00:00",
                        $_GET[ 'date4' ] . " 23:59:59",
                        $pay_method_query );
                }
            } else {
                if ( "NOTOK" == $_GET[ 'esito' ] ) {
                    $q_esito = " != 'OK'";
                } else {
                    $q_esito = " = '" . $_GET[ 'esito' ] . "'";
                }
                $query_donazione = sprintf( "SELECT Donazione.Id_a, Donazione.CodTrans, Donazione.importo,  Donazione.Esito, Donazione.data, Donazione.pay_method, Donazione.tipo, Donazione.CodiceMentor,
                            Anagrafica.ID_Mentor, Anagrafica.nome, Anagrafica.cognome, Anagrafica.ragioneSociale
                            FROM Donazione
                            LEFT JOIN Anagrafica
                            ON Donazione.Id_a = Anagrafica.Id_A
                            WHERE Donazione.Esito %s
                            AND Donazione.tipo  = 'oneoff'
                            
                            AND  Anagrafica.data_ins  >= '%s'
                            AND Anagrafica.data_ins  <= '%s'
                            AND pay_method %s
                            ORDER BY Id_a DESC",
                    $q_esito,
                    $_GET[ 'date3' ] . " 00:00:00",
                    $_GET[ 'date4' ] . " 23:59:59",
                    $pay_method_query );
                $query_limit_donazione = sprintf( "%s LIMIT %d, %d", $query_donazione, $startRow_donazione, $maxRows_donazione );
                //Query conteggio
                $query_count_donazione = sprintf( "SELECT count(Donazione.Id_a) AS N_righe
                            FROM Donazione
                            LEFT JOIN Anagrafica
                            ON Donazione.Id_a = Anagrafica.Id_A
                            WHERE Donazione.Esito %s
                            AND Donazione.tipo  = 'oneoff'                           
                            AND  Anagrafica.data_ins  >= '%s'
                            AND Anagrafica.data_ins  <= '%s'
                            AND pay_method %s
                            ",
                    $q_esito,
                    $_GET[ 'date3' ] . " 00:00:00",
                    $_GET[ 'date4' ] . " 23:59:59",
                    $pay_method_query  );

            }

            //echo $query_limit_donazione;
            $donazione = mysqli_query( $conn, $query_limit_donazione )or die( mysqli_error() );
            $row_donazione = mysqli_fetch_assoc( $donazione );
            $totalRows_donazione = mysqli_num_rows( $donazione );
            if ( isset( $_GET[ 'totalRows_donazione' ] ) ) {
                $totalRows_donazione = $_GET[ 'totalRows_donazione' ];
            } else {
                $all_donazione = mysqli_query( $conn, $query_count_donazione )or die( mysqli_error() );
                $row_all_donazione = mysqli_fetch_assoc( $all_donazione );
                $totalRows_donazione = $row_all_donazione[ 'N_righe' ];
            }
            $totalPages_donazione = ceil( $totalRows_donazione / $maxRows_donazione ) - 1;
            $queryString_donazione = "";
            if ( !empty( $_SERVER[ 'QUERY_STRING' ] ) ) {
                $params = explode( "&", $_SERVER[ 'QUERY_STRING' ] );
                $newParams = array();
                foreach ( $params as $param ) {
                    if ( stristr( $param, "pageNum_donazione" ) == false &&
                        stristr( $param, "totalRows_donazione" ) == false ) {
                        array_push( $newParams, $param );
                    }
                }
                if ( count( $newParams ) != 0 ) {
                    $queryString_donazione = "&" . htmlentities( implode( "&", $newParams ) );
                }
            }
            $queryString_donazione = sprintf( "&totalRows_donazione=%d%s", $totalRows_donazione, $queryString_donazione );
        } 
        elseif ( isset( $_GET[ 'tipo_donazioni' ] ) && "RCC" == $_GET[ 'tipo_donazioni' ] ) {
            //echo "<h1>Continuative con Carta di Credito</h1>";
            $h1 = "Continuative con Carta di Credito";
            $currentPage = $_SERVER[ "PHP_SELF" ];
            $maxRows_donazione = 35;
            $pageNum_donazione = 0;
            if ( isset( $_GET[ 'pageNum_donazione' ] ) ) {
                $pageNum_donazione = $_GET[ 'pageNum_donazione' ];
            }
            $startRow_donazione = $pageNum_donazione * $maxRows_donazione;
            if ( isset( $_GET[ 'esito' ] ) && "OK" == $_GET[ 'esito' ] ) {
                if ( isset( $_GET[ 'ID_Mentor' ] ) && "NOTNULL" == $_GET[ 'ID_Mentor' ] ) { //Esito OK e SCRITTE IN MENTOR
                    $query_mentor = "IS NOT NULL";
                    $query_donazione = sprintf( "SELECT Donazione.Id_a, Donazione.CodTrans, Donazione.importo, Donazione.Esito, Donazione.data, Donazione.pay_method, Donazione.tipo, Donazione.CodiceMentor,
                            Anagrafica.ID_Mentor, Anagrafica.nome, Anagrafica.cognome, Anagrafica.ragioneSociale
                            FROM Donazione
                            LEFT JOIN Anagrafica
                            ON Donazione.Id_a = Anagrafica.Id_A
                            WHERE Donazione.Esito ='OK'
                            AND Donazione.tipo  = 'regular'
                            AND Anagrafica.ID_Mentor %s
                            AND Donazione.CodiceMentor %s
                            AND  Donazione.Data >= '%s'
                            AND Donazione.Data <= '%s'
                            
                            ORDER BY Id_a DESC",
                        $query_mentor,
                        $query_mentor,
                        $_GET[ 'date3' ] . " 00:00:00",
                        $_GET[ 'date4' ] . " 23:59:59" );
                    $query_limit_donazione = sprintf( "%s LIMIT %d, %d", $query_donazione, $startRow_donazione, $maxRows_donazione );
                    //Query conteggio
                    $query_count_donazione = sprintf( "SELECT count(Donazione.Id_a) AS N_righe
                            FROM Donazione
                            LEFT JOIN Anagrafica
                            ON Donazione.Id_a = Anagrafica.Id_A
                            WHERE Donazione.Esito ='OK'
                            AND Donazione.tipo  = 'regular'
                            AND Anagrafica.ID_Mentor %s
                            AND Donazione.CodiceMentor %s
                            AND  Donazione.Data >= '%s'
                            AND Donazione.Data <= '%s'
                            ",
                        $query_mentor,
                        $query_mentor,
                        $_GET[ 'date3' ] . " 00:00:00",
                        $_GET[ 'date4' ] . " 23:59:59" );
                } else { // NON SCRITTE IN MENTOR
                    $query_mentor = "IS NULL";
                    $query_donazione = sprintf( "SELECT 
                        Donazione.Id_a, Donazione.CodTrans, Donazione.importo, Donazione.Esito, Donazione.data, Donazione.pay_method, Donazione.tipo, Donazione.CodiceMentor,
                        Anagrafica.ID_Mentor, Anagrafica.nome, Anagrafica.cognome, Anagrafica.ragioneSociale
                        FROM Donazione 
                        LEFT JOIN Anagrafica 
                        ON Donazione.Id_a = Anagrafica.Id_A 
                        WHERE Donazione.Esito ='OK'
                        AND  Donazione.tipo = 'regular'
                        AND (Anagrafica.ID_Mentor %s
                            OR Donazione.CodiceMentor %s)
                        AND Donazione.Data >= '%s' 
                        AND Donazione.Data <= '%s'  
                        ORDER BY Id_a DESC",
                        $query_mentor,
                        $query_mentor,
                        $_GET[ 'date3' ] . " 00:00:00",
                        $_GET[ 'date4' ] . " 23:59:59" );
                    $query_limit_donazione = sprintf( "%s LIMIT %d, %d", $query_donazione, $startRow_donazione, $maxRows_donazione );
                    //Query conteggio
                    $query_count_donazione = sprintf( "SELECT count(Donazione.Id_a) AS N_righe 
                        FROM Donazione
                        LEFT JOIN Anagrafica
                        ON Donazione.Id_a = Anagrafica.Id_A
                        WHERE Donazione.Esito ='OK'
                        AND  Donazione.tipo = 'regular'
                        AND (Anagrafica.ID_Mentor %s
                            OR Donazione.CodiceMentor %s)
                        AND  Donazione.Data >= '%s'
                        AND Donazione.Data <= '%s'",
                        $query_mentor,
                        $query_mentor,
                        $_GET[ 'date3' ] . " 00:00:00",
                        $_GET[ 'date4' ] . " 23:59:59" );
                }
            } else {
                if ( "NOTOK" == $_GET[ 'esito' ] ) {
                    $q_esito = " != 'OK'";
                } else {
                    $q_esito = " = '" . $_GET[ 'esito' ] . "'";
                }
                $query_donazione = sprintf( "SELECT Donazione.Id_a, Donazione.CodTrans, Donazione.importo,  Donazione.Esito, Donazione.data, Donazione.pay_method, Donazione.tipo, Donazione.CodiceMentor,
                            Anagrafica.ID_Mentor, Anagrafica.nome, Anagrafica.cognome, Anagrafica.ragioneSociale
                            FROM Donazione
                            LEFT JOIN Anagrafica
                            ON Donazione.Id_a = Anagrafica.Id_A
                            WHERE Donazione.Esito %s
                            AND Donazione.tipo  = 'regular'
                            
                            AND  Donazione.Data >= '%s'
                            AND Donazione.Data <= '%s'
                            ORDER BY Id_a DESC",
                    $q_esito,
                    $_GET[ 'date3' ] . " 00:00:00",
                    $_GET[ 'date4' ] . " 23:59:59" );
                $query_limit_donazione = sprintf( "%s LIMIT %d, %d", $query_donazione, $startRow_donazione, $maxRows_donazione );
                //Query conteggio
                $query_count_donazione = sprintf( "SELECT count(Donazione.Id_a) AS N_righe
                            FROM Donazione
                            LEFT JOIN Anagrafica
                            ON Donazione.Id_a = Anagrafica.Id_A
                            WHERE Donazione.Esito %s
                            AND Donazione.tipo  = 'regular'                           
                            AND  Donazione.Data >= '%s'
                            AND Donazione.Data <= '%s'
                            ",
                    $q_esito,
                    $_GET[ 'date3' ] . " 00:00:00",
                    $_GET[ 'date4' ] . " 23:59:59" );

            }


            $donazione = mysqli_query( $conn, $query_limit_donazione )or die( mysqli_error() );
            $row_donazione = mysqli_fetch_assoc( $donazione );
            $totalRows_donazione = mysqli_num_rows( $donazione );
            if ( isset( $_GET[ 'totalRows_donazione' ] ) ) {
                $totalRows_donazione = $_GET[ 'totalRows_donazione' ];
            } else {
                $all_donazione = mysqli_query( $conn, $query_count_donazione )or die( mysqli_error() );
                $row_all_donazione = mysqli_fetch_assoc( $all_donazione );
                $totalRows_donazione = $row_all_donazione[ 'N_righe' ];
            }
            $totalPages_donazione = ceil( $totalRows_donazione / $maxRows_donazione ) - 1;
            $queryString_donazione = "";
            if ( !empty( $_SERVER[ 'QUERY_STRING' ] ) ) {
                $params = explode( "&", $_SERVER[ 'QUERY_STRING' ] );
                $newParams = array();
                foreach ( $params as $param ) {
                    if ( stristr( $param, "pageNum_donazione" ) == false &&
                        stristr( $param, "totalRows_donazione" ) == false ) {
                        array_push( $newParams, $param );
                    }
                }
                if ( count( $newParams ) != 0 ) {
                    $queryString_donazione = "&" . htmlentities( implode( "&", $newParams ) );
                }
            }
            $queryString_donazione = sprintf( "&totalRows_donazione=%d%s", $totalRows_donazione, $queryString_donazione );
        }
        elseif ( isset( $_GET[ 'tipo_donazioni' ] ) && "SDD" == $_GET[ 'tipo_donazioni' ] ) {
            //echo "<h1>SDD</h1>";
            $h1 = "Continuative con Carta di Credito";
        }
        else {
            //echo "<h1>Non supportato</h1>";
            $h1 = "Non supportato";
        }
        //echo $query_limit_donazione;
    }
}
?>
<?php require('inc/head.inc.php'); ?>
<body>
<?php require('inc/nav_hor.inc.php'); ?>
<div class="container-fluid">
    <div class="row">
        <?php require('inc/nav_ver.inc.php'); ?>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <?php if (isset($h1))  echo $h1; else echo"Cerca Transazioni NON OK";?>
                </h1>
            </div>
            <!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
            <?php if(isset($totalRows_donazione)&& $totalRows_donazione >=1 ){?>
            <h2>Verifica Donazioni </h2>
            <?php echo "Numero righe: " . $totalRows_donazione ."<br>"; ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <?php if ("A" == $_SESSION['MM_UserGroup'] && "OK" ==$_GET['esito'] ){ ?>
                            <th>Reset</th>
                            <?php } ?>
                            <th scope="col">ID_A</th>
                            <th scope="col">Cod_Trans</th>
                            <th scope="col">Esito</th>
                            <th scope="col">Importo</th>
                            <th scope="col">Data</th>
                            <th scope="col">Metodo</th>
                            <!--<th scope="col">Tipo</th>-->
                            <th scope="col">Anagrafica</th>
                            <?php if("OK" ==$_GET['esito'] && USE_MENTOR == true) {?>
                            <th scope="col">ID Mentor A</th>
                            <th scope="col">Codice Mentor D</th>
                            <?php }?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php do{ ?>
                        <tr>
                            <?php if ("A" == $_SESSION['MM_UserGroup'] && "OK" ==$_GET['esito']){ ?>
                            <td><a href="reset.php?Id_a=<?php echo stripslashes($row_donazione['Id_a']); ?>">reset</a></td>
                            <?php } ?>
                            <td><?php echo stripslashes($row_donazione['Id_a']); ?></td>
                            <td><?php if("regular" == $row_donazione['tipo']){ ?>
                                <a href="donazione_singola.php?Id_a=<?php echo stripslashes($row_donazione['Id_a']); ?>">
                                <?php } else { ?>
                                <a href="donazione_singola.php?CodTrans=<?php echo stripslashes($row_donazione['CodTrans']); ?>">
                                <?php } ?>
                                <?php echo stripslashes($row_donazione['CodTrans']); ?> </a></td>
                            <td><?php echo $row_donazione['Esito']; ?></td>
                            <td>&euro; <?php echo $row_donazione['importo']; ?></td>
                            <td><?php echo stripslashes($row_donazione['data']); ?></td>
                            <td><?php echo $row_donazione['pay_method']; ?></td>
                            <td><?php if ($row_donazione['ragioneSociale']==""){ echo $row_donazione['nome']. " " .$row_donazione['cognome']; } else{ $row_donazione['ragioneSociale']; } ?></td>
                            <?php if ("OK" ==$_GET['esito'] && USE_MENTOR == true){?>
                            <?php if ($row_donazione['ID_Mentor']=="" && $row_donazione['CodiceMentor']==""){?>
                            <td colspan="2" style="text-align: center;"><?php if ($row_donazione['ID_Mentor']=="" &&  ($_SESSION['MM_UserGroup'] =="A" ||$_SESSION['MM_UserGroup'] =="S")){ ?>
                                <a href="donazione_singola.php?CodTrans=<?php echo stripslashes($row_donazione['CodTrans']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php } else { ?>
                                <span style="color:#F00; font-weight: bold;">Non importata </span>
                                <?php } ?></td>
                            <?php } else{ ?>
                            <td><?php if ($row_donazione['ID_Mentor']=="" &&  ($_SESSION['MM_UserGroup'] =="A" ||$_SESSION['MM_UserGroup'] =="S")){ ?>
                                <a href="donazione_singola.php?CodTrans=<?php echo stripslashes($row_donazione['CodTrans']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php } elseif ($row_donazione['ID_Mentor']=="" &&  $_SESSION['MM_UserGroup'] =="U"){ ?>
                                <span style="color:#F00; font-weight: bold;">Non importata </span>
                                <?php } else { echo $row_donazione['ID_Mentor']; }?></td>
                            <td><?php  if ($row_donazione['CodiceMentor']=="" &&  ($_SESSION['MM_UserGroup'] =="A" ||$_SESSION['MM_UserGroup'] =="S")){ ?>
                                <a href="donazione_singola.php?CodTrans=<?php echo stripslashes($row_donazione['CodTrans']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php } elseif ($row_donazione['CodiceMentor']=="" &&  $_SESSION['MM_UserGroup'] =="U"){ ?>
                                <span style="color:#F00; font-weight: bold;">Non importata </span>
                                <?php } else{echo $row_donazione['CodiceMentor']; } ?></td>
                            <?php
                            }
                            }
                            ?>
                        </tr>
                        <?php } while ($row_donazione = mysqli_fetch_assoc($donazione)); ?>
                        <tr>
                            <?php if ($_SESSION['MM_UserGroup'] =="A"){ ?>
                            <td colspan="10"><?php } else{ ?>
                            <td colspan="9"><?php } ?>
                                <div class="table-responsive">
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
            <?php }?>
            <h3>Visualizza le donazioni del periodo:</h3>
            <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="Ricerca_periodo">
                <div style="clear:both;">
                    <p>
                        <label for="tipo_donazioni">Tipo Donazioni :</label>
                        <select name="tipo_donazioni">
                            <option value="OOC" selected="selected">One OFF</option>
                            <!--<option value="SDD">SDD</option>-->
                            <option value="RCC">Regolari con Carta</option>
                        </select>
                    </p>
                    <p>
                        <label for="esito">Esito:</label>
                        <select name="esito">
                            <!--<option value="OK" >OK</option>-->
                            <option value="NOTOK" selected="selected" >&ne; OK</option>
                            <option value="KO">KO</option>
                            <option value="WA">WA</option>
                            <option value="RF">RF</option>
                        </select>
                    </p>
                      <p>
                        <label for="pay_method">Metodo:</label>
                        <select name="pay_method">
                            <option value="ALL" selected="selected" >Tutti</option>
                            <?php if ( USE_GESTPAY == true || USE_STRIPE == true ) { ?><option value="CC">Carta di credito</option><?php } ?>
                            <?php if ( USE_PAYPAL == true ) {?><option value="PP">PayPal</option><?php } ?>
                            <?php if ( USE_SATISPAY == true ) {?> <option value="SY">Satispay</option><?php } ?>
                        </select> (Funziona solo per One OFF)
                    </p>
                </div>
                <p>Scegli le date: </p>
                <?php
                $date3_default = date( 'Y-m-01' );
                $date4_default = date( 'Y-m-d' );
                $val_date3 = isset( $_GET['date3'] ) ? htmlspecialchars( $_GET['date3'] ) : $date3_default;
                $val_date4 = isset( $_GET['date4'] ) ? htmlspecialchars( $_GET['date4'] ) : $date4_default;
                ?>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
                <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
                <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>
                <div class="form-row align-items-center mb-3">
                    <div class="col-auto">
                        <label class="font-weight-bold mr-1">Da:</label>
                        <input type="text" id="date3" name="date3" class="form-control d-inline-block"
                               style="width:150px" value="<?= $val_date3 ?>" readonly>
                    </div>
                    <div class="col-auto">
                        <label class="font-weight-bold mr-1">A:</label>
                        <input type="text" id="date4" name="date4" class="form-control d-inline-block"
                               style="width:150px" value="<?= $val_date4 ?>" readonly>
                    </div>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var fp1 = flatpickr("#date3", {
                        dateFormat: "Y-m-d", locale: "it",
                        minDate: "2010-01-01", maxDate: "today",
                        onChange: function(s, str) { if (str) fp2.set('minDate', str); }
                    });
                    var fp2 = flatpickr("#date4", {
                        dateFormat: "Y-m-d", locale: "it",
                        minDate: "2010-01-01", maxDate: "today",
                        onChange: function(s, str) { if (str) fp1.set('maxDate', str); }
                    });
                });
                </script>
           
                <br/>
                <div style="clear:both;">
                    <p> <!--<label for="max_rows">Scrtittura in Menrtor (SOLO OK):</label>
                <select name="ID_Mentor">
                    <option value="NULL" selected="selected">No</option>
                    <option value="NOTNULL">SI</option>
                </select>--> 
                    </p>
                </div>
                <input type="hidden" name="ricerca" value="periodo"/>
                <input type="submit" name="button" id="button1" value="Invia"/>
            </form>
        </main>
    </div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>
