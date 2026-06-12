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
//CALENDARIO -INIZIO

$hl = ( isset( $_GET[ "hl" ] ) ) ? $_GET[ "hl" ] : false;
/*if ( !defined( "L_LANG" ) || L_LANG == "L_LANG" ) {
    if ( $hl )define( "L_LANG", $hl );
    // You need to tell the class which language do you use.
    // L_LANG should be defined as en_US format!!! Next line is an example, just put your own language from the provided list
    else define( "L_LANG", "it_IT" );
}
// START OF: Needed for the manual Language selector - not needed if you pass the LANG from your own script
$langs = 'calendar/lang/';
$langfiles = opendir( $langs ); #open directory
$i = 0;
while ( false !== ( $langfile = readdir( $langfiles ) ) ) {
    if ( !stristr( $langfile, "html" ) && !stristr( $langfile, "localization" ) && !stristr( $langfile, "xx_YY" ) && $langfile !== '.' && $langfile !== '..' ) {
        $hl = str_replace( "calendar.", "", $langfile );
        $hl = str_replace( ".", "", $hl );
        $hl = str_replace( "php", "", $hl );
        $langsfile[] = $hl;
        $i++;
    }
}
if ( $langsfile ) {
    array_push( $langsfile, "it_IT" );
    natsort( $langsfile );
}
closedir( $langfiles );
*/
// END OF: Needed for the manual Language selector - not needed if you pass the LANG from your own script
//CALENDARIO -FINE
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
        $currentPage = $_SERVER[ "PHP_SELF" ];
        $maxRows_donazione = 35;
        $pageNum_donazione = 0;
        if ( isset( $_GET[ 'pageNum_donazione' ] ) ) {
            $pageNum_donazione = $_GET[ 'pageNum_donazione' ];
        }
        $startRow_donazione = $pageNum_donazione * $maxRows_donazione;
        if ( isset( $_GET[ 'ID_Mentor' ] ) && $_GET[ 'ID_Mentor' ] == "NOTNULL" ) {
            $query_mentor = "IS NOT NULL";
            $query_donazione = sprintf( "SELECT Donazione.Id_a, Donazione.CodTrans, Donazione.esito, Donazione.importo, Donazione.data, Donazione.pay_method, Donazione.tipo, Donazione.CodiceMentor,
                Anagrafica.ID_Mentor, Anagrafica.nome, Anagrafica.cognome, Anagrafica.ragioneSociale, Mandato.CodiceMandatoMentor 
                FROM Donazione
                LEFT JOIN Anagrafica
                ON Donazione.Id_a = Anagrafica.Id_A 
                LEFT JOIN Mandato 
                ON Mandato.Id_a = Anagrafica.Id_A
                WHERE Donazione.tipo = 'regular'
                AND Anagrafica.ID_Mentor %s
                AND Mandato.CodiceMandatoMentor %s
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
                LEFT JOIN Mandato 
                ON Mandato.Id_a = Anagrafica.Id_A
                ON Donazione.Id_a = Anagrafica.Id_A
                WHERE Donazione.tipo = 'regular'
                AND Anagrafica.ID_Mentor %s
                AND Mandato.CodiceMandatoMentor %s
                AND  Donazione.Data >= '%s'
                AND Donazione.Data <= '%s'
                ",
                $query_mentor,
                $query_mentor,
                $_GET[ 'date3' ] . " 00:00:00",
                $_GET[ 'date4' ] . " 23:59:59" );
        } else {
            $query_mentor = "IS NULL";
            $query_donazione = sprintf( "SELECT 
                Donazione.Id_a, Donazione.CodTrans, Donazione.esito, Donazione.importo, Donazione.data, Donazione.pay_method, Donazione.tipo, Donazione.CodiceMentor, 
                Anagrafica.ID_Mentor, Anagrafica.nome, Anagrafica.cognome, Anagrafica.ragioneSociale, 
                Mandato.CodiceMandatoMentor 
                FROM Donazione 
                LEFT JOIN Anagrafica 
                ON Donazione.Id_a = Anagrafica.Id_A 
                LEFT JOIN Mandato 
                ON Mandato.Id_a = Anagrafica.Id_A 
                WHERE Donazione.tipo = 'regular'
                AND Donazione.pay_method ='CC' 
                AND Mandato.Token <>''
                AND (Anagrafica.ID_Mentor  %s 
                    OR (Donazione.CodiceMentor  %s 
                    AND Donazione.esito ='OK')
                    OR Mandato.CodiceMandatoMentor  %s 
                    OR Mandato.codiceDonatore  %s 
                )
                AND Donazione.Data >= '%s' 
                AND Donazione.Data <= '%s'",
                $query_mentor,
                $query_mentor,
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
                LEFT JOIN Mandato 
                ON Mandato.Id_a = Anagrafica.Id_A 
                WHERE Donazione.tipo = 'regular'
                AND Donazione.pay_method ='CC' 
                AND Mandato.Token <>''
                AND (Anagrafica.ID_Mentor  %s 
                    OR (Donazione.CodiceMentor  %s 
                    AND Donazione.esito ='OK')
                    OR Mandato.CodiceMandatoMentor  %s 
                    OR Mandato.codiceDonatore  %s 
                )
                AND Donazione.Data >= '%s' 
                AND Donazione.Data <= '%s'",
                $query_mentor,
                $query_mentor,
                $query_mentor,
                $query_mentor,
                $_GET[ 'date3' ] . " 00:00:00",
                $_GET[ 'date4' ] . " 23:59:59" );
        }
        //echo $query_limit_donazione;
        $donazione = mysqli_query( $conn, $query_limit_donazione )or die( mysqli_error() );
        $row_donazione = mysqli_fetch_assoc( $donazione );
        $totalRows_donazione = mysqli_num_rows( $donazione );
        //echo "<br> Numero righe:" . $totalRows_donazione;
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
                <h1 class="h2">Verifica Donazioni regolari con carta di credito</h1>
            </div>
            <!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
            <?php if(isset($totalRows_donazione)&& $totalRows_donazione >=1 ){?>
            <?php echo "Numero righe: " . $totalRows_donazione ."<br>"; ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <?php if ( $_SESSION['MM_UserGroup'] =="A"){ ?>
                            <th>Reset</th>
                            <?php } ?>
                            <th scope="col">ID_A</th>
                            <th scope="col">Cod_Trans</th>
                            <th scope="col">Importo</th>
                            <th scope="col">Data</th>
                            <!--<th scope="col">Metodo</th>
                    <th scope="col">Tipo</th>-->
                            <th scope="col">Anagrafica</th>
                            <th scope="col">ID Mentor A</th>
                            <th scope="col">Codice Mentor D</th>
                            <th scope="col">Codice Mandato Mentor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php do{ ?>
                        <tr>
                            <?php if ($_SESSION['MM_UserGroup'] =="A"){ ?>
                            <td><a href="reset.php?Id_a=<?php echo stripslashes($row_donazione['Id_a']); ?>">reset</a></td>
                            <?php } ?>
                            <td><?php echo stripslashes($row_donazione['Id_a']); ?></td>
                            <td><?php if ($row_donazione['ID_Mentor']==""){ ?>
                                <?php echo stripslashes($row_donazione['CodTrans']); ?>
                                <?php } else {?>
                                <?php if($row_donazione['tipo'] =="oneoff"){ ?>
                                <a href="singola.php?CodTrans=<?php echo stripslashes($row_donazione['CodTrans']); ?>"> <?php echo stripslashes($row_donazione['CodTrans']); ?> </a>
                                <?php } else{?>
                                <a href="singola.php?Id_a=<?php echo stripslashes($row_donazione['Id_a']); ?>"> <?php echo stripslashes($row_donazione['CodTrans']); ?> </a>
                                <?php } ?>
                                <?php } ?></td>
                            <td>&euro; <?php echo $row_donazione['importo']; ?></td>
                            <td><?php echo stripslashes($row_donazione['data']); ?></td>
                            <!--<td><?php echo $row_donazione['pay_method']; ?></td>
                        <td><?php echo $row_donazione['tipo']; ?></td>  -->
                            <td><?php if ($row_donazione['ragioneSociale']==""){ echo $row_donazione['nome']. " " .$row_donazione['cognome']; } else{ $row_donazione['ragioneSociale']; } ?></td>
                            <?php if ($row_donazione['ID_Mentor']=="" && $row_donazione['CodiceMentor']=="" && $row_donazione['CodiceMandatoMentor'] ==""){?>
                            <td colspan="3" style="text-align: center;"><?php if ($_SESSION['MM_UserGroup'] =="A" ||$_SESSION['MM_UserGroup'] =="S"){ ?>
                                <a href="singola.php?Id_a=<?php echo stripslashes($row_donazione['Id_a']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php
                                } else {
                                    echo "<span style=\"color:#F00; font-weight: bold;\">Anagrafica non importata </span>";
                                }
                                ?></td>
                            <?php }else { ?>
                            <td><?php if ($row_donazione['ID_Mentor']=="" &&  ($_SESSION['MM_UserGroup'] =="A" ||$_SESSION['MM_UserGroup'] =="S")){ ?>
                                <a href="singola.php?Id_a=<?php echo stripslashes($row_donazione['Id_a']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php
                                } elseif ( $row_donazione[ 'ID_Mentor' ] == "" && $_SESSION[ 'MM_UserGroup' ] == "U" ) {
                                    echo "<span style=\"color:#F00; font-weight: bold;\">Anagrafica non importata </span>";
                                } else {
                                    echo $row_donazione[ 'ID_Mentor' ];
                                }
                                ?></td>
                            <td><?php
                            if ( $row_donazione[ 'esito' ] == "KO" ) {
                                echo "<strong>Esito KO</strong>";
                            } else if ( $row_donazione[ 'esito' ] == "WA" ) {
                                echo "<strong>Esito WA</strong>";
                            } else {
                                ?>
                                <?php if ($row_donazione['CodiceMentor']=="" &&  ($_SESSION['MM_UserGroup'] =="A" ||$_SESSION['MM_UserGroup'] =="S")){ ?>
                                <a href="singola.php?Id_a=<?php echo stripslashes($row_donazione['Id_a']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php
                                } elseif ( $row_donazione[ 'CodiceMentor' ] == "" && $_SESSION[ 'MM_UserGroup' ] == "U" ) {
                                    echo "<span style=\"color:#F00; font-weight: bold;\">Donazione non importata </span>";
                                }
                                else {
                                    echo $row_donazione[ 'CodiceMentor' ];
                                }
                                }
                                ?></td>
                            <td><?php
                            if ( $row_donazione[ 'CodiceMandatoMentor' ] != "" ) {
                                echo $row_donazione[ 'CodiceMandatoMentor' ];
                            } elseif ( $row_donazione[ 'CodiceMandatoMentor' ] == "" && ( $_SESSION[ 'MM_UserGroup' ] == "A" || $_SESSION[ 'MM_UserGroup' ] == "S" ) ) {
                                    ?>
                                <a href="singola.php?Id_a=<?php echo stripslashes($row_donazione['Id_a']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php
                                } else {
                                    echo "<span style=\"color:#F00; font-weight: bold;\">Non importata </span>";
                                }
                                ?></td>
                            <?php } ?>
                        </tr>
                        <?php } while ($row_donazione = mysqli_fetch_assoc($donazione)); ?>
                        <tr>
                            <?php if ($_SESSION['MM_UserGroup'] =="A"){ ?>
                            <td colspan="9"><?php } else{ ?>
                            <td colspan="8"><?php }?>
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
                <?php
                $date3_default = date( "Y-m" ) . "-1";
                $date4_default = date( 'Y-m-d' ); //date("Y-m-d");
                $myCalendar = new tc_calendar( "date3", true, false );
                $myCalendar->setIcon( "calendar/images/iconCalendar.gif" );
                $myCalendar->setDate( date( 'd', strtotime( $date3_default ) ), date( 'm', strtotime( $date3_default ) ), date( 'Y', strtotime( $date3_default ) ) );
                $myCalendar->setPath( "calendar/" );
                $myCalendar->setYearInterval( 2010, date( "Y" ) );
                $myCalendar->setAlignment( 'left', 'bottom' );
                $myCalendar->setDatePair( 'date3', 'date4', $date4_default );
                $myCalendar->writeScript();
                $myCalendar = new tc_calendar( "date4", true, false );
                $myCalendar->setIcon( "calendar/images/iconCalendar.gif" );
                $myCalendar->setDate( date( 'd', strtotime( $date4_default ) ), date( 'm', strtotime( $date4_default ) ), date( 'Y', strtotime( $date4_default ) ) );
                $myCalendar->setPath( "calendar/" );
                $myCalendar->setYearInterval( 2010, date( "Y" ) );
                $myCalendar->setAlignment( 'left', 'bottom' );
                $myCalendar->setDatePair( 'date3', 'date4', $date3_default );
                $myCalendar->writeScript();
                ?>
                <div style="clear:both;">
                    <p>
                        <label for="max_rows">Tipo Donazioni :</label>
                        <select name="ID_Mentor">
                            <option value="NULL" selected="selected">Non scritte in Mentor</option>
                            <option value="NOTNULL">Scritte in Mentor</option>
                        </select>
                    </p>
                </div>
                <input type="hidden" name="ricerca" value="periodo"/>
                <input type="submit" name="button" id="button1" value="Invia"/>
            </form>
            <!--<h3>Ricerca donazione specifica:</h3>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="Ricerca_donazion_CodTranse">
            <label for="CodTrans">Codice Transazione: </label><input name="CodTrans" type="text" />
            <input type="submit" name="button" id="button3" value="Invia" />
        </form>--> 
        </main>
    </div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>