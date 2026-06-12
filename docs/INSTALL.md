# Installazione

## 1. Prerequisiti

- PHP >= 7.4 con estensioni `mysqli`, `curl`, `gd`, `mbstring`, `phar`
- MySQL / MariaDB
- Composer
- Server web (Apache/Nginx) con HTTPS attivo (obbligatorio per i pagamenti)
- Un server SMTP per le email transazionali

## 2. Codice e dipendenze

```bash
git clone <repo> dona.example.org
cd dona.example.org
composer install
```

## 3. Database

1. Crea un database e un utente dedicato:

```sql
CREATE DATABASE donazioni CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'donazioni'@'localhost' IDENTIFIED BY '<password-robusta>';
GRANT SELECT, INSERT, UPDATE, DELETE ON donazioni.* TO 'donazioni'@'localhost';
```

2. Importa lo schema:

```bash
mysql -u donazioni -p donazioni < docs/schema.sql
```

> **Nota**: lo schema in `docs/schema.sql` è ricostruito dalle query del codice.
> Se possiedi un'installazione esistente, esporta lo schema reale con
> `mysqldump --no-data` e usa quello come riferimento autoritativo.

3. Popola la tabella `config` con i parametri della tua istanza
   (vedi la sezione *Parametri di configurazione* in fondo a `docs/schema.sql`).
   La colonna `Form` identifica l'istanza e deve corrispondere al valore `LP` nel `.env`.

## 4. Configurazione ambiente

```bash
cp .env.example .env
```

Compila `.env` con: credenziali DB, codice istanza `LP`, identità organizzazione (`ORG_*`),
IP autorizzati e — se usi Satispay — le chiavi `SY_AUTH_*`.

Imposta i permessi: il web server deve poter scrivere in `log/` e `img/qr/`.

## 5. Personalizzazione white label

1. **Logo**: carica `images/logo.png`
2. **Banner email**: carica `images/bannermail.jpg` (larghezza consigliata 650px)
3. **Testi del form**: modifica `inc/formconf.inc.php`
4. **Colori**: modifica le variabili `--brand-*` in `css/donation.css`
5. **Privacy**: sostituisci il contenuto di `privacy/index.php` e dell'informativa breve
   in `inc/formconf.inc.php` con i testi della tua organizzazione

## 6. Gateway di pagamento

Attiva i gateway impostando nella tabella `config` (valori `1`/`0`):
`USE_PAYPAL`, `USE_SATISPAY`, `USE_GESTPAY`, `USE_STRIPE` — e `USE_SANDBOX` per l'ambiente di test.

| Gateway | Parametri richiesti (tabella `config`) |
|---|---|
| PayPal | `CLIENT_ID_PP`, `SECRET_ID_PP`, `PP_URLAPI`, `PP_REDIRECT` |
| Satispay | chiavi in `.env` (`SY_AUTH_*`), `SY_APIURL`, `SY_REDIRECT` |
| GestPay/Axerve | `GP_COD_ESE`, `GP_APIKEY`, `GP_URLAPI` |
| Stripe | `SP_PK_APIKEY`, `SP_SK_APIKEY`, `ST_URLAPI` *(integrazione parziale)* |

Configura gli URL di callback nel pannello del gateway:
- PayPal → `https://<dominio>/function/paypal.php`
- Satispay → `https://<dominio>/function/satispay.php`
- GestPay → `https://<dominio>/function/gestpay.php`

## 7. Email

Parametri nella tabella `config`: `MAIL_SMTP`, `FROM_MAIL`, `FROM_NAME` e `MAIL_PWD`
(la password SMTP è tra le chiavi sensibili: viene letta dal DB e mai scritta nella cache).
Il template dell'email di ringraziamento è `email/it/index.singola.html` e usa i token
`{{NOME}}`, `{{IMPORTO}}`, `{{ORG_NAME}}`, ecc.

In alternativa è supportato l'invio tramite piattaforma MagNews (`USE_MAGNEWS`).

## 8. Cron job (opzionali)

```cron
# Riconciliazione pagamenti Satispay in stato WA (ogni 10 minuti)
*/10 * * * * php /percorso/sito/function/cron_SY.php

# Log rotation (settimanale)
0 3 * * 0 php /percorso/sito/function/slr.php
```

## 9. Verifica

1. Apri `https://<dominio>/` — il form deve mostrare i gateway attivi
2. Con `USE_SANDBOX=1`, esegui una donazione di test per ogni gateway
3. Verifica la ricezione dell'email di ringraziamento e il QR in `img/qr/`
4. Accedi al backend `https://<dominio>/be/` (crea prima l'utente nella tabella `Utenti`)
