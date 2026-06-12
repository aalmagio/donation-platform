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
closedir( $langfiles );*/
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
$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME_TGIFT )or trigger_error( mysqli_error(), E_USER_ERROR );
//mysql_select_db(DB_DBNAME, $Donazioni);
if ( isset( $_GET[ 'date3' ] ) && isset( $_GET[ 'date4' ] ) ) {
	if ( $_GET[ 'date3' ] > date( "Y-m-d" ) ) {
		echo "<p>La data iniziale del periodo &egrave; nel futuro. Non &egrave; possibile effettuare la query.</p>";
	} else {
		$currentPage = $_SERVER[ "PHP_SELF" ];
		$maxRows_tgift = 35;
		$pageNum_tgift = 0;
		if ( isset( $_GET[ 'pageNum_tgift' ] ) ) {
			$pageNum_tgift = $_GET[ 'pageNum_tgift' ];
		}
		$startRow_tgift = $pageNum_tgift * $maxRows_tgift;
		$esito = $_GET['esito_v'];
		$req_tess ="";
		if (isset($_GET['req_tess'])){
			$req_tess = $_GET['req_tess'];
			if ("all" == $req_tess ) $q_tess_req ="";
			else{
				if (0 == $req_tess){
					$q_tess_req ="AND id_richiesta = '0'" ;
				} else {
					$q_tess_req ="AND id_richiesta <> '0'" ;
				}

			}
		}
		$query_tgift = sprintf( "SELECT *
                    
                    FROM Voucher 

                    WHERE Esito_donazione = '%s'
					%s
                    AND data_donazione >= '%s' 
                    AND data_donazione <= '%s'  
                    ORDER BY Id_donato DESC",
				$esito,
				$q_tess_req,
				$_GET[ 'date3' ] . " 00:00:00",
				$_GET[ 'date4' ] . " 23:59:59" );
			$query_limit_tgift = sprintf( "%s LIMIT %d, %d", $query_tgift, $startRow_tgift, $maxRows_tgift );
			//Query conteggio
			//echo $query_limit_tgift;
			$query_count_tgift = sprintf( "SELECT count(Id_donato) AS N_righe 
                    FROM Voucher 

                    WHERE Esito_donazione = '%s'
                    %s
                    AND data_donazione >= '%s' 
                    AND data_donazione <= '%s'  
                    ORDER BY Id_donato DESC",
				$esito,
				$q_tess_req,
				$_GET[ 'date3' ] . " 00:00:00",
				$_GET[ 'date4' ] . " 23:59:59" );

		//echo $query_limit_tgift;
		$tgift = mysqli_query( $conn, $query_limit_tgift )or die( mysqli_error($conn) );
		$row_tgift = mysqli_fetch_assoc( $tgift );
		$totalRows_tgift = mysqli_num_rows( $tgift );
		if ( isset( $_GET[ 'totalRows_tgift' ] ) ) {
			$totalRows_tgift = $_GET[ 'totalRows_tgift' ];
		} else {
			$all_tgift = mysqli_query( $conn, $query_count_tgift )or die( mysqli_error($conn) );
			$row_all_tgift = mysqli_fetch_assoc( $all_tgift );
			$totalRows_tgift = $row_all_tgift[ 'N_righe' ];
		}
		$totalPages_tgift = ceil( $totalRows_tgift / $maxRows_tgift ) - 1;
		$queryString_tgift = "";
		if ( !empty( $_SERVER[ 'QUERY_STRING' ] ) ) {
			$params = explode( "&", $_SERVER[ 'QUERY_STRING' ] );
			$newParams = array();
			foreach ( $params as $param ) {
				if ( stristr( $param, "pageNum_tgift" ) == false &&
					stristr( $param, "totalRows_tgift" ) == false ) {
					array_push( $newParams, $param );
				}
			}
			if ( count( $newParams ) != 0 ) {
				$queryString_tgift = "&" . htmlentities( implode( "&", $newParams ) );
			}
		}
		$queryString_tgift = sprintf( "&totalRows_tgift=%d%s", $totalRows_tgift, $queryString_tgift );
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
				<h1 class="h2">Verifica Tessere in regalo </h1>
			</div>
			<!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
			<?php
			// Risultato ricerca - INIZO
			if ( isset( $totalRows_tgift ) && $totalRows_tgift >= 1 ) {
				?>
			<?php echo "Numero righe: " . $totalRows_tgift ."<br>"; ?>
			<div class="table-responsive">
				<table class="table table-striped table-sm">
					<thead>
						<tr>
							<?php if ($_SESSION['MM_UserGroup'] =="A"){ ?>
							<th>Reset</th>
							<?php } ?>
							<th scope="col">Id_donato</th>
							<th scope="col">Cod_Trans</th>
							<th scope="col">Esito (Voucher)</th>
							<th scope="col">Invio Mail</th>
							<th scope="col">Richiesta Tessera</th>
							<!--<th scope="col">Tipo</th>-->
							<th scope="col">Anagrafica</th>
							<th scope="col">ID Mentor A</th>
							<th scope="col">Codice Mentor D</th>
						</tr>
					</thead>
					<tbody>
						<?php do{ ?>
						<tr>
							<?php if ($_SESSION['MM_UserGroup'] =="A"){ ?>
							<td><a href="reset.php?Id_a=<?php echo stripslashes($row_tgift['Id_donatore']); ?>">reset</a></td>
							<?php } ?>
							<td><?php echo stripslashes($row_tgift['Id_donato']); ?></td>
							<td><?php if ($row_tgift['id_mentor_donatore']==""){  echo stripslashes($row_tgift['CodTrans']); } else {?>
								<a href="singola.php?CodTrans=<?php echo stripslashes($row_tgift['CodTrans']); ?>"> <?php echo stripslashes($row_tgift['CodTrans']); ?> </a>
								<?php } ?></td>
							<td><?php echo $row_tgift['Esito_donazione']; ?></td>
							<td><?php if ("0" == $row_tgift['invio_mail']) echo "<strong>NO</strong>"; else{ echo $row_tgift['data_invio_mail']; } ?></td>
							<td><?php if ("0" == $row_tgift['id_richiesta']) echo "<strong>NO</strong>"; else{ echo $row_tgift['id_richiesta']; } ?></td>
							<td><?php echo $row_tgift['nome_d']. " " .$row_tgift['cognome_d'];  ?></td>
							<td><?php echo $row_tgift['id_mentor_donatore']; ?></td>
							<td><?php echo $row_tgift['id_mentor_donazione']; ?></td>
						</tr>
						<?php } while ($row_tgift = mysqli_fetch_assoc($tgift)); ?>
						<tr>
							<?php if ($_SESSION['MM_UserGroup'] =="A"){ ?>
							<td colspan="9"><?php } else{ ?>
							<td colspan="8"><?php } ?>
								<div class="table-responsive">
									<table class="table table-striped table-sm">
										<tbody>
											<tr>
												<td style="text-align: center;"><?php if ($pageNum_tgift > 0) { // Show if not first page ?>
														<a href="<?php printf(" %s?pageNum_tgift=%d%s ", $currentPage, max(0, $pageNum_tgift - 1), $queryString_tgift); ?>">indietro</a>
														<?php } // Show if not first page 
											else{ echo "&nbsp;";} ?></td>
												<td style="text-align: center;"><?php if ($pageNum_tgift > 0) { // Show if not first page ?>
														<p><a href="<?php printf(" %s?pageNum_tgift=%d%s ", $currentPage, 0, $queryString_tgift); ?>">inizio</a>
															<?php } // Show if not first page 
											else{ echo "&nbsp;";} ?></td>
												<td style="text-align: center;">&nbsp;</td>
												<td style="text-align: center;">pagina <?php echo $pageNum_tgift+1; ?> di <?php echo $totalPages_tgift+1;?></td>
												<td style="text-align: center;">&nbsp;</td>
												<td style="text-align: center;"><?php if ($pageNum_tgift < $totalPages_tgift) { // Show if not last page ?>
													<a href="<?php printf(" %s?pageNum_tgift=%d%s ", $currentPage, $totalPages_tgift, $queryString_tgift); ?>">fine</a>
													<?php } // Show if not last page  
											else{ echo "&nbsp;";} ?></td>
												<td style="text-align: center;"><?php if ($pageNum_tgift < $totalPages_tgift) { // Show if not last page ?>
														<a href="<?php printf(" %s?pageNum_tgift=%d%s ", $currentPage, min($totalPages_tgift, $pageNum_tgift + 1), $queryString_tgift); ?>">avanti</a>
														<?php } // Show if not last page 
											else{ echo "&nbsp;";} ?></td>
											</tr>
										</tbody>
									</table>
								</div></td>
						</tr>
					</tbody>
				</table>
			</div>
			<hr>
			<?php }
			// Risultato ricerca - FINE
			?>
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
						<label for="esito_v">Esito (Voucher) :</label>
						<select name="esito_v">
							<option value="OK" selected="selected">OK</option>
							<option value="KO">KO</option>
							<option value="WA">WA</option>
						</select>
					</p>
					<p>
						<label for="req_tess">Richista tessera :</label>
						<select name="req_tess">
							<option value="all" selected="selected">Tutti</option>
							<option value="0">No</option>
							<option value="1">S&igrave;</option>
						</select>
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