<?php
/*
* v 202012011107
* add log
* add reset tessera x terzi 
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
        $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error($conn), E_USER_ERROR );
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
$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error($conn), E_USER_ERROR );
//mysql_select_db(DB_DBNAME, $Donazioni);
if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == "reset" ) {
    if ( isset( $_GET[ 'Id_a' ] ) && isset( $_GET[ 'chk' ] ) ) {
        $verify = md5( $_GET[ 'Id_a' ] . PRIVATE_KEY );
        if ( $_GET[ 'chk' ] === $verify ) {
            /*
             UPDATE `Donazione` SET `CodiceMentor`= NULL WHERE  `Id_a`=12055 ;
             UPDATE `Anagrafica` SET `ID_Mentor`=NULL  WHERE  `Id_a`=12055 ;
             UPDATE `Mandato` SET codiceDonatore = NULL,  CodiceMandatoMentor = NULL WHERE `Id_a`=12055 ;
            */
            $query_reset_ana = sprintf( "UPDATE `Donazione` SET `CodiceMentor`= NULL WHERE  `Id_a`= %s;",
                $_REQUEST[ 'Id_a' ] );
            $query_reset_don = sprintf( "UPDATE `Anagrafica` SET `ID_Mentor`=NULL  WHERE  `Id_a`= %s;",
                $_REQUEST[ 'Id_a' ] );
            $query_reset_man = sprintf( "UPDATE `Mandato` SET codiceDonatore = NULL, CodiceMandatoMentor = NULL WHERE `Id_a`= %s;",
                $_REQUEST[ 'Id_a' ] );
			//$query_reset_tessera = sprintf( "UPDATE `Voucher` SET id_mentor_donatore = NULL, id_mentor_donazione = NULL WHERE `Id_donatore`= %s;",
                //$_REQUEST[ 'Id_a' ] );			
            //
            $reset_ana = mysqli_query( $conn, $query_reset_ana )or die( mysqli_error($conn) );
            $result_ana = mysqli_affected_rows($conn);
            $reset_don = mysqli_query( $conn, $query_reset_don )or die( mysqli_error($conn) );
            $result_don = mysqli_affected_rows($conn);
            $reset_man = mysqli_query( $conn, $query_reset_man )or die( mysqli_error($conn) );
            $result_man = mysqli_affected_rows($conn);
			
			$conn_tes = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME_TGIFT )or trigger_error( mysqli_error($conn_tes), E_USER_ERROR );	
			//$query_reset_tessera = mysqli_query( $conn_tes, $query_reset_man )or die( mysqli_error($conn_tes) );
            //$result_tessera = mysqli_affected_rows($conn_tes);
	
			error_log( date( '[Y-m-d H:i:s e] ' ) . "BE-Mentor Reset  " .$_SESSION[ 'MM_Username' ] ." Id_A ". $_REQUEST[ 'Id_a' ]. PHP_EOL, 3, LOG_FILE ); //DEBUG
        }
    }
}
// Gestione caratteri spciali -FINE
if ( isset( $_REQUEST[ 'Id_a' ] ) ) {
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
    $singola_donazione = mysqli_query( $conn, $query_singola_donazione )or die( mysqli_error($conn) );
    $row_singola_donazione = mysqli_fetch_assoc( $singola_donazione );
    $totalRows_singola_donazione = mysqli_num_rows( $singola_donazione );
    if ( $row_singola_donazione[ 'pay_method' ] == "CC" ) {
        $q_table = "GestPay";
    } else {
       $row_singola_donazione[ 'data' ] >= '2020-06-10' ? $q_table = "PayPalCheckout" : $q_table = "PayPal";
    }
    if ( isset( $_REQUEST[ 'CodTrans' ] ) ) {
        $q_var = trim( $_REQUEST[ 'CodTrans' ] );
    } else {
        $q_var = $row_singola_donazione[ 'CodTrans' ];
    }
    $query_singola_donazione_ADD = sprintf( "SELECT * FROM %s WHERE CodTrans = '%s';",
        $q_table,
        $q_var, 0, 20 );
    $singola_donazione_ADD = mysqli_query( $conn, $query_singola_donazione_ADD )or die( mysqli_error($conn) );
    $row_singola_donazione_ADD = mysqli_fetch_assoc( $singola_donazione_ADD );
    $totalRows_singola_donazione_ADD = mysqli_num_rows( $singola_donazione_ADD );
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
				<h1 class="h2">Reset Donazione e Anagrafica</h1>
			</div>
    <!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
    <?php if((!isset($_GET['action']) || $_GET['action']!="reset")|| $_GET['chk'] != $verify){ ?>
    <p style="color: red; font-weight: bold; font-style: italic;">Attenzione! L'operazione di reset rimuove gli identificativi di Mentor dalle tabelle Anagrafica, Donazione e Mandato del database online rendendo i dati nuovamente importabili in Mentor. Se i dati sono gi&agrave; presenti in Mentor, l'anagrafica verr&agrave; importata con cirteri di deduplica ma donazione e/o mandato saranno duplicati rispetto a quanto esistente.</p>
    <?php $chk= md5($row_singola_donazione['Id_a'].PRIVATE_KEY) ?>
    <p><a href="reset.php?Id_a=<?php echo $row_singola_donazione['Id_a'];?>&chk=<?php echo $chk;?>&action=reset">RESET</a>
    </p>
    <?php } else{
            if ($result_ana ==1 ) echo "<p style=\"color: red; font-weight: bold; font-style: italic;\">Reset anagrafica riuscito!</p>";
            if ($result_man ==1 ) echo "<p style=\"color: red; font-weight: bold; font-style: italic;\">Reset mandato riuscito!</p>";
            if ($result_ana ==1 ) echo "<p style=\"color: red; font-weight: bold; font-style: italic;\">Reset donazione riuscito!</p>";
			//if ($result_tessera ==1 ) echo "<p style=\"color: red; font-weight: bold; font-style: italic;\">Reset tessera riuscito!</p>";
            } ?>
    <?php if (isset($totalRows_singola_donazione) && $totalRows_singola_donazione >0){?>
    <?php do{?>
    <p>Donazione
        <?php echo $row_singola_donazione['CodTrans'];?> </p>
    <p>Nome:
        <?php echo $row_singola_donazione['nome'];?>
        <?php echo $row_singola_donazione['cognome'];?><br/> Indirizzo:
        <?php echo $row_singola_donazione['indirizzo'];?>
        <?php echo $row_singola_donazione['civico'];?> -
        <?php echo $row_singola_donazione['cap'];?> -
        <?php echo $row_singola_donazione['citta'];?> (
        <?php echo $row_singola_donazione['provincia'];?>)<br/> mail:
        <?php echo $row_singola_donazione['mail'];?><br/> tel:
        <?php echo $row_singola_donazione['tel'];?>
    </p>
    <p>Data:
        <?php echo $row_singola_donazione['data'];?><br/> IP:
        <?php echo $row_singola_donazione['IP'];?><br/>
    </p>
    <p><strong>Codice Anagrafica in Mentor</strong>:
        <?php echo $row_singola_donazione['ID_Mentor'];?><br/>
        <?php if ($row_singola_donazione['tipo'] =="regular"){?>
        <strong>Codice Mandato in Mentor</strong> :
        <?php echo $row_singola_donazione['CodiceMandatoMentor'];?><br/>
        <?php } ?>
        <strong>Codice Donazione in Mentor</strong>:
        <?php echo $row_singola_donazione['CodiceMentor'];?>
        <?php if ($row_singola_donazione['esito']=="OK"){ ?>
        <?php } 
                    else { echo "<br><strong>La donazione non ha esito positivo</strong>: " . $row_singola_donazione['esito'];}?>
    </p>
    <p>Metodo:
        <?php 
                    switch ($row_singola_donazione['pay_method']) {
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
					case "SY":
                    echo "Satispay";
                    break;	
                    }
                ?> <br/> Tipo:
        <?php echo $row_singola_donazione['tipo'];?>
    </p>
    <p>Importo:
        <?php echo $row_singola_donazione['importo'];?> &euro;
        <?php if ($row_singola_donazione['tipo'] =="regular"){ echo "<br />Importo mandato: " .$row_singola_donazione['importo_mandato'] . "&euro; ogni " . $row_singola_donazione['frequenza'] ." mese/i" ; }?> <br/> Esito:
        <?php echo $row_singola_donazione['esito'];?>
    </p>
    <p>Campagna:
        <?php echo $row_singola_donazione['id_campagna'];?>
    </p>
    <?php if($row_singola_donazione['tipo'] =="regular"){ ?>
    <p><strong>Dati Mandato</strong><br/> Codice Donatore:
        <?php echo $row_singola_donazione['codiceDonatore'];?><br/> Codice Campanga:
        <?php echo $row_singola_donazione['codiceCampanga'];?><br/> Codice Centro:
        <?php echo $row_singola_donazione['codiceCentro'];?><br/> Codice Mandato Mentor:
        <?php echo $row_singola_donazione['CodiceMandatoMentor'];?><br/> Codice Fiscale Titolare:
        <?php echo $row_singola_donazione['codFis'];?><br/> Nome Titolare:
        <?php echo $row_singola_donazione['nomeTitolare'];?><br/>
        <?php if ($row_singola_donazione['metodo'] =="K"){?> Token:
        <?php echo $row_singola_donazione['Token'];?> -
        <?php echo $row_singola_donazione['meseToken'];?>/
        <?php echo $row_singola_donazione['annoToken'];?><br/>
        <?php } else{ ?> IBAN:
        <?php echo $row_singola_donazione['IBAN'];?> (BIC:
        <?php echo $row_singola_donazione['BIC'];?>)<br/>
        <?php }?> Metodo:
        <?php echo $row_singola_donazione['metodo'];?><br/>
    </p>
    <?php } ?>
    <?php if ($row_singola_donazione['pay_method'] =="CC" || $row_singola_donazione['pay_method'] =="PP" ){?>
    <p><strong>Dati di transazione specifici del POS</strong><br/>
        <?php foreach($row_singola_donazione_ADD as $key => $value){
                            echo $key.": " .$value ."<br />";
                        }?>
    </p>
    <?php } ?>
    <?php } while ($row_singola_donazione = mysqli_fetch_assoc($singola_donazione));?>
    <?php }?>
   </main>
	</div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($Donazioni)){mysqli_close($Donazioni); }?>