<?php
/*
* V 202012151825
* Aggiunto esito
* Forza scrittura
* Aggiunto metodo Satispay
*/
if ( session_status() === PHP_SESSION_NONE ) {
    session_start();
}
require '../inc/config.inc.php';
require '../inc/data.inc.php';
require_once '../inc/security.php';
send_security_headers();
require 'inc/auth-logout.php';
require_once 'inc/db_helpers.php';
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
closedir( $langfiles );*/
// END OF: Needed for the manual Language selector - not needed if you pass the LANG from your own script
//CALENDARIO -FINE
// Gestione caratteri spciali -FINE
if (isset($_GET['ricerca']) && "transazioni"==$_GET['ricerca']){
    $conn = safe_db_connect();
    if ( !$conn ) {
        die( 'Errore di connessione al database.' );
    }

    $currentPage = $_SERVER[ "PHP_SELF" ];
    $maxRows_donazione = 35;
    $pageNum_donazione = 0;
    if ( isset( $_GET[ 'pageNum_donazione' ] ) ) {
        $pageNum_donazione = intval( $_GET[ 'pageNum_donazione' ] );
    }
    $startRow_donazione = $pageNum_donazione * $maxRows_donazione;
    $query_mentor = "";

    // Whitelists for constrained fields
    $allowed_esito = array( 'OK', 'KO', 'WA' );
    $allowed_pay_method = array( 'CC', 'PP', 'SY' );
    // Whitelist for search operators
    $allowed_operators = array( 'U', 'I', 'F', 'C' );

    if ( isset( $_GET[ 'CodTrans' ] ) && "" != $_GET[ 'CodTrans' ] ) {
        $query_mentor .= " AND Donazione.CodTrans ='" . $conn->real_escape_string( $_GET[ 'CodTrans' ] ) . "'";
    }
    if ( isset( $_GET[ 'esito' ] ) && "" != $_GET[ 'esito' ] ) {
        if ( in_array( $_GET[ 'esito' ], $allowed_esito, true ) ) {
            $query_mentor .= " AND Donazione.esito ='" . $_GET[ 'esito' ] . "'";
        }
    }
    if ( isset( $_GET[ 'pay_method' ] ) && "" != $_GET[ 'pay_method' ] ) {
        if ( in_array( $_GET[ 'pay_method' ], $allowed_pay_method, true ) ) {
            $query_mentor .= " AND Donazione.pay_method ='" . $_GET[ 'pay_method' ] . "'";
        }
    }
    if ( isset( $_GET[ 'ID_Mentor' ] ) && "" != $_GET[ 'ID_Mentor' ] ) {
        //0000000404
        $query_mentor .= " AND Anagrafica.ID_Mentor ='" . $conn->real_escape_string( str_pad($_GET[ 'ID_Mentor' ], 10, "0", STR_PAD_LEFT) ) . "'";
    }
    if ( isset( $_GET[ 'CodiceMentor' ] ) && "" != $_GET[ 'CodiceMentor' ] ) {
        $query_mentor .= " AND Donazione.CodiceMentor ='" . $conn->real_escape_string( $_GET[ 'CodiceMentor' ] ) . "'";
    }
    if ( isset( $_GET[ 'id_campagna' ] ) && "" != $_GET[ 'id_campagna' ] ) {
        $op_campagna = isset( $_GET[ 'op_campagna' ] ) ? $_GET[ 'op_campagna' ] : '';
        if ( in_array( $op_campagna, $allowed_operators, true ) ) {
            $escaped_campagna = $conn->real_escape_string( $_GET[ 'id_campagna' ] );
            switch ( $op_campagna ) {
                case 'U':
                    $query_mentor .= " AND Anagrafica.id_campagna ='" . $escaped_campagna . "'";
                    break;
                case 'I':
                    $query_mentor .= " AND Anagrafica.id_campagna LIKE '%" . $escaped_campagna . "'";
                    break;
                case 'F':
                    $query_mentor .= " AND Anagrafica.id_campagna LIKE '" . $escaped_campagna . "%'";
                    break;
                case 'C':
                    $query_mentor .= " AND Anagrafica.id_campagna LIKE '%" . $escaped_campagna . "%'";
                    break;
            }
        }
    }
    if ( isset( $_GET[ 'cognome' ] ) && "" != $_GET[ 'cognome' ] ) {
        $op_cognome = isset( $_GET[ 'op_cognome' ] ) ? $_GET[ 'op_cognome' ] : '';
        if ( in_array( $op_cognome, $allowed_operators, true ) ) {
            $escaped_cognome = $conn->real_escape_string( $_GET[ 'cognome' ] );
            switch ( $op_cognome ) {
                case 'U':
                    $query_mentor .= " AND Anagrafica.cognome ='" . $escaped_cognome . "'";
                    break;
                case 'I':
                    $query_mentor .= " AND Anagrafica.cognome LIKE '%" . $escaped_cognome . "'";
                    break;
                case 'F':
                    $query_mentor .= " AND Anagrafica.cognome LIKE '" . $escaped_cognome . "%'";
                    break;
                case 'C':
                    $query_mentor .= " AND Anagrafica.cognome LIKE '%" . $escaped_cognome . "%'";
                    break;
            }
        }
    }
    if ( isset( $_GET[ 'mail' ] ) && "" != $_GET[ 'mail' ] ) {
        $op_mail = isset( $_GET[ 'op_mail' ] ) ? $_GET[ 'op_mail' ] : '';
        if ( in_array( $op_mail, $allowed_operators, true ) ) {
            $escaped_mail = $conn->real_escape_string( $_GET[ 'mail' ] );
            switch ( $op_mail ) {
                case 'U':
                    $query_mentor .= " AND Anagrafica.mail ='" . $escaped_mail . "'";
                    break;
                case 'I':
                    $query_mentor .= " AND Anagrafica.mail LIKE '%" . $escaped_mail . "'";
                    break;
                case 'F':
                    $query_mentor .= " AND Anagrafica.mail LIKE '" . $escaped_mail . "%'";
                    break;
                case 'C':
                    $query_mentor .= " AND Anagrafica.mail LIKE '%" . $escaped_mail . "%'";
                    break;
            }
        }
    }

	$query_donazione = sprintf( "SELECT Donazione.Id_a, Donazione.CodTrans, Donazione.importo, Donazione.data, Donazione.pay_method, Donazione.tipo, Donazione.CodiceMentor, Donazione.esito,
                Anagrafica.ID_Mentor, Anagrafica.nome, Anagrafica.cognome, Anagrafica.ragioneSociale
                FROM Donazione
                LEFT JOIN Anagrafica
                ON Donazione.Id_a = Anagrafica.Id_A
                WHERE Donazione.Id_a >0
                %s
                ORDER BY Anagrafica.Id_a DESC",
        $query_mentor );
    $query_limit_donazione = sprintf( "%s LIMIT %d, %d", $query_donazione, $startRow_donazione, $maxRows_donazione );

    //Query conteggio
    $query_count_donazione = sprintf( "SELECT count(Donazione.Id_a) AS N_righe
                FROM Donazione
                LEFT JOIN Anagrafica
                ON Donazione.Id_a = Anagrafica.Id_A
                WHERE Donazione.Id_a >0
                %s
                ORDER BY Anagrafica.Id_a DESC",
        $query_mentor );


    $donazione = safe_query( $conn, $query_limit_donazione, 'cerca.php:query_limit_donazione' );
    if ( !$donazione ) {
        die( 'Errore nella query di ricerca.' );
    }
    $row_donazione = mysqli_fetch_assoc( $donazione );
    $totalRows_donazione = mysqli_num_rows( $donazione );
    if ( isset( $_GET[ 'totalRows_donazione' ] ) ) {
        $totalRows_donazione = intval( $_GET[ 'totalRows_donazione' ] );
    } else {
        $all_donazione = safe_query( $conn, $query_count_donazione, 'cerca.php:query_count_donazione' );
        if ( !$all_donazione ) {
            die( 'Errore nella query di conteggio.' );
        }
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
?>
<?php require('inc/head.inc.php'); ?>
<body>
<?php require('inc/nav_hor.inc.php'); ?>
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
            if ( isset( $totalRows_donazione ) && $totalRows_donazione >= 1 ) {
                ?>
            <?php echo "Numero righe: " . $totalRows_donazione ."<br>"; ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <?php if ($_SESSION['MM_UserGroup'] =="A"){ ?>
                            <th>Reset</th>
                            <?php } ?>
                            <th scope="col">ID_A</th>
                            <th scope="col">Cod_Trans</th>
                            <th scope="col">Importo</th>
							<th scope="col">Esito</th>
                            <th scope="col">Data</th>
                            <th scope="col">Metodo</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Anagrafica</th>
                           <?php if ( USE_MENTOR == true ) { //Mentor	?> <th scope="col">ID Mentor A</th>
                            <th scope="col">Codice Mentor D</th>
						   <?php } //Mentor	 
						   else{ ?>
						   <th scope="col">Verifica</th>
						   <?php }?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php do{ ?>
                        <tr>
                            <?php if ($_SESSION['MM_UserGroup'] =="A"){ ?>
                            <td><a href="reset.php?Id_a=<?php echo htmlspecialchars($row_donazione['Id_a']); ?>">reset</a></td>
                            <?php } ?>
                            <td><?php echo htmlspecialchars($row_donazione['Id_a']); ?></td>
                            <?php if ( USE_MENTOR == true ) { //Mentor	?>
                            <td> <?php if ($row_donazione['ID_Mentor']==""){  echo htmlspecialchars($row_donazione['CodTrans']); } else {?>
                                <?php if ("regular" ==$row_donazione['tipo']){?>
                                    <a href="singola.php?Id_a=<?php echo htmlspecialchars($row_donazione['Id_a']); ?>"> <?php echo htmlspecialchars($row_donazione['CodTrans']); ?> </a>
                                <?php } else{?>
                                <a href="singola.php?CodTrans=<?php echo htmlspecialchars($row_donazione['CodTrans']); ?>"> <?php echo htmlspecialchars($row_donazione['CodTrans']); ?> </a>
                                <?php } ?>
                                <?php } ?></td>
                            <?php }else{ ?>
                            <td>
                                <?php if ("regular" ==$row_donazione['tipo']){?>
                                    <a href="singola.php?Id_a=<?php echo htmlspecialchars($row_donazione['Id_a']); ?>"> <?php echo htmlspecialchars($row_donazione['CodTrans']); ?> </a>
                                <?php } else{?>
                                <a href="singola.php?CodTrans=<?php echo htmlspecialchars($row_donazione['CodTrans']); ?>"> <?php echo htmlspecialchars($row_donazione['CodTrans']); ?> </a>
                                <?php } ?>
                                </td>
                            <?php } ?>
                            <td>&euro; <?php echo $row_donazione['importo']; ?></td>
							<td><?php echo $row_donazione['esito']; ?></td>
                            <td><?php echo htmlspecialchars($row_donazione['data']); ?></td>
                            <td><?php echo $row_donazione['pay_method']; ?></td>
                            <td><?php echo $row_donazione['tipo']; ?></td>
                            <td><?php if ($row_donazione['ragioneSociale']==""){ echo $row_donazione['nome']. " " .$row_donazione['cognome']; } else{ $row_donazione['ragioneSociale']; } ?></td>
                            <?php if ( USE_MENTOR == true ) { //Mentor	?>
							<?php if ($row_donazione['ID_Mentor']=="" && $row_donazione['CodiceMentor']==""){?>
                            <td colspan="2" style="text-align: center;"><?php if ($row_donazione['ID_Mentor']=="" &&  ($_SESSION['MM_UserGroup'] =="A" ||$_SESSION['MM_UserGroup'] =="S")){ ?>
								<?php if ("OK" != $row_donazione['esito']  ){?>
								<a href="donazione_singola.php?CodTrans=<?php echo htmlspecialchars($row_donazione['CodTrans']); ?>"><strong>Verifica dati</strong></a> | <a href="singola.php?CodTrans=<?php echo htmlspecialchars($row_donazione['CodTrans']); ?>"><strong>Forza scrittura anagrafica in Mentor</strong></a>
								<?php }else{?>
                                <a href="singola.php?CodTrans=<?php echo htmlspecialchars($row_donazione['CodTrans']); ?>"><strong>Scrivi in Mentor</strong></a>
								<?php
									}
								} else { ?>
                                <span style="color:#F00; font-weight: bold;">Non importata </span>
                                <?php } ?></td>
                            <?php } else{ ?>
                            <td><?php if ($row_donazione['ID_Mentor']=="" &&  ($_SESSION['MM_UserGroup'] =="A" ||$_SESSION['MM_UserGroup'] =="S")){ ?>
                                <a href="singola.php?CodTrans=<?php echo htmlspecialchars($row_donazione['CodTrans']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php } elseif ($row_donazione['ID_Mentor']=="" &&  $_SESSION['MM_UserGroup'] =="U"){ ?>
                                <span style="color:#F00; font-weight: bold;">Non importata </span>
                                <?php } else { echo $row_donazione['ID_Mentor']; }?></td>
                            <td><?php  if ($row_donazione['CodiceMentor']=="" &&  ($_SESSION['MM_UserGroup'] =="A" ||$_SESSION['MM_UserGroup'] =="S")){ ?>
                                <a href="singola.php?CodTrans=<?php echo htmlspecialchars($row_donazione['CodTrans']); ?>"><strong>Scrivi in Mentor</strong></a>
                                <?php } elseif ($row_donazione['CodiceMentor']=="" &&  $_SESSION['MM_UserGroup'] =="U"){ ?>
                                <span style="color:#F00; font-weight: bold;">Non importata </span>
                                <?php } else{echo $row_donazione['CodiceMentor']; } ?></td>
                            <?php } ?>
							<?php } else {?>
								<td>
									<a href="donazione_singola.php?CodTrans=<?php echo htmlspecialchars($row_donazione['CodTrans']); ?>"><strong>Verifica dati</strong></a>
								</td>
								
							<?php } ?>
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
                                                    <a href="<?php printf(" %s?pageNum_donazione=%d%s ", htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'), max(0, $pageNum_donazione - 1), $queryString_donazione); ?>">indietro</a>
                                                    <?php
                                                    } // Show if not first page
                                                    else {
                                                        echo "&nbsp;";
                                                    }
                                                    ?></td>
                                                <td style="text-align: center;"><?php if ($pageNum_donazione > 0) { // Show if not first page ?>
                                                    <p><a href="<?php printf(" %s?pageNum_donazione=%d%s ", htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'), 0, $queryString_donazione); ?>">inizio</a>
                                                        <?php
                                                        } // Show if not first page
                                                        else {
                                                            echo "&nbsp;";
                                                        }
                                                        ?></td>
                                                <td style="text-align: center;">&nbsp;</td>
                                                <td style="text-align: center;">pagina <?php echo $pageNum_donazione+1; ?> di <?php echo $totalPages_donazione+1;?></td>
                                                <td style="text-align: center;">&nbsp;</td>
                                                <td style="text-align: center;"><?php if ($pageNum_donazione < $totalPages_donazione) { // Show if not last page ?>
                                                    <a href="<?php printf(" %s?pageNum_donazione=%d%s ", htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'), $totalPages_donazione, $queryString_donazione); ?>">fine</a>
                                                    <?php
                                                    } // Show if not last page
                                                    else {
                                                        echo "&nbsp;";
                                                    }
                                                    ?></td>
                                                <td style="text-align: center;"><?php if ($pageNum_donazione < $totalPages_donazione) { // Show if not last page ?>
                                                    <a href="<?php printf(" %s?pageNum_donazione=%d%s ", htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8'), min($totalPages_donazione, $pageNum_donazione + 1), $queryString_donazione); ?>">avanti</a>
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
            <h3>Cerca una donazione per:</h3>
            <p class="text-danger">I campi di ricerca sono in AND logico</p>
            <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" name="Ricerca_periodo">
                <?php
                /*$date3_default = date( "Y-m" ) . "-1";
                $date4_default = date( 'Y-m-d' ); //date("Y-m-d");
                $myCalendar = new tc_calendar( "date3", true, false );
                $myCalendar->setIcon( "calendar/images/iconCalendar.gif" );
                $myCalendar->setDate( date( 'd', strtotime( $date3_default ) ), date( 'm', strtotime( $date3_default ) ), date( 'Y', strtotime( $date3_default ) ) );
                $myCalendar->setPath( "calendar/" );
                $myCalendar->setYearInterval( 2019, date( "Y" ) );
                $myCalendar->setAlignment( 'left', 'bottom' );
                $myCalendar->setDatePair( 'date3', 'date4', $date4_default );
                $myCalendar->writeScript();
                $myCalendar = new tc_calendar( "date4", true, false );
                $myCalendar->setIcon( "calendar/images/iconCalendar.gif" );
                $myCalendar->setDate( date( 'd', strtotime( $date4_default ) ), date( 'm', strtotime( $date4_default ) ), date( 'Y', strtotime( $date4_default ) ) );
                $myCalendar->setPath( "calendar/" );
                $myCalendar->setYearInterval( 2019, date( "Y" ) );
                $myCalendar->setAlignment( 'left', 'bottom' );
                $myCalendar->setDatePair( 'date3', 'date4', $date3_default );
                $myCalendar->writeScript();*/
                ?>
                <div style="clear:both;">
                    <fieldset>
                        <legend> Dati transazione</legend>
                        <div class="col-8">
                            <label for="CodTrans">Codice Transazione: </label>
                            <input type="text" name="CodTrans">
                            <br>
                            <label for="esito">Esito: </label>
                            <select name="esito">
                                <option value="OK">OK</option>
                                <option value="KO">KO</option>
                                <option value="WA">WA</option>
                                <option value="" selected="selected">Tutti</option>
                            </select>
                            <br>
                            <label for="pay_method">Metodo Pagamento  :</label>
                            <select name="pay_method">
                                <option value="CC">Carta di credito</option>
                                <option value="PP">PayPal</option>
								<option value="SY">Satispay</option>
                                <option value="" selected="selected">Tutti</option>
                            </select>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend>Dati Mentor</legend>
                        <div class="col-8">
                            <label for="ID_Mentor">Codice Donatore Mentor: </label>
                            <input type="text" name="ID_Mentor">
                            <br>
                            <label for="CodiceMentor">Codice Donazione Mentor: </label>
                            <input type="text" name="CodiceMentor">
                            <br>
                            <label for="id_campagna">Campagna: </label>
                            <select name="op_campagna">
                                <option value="U" selected="selected">=</option>
                                <option value="I">Inizia con</option>
                                <option value="F">Finisce con</option>
                                <option value="C">Contiene</option>
                            </select>
                            &nbsp;
                            <input type="text" name="id_campagna">
                            <br>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend>Dati Donatore</legend>
                        <div class="col-8">
                            <label for="cognome">Cognome: </label>
                            <select name="op_cognome">
                                <option value="U" >=</option>
                                <option value="I">Inizia con</option>
                                <option value="F">Finisce con</option>
                                <option value="C" selected="selected">Contiene</option>
                            </select>
                            &nbsp;
                            <input type="text" name="cognome">
                            <br>
                            <label for="mail">Mail: </label>
                            <select name="op_mail">
                                <option value="U" >=</option>
                                <option value="I">Inizia con</option>
                                <option value="F">Finisce con</option>
                                <option value="C" selected="selected">Contiene</option>
                            </select>
                            &nbsp;
                            <input type="text" name="mail">
                        </div>
                    </fieldset>
                </div>
                <input type="hidden" name="ricerca" value="transazioni"/>
                <input type="submit" name="button" id="button1" value="Invia"/>
            </form>
        </main>
    </div>
</div>
<?php
    require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>
