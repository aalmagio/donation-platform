<?php
require 'inc/config.inc.php';
require 'inc/data.inc.php';
/*if($_GET){
	$TY = json_encode( $_GET );
	if(!isset($_GET['p']) || "" == $_GET['p']){
	error_log( date( '[Y-m-d H:i:s e] ' ) . "grazie.php parametro diverso da p: " . $TY . PHP_EOL, 3, LOG_FILE ); //DEBUG		
	}
}*/
//Backup per gestpay.php - INIZIO
if ( USE_GESTPAY == true ) { //GestPay
//Backup per gestpay.php - INIZIO
     if (isset($_GET['a']) && isset($_GET['paymentID']) && isset($_GET['paymentToken'])){// Solo per le chimate Gestpay
	$errore = "";
	if(GP_COD_ESE != $_GET['a']){
    	$errore .= "Non coincide il codice eserente<br>";
	}
	if (!isset( $_GET[ 'paymentID' ] ) ) {
    	$errore .= "Non è definito il codice del pagamento<br>";
	} 
	if ( trim( $errore ) == "" ) {
		$azione_data['TransactionResult'] = $_GET['Status'];
		$connection = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
		if (!$connection) { error_log(date('[Y-m-d H:i:s e] ') . "noticket.php DB connection failed: " . mysqli_connect_error() . PHP_EOL, 3, LOG_FILE); }
		$stmt_nt = $connection->prepare("SELECT GestPayREST.shopTransactionID, GestPayREST.paymentID, GestPayREST.transactionErrorCode, GestPayREST.transactionErrorDescription, Donazione.CodTrans, Donazione.Id_a, Donazione.importo, Donazione.centro, Donazione.pay_method, Donazione.nota, Donazione.tessera, Donazione.tipotessera, Donazione.esito, Donazione.`data`, Donazione.CodiceMentor, Donazione.tipo, Donazione.codicePartner, Anagrafica.* FROM GestPayREST LEFT JOIN Donazione ON GestPayREST.shopTransactionID = Donazione.CodTrans LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a WHERE GestPayREST.paymentID =?");
		$stmt_nt->bind_param('s', $_GET['paymentID']);
		$stmt_nt->execute();
		$donazione = $stmt_nt->get_result();
		if (!$donazione) { error_log(date('[Y-m-d H:i:s e] ') . "noticket.php query failed: " . $connection->error . PHP_EOL, 3, LOG_FILE); }
		$row_donazione = mysqli_fetch_assoc( $donazione );
		$totalRows_donazione = mysqli_num_rows( $donazione );
		error_log( date( '[Y-m-d H:i:s e] ' ) . "Query grazie.php: (" .$totalRows_donazione .") " .$query_donazione  . PHP_EOL, 3, LOG_FILE );// TEMP
		if($totalRows_donazione>0){
			foreach ( $row_donazione as $key => $value ) {
				if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
					$azione_data[ $key ] = $value;
				}
			}
		} else {
			  error_log( date( '[Y-m-d H:i:s e] ' ) . "Query grazie.php: (" .$totalRows_donazione .") " .$query_donazione  . PHP_EOL, 3, LOG_FILE );
		}
		if ("WA" == $row_donazione['esito'] ){
			if ( $row_donazione[ 'tipo' ] == "regular" ) {
				$query_mandato = sprintf( "SELECT Id_mandato, frequenza, Token, meseToken, annoToken, nomeTitolare FROM Mandato WHERE Id_a =%s", $row_donazione[ 'Id_a' ] );
				$mandato = mysqli_query( $connection, $query_mandato );
			if (!$mandato) { error_log(date('[Y-m-d H:i:s e] ') . "noticket.php mandato query failed: " . mysqli_error($connection) . PHP_EOL, 3, LOG_FILE); }
				$row_mandato = mysqli_fetch_assoc( $mandato );
				$totalRows_mandato = mysqli_num_rows( $mandato );
				foreach ( $row_mandato as $key => $value ) {
					if ( $key != "" && $key != NULL && $value != "" && $value != NULL ) {
						$azione_data[ $key ] = $value;
					}
				}
			}
			if ( DEBUG == true ) {
				error_log( date( '[Y-m-d H:i:s e] ' ) . "grazie.php ESITO 3D : " . $row_donazione['CodTrans'] ." - Status: " .$_GET['Status'] . PHP_EOL, 3, LOG_FILE );
			}
			if(isset($row_donazione['CodTrans']) && preg_match( "/^[A-Z]{1}-[0-9]{17}-[A-Z]{2}/", $row_donazione['CodTrans'])){
				if (empty ($row_donazione['CodiceMentor'])){	
					$azione = array( "operation" => "save",
						"param" => "GestPay3D",
						"data" => $azione_data
					);
					$azione_string = json_encode( $azione );
					$ch = curl_init( DON_WS );
					curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, $azione_string );
					curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
					curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json',
						'Content-Length: ' . strlen( $azione_string ) ) );
					$result = curl_exec( $ch ); 
				}
				else{
					error_log( date( '[Y-m-d H:i:s e] ' ) . "grazie.php WA con codice mentor : " . $row_donazione['CodTrans'] ." - CodTrans: " .$row_donazione['CodTrans']  . " - Codice Mentor: " .$_GET['CodiceMentor']  . PHP_EOL, 3, LOG_FILE );	
				}
			}
			// MAIL - INIZIO
			$secret = md5( $row_donazione[ 'Id_a' ] . SALT_MAIL );
			$redirect_url = $url_di_base . "/function/mail.php?d=" . $row_donazione[ 'Id_a' ] . "&s=" . $secret;
			// create a new cURL resource
			$ch_mail = curl_init();
			// set URL and other appropriate options
			curl_setopt($ch_mail, CURLOPT_URL, $redirect_url);
			curl_setopt($ch_mail, CURLOPT_HEADER, 0);

			// grab URL and pass it to the browser
			$result_mail = curl_exec($ch_mail);

			// close cURL resource, and free up system resources
			curl_close($ch_mail);

			// MAIL - FINE
		}
	}
}
//Backup per gestpay.php - FINE
}


?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php if(USE_SANDBOX == true){echo "SANDBOX - ";} echo htmlspecialchars(ORG_NAME); ?> - Errore</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.4/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/donation.css" lang="css">
    <!-- Favicons -->
<link rel="apple-touch-icon" sizes="180x180" href="/img/favicon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/img/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/img/favicon/favicon-16x16.png">
<link rel="manifest" href="/img/favicon/site.webmanifest">
<link rel="mask-icon" href="/img/favicon/safari-pinned-tab.svg" color="#631719">
<link rel="shortcut icon" href="/img/favicon/favicon.ico">
<meta name="msapplication-TileColor" content="#631719">
<meta name="msapplication-config" content="/img/favicon/browserconfig.xml">
<meta name="theme-color" content="#ffffff">
</head>
    <style>
    .wr-ty-container {
        padding: 100px;
        padding-top: 70px;

    }

    .font-ty-h1 {
        font-size: 100px;
        line-height: 50px;
    }

    .font-ty-h3 {
        font-size: 36px;
        line-height: 50px;
    }

    .font-ty-h5 {
        font-size: 24px;
        line-height: 31px;
    }

    .font-ty-p {
        font-size: 21px;
        line-height: 32px;
    }

    .font-ty-label {
        font-size: 14px;
        line-height: 28px;
    }

    .wr-footer {
        background-color: #F7F7F7;
    }

    #wr-comment textarea {
        width: 75%;
    }

    @media (max-width: 960px) {
        .wr-ty-container {
            padding: 40px;
        }

        .font-ty-h1 {
            font-size: 70px;
            line-height: 50px;
        }

        .font-ty-h3 {
            font-size: 21px;
            line-height: 30px;
        }

        .font-ty-h5 {
            font-size: 17px;
            line-height: 23px;
        }

        .font-ty-p {
            font-size: 21px;
            line-height: 32px;
        }

        .font-ty-label {
            font-size: 14px;
            line-height: 28px;
        }

        #wr-comment textarea {
            width: 100%;
        }
    }
</style>

<body>
<div class="container-fluid overflow-hidden position-relative">
        <div class="row wr-ty-container">
            <div class="col-12 col-lg-4 ps-0 ps-lg-5 pt-0 pt-lg-5 pe-0 pe-lg-5">
                <p class="font-ty-h1 fw-bold">OOPS!</p>
                <div class="d-none d-lg-block" style="margin-top:200px">
                    <!-- <hr>
                   <button class="btn align-items-center" onclick="showShareOptions('comment')" id="comment-btn">
                        <i class="bi bi-circle align-middle fs-5 me-2 d-none"></i>
                        <i class="bi bi-check-circle align-middle me-2 fs-5"></i>
                        Scrivi un commento
                    </button>
                    <hr>
                    <button class="btn" onclick="showShareOptions('share')" id="share-btn">
                        <i class="bi bi-circle align-middle fs-5 me-2"></i>
                        <i class="bi bi-check-circle align-middle fs-5 me-2 d-none "></i>
                        Condividi la campagna <br>Con i tuoi amici
                    </button>

                    <hr>-->
                </div>
            </div>
            <div class="col-lg-8 col-12 ps-0 pe-0 pe-lg-5 pt-0 pt-lg-5">
                <p class="font-ty-h3">Si è verificato un errore: il tuo biglietto sembra non esser valido. <br class="d-none d-lg-block">
                   
                <span class="fw-bold"><a href="form.php" style="color:#631719; text-decoration: none;">Ci dispiace dell'inconveniente se vuoi puoi effettuare una donazione</a></span> 
                oppure contattarci all'indirizzo <a href="mailto:<?php echo ORG_EMAIL; ?>" style="text-decoration: none;"><?php echo ORG_EMAIL; ?></a>
                </p>
                <p class="font-ty-h5 text-lighter"><a href="form.php">Vai alla pagina della donazione</a> </p>
                <!--<div class="d-block d-lg-none" id="wr-share">
                    <button class="wr-social-link mt-2 "
                        onclick="window.open('https://www.facebook.com/sharer/sharer.php?p[url]=example.org&p[title]=Donazione')"
                        target="_blank" style="background-color:#3C5A98">
                        <span>Condividilo su Facebook</span>
                        <img src="img/facebook.svg" alt="">
                    </button>
                    <button class="wr-social-link mt-2" style="background-color:#54A9EB"
                        onclick="window.open('https://twitter.com/intent/tweet?text=example.org')">
                        <span>Condividilo su Twitter</span>
                        <img src="img/twitter.svg" alt="">
                    </button>
                    <button class="wr-social-link mt-2" style="background-color:#D73D32"
                        onclick="window.open('mailto:');">
                        <span>Condividilo via email</span>
                        <img src="img/telegram.svg" alt="">
                    </button>
                    <button class="wr-social-link mt-2" style="background-color:#00E777"
                        onclick="window.open('whatsapp://send?text=example.org')">
                        <span>Condividilo su Whatsapp</span>
                        <img src="img/whatsapp.svg" alt="">
                    </button>
                    <button class="wr-social-link mt-2" style="background-color:#0077B7"
                        onclick="window.open('http://www.linkedin.com/shareArticle?mini=true&url=example.org&source=example.org')">
                        <span>Condividilo su LinkedIn</span>
                        <img src="img/linkedin.svg" alt="">
                    </button>
                </div>
                <form action="" id="wr-comment">
                    <p class="font-ty-p mt-5 mb-3 text-lighter">
                        Lascia un commento
                    </p>
                    <textarea class="form-control" placeholder="Scrivi qui il tuo commento" name="" id="comment"
                        rows="5"></textarea>
                    <label class="font-ty-caption form-label text-lighter text-grey">I commenti vengono pubblicati
                        nella
                        pagina del progetto</label>
                    <div class="row">
                        <div class="col-12 col-lg-3">
                            <button class="wr-btn-primary d-block text-normalize mt-3">Invia</button>
                        </div>
                    </div>
                </form>-->
            </div>

        </div>
        <!-- Footer -->
        <div class="row wr-container-left wr-container-right wr-footer ">
            <div class="col-3">
                <span class="d-none d-lg-inline">Landing page powered by</span>
                <img src="img/pdb-short-logo.png" alt="" class="d-inline">
            </div>
            <div class="col-9 d-flex justify-content-end d-none d-lg-block" style="text-align:right;">
                <span>©2023 FolkFunding srl | VAT 08378490968 | </span>
                <a href="">Termini e condizioni</a> |
                <a href="">Cookie policy</a>
            </div>
            <div class="col-9  justify-content-end d-block d-lg-none">
                <span>©2023 FolkFunding srl | VAT 08378490968 | </span> <br>
                <a href="">Termini e condizioni</a> |
                <a href="">Cookie policy</a>
            </div>
        </div>
    </div>
 

<?php if (true == USE_SANDBOX) { include('inc/debug.inc.php'); } ?>
    </body>
</html>
