<?php
/*
 * Pagina Privacy Policy (segnaposto white label).
 * IMPORTANTE: sostituisci il contenuto con l'informativa privacy completa
 * della tua organizzazione, redatta da un consulente legale.
 */
require '../inc/config.inc.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>Privacy Policy | <?php echo htmlspecialchars(ORG_NAME); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="../css/normalize.css" rel="stylesheet" type="text/css">
<link href="../css/donation.css" rel="stylesheet" type="text/css">
</head>
<body>

<header class="site-header">
  <span class="org-name"><?php echo htmlspecialchars(ORG_NAME); ?></span>
</header>

<div class="page-wrap">
  <h1>Informativa sul trattamento dei dati personali</h1>

  <div class="card">
    <p><strong>&#9888;&#65039; TESTO SEGNAPOSTO &mdash; DA SOSTITUIRE PRIMA DELLA MESSA IN PRODUZIONE</strong></p>
    <p>Questa pagina deve contenere l'informativa privacy della tua organizzazione ai sensi del
      Regolamento UE 2016/679 (GDPR). L'informativa deve indicare almeno:</p>
    <ul>
      <li>Titolare del trattamento e dati di contatto</li>
      <li>Categorie di dati personali trattati</li>
      <li>Finalit&agrave; e basi giuridiche del trattamento</li>
      <li>Modalit&agrave; del trattamento</li>
      <li>Destinatari ed eventuali trasferimenti</li>
      <li>Diritti dell'interessato e modalit&agrave; di esercizio</li>
      <li>Termini di conservazione dei dati</li>
    </ul>
    <p>Per qualsiasi richiesta relativa al trattamento dei dati:
      <a href="mailto:<?php echo ORG_PRIVACY_EMAIL; ?>"><?php echo ORG_PRIVACY_EMAIL; ?></a></p>
  </div>
</div>

<footer class="site-footer">
  &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(ORG_NAME); ?>
</footer>

</body>
</html>
