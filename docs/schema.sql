-- ============================================================
-- Donation Platform - Schema database (MySQL/MariaDB)
--
-- ATTENZIONE: questo schema è RICOSTRUITO dalle query presenti
-- nel codice. Se disponi di un'installazione esistente, esporta
-- lo schema reale con `mysqldump --no-data` e usa quello come
-- riferimento autoritativo, segnalando le differenze.
-- ============================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Configurazione applicativa (letta da inc/config.inc.php)
-- La colonna `Form` identifica l'istanza (valore LP nel .env)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `config` (
  `Id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Form` VARCHAR(16) NOT NULL,
  `PARAMETER` VARCHAR(64) NOT NULL,
  `VALUE` TEXT,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `uk_form_param` (`Form`, `PARAMETER`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Anagrafica donatori
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Anagrafica` (
  `Id_a` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(100),
  `cognome` VARCHAR(100),
  `ragioneSociale` VARCHAR(255),
  `sesso` CHAR(1) DEFAULT 'X',                 -- M/F/S(società)/X(da verificare)
  `indirizzo` VARCHAR(255),
  `civico` VARCHAR(16),
  `cap` VARCHAR(10),
  `citta` VARCHAR(100),
  `provincia` VARCHAR(4),
  `stato` VARCHAR(4) DEFAULT 'I',              -- codice stato (I = Italia)
  `tel` VARCHAR(32),
  `mail` VARCHAR(255),
  `codFis` VARCHAR(16),
  `PIVA` VARCHAR(16),
  `datanascita` DATE NULL,
  `privacy` CHAR(1) DEFAULT 'N',
  `id_fonte` VARCHAR(32),
  `id_campagna` VARCHAR(64),
  `IP` VARCHAR(45),
  `tipo_ana` VARCHAR(8),
  `operazione` VARCHAR(16),                    -- oneoff / regular
  `lang` VARCHAR(4) DEFAULT 'it',
  `CodicePersonale` VARCHAR(64),
  `CodiceReferral` VARCHAR(64),
  `ID_Mentor` VARCHAR(32),                     -- codice anagrafica nel CRM (nullable)
  `data_ins` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id_a`),
  KEY `idx_mail` (`mail`),
  KEY `idx_ip_data` (`IP`, `data_ins`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Donazioni
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Donazione` (
  `Id_d` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Id_a` INT UNSIGNED NOT NULL,
  `CodTrans` VARCHAR(32) NOT NULL,            -- es. D-20240520123456789-PP
  `importo` DECIMAL(10,2),
  `centro` VARCHAR(16),
  `pay_method` VARCHAR(4),                    -- PP, SY, CC, ST, SD
  `nota` VARCHAR(255),
  `tessera` VARCHAR(16),
  `tipotessera` VARCHAR(16),
  `esito` VARCHAR(4),                         -- OK, KO, WA (in attesa)
  `data` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `tipo` VARCHAR(16),                         -- oneoff, regular
  `causale` VARCHAR(64),
  `CodiceMentor` VARCHAR(64),
  `codicePartner` VARCHAR(64),
  `id_campagna` VARCHAR(64),
  `gadget` VARCHAR(64),                        -- eventuale ricompensa/gadget associato
  `valido` CHAR(1) DEFAULT 'Y',
  `ringraziata` CHAR(1) DEFAULT 'N',
  `remainder` INT DEFAULT 0,
  PRIMARY KEY (`Id_d`),
  UNIQUE KEY `uk_codtrans` (`CodTrans`),
  KEY `idx_id_a` (`Id_a`),
  KEY `idx_esito` (`esito`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Mandati per donazioni regolari (SDD / carta ricorrente)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Mandato` (
  `Id_mandato` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Id_a` INT UNSIGNED NOT NULL,
  `frequenza` VARCHAR(4),                     -- 1 = mensile, 12 = annuale
  `importo` DECIMAL(10,2),
  `Token` VARCHAR(128),
  `meseToken` VARCHAR(2),
  `annoToken` VARCHAR(2),
  `nomeTitolare` VARCHAR(128),
  PRIMARY KEY (`Id_mandato`),
  KEY `idx_id_a` (`Id_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Transazioni GestPay/Axerve (API REST)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `GestPayREST` (
  `Id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shopTransactionID` VARCHAR(32),             -- = Donazione.CodTrans
  `transactionResult` VARCHAR(8),              -- OK / KO / XX (3DS pending)
  `transactionErrorCode` VARCHAR(16),
  `transactionErrorDescription` VARCHAR(255),
  `bankTransactionID` VARCHAR(64),
  `authorizationCode` VARCHAR(32),
  `paymentID` VARCHAR(64),
  `paymentToken` VARCHAR(128),
  `currency` VARCHAR(8),
  `country` VARCHAR(8),
  `company` VARCHAR(64),
  `tdLevel` VARCHAR(16),                        -- livello 3D Secure
  `buyername` VARCHAR(128),
  `buyermail` VARCHAR(255),
  `riskResponseCode` VARCHAR(16),
  `riskResponseDescription` VARCHAR(255),
  `alertCode` VARCHAR(16),
  `alertDescription` VARCHAR(255),
  `cvvPresent` VARCHAR(8),
  `maskedPAN` VARCHAR(32),
  `paymentMethod` VARCHAR(32),
  `productType` VARCHAR(32),
  `token` VARCHAR(128),
  `tokenExpiryMonth` VARCHAR(2),
  `tokenExpiryYear` VARCHAR(4),
  `data` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `idx_paymentid` (`paymentID`),
  KEY `idx_shoptrans` (`shopTransactionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabella legacy GestPay (API SOAP) - presente per compatibilità
CREATE TABLE IF NOT EXISTS `GestPay` (
  `Id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `CodTrans` VARCHAR(32),
  `esito` VARCHAR(8),
  `data` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Transazioni PayPal Checkout
-- (colonne allineate all'INSERT in function/inc/functions_paypal.php)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `PayPalCheckout` (
  `Id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `CodTrans` VARCHAR(32),
  `Id_OrderPayPal` VARCHAR(64),               -- order/token PayPal
  `token_type` VARCHAR(32),
  `access_token` TEXT,
  -- Dati di cattura (popolati da aggiornaOrdinePP dopo il pagamento)
  `Payment` VARCHAR(64),                       -- id della capture
  `Status` VARCHAR(32),
  `gross_amount_currency_code` VARCHAR(8),
  `gross_amount_value` VARCHAR(16),
  `paypal_fee_currency_code` VARCHAR(8),
  `paypal_fee_value` VARCHAR(16),
  `net_amount_currency_code` VARCHAR(8),
  `net_amount_value` VARCHAR(16),
  `create_time` VARCHAR(40),
  `update_time` VARCHAR(40),
  `PP_given_name` VARCHAR(100),
  `PP_surname` VARCHAR(100),
  `PP_email_address` VARCHAR(255),
  `payer_id` VARCHAR(64),
  `data` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `idx_codtrans` (`CodTrans`),
  KEY `idx_orderpaypal` (`Id_OrderPayPal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Transazioni Satispay
-- (colonne allineate all'INSERT in function/inc/functions_satispay.php)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Satispay` (
  `Id_satispay` INT UNSIGNED NOT NULL AUTO_INCREMENT,  -- PK interna (rinominata: `id` è il payment id Satispay)
  `CodTrans` VARCHAR(32),
  `id` VARCHAR(64),                           -- payment id Satispay
  `code_identifier` VARCHAR(128),
  `type` VARCHAR(32),
  `amount_unit` VARCHAR(16),                  -- importo in centesimi (199 = 1,99)
  `currency` VARCHAR(8),
  `status` VARCHAR(32),
  `expired` VARCHAR(8),
  `insert_date` VARCHAR(40),
  `expire_date` VARCHAR(40),
  `flow` VARCHAR(32),
  `data` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id_satispay`),
  KEY `idx_codtrans` (`CodTrans`),
  KEY `idx_code_identifier` (`code_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Ticket / lasciapassare con QR code (per eventi)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Ticket` (
  `Id_ticket` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Id_a` INT UNSIGNED,
  `CodTrans` VARCHAR(32),
  `Tipo` CHAR(1),                             -- es. C = cena+evento
  `usato` CHAR(1) DEFAULT 'N',
  `data_uso` DATETIME NULL,
  PRIMARY KEY (`Id_ticket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Partner (sponsor con inviti omaggio)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Partner` (
  `Id_partner` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Nome` VARCHAR(128),
  `mail` VARCHAR(255),
  `codicePartner` VARCHAR(64),
  `inviti` INT DEFAULT 0,
  PRIMARY KEY (`Id_partner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Voucher / tessere in regalo (feature TESSERA_GIFT)
-- (colonne allineate alle query in function/inc/functions_mysql.php)
-- Spesso risiede in un DB separato: vedi DB_DBNAME_TGIFT
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Voucher` (
  `Id_donato` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Id_donatore` INT UNSIGNED,                 -- Id_a del donatore che regala
  `CodTrans` VARCHAR(32),
  `GUID` VARCHAR(64),
  `nome_d` VARCHAR(100),
  `cognome_d` VARCHAR(100),
  `mail_d` VARCHAR(255),
  `campagna` VARCHAR(64),
  `campagna_donazione` VARCHAR(64),
  `data_invio_mail` DATETIME NULL,
  `id_richiesta` INT UNSIGNED NULL,           -- Id_a del beneficiario, dopo il riscatto
  `Esito_donazione` VARCHAR(4) NULL,
  `id_mentor_donatore` VARCHAR(64) NULL,
  `id_mentor_donazione` VARCHAR(64) NULL,
  PRIMARY KEY (`Id_donato`),
  KEY `idx_codtrans` (`CodTrans`),
  KEY `idx_guid` (`GUID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Utenti del backend amministrativo (be/)
-- Le password sono hash bcrypt (migrazione automatica da MD5 legacy)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `Utenti` (
  `Id_utente` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Nominativo` VARCHAR(128),
  `mail` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `gruppo` VARCHAR(32),
  `attivo` CHAR(1) DEFAULT 'Y',
  `scadenza_pwd` DATE NULL,
  PRIMARY KEY (`Id_utente`),
  UNIQUE KEY `uk_mail` (`mail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `password_reset` (
  `Id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mail` VARCHAR(255),
  `ID_utente` INT UNSIGNED,
  `token` VARCHAR(128),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Parametri di configurazione (istanza DEFAULT)
-- Adatta i valori alla tua organizzazione.
-- Le chiavi sensibili (MAIL_PWD, CLIENT_ID_PP, SECRET_ID_PP,
-- GP_APIKEY, SP_PK_APIKEY, SP_SK_APIKEY, SALT_3D, SALT_MAIL,
-- MN_APP_SECRET, MN_REFRESH_TOKEN) vanno valorizzate qui ma non
-- vengono mai scritte nella cache su disco.
-- ============================================================
INSERT INTO `config` (`Form`, `PARAMETER`, `VALUE`) VALUES
('DEFAULT', 'URL_DI_BASE', 'https://dona.example.org'),
('DEFAULT', 'PERCORSO_DI_BASE', '/var/www/vhosts/example.org/dona.example.org'),
('DEFAULT', 'FORM_LANG', 'it'),
('DEFAULT', 'TIMEZONE', 'Europe/Rome'),
('DEFAULT', 'CURRENCY', '978'),
('DEFAULT', 'GOAL', '10000'),
('DEFAULT', 'IMPORTO_MINIMO_ONE', '10'),
('DEFAULT', 'IMPORTO_MINIMO_REG', '60'),
('DEFAULT', 'REQ_FIELDS_DEFAULT', 'nome,cognome,mail,tel'),
('DEFAULT', 'ID_CAMPAGNA_DEFAULT', 'Campagna 2026'),
('DEFAULT', 'ID_CAMPAGNA_DONATI_DEFAULT', '26.GEN.WEB'),
('DEFAULT', 'ID_FONTE_DEFAULT', '0'),
('DEFAULT', 'CANALE_DEFAULT', '10'),
('DEFAULT', 'CENTRO_DEFAULT', '100'),
('DEFAULT', 'ALERT_MAIL', 'admin@example.org'),
-- Flag funzionalità
('DEFAULT', 'USE_SANDBOX', '1'),
('DEFAULT', 'USE_PAYPAL', '0'),
('DEFAULT', 'USE_SATISPAY', '0'),
('DEFAULT', 'USE_GESTPAY', '0'),
('DEFAULT', 'USE_STRIPE', '0'),
('DEFAULT', 'USE_MAGNEWS', '0'),
('DEFAULT', 'USE_MENTOR', '0'),
('DEFAULT', 'USE_TESSERA', '0'),
-- Email (SMTP)
('DEFAULT', 'MAIL_SMTP', 'smtp.example.org'),
('DEFAULT', 'FROM_MAIL', 'donazioni@example.org'),
('DEFAULT', 'FROM_NAME', 'La tua Organizzazione'),
('DEFAULT', 'MAIL_PWD', ''),
-- Sicurezza (genera valori casuali robusti!)
('DEFAULT', 'SALT_3D', ''),
('DEFAULT', 'SALT_MAIL', ''),
-- PayPal
('DEFAULT', 'PP_URLAPI', 'https://api.paypal.com'),
('DEFAULT', 'PP_REDIRECT', 'https://www.paypal.com'),
('DEFAULT', 'CLIENT_ID_PP', ''),
('DEFAULT', 'SECRET_ID_PP', ''),
-- Satispay
('DEFAULT', 'SY_APIURL', 'https://authservices.satispay.com/'),
('DEFAULT', 'SY_REDIRECT', 'https://online.satispay.com'),
-- GestPay / Axerve
('DEFAULT', 'GP_URLAPI', 'https://ecomms2s.sella.it/api'),
('DEFAULT', 'GP_COD_ESE', ''),
('DEFAULT', 'GP_APIKEY', ''),
-- Stripe
('DEFAULT', 'ST_URLAPI', 'https://api.stripe.com/v1'),
('DEFAULT', 'SP_PK_APIKEY', ''),
('DEFAULT', 'SP_SK_APIKEY', ''),
-- MagNews (opzionale)
('DEFAULT', 'MN_API_URL', 'https://ws-mn1.mag-news.it/ws/rest/api'),
('DEFAULT', 'MN_TOKEN_URL', 'https://mn.mag-news.it/be/oauth/token'),
('DEFAULT', 'MN_CLIENT_ID', ''),
('DEFAULT', 'MN_APP_SECRET', ''),
('DEFAULT', 'MN_REFRESH_TOKEN', ''),
('DEFAULT', 'MN_TNX_EMAIL_ID', '2'),
('DEFAULT', 'MN_REMINDER_EMAIL_ID', '8'),
-- Mentor / Direct Channel (in attesa di nuove API)
('DEFAULT', 'MENTOR_API_URL', 'https://dc.directchannel.it/mentor/api'),
('DEFAULT', 'MENTOR_USER', ''),
('DEFAULT', 'ID_AMBIENTE', ''),
('DEFAULT', 'ID_APP', ''),
('DEFAULT', 'PRIVATE_KEY', '');
