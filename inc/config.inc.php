<?php
//202504291630 - Updated: credentials moved to .env
/*
 *  Added cache for configuration
 *  Main configuration Data moved in DB
 *  Credentials loaded from environment variables (.env)
 */

// Load environment variables
require_once __DIR__ . '/env_loader.php';
loadEnvFile(dirname(__DIR__) . '/.env');

define( 'LP', env('LP', 'WRPR') ); //WRSB sito di sviluppo; WRPR sito di produzione

// Funzione per scrivere messaggi nel log personalizzato
function writeLog($message) {
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(LOG_FILE_CONF, $logEntry, FILE_APPEND);
}

// Funzione per ottenere una connessione sicura al database
function getDbConnection() {
    $conn = mysqli_connect(DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME);
    if (!$conn) {
        writeLog("Errore di connessione al DB: " . mysqli_connect_error());
        http_response_code(500);
        echo json_encode(['error' => 'Errore interno del server']);
        exit;
    }
    return $conn;
}

// Funzione per invalidare manualmente la cache
function invalidateCache() {
    if (file_exists(CACHE_FILE)) {
        unlink(CACHE_FILE);
        writeLog("Cache invalidata manualmente.");
    }
}

// Carica dal DB solo le chiavi sensibili (mai scritte su disco)
function loadSensitiveConfig(): array {
    global $config_sensitive_keys;
    $connection = getDbConnection();
    $lp = LP;
    $placeholders = implode(',', array_fill(0, count($config_sensitive_keys), '?'));
    $stmt = $connection->prepare(
        "SELECT PARAMETER, `VALUE` FROM config WHERE Form = ? AND PARAMETER IN ($placeholders)"
    );
    if (!$stmt) {
        writeLog("loadSensitiveConfig prepare failed: " . $connection->error);
        mysqli_close($connection);
        return [];
    }
    $types = str_repeat('s', 1 + count($config_sensitive_keys));
    $params = array_merge([$lp], $config_sensitive_keys);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $sensitive = [];
    while ($row = $result->fetch_assoc()) {
        $sensitive[$row['PARAMETER']] = $row['VALUE'];
    }
    $stmt->close();
    mysqli_close($connection);
    return $sensitive;
}

// Funzione per caricare la configurazione
function loadConfig() {
    global $authorized_IP, $config_sensitive_keys;

    if (isset($_GET['invalidate_cache']) && $_GET['invalidate_cache'] == 1) {
        if (in_array($_SERVER['REMOTE_ADDR'] ?? '', $authorized_IP)) {
            invalidateCache();
        }
    }

    $cache_valid = file_exists(CACHE_FILE) && (filemtime(CACHE_FILE) + CACHE_TTL) > time();

    if ($cache_valid) {
        // Cache contiene solo parametri non sensibili
        $config = include CACHE_FILE;
        // Sensibili sempre caricati freschi dal DB (mai su disco)
        $config = array_merge($config, loadSensitiveConfig());
    } else {
        $connection = getDbConnection();
        $stmt = $connection->prepare("SELECT PARAMETER, `VALUE` FROM config WHERE Form = ?");
        $lp = LP;
        $stmt->bind_param('s', $lp);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            writeLog("Errore nella query di configurazione: " . $connection->error);
            http_response_code(500);
            echo json_encode(['error' => 'Errore interno del server']);
            exit;
        }
        $config = [];
        while ($row = $result->fetch_assoc()) {
            $config[$row['PARAMETER']] = $row['VALUE'];
        }
        $stmt->close();
        mysqli_close($connection);

        if (count($config) < 3) {
            writeLog("Configurazione sospetta: meno di 3 parametri. Cache non aggiornata.");
        } else {
            // Scrivi in cache SOLO i parametri non sensibili
            $cacheable = array_diff_key($config, array_flip($config_sensitive_keys));
            file_put_contents(CACHE_FILE, '<?php return ' . var_export($cacheable, true) . ';');
            writeLog("Cache della configurazione rigenerata.");
        }
    }

    foreach ($config as $key => $value) {
        if (!defined($key)) {
            define($key, $value);
        }
    }
}


// --- Credenziali dal file .env ---
define( 'DB_IP', env('DB_IP', 'localhost') );
define( 'DB_USER', env('DB_USER', '') );
define( 'DB_PASSWORD', env('DB_PASSWORD', '') );
define( 'DB_DBNAME', env('DB_DBNAME', '') );

// Imposta la durata della cache in secondi (es: 300 secondi = 5 minuti)
define('CACHE_TTL', 300); // 5 minuti
// Percorso del file cache
define('CACHE_FILE', __DIR__ . '/cache_config.php');
// Percorso del file di log
define('LOG_DIR', dirname(__DIR__) . '/log');
define('LOG_FILE_CONF', LOG_DIR . '/config_log.txt');

// IP autorizzati dal .env (comma separated) o fallback
$authorized_IP = array_map('trim', explode(',', env('AUTHORIZED_IPS', '127.0.0.1')));

// Chiavi sensibili: non vengono mai scritte nel file di cache su disco
$config_sensitive_keys = [
    'MAIL_PWD', 'CLIENT_ID_PP', 'SECRET_ID_PP', 'GP_APIKEY',
    'MN_APP_SECRET', 'MN_REFRESH_TOKEN', 'SALT_3D', 'SALT_MAIL',
    'SP_PK_APIKEY', 'SP_SK_APIKEY',
];

// --- Carica i parametri dalla configurazione ---
loadConfig();

// --- Identità dell'organizzazione (white label) ---
// Sovrascrivibili dalla tabella `config` (ha priorità) o dal .env
if (!defined('ORG_NAME'))          define('ORG_NAME', env('ORG_NAME', 'La tua Organizzazione'));
if (!defined('ORG_EMAIL'))         define('ORG_EMAIL', env('ORG_EMAIL', 'info@example.org'));
if (!defined('ORG_NOREPLY'))       define('ORG_NOREPLY', env('ORG_NOREPLY', 'noreply@example.org'));
if (!defined('ORG_PRIVACY_EMAIL')) define('ORG_PRIVACY_EMAIL', env('ORG_PRIVACY_EMAIL', 'privacy@example.org'));
if (!defined('ORG_WEBSITE'))       define('ORG_WEBSITE', env('ORG_WEBSITE', 'https://www.example.org'));
if (!defined('ORG_PRIVACY_URL'))   define('ORG_PRIVACY_URL', env('ORG_PRIVACY_URL', '/privacy/'));
// Email del super-amministratore del backend (autorizzato a creare utenti admin)
if (!defined('SUPERADMIN_EMAIL'))  define('SUPERADMIN_EMAIL', env('SUPERADMIN_EMAIL', ''));

// --- Default difensivi per parametri opzionali ---
// Evitano errori fatali su installazioni che non hanno questi record nella tabella `config`.
if (!defined('ALERT_MAIL'))      define('ALERT_MAIL', ORG_EMAIL);
// IP Attempt Limiter: blocca/segnala troppe donazioni dallo stesso IP. Disattivo di default.
if (!defined('IPAL_MAIL_ENABLE')) define('IPAL_MAIL_ENABLE', 0);
if (!defined('IPAL_STOP_ENABLE')) define('IPAL_STOP_ENABLE', 0);
if (!defined('IPAL_TIME'))        define('IPAL_TIME', 60);   // finestra in minuti
if (!defined('IPAL_ATTEMPTS'))    define('IPAL_ATTEMPTS', 10); // soglia di alert
if (!defined('IPAL_STOP'))        define('IPAL_STOP', 20);   // soglia di blocco

// Feature "tessere associative" (USE_TESSERA): costanti referenziate dalla validazione
// anche quando la feature è spenta. Default = sentinelle che non corrispondono a centri reali.
if (!defined('DB_DBNAME_TGIFT'))   define('DB_DBNAME_TGIFT', DB_DBNAME);
if (!defined('TESSERA_COD'))       define('TESSERA_COD', '__no_tessera__');
if (!defined('TESSERA_GIFT'))      define('TESSERA_GIFT', '__no_tessera_gift__');
if (!defined('TESSERA_DESC'))      define('TESSERA_DESC', '');
if (!defined('TESSERA_COST_JUNIOR')) define('TESSERA_COST_JUNIOR', 0);
if (!defined('TESSERA_COST_SENIOR')) define('TESSERA_COST_SENIOR', 0);
if (!defined('DATA_SCAD_TESSERA')) define('DATA_SCAD_TESSERA', '');
// Chiave per autorizzare il cron addebiti ricorrenti via HTTP (vuota = solo CLI)
if (!defined('CRON_REGULAR_KEY')) define('CRON_REGULAR_KEY', env('CRON_REGULAR_KEY', ''));

// Debug automatico basato su USE_SANDBOX
if (defined('USE_SANDBOX') && USE_SANDBOX) {
    define('DEBUG', 1);
} else {
    define('DEBUG', 0);
}

// Eventuale definizione TOKEN se serve
if (defined('USE_MENTOR') && USE_MENTOR) {
    if (!defined('TOKEN') && defined('ID_AMBIENTE') && defined('ID_APP') && defined('PRIVATE_KEY')) {
        define('TOKEN', sha1(ID_AMBIENTE . ID_APP . PRIVATE_KEY));
    }
}

$url_di_base = URL_DI_BASE;

// SatisPay: chiavi caricate da .env
if ( defined('USE_SATISPAY') && USE_SATISPAY == true ) {
    if ( defined('USE_SANDBOX') && USE_SANDBOX == true ) {
        $sy_auth = env('SY_AUTH_SANDBOX', '');
    } else {
        $sy_auth = env('SY_AUTH_PRODUCTION', '');
    }
    if (!empty($sy_auth)) {
        define( "SY_AUTH", $sy_auth );
    }
}

if ( defined('USE_GESTPAY') && USE_GESTPAY == true ) {
	define( 'GP_BUYEROK', $url_di_base . '/grazie.php' );
	define( 'GP_BUYERKO', $url_di_base . '/errore.php' );
	define( 'GP_NOTIFURL', $url_di_base . '/function/gestpay.php' );
}

if ( defined('USE_SATISPAY') && USE_SATISPAY == true ) {
	define( 'SY_BUYEROK', $url_di_base . '/grazie.php' );
	define( 'SY_BUYERKO', $url_di_base . '/errore.php' );
}
if ( defined('USE_STRIPE') && USE_STRIPE == true ) {
	define( 'ST_URLAPI', 'https://api.stripe.com/v1' );
}

define( 'INCLUDE_FOLDER', PERCORSO_DI_BASE . '/inc' );
define( 'EMAIL_FOLDER', PERCORSO_DI_BASE . '/email' );
define( 'LIB_FOLDER', PERCORSO_DI_BASE . '/lib' );
define( 'PAGES_FOLDER', PERCORSO_DI_BASE . '/pages' );
define( 'LOG_FILE', PERCORSO_DI_BASE . '/log/DON_WS.log' );
define( 'EM_DEBUG_LOG_FILE', PERCORSO_DI_BASE . '/log/form.log' );

if ( defined('USE_PAYPAL') && USE_PAYPAL == true ) {
    define( 'PP_WS', $url_di_base . '/function/paypal.php' );
    $PPitem_name = 'Donazione%20Online';
}

define( 'DON_WS', $url_di_base . '/function/donation_WS.php' );
define( 'FORM_THANK_YOU_PAGE', $url_di_base . '/grazie.php' );
define( 'FORM_ERROR_PAGE', $url_di_base . '/errore.php' );
