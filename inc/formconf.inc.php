<?php
/*
 * Configurazione testi del form di donazione (white label).
 * Personalizza titoli, etichette, importi suggeriti e informativa privacy.
 * I dati dell'organizzazione provengono dalle costanti ORG_* (vedi inc/config.inc.php).
 */
$form_conf = array();
$form_conf['pagetitle'] = "Sostieni " . ORG_NAME;
$form_conf['campagin_header'] = '<h2>Sostieni</h2><h1>' . ORG_NAME . '</h1><p>La tua donazione fa la differenza. Grazie per il tuo sostegno!</p>';

$form_conf['legend'] = array();
$form_conf['legend']['ana'] = "Inserisci i tuoi dati";
$form_conf['legend']['pay_method'] = "Scegli il metodo di pagamento";
$form_conf['legend']['amount'] = "Donazione";
$form_conf['legend']['cc'] = "Dati della carta di credito";

$form_conf['field'] = array();
$form_conf['field']['name'] = "Nome";
$form_conf['field']['surname'] = "Cognome";
$form_conf['field']['email'] = "E-mail";
$form_conf['field']['mobile'] = "Cellulare";
$form_conf['field']['pp'] = "PayPal";
$form_conf['field']['sy'] = "SatisPay";
$form_conf['field']['cc'] = "Carta di Credito";
$form_conf['field']['cc_noscript'] = "Carta di Credito";

$form_conf['field']['note'] = "Lascia un commento (max 200 caratteri)";
$form_conf['field']['owner'] = "Titolare";
$form_conf['field']['cardn'] = "Numero di carta";
$form_conf['field']['cvv'] = "CVV";
$form_conf['field']['exp_mm'] = "Mese";
$form_conf['field']['exp_yy'] = "Anno";

// Importi suggeriti per tipo donazione, letti dalla tabella config (CSV) — configurabili da backend
$csv_to_amounts = function ( $csv ) {
    $out = array();
    foreach ( explode( ',', (string) $csv ) as $v ) {
        $v = trim( $v );
        if ( $v !== '' && ctype_digit( $v ) ) { $out[] = (int) $v; }
    }
    return $out;
};
$form_conf['field']['amount_oneoff']  = $csv_to_amounts( defined('AMOUNTS_ONEOFF')  ? AMOUNTS_ONEOFF  : '20,30,50,100' );
$form_conf['field']['amount_mensile'] = $csv_to_amounts( defined('AMOUNTS_MENSILE') ? AMOUNTS_MENSILE : '10,15,25,50' );
$form_conf['field']['amount_annuale'] = $csv_to_amounts( defined('AMOUNTS_ANNUALE') ? AMOUNTS_ANNUALE : '120,180,300,600' );
$form_conf['field']['amount']['altro'] = "Altro importo";
$form_conf['field']['amount']['free'] = "Indica l'importo (senza decimali)";

$form_conf['field']['privacy'] = 'Ho letto e accetto i termini dell&rsquo;Informativa sul trattamento dei dati (<strong>conferma per proseguire</strong>)';
$form_conf['field']['info_privacy'] = '<small id="informativa" class="" style="font-size:60%;line-height:1.4;display:inline-block;color:#555555;height:8em;overflow-y:scroll;background-color: white;padding:10px;"><p><strong>Informativa ai sensi del Regolamento UE 2016/679 (GDPR):</strong> ' . ORG_NAME . ', in qualit&agrave; di Titolare del trattamento ai sensi del Regolamento UE 2016/679 (GDPR), tratta i Dati Personali conferiti per la gestione delle donazioni.</p>
        <p><strong>NOTA PER L&rsquo;INSTALLAZIONE:</strong> questo testo &egrave; un segnaposto. Sostituiscilo con l&rsquo;informativa privacy completa della tua organizzazione, redatta da un consulente legale. Indica almeno: categorie di dati trattati, finalit&agrave; e basi giuridiche, modalit&agrave;, destinatari, diritti dell&rsquo;interessato e termini di conservazione.</p>
        <p>Per qualsiasi richiesta relativa al trattamento dei dati: <strong><a href="mailto:' . ORG_PRIVACY_EMAIL . '">' . ORG_PRIVACY_EMAIL . '</a></strong>.</p>
        <p><em>La versione aggiornata dell&rsquo;informativa &egrave; disponibile <a href="' . ORG_PRIVACY_URL . '" target="_blank">sulla pagina Privacy del nostro sito</a>.</em></p></small>';

$form_conf['error'] = array();
$form_conf['error']['M'] = array();
$form_conf['error']['M']['001'] = "Indica il tuo nome";
$form_conf['error']['M']['002'] = "Indica il tuo cognome";
$form_conf['error']['M']['005'] = "Indica la tua email";
$form_conf['error']['M']['006'] = "Indica il tuo telefono";
$form_conf['error']['M']['021'] = "Indica l&rsquo;importo della tua donazione";
$form_conf['error']['M']['022'] = "";
$form_conf['error']['M']['027'] = "Indica il numero della carta";
$form_conf['error']['M']['028'] = "Indica il mese di scadenza";
$form_conf['error']['M']['029'] = "Indica l&rsquo;anno di scadenza";
$form_conf['error']['M']['030'] = "Indica il codice di sicurezza";
$form_conf['error']['M']['031'] = "Indica nome e cognome del titolare della carta";
$form_conf['error']['M']['036'] = "Per proseguire devi dare il consenso";
$form_conf['error']['M']['056'] = "Indica il metodo di donazione";

$form_conf['error']['E'] = array();
$form_conf['error']['E']['001'] = "Il nome indicato non &egrave; valido";
$form_conf['error']['E']['002'] = "Il cognome indicato non &egrave; valido";
$form_conf['error']['E']['005'] = "Non &egrave; un&rsquo;email valida";
$form_conf['error']['E']['006'] = "Non &egrave; un numero valido";
$form_conf['error']['E']['021'] = "L&rsquo;importo minimo è " . IMPORTO_MINIMO_ONE . " &euro; e deve essere un numero intero. Grazie per il tuo sostegno!";
$form_conf['error']['E']['022'] = "";
$form_conf['error']['E']['027'] = "Il numero della carta non sembra valido";
$form_conf['error']['E']['028'] = "Non &egrave; un mese valido (due numeri)";
$form_conf['error']['E']['029'] = "Non un anno valido (due numeri)";
$form_conf['error']['E']['030'] = "Non &egrave; un codice valido (min. tre numeri)";
$form_conf['error']['E']['031'] = "Non &egrave; un nome valido";
$form_conf['error']['E']['036'] = "";
$form_conf['error']['E']['056'] = "Non &egrave; un metodo di donazione valido";
