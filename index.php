<?php
/*
 * Pagina di donazione (white label).
 * Testi e importi configurabili in inc/formconf.inc.php;
 * identità organizzazione nelle costanti ORG_* (inc/config.inc.php).
 */
require 'inc/config.inc.php';
require 'inc/formconf.inc.php';
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<title><?php if (USE_SANDBOX == true) { echo "SANDBOX - "; } echo htmlspecialchars($form_conf['pagetitle']); ?></title>
<meta name="description" content="Sostieni <?php echo htmlspecialchars(ORG_NAME); ?> con una donazione online">
<meta property="og:title" content="<?php echo htmlspecialchars($form_conf['pagetitle']); ?>">
<meta property="og:description" content="Sostieni <?php echo htmlspecialchars(ORG_NAME); ?> con una donazione online">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="canonical" href="<?php echo URL_DI_BASE; ?>/">
<link href="css/normalize.css" rel="stylesheet" type="text/css">
<link href="css/donation.css" rel="stylesheet" type="text/css">
<link href="images/logo.png" rel="shortcut icon" type="image/x-icon">
</head>
<body>

<header class="site-header">
  <img class="logo" src="images/logo.png" alt="Logo <?php echo htmlspecialchars(ORG_NAME); ?>" onerror="this.style.display='none'">
  <span class="org-name"><?php echo htmlspecialchars(ORG_NAME); ?></span>
</header>

<div class="page-wrap">
  <div class="campaign-header">
    <?php echo $form_conf['campagin_header']; ?>
  </div>

  <div class="card">
    <form id="donationForm" class="formstyle">

      <!-- STEP 1: dati anagrafici e privacy -->
      <div id="step1" class="step active">
        <legend class="form-legend"><?php echo $form_conf['legend']['ana']; ?></legend>
        <input type="text" id="nome" name="nome" placeholder="<?php echo $form_conf['field']['name']; ?>" required class="text-field">
        <input type="text" id="cognome" name="cognome" placeholder="<?php echo $form_conf['field']['surname']; ?>" required class="text-field">
        <input type="email" id="mail" name="mail" placeholder="<?php echo $form_conf['field']['email']; ?>" required class="text-field">
        <input type="tel" id="tel" name="tel" placeholder="<?php echo $form_conf['field']['mobile']; ?>" required class="text-field">
        <hr>
        <legend class="form-legend">Privacy</legend>
        <label class="privacy-label">
          <input type="checkbox" id="privacy" name="privacy" value="Y">
          Confermo di aver letto e accetto la <a href="<?php echo ORG_PRIVACY_URL; ?>" target="_blank">Privacy Policy</a>
        </label>
        <?php echo $form_conf['field']['info_privacy']; ?>
        <button type="button" class="btn btn-next" data-next="step2">Prosegui</button>
      </div>

      <!-- STEP 2: metodo di pagamento e importo -->
      <div id="step2" class="step">
        <?php if (defined('USE_GESTPAY') && USE_GESTPAY == true) { ?>
        <!-- Tipo di donazione: una tantum o ricorrente (la ricorrente richiede carta di credito) -->
        <fieldset class="radio-buttons" id="freqChoice">
          <legend class="form-legend">Tipo di donazione</legend>
          <label class="radio-option"><input type="radio" name="freq_choice" value="oneoff" checked> Una tantum</label>
          <label class="radio-option"><input type="radio" name="freq_choice" value="1"> Ogni mese</label>
          <label class="radio-option"><input type="radio" name="freq_choice" value="12"> Ogni anno</label>
        </fieldset>
        <p id="regularHint" style="display:none;color:var(--brand-muted);font-size:.85rem">Le donazioni ricorrenti sono addebitate su carta di credito; potrai sospenderle in qualsiasi momento contattandoci.</p>
        <?php } ?>

        <fieldset class="radio-buttons" id="payMethods">
          <legend class="form-legend"><?php echo $form_conf['legend']['pay_method']; ?></legend>
          <?php if (defined('USE_PAYPAL') && USE_PAYPAL == true) { ?>
          <label class="radio-option">
            <input type="radio" name="pay_method" value="PP">
            <img src="images/paypal.png" alt="PayPal">
          </label>
          <?php } ?>
          <?php if (defined('USE_SATISPAY') && USE_SATISPAY == true) { ?>
          <label class="radio-option">
            <input type="radio" name="pay_method" value="SY">
            <img src="images/satispayimg.png" alt="Satispay">
          </label>
          <?php } ?>
          <?php if ((defined('USE_GESTPAY') && USE_GESTPAY == true) || (defined('USE_STRIPE') && USE_STRIPE == true)) { ?>
          <label class="radio-option">
            <input type="radio" name="pay_method" value="CC">
            <img src="images/carte.png" alt="<?php echo $form_conf['field']['cc']; ?>">
          </label>
          <?php } ?>
        </fieldset>

        <!-- Dati carta di credito (mostrati solo se pay_method = CC) -->
        <div id="cardDetails">
          <legend class="form-legend"><?php echo $form_conf['legend']['cc']; ?></legend>
          <input type="text" id="titolare" name="titolare" placeholder="<?php echo $form_conf['field']['owner']; ?>" class="text-field">
          <input type="text" id="cartan" name="cartan" placeholder="<?php echo $form_conf['field']['cardn']; ?>" class="text-field">
          <input type="text" id="exp_mm" name="exp_mm" placeholder="<?php echo $form_conf['field']['exp_mm']; ?>" class="text-field" maxlength="2">
          <input type="text" id="exp_yy" name="exp_yy" placeholder="<?php echo $form_conf['field']['exp_yy']; ?>" class="text-field" maxlength="2">
          <input type="text" id="cvv" name="cvv" placeholder="<?php echo $form_conf['field']['cvv']; ?>" class="text-field" maxlength="4">
        </div>

        <hr>
        <fieldset class="radio-buttons">
          <legend class="form-legend">Seleziona l'importo da donare</legend>
          <?php foreach (array(3, 2, 1, 0) as $i) { $imp = $form_conf['field']['amount'][$i]; ?>
          <label class="radio-option">
            <input type="radio" name="importo" value="<?php echo $imp; ?>">
            <?php echo $imp; ?>&euro;
            <?php if (!empty($form_conf['field']['cost_ex'][$i])) { ?><small><?php echo $form_conf['field']['cost_ex'][$i]; ?></small><?php } ?>
          </label>
          <?php } ?>
          <label class="radio-option">
            <input type="radio" name="importo" value="altro" id="importodinamico">
            <?php echo $form_conf['field']['amount']['altro']; ?>
          </label>
        </fieldset>

        <div id="altro_importo">
          <legend class="form-legend"><?php echo $form_conf['field']['amount']['free']; ?></legend>
          <input type="number" step="1" min="1" id="importolibero" name="importolibero" placeholder="Importo" class="text-field">
        </div>

        <hr>
        <legend class="form-legend"><?php echo $form_conf['field']['note']; ?></legend>
        <input type="text" id="nota" name="nota" maxlength="200" placeholder="Commento" class="text-field">

        <input type="hidden" id="tipo_donazione" name="tipo_donazione" value="oneoff">
        <input type="hidden" id="frequenza" name="frequenza" value="">
        <input type="hidden" id="IP" name="IP" value="<?php echo htmlspecialchars($client_ip); ?>">
        <input type="hidden" id="req_fields" name="req_fields" value="nome,cognome,mail,tel">
        <button type="submit" class="btn">Dona!</button>
      </div>

      <!-- Attesa redirect al pagamento -->
      <div id="okform" class="center" style="display:none">
        <div id="loader" class="loader"></div>
        <legend class="form-legend">Attendi un attimo...</legend>
      </div>

      <!-- Errore -->
      <div id="errorform" class="center" style="display:none">
        <h1>&#9888;&#65039;</h1>
        <legend class="form-legend">Si è verificato un errore con la tua donazione.<br>
          Per favore, aggiorna questa pagina e riprova.<br><br>
          Se non riesci, scrivici a <a href="mailto:<?php echo ORG_EMAIL; ?>"><?php echo ORG_EMAIL; ?></a>: saremo pronti ad aiutarti!
        </legend>
      </div>

    </form>
  </div>
</div>

<footer class="site-footer">
  &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(ORG_NAME); ?> &middot;
  <a href="<?php echo ORG_PRIVACY_URL; ?>">Privacy Policy</a>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    function validateEmail(email) {
        var re = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        return re.test(String(email).toLowerCase());
    }
    function validatePhone(phone) {
        var re = /^[0-9]{8,15}$/;
        return re.test(String(phone));
    }

    $('#importolibero').on('input', function() {
        $('#importodinamico').val(this.value.replace(/[^0-9]/g, ''));
    });

    $('.btn-next').click(function() {
        var requiredFields = ['#nome', '#cognome', '#mail', '#tel'];
        var isValid = true;

        for (var i = 0; i < requiredFields.length; i++) {
            var field = $(requiredFields[i]);
            field.toggleClass('error', field.val() === '');
            if (field.val() === '') { isValid = false; }
        }
        if (!validateEmail($('#mail').val())) { isValid = false; $('#mail').addClass('error'); }
        if (!validatePhone($('#tel').val())) { isValid = false; $('#tel').addClass('error'); }
        if (!$('#privacy').is(':checked')) { isValid = false; }

        if (isValid) {
            var nextStep = $(this).data('next');
            $(this).parent().removeClass('active');
            $('#' + nextStep).addClass('active');
        } else {
            alert('Per favore, compila tutti i campi obbligatori correttamente e accetta la privacy.');
        }
    });

    $('input[name="pay_method"]').change(function() {
        $('#cardDetails').toggle($(this).val() === 'CC');
    });

    $('input[name="importo"]').change(function() {
        $('#altro_importo').toggle($(this).val() === 'altro');
    });

    // Tipo donazione: ricorrente => forza carta di credito (unico metodo con tokenizzazione)
    $('input[name="freq_choice"]').change(function() {
        var v = $(this).val();
        if (v === 'oneoff') {
            $('#tipo_donazione').val('oneoff');
            $('#frequenza').val('');
            $('#payMethods .radio-option').show();
            $('#regularHint').hide();
        } else {
            $('#tipo_donazione').val('regular');
            $('#frequenza').val(v); // 1 = mensile, 12 = annuale
            // Solo carta: nascondo gli altri metodi e seleziono CC
            $('#payMethods .radio-option').each(function() {
                var isCC = $(this).find('input[name="pay_method"]').val() === 'CC';
                $(this).toggle(isCC);
            });
            $('#payMethods input[name="pay_method"][value="CC"]').prop('checked', true).trigger('change');
            $('#regularHint').show();
        }
    });

    $('#donationForm').on('submit', function(e) {
        e.preventDefault();

        if (!$('input[name="pay_method"]:checked').val()) {
            alert('Per favore, seleziona un metodo di pagamento.');
            return false;
        }
        if (!$('input[name="importo"]:checked').val()) {
            alert('Per favore, seleziona un importo.');
            return false;
        }
        var isRegular = $('#tipo_donazione').val() === 'regular';
        if (isRegular && $('input[name="pay_method"]:checked').val() !== 'CC') {
            alert('Le donazioni ricorrenti sono possibili solo con carta di credito.');
            return false;
        }

        var formData = {
            operation: "do",
            param: isRegular ? "regular" : "transaction",
            data: {
                nome: $('#nome').val(),
                cognome: $('#cognome').val(),
                mail: $('#mail').val(),
                tel: $('#tel').val(),
                nota: $('#nota').val(),
                titolare: $('#titolare').val(),
                cartan: $('#cartan').val(),
                exp_mm: $('#exp_mm').val(),
                exp_yy: $('#exp_yy').val(),
                cvv: $('#cvv').val(),
                privacy: $('#privacy').val(),
                tipo_donazione: $('#tipo_donazione').val(),
                frequenza: $('#frequenza').val(),
                IP: $('#IP').val(),
                importo: $('input[name="importo"]:checked').val(),
                importo_liber: $('#importolibero').val(),
                req_fields: $('#req_fields').val(),
                pay_method: $('input[name="pay_method"]:checked').val()
            }
        };

        $.ajax({
            url: '<?php echo DON_WS; ?>',
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify(formData),
            success: function(response) {
                $('#okform').show();
                $('#step2').hide();
                if (response.Transazione && response.Transazione.URL_trans) {
                    window.location.href = response.Transazione.URL_trans;
                } else {
                    $('#okform').hide();
                    $('#errorform').show();
                }
            },
            error: function(error) {
                $('#errorform').show();
                $('#step2').hide();
            }
        });
    });
});
</script>
</body>
</html>
