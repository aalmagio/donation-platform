<?php
if ( !isset( $_SESSION ) ) {
    session_start();
}
require '../inc/config.inc.php';
require '../inc/data.inc.php';
require 'inc/auth-logout.php';
define( 'INVIO_MAIL', 1 ); // Numero di invio
$test_mode = isset( $_GET['button_test'] ) && !empty( $_GET['test_email'] );
$test_email_addr = $test_mode ? ( filter_var( trim( $_GET['test_email'] ), FILTER_VALIDATE_EMAIL ) ?: '' ) : '';
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

// --- Sanitizzazione input (anti SQL injection) ---
foreach ( array( 'date1', 'date2' ) as $d ) {
    if ( isset( $_GET[ $d ] ) && !preg_match( '/^\d{4}-\d{2}-\d{2}$/', $_GET[ $d ] ) ) {
        unset( $_GET[ $d ] );
    }
}
foreach ( array( 'pay_method', 'ID_Mentor', 'destinatari' ) as $p ) {
    if ( isset( $_GET[ $p ] ) ) {
        $_GET[ $p ] = $conn->real_escape_string( $_GET[ $p ] );
    }
}

if ( isset( $_GET[ 'date1' ] ) && isset( $_GET[ 'date2' ] ) ) {
    if ( $_GET[ 'date1' ] > date( "Y-m-d" ) ) {
        echo "<p>La data iniziale del periodo &egrave; nel futuro. Non &egrave; possibile effettuare la query.</p>";
    } else {
        if ( $_GET[ 'destinatari' ] == "partner" ) { // Mando ai partner
            if ( USE_SANDBOX == true ) {
                $limite = 5;
            } else {
                $limite = 100;
            }
            if ( isset( $_GET[ 'mn_template' ] ) && is_numeric( $_GET[ 'mn_template' ] ) ) {
                $template_MN = $_GET[ 'mn_template' ];
            } else {
                $template_MN = MN_REMINDER_EMAIL_ID;
            }
            if ( isset( $_GET[ 'n' ] ) && is_numeric( $_GET[ 'n' ] ) ) {
                $invio_n = $_GET[ 'n' ];
            } else {
                $invio_n = INVIO_MAIL;
            }
            $query_recipient = sprintf( "SELECT Ticket.*, Partner.Nome FROM Ticket LEFT JOIN Partner ON Ticket.Id_partner = Partner.Id_partner
                        WHERE remainder < %d
                        ORDER BY Id_ticket ASC
                        LIMIT %d",
                    $invio_n,
                    $limite );

        } else { //Mando ai donatori 
            if ( isset( $_GET[ 'pay_method' ] ) && "ALL" == $_GET[ 'pay_method' ] ) {
                $pay_method_query = " IS NOT NULL  ";
            } else {
                $pay_method_query = " = '" . $_GET[ 'pay_method' ] . "'";
            }
            if ( USE_SANDBOX == true ) {
                $limite = 5;
            } else {
                $limite = 100;
            }
            //echo $query_recipient;
            if ( isset( $_GET[ 'mn_template' ] ) && is_numeric( $_GET[ 'mn_template' ] ) ) {
                $template_MN = $_GET[ 'mn_template' ];
            } else {
                $template_MN = MN_REMINDER_EMAIL_ID;
            }
            if ( isset( $_GET[ 'n' ] ) && is_numeric( $_GET[ 'n' ] ) ) {
                $invio_n = $_GET[ 'n' ];
            } else {
                $invio_n = INVIO_MAIL;
            }

            if ( isset( $_GET[ 'ID_Mentor' ] ) && $_GET[ 'ID_Mentor' ] == "NOTNULL" ) { //SCRITTE IN MENTOR
                $query_mentor = "IS NOT NULL";
                /*$query_recipient = sprintf( "SELECT Donazione.importo, Donazione.pay_method, Donazione.CodTrans, Donazione.remainder, Anagrafica.Id_a, Anagrafica.nome, Anagrafica.cognome, Anagrafica.mail, Anagrafica.tel 
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
                        AND Donazione.remainder < %d
                        ORDER BY Id_a ASC
                        LIMIT %d",
                        */
                //TEST
                    $query_recipient = sprintf( "SELECT Donazione.importo, Donazione.pay_method, Donazione.CodTrans, Donazione.remainder, Anagrafica.Id_a, Anagrafica.nome, Anagrafica.cognome, Anagrafica.mail, Anagrafica.tel 
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
                        AND Donazione.remainder < %d
                        ORDER BY Id_a ASC
                        LIMIT %d",
                    $query_mentor,
                    $query_mentor,
                    $_GET[ 'date1' ] . " 00:00:00",
                    $_GET[ 'date2' ] . " 23:59:59",
                    $pay_method_query,
                    $invio_n,
                    $limite );
            } else { // NON SCRITTE IN MENTOR
                $query_mentor = "IS NULL";
                /*$query_recipient = sprintf( "SELECT Donazione.importo, Donazione.pay_method, Donazione.CodTrans, Donazione.remainder, Anagrafica.Id_a, Anagrafica.nome, Anagrafica.cognome, Anagrafica.mail, Anagrafica.tel 
                        FROM Donazione
                        LEFT JOIN Anagrafica
                        ON Donazione.Id_a = Anagrafica.Id_A
                        WHERE Donazione.Esito ='OK'
                        AND Anagrafica.mail  LIKE '%example.org%'
                        AND Donazione.tipo  = 'oneoff'
                        AND (Anagrafica.ID_Mentor %s
                            OR Donazione.CodiceMentor %s)
                        AND Donazione.Data >= '%s' 
                        AND Donazione.Data <= '%s'
                        AND pay_method %s
                        AND Donazione.remainder < %d
                        ORDER BY Id_a ASC
                        LIMIT %d",
                    $query_mentor,
                    $query_mentor,
                    $_GET[ 'date1' ] . " 00:00:00",
                    $_GET[ 'date2' ] . " 23:59:59",
                    $pay_method_query,
                    $invio_n,
                    $limite );*/
                $query_recipient = sprintf(
    "SELECT Donazione.importo, Donazione.pay_method, Donazione.CodTrans, Donazione.remainder, Anagrafica.Id_a, Anagrafica.nome, Anagrafica.cognome, Anagrafica.mail, Anagrafica.tel 
     FROM Donazione
     LEFT JOIN Anagrafica ON Donazione.Id_a = Anagrafica.Id_a
     WHERE Donazione.Esito = 'OK'
    
     AND Donazione.tipo = 'oneoff'
    
     AND Donazione.Data >= '%s'
     AND Donazione.Data <= '%s'
    
     AND Donazione.remainder < %d
     ORDER BY Anagrafica.Id_a ASC
     LIMIT %d",
    $_GET['date1'] . " 00:00:00", // terzo %s
    $_GET['date2'] . " 23:59:59", // quarto %s
  
    $invio_n,                // %d per il numero intero
    $limite                  // %d per il limite
);
            }
        }

        $recipient = mysqli_query( $conn, $query_recipient )or die( mysqli_error() );
        $row_recipient = mysqli_fetch_assoc( $recipient );
        $totalRows_recipient = mysqli_num_rows( $recipient );
        if ( $test_mode ) {
            if ( $_GET['destinatari'] == 'partner' ) {
                $row_recipient = [
                    'Id_ticket'  => 99999,
                    'Id_partner' => 99999,
                    'mail'       => $test_email_addr,
                    'nome'       => 'Mario',
                    'cognome'    => 'Rossi',
                    'telefono'   => '3331234567',
                    'tipo'       => 'F',
                    'Nome'       => 'Partner Test Srl',
                ];
            } else {
                $row_recipient = [
                    'Id_a'            => 99999,
                    'mail'            => $test_email_addr,
                    'nome'            => 'Mario',
                    'cognome'         => 'Rossi',
                    'tel'             => '3331234567',
                    'importo'         => 50.00,
                    'pay_method'      => 'CC',
                    'CodTrans'        => 'TEST-0001',
                    'remainder'       => 0,
                    'CodicePersonale' => 'TEST123',
                ];
            }
            $totalRows_recipient = 1;
        }
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
                <h1 class="h2">Invia una mail ai donatori</h1>
            </div>
            <?php
            if ( isset($_GET[ 'destinatari' ]) && $_GET[ 'destinatari' ] == "partner" ) { // Mando ai prtner
                 require_once('remailer-partner.inc.php');   
            } elseif ( isset($_GET[ 'destinatari' ]) && $_GET[ 'destinatari' ] == "donor" ) { //Mando ai donatori 
                require_once('remailer-donor.inc.php');          
            }
            ?>
            
            <!--<p>Databse in use: <?php echo DB_DBNAME; ?></p>-->
            <h3>A chi vuoi mandare la mail</h3>
            <label for="Ricerca_periodo" class="h4 mt-4">Scegli il periodo:</label>
            <br>
            <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" name="Ricerca_periodo" >
                <?php
                $date1_default = date( 'Y-m-01', strtotime( 'first day of last month' ) );
                $date2_default = date( 'Y-m-d' );
                $val_date1 = isset( $_GET['date1'] ) ? htmlspecialchars( $_GET['date1'] ) : $date1_default;
                $val_date2 = isset( $_GET['date2'] ) ? htmlspecialchars( $_GET['date2'] ) : $date2_default;
                ?>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
                <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
                <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>
                <div class="form-row align-items-center mb-3">
                    <div class="col-auto">
                        <label class="font-weight-bold mr-1">Da:</label>
                        <input type="text" id="date1" name="date1" class="form-control d-inline-block"
                               style="width:150px" value="<?= $val_date1 ?>" readonly>
                    </div>
                    <div class="col-auto">
                        <label class="font-weight-bold mr-1">A:</label>
                        <input type="text" id="date2" name="date2" class="form-control d-inline-block"
                               style="width:150px" value="<?= $val_date2 ?>" readonly>
                    </div>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var fp1 = flatpickr("#date1", {
                        dateFormat: "Y-m-d", locale: "it",
                        minDate: "2020-01-01", maxDate: "today",
                        onChange: function(s, str) { if (str) fp2.set('minDate', str); }
                    });
                    var fp2 = flatpickr("#date2", {
                        dateFormat: "Y-m-d", locale: "it",
                        minDate: "2020-01-01", maxDate: "today",
                        onChange: function(s, str) { if (str) fp1.set('maxDate', str); }
                    });
                });
                </script>
                <div style="clear:both;">
                    <div class="row">
                        <?php if ( USE_MENTOR == true ) { //Mentor ?>
                        <div class="col">
                            <label for="ID_Mentor" class="h3">Tipo Donazioni :</label>
                            <select name="ID_Mentor" class="form-control" required>
                                <option value="NULL" selected="selected">Non scritte in Mentor</option>
                                <option value="NOTNULL">Scritte in Mentor</option>
                            </select>
                        </div>
                        <?php }  else{ ?>
                        <input type="hidden" name="ID_Mentor" value="NULL">
                        <?php }?>
                        <div class="col">
                            <label for="pay_method" class="h4 mt-4">Metodo:</label>
                            <br>
                            <select name="pay_method" class="form-control form-group" required>
                                <option value="ALL" selected="selected" >Tutti</option>
                                <?php if ( USE_GESTPAY == true  || USE_STRIPE == true) {?>
                                <option value="CC">Carta di credito</option>
                                <?php } ?>
                                <?php if ( USE_PAYPAL == true  ) {?>
                                <option value="PP">PayPal</option>
                                <?php } ?>
                                <?php if ( USE_SATISPAY == true  ) {?>
                                <option value="SY">Satispay</option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col">
                            <label for="destinatari" class="h4 mt-4">Destinatari:</label>
                            <br>
                            <select name="destinatari" class="form-control form-group" required>
                                <option value="donor" selected>Donatori</option>
                                <option value="partner">Ospiti</option>
                            </select>
                        </div>
                        <?php if ( USE_MAGNEWS == true ) { ?>
                        <div class="col">
                            <label for="mn_template" class="h4 mt-4">Modello:</label>
                            <br>
                            <input type="number"  name="mn_template" class="form-control form-group" required value="<?php if (isset($_GET['mn_template'])) {echo $_GET['mn_template'];} else{ echo MN_REMINDER_EMAIL_ID;} ?>">
                        </div>
                        <?php
                        } else { //da dafinire per invio mail senza MN 
                        }
                        ?>
                        <div class="col">
                            <label for="n" class="h4 mt-4">Invio N:</label>
                            <br>
                            <input type="number" name="n" class="form-control form-group" required value="<?php if (isset($_GET['n'])) { echo $_GET['n'];} ?>">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="ricerca" value="periodo"/>
                <div class="row mt-3 align-items-end">
                    <div class="col-md-5">
                        <label for="test_email" class="h4 mt-2">Email di test:</label>
                        <input type="email" name="test_email" id="test_email" class="form-control"
                               placeholder="indirizzo@esempio.it"
                               value="<?php echo isset($_GET['test_email']) ? htmlspecialchars($_GET['test_email']) : ''; ?>">
                    </div>
                    <div class="col-md-7 d-flex align-items-end mt-3 gap-2">
                        <input type="submit" name="button" id="button1" value="Invia" class="btn btn-primary mr-2"/>
                        <input type="submit" name="button_test" id="button_test" value="Invia Test" class="btn btn-warning mr-2"/>
                        <button type="button" class="btn btn-info" onclick="openPreview()">Anteprima</button>
                    </div>
                </div>
            </form>
            <p>Remainder defult per donatori = 150. Remaindere Defult per patner = 152.</p>
        </main>
    </div>
</div>
<!-- Modal Anteprima -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Anteprima destinatari</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="previewModalBody">
                <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
            </div>
            <div class="modal-footer" id="previewModalFooter">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

<script>
var _previewParams = null;

function openPreview() {
    var form   = document.forms['Ricerca_periodo'];
    var params = {};
    ['date1','date2','ID_Mentor','pay_method','destinatari','n'].forEach(function(name) {
        var el = form.elements[name];
        if (el) params[name] = el.value;
    });
    _previewParams = params;
    $('#previewModal').modal('show');
    loadPreviewPage(1);
}

function loadPreviewPage(page) {
    var params   = $.extend({}, _previewParams, { page: page });
    var queryStr = $.param(params);

    $('#previewModalBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
    $('#previewModalFooter').html('<button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>');

    $.getJSON('remailer-preview.php?' + queryStr)
        .done(function(data) {
            if (data.error) {
                $('#previewModalBody').html('<div class="alert alert-danger">Errore: ' + $('<span>').text(data.error).html() + '</div>');
                return;
            }
            renderPreviewTable(data);
        })
        .fail(function() {
            $('#previewModalBody').html('<div class="alert alert-danger">Errore nel caricamento.</div>');
        });
}

function renderPreviewTable(data) {
    var isDonor = (data.destinatari !== 'partner');
    var start   = (data.page - 1) * data.per_page + 1;

    var html = '<p class="mb-2"><strong>' + data.total + '</strong> contatti totali &mdash; pagina <strong>' + data.page + '</strong> di <strong>' + data.pages + '</strong> (mostrando ' + start + '&ndash;' + Math.min(start + data.per_page - 1, data.total) + ')</p>';

    if (data.contacts.length === 0) {
        html += '<div class="alert alert-warning">Nessun contatto trovato con i filtri selezionati.</div>';
    } else {
        html += '<div class="table-responsive"><table class="table table-sm table-striped table-bordered">';
        html += '<thead class="thead-dark"><tr>';
        html += '<th>#</th>';
        if (isDonor) {
            html += '<th>ID</th><th>Nome</th><th>Cognome</th><th>Email</th><th>Importo</th><th>Metodo</th><th>Remainder</th>';
        } else {
            html += '<th>ID Ticket</th><th>Nome</th><th>Cognome</th><th>Email</th><th>Tipo</th><th>Partner</th><th>Remainder</th>';
        }
        html += '</tr></thead><tbody>';

        $.each(data.contacts, function(i, c) {
            var esc = function(v) { return $('<span>').text(v != null ? v : '').html(); };
            html += '<tr>';
            html += '<td>' + (start + i) + '</td>';
            if (isDonor) {
                html += '<td>' + esc(c.Id_a)       + '</td>';
                html += '<td>' + esc(c.nome)        + '</td>';
                html += '<td>' + esc(c.cognome)     + '</td>';
                html += '<td>' + esc(c.mail)        + '</td>';
                html += '<td>' + esc(c.importo)     + ' &euro;</td>';
                html += '<td>' + esc(c.pay_method)  + '</td>';
                html += '<td>' + esc(c.remainder)   + '</td>';
            } else {
                html += '<td>' + esc(c.Id_ticket)   + '</td>';
                html += '<td>' + esc(c.nome)        + '</td>';
                html += '<td>' + esc(c.cognome)     + '</td>';
                html += '<td>' + esc(c.mail)        + '</td>';
                html += '<td>' + esc(c.tipo)        + '</td>';
                html += '<td>' + esc(c.NomePartner) + '</td>';
                html += '<td>' + esc(c.remainder)   + '</td>';
            }
            html += '</tr>';
        });

        html += '</tbody></table></div>';
    }

    $('#previewModalBody').html(html);

    // Pagination footer
    var footer = '<button type="button" class="btn btn-secondary mr-auto" data-dismiss="modal">Chiudi</button>';
    footer += '<div class="d-flex align-items-center">';
    footer += '<button class="btn btn-outline-primary btn-sm mr-2" onclick="loadPreviewPage(' + (data.page - 1) + ')" ' + (data.page <= 1 ? 'disabled' : '') + '>&laquo; Prec</button>';
    for (var p = 1; p <= data.pages; p++) {
        if (data.pages <= 10 || p === 1 || p === data.pages || Math.abs(p - data.page) <= 2) {
            footer += '<button class="btn btn-sm mr-1 ' + (p === data.page ? 'btn-primary' : 'btn-outline-secondary') + '" onclick="loadPreviewPage(' + p + ')">' + p + '</button>';
        } else if (Math.abs(p - data.page) === 3) {
            footer += '<span class="mr-1">…</span>';
        }
    }
    footer += '<button class="btn btn-outline-primary btn-sm ml-2" onclick="loadPreviewPage(' + (data.page + 1) + ')" ' + (data.page >= data.pages ? 'disabled' : '') + '>Succ &raquo;</button>';
    footer += '</div>';
    $('#previewModalFooter').html(footer);
}
</script>

<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>
