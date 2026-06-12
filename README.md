# Donation Platform

**[EN]** An open-source, white-label donation platform for non-profits, written in PHP.
Born for a real fundraising campaign (originally developed for Emergency NGO, then adapted
for the Wineraising committee), now released as a generic, brandable platform.
Documentation is currently in Italian — contributions welcome.

---

Piattaforma di donazioni **open source e white label** per organizzazioni non profit, scritta in PHP.
Nata per campagne di raccolta fondi reali, oggi rilasciata come piattaforma generica e personalizzabile.

## Funzionalità

- **Form di donazione** a step (anagrafica → metodo di pagamento → importo) con validazione client e server
- **Gateway di pagamento**: PayPal (Checkout API), Satispay (GBusiness API), GestPay/Axerve (carte di credito), Stripe (parziale)
- **Email transazionali** di ringraziamento con ricevuta e QR code (PHPMailer via SMTP, oppure MagNews)
- **Ticket con QR code**: ogni donazione può generare un lasciapassare con QR verificabile (utile per eventi di raccolta fondi)
- **Backend amministrativo** (`be/`): gestione donazioni singole e regolari, ricerche, esportazione XLS, re-invio email, gestione utenti con bcrypt + CSRF + rate limiting, gestione partner, rimborsi Satispay
- **Cron job**: riconciliazione pagamenti Satispay, invio promemoria, log rotation
- **Configurazione centralizzata**: tabella `config` su DB (con cache) + credenziali in `.env`
- **Multi-istanza**: la colonna `Form` della tabella `config` permette più configurazioni sulla stessa base dati (es. produzione/sviluppo)

## White label

Tutta l'identità dell'organizzazione è configurabile senza toccare il codice:

| Costante | Dove si imposta | Descrizione |
|---|---|---|
| `ORG_NAME` | `.env` o tabella `config` | Nome dell'organizzazione |
| `ORG_EMAIL` | `.env` o tabella `config` | Email di contatto/supporto |
| `ORG_NOREPLY` | `.env` o tabella `config` | Mittente delle email automatiche |
| `ORG_PRIVACY_EMAIL` | `.env` o tabella `config` | Contatto privacy/GDPR |
| `ORG_WEBSITE` | `.env` o tabella `config` | Sito istituzionale |
| `ORG_PRIVACY_URL` | `.env` o tabella `config` | URL della privacy policy |

I testi del form (titoli, etichette, importi suggeriti, messaggi di errore) si personalizzano in
[inc/formconf.inc.php](inc/formconf.inc.php). I colori del tema in [css/donation.css](css/donation.css)
(variabili CSS `--brand-*`). Logo e immagini in `images/`.

**Da personalizzare obbligatoriamente prima di andare in produzione:**

1. `images/logo.png` — logo dell'organizzazione
2. `images/bannermail.jpg` — banner dell'email di ringraziamento
3. `privacy/index.php` — informativa privacy completa (il file incluso è un segnaposto)
4. Informativa breve in `inc/formconf.inc.php` (`info_privacy`)

## Requisiti

- PHP >= 7.4 con estensioni `mysqli`, `curl`, `gd`, `mbstring`, `phar`
- MySQL / MariaDB
- Composer
- Un server SMTP per le email transazionali

## Installazione

Vedi [docs/INSTALL.md](docs/INSTALL.md). In sintesi:

```bash
git clone <repo>
composer install
cp .env.example .env       # e compila i valori
# crea il database e importa docs/schema.sql
# carica i parametri nella tabella `config`
```

## Struttura del progetto

```
index.php            Pagina di donazione (white label)
grazie.php           Thank you page (riepilogo + commento)
errore.php           Pagina di errore pagamento
ticket.php           Visualizzazione/verifica ticket con QR code
function/            Web service donazioni e integrazioni
  donation_WS.php    Endpoint principale (riceve il form, avvia la transazione)
  paypal.php         Callback PayPal
  satispay.php       Callback Satispay
  gestpay.php        Callback GestPay/Axerve
  mail.php           Invio email di ringraziamento + QR
  cron_SY.php        Cron riconciliazione Satispay
  inc/               Librerie di integrazione (paypal, satispay, gestpay, stripe, mentor, magnews, mysql)
inc/                 Configurazione, sicurezza, testi del form
be/                  Backend amministrativo
email/               Template email transazionali
docs/                Documentazione e schema DB
```

## Roadmap

- [ ] Integrazione **Stripe** completa (Payment Intents + webhook)
- [ ] Integrazione **Nexi** (XPay)
- [ ] Integrazione con le **nuove API di Mentor** (Direct Channel) — le API usate in `function/inc/functions_mentor.php` non sono più disponibili
- [ ] Integrazione **CiviCRM**
- [ ] Migrazione del codice legacy a PDO/prepared statements ovunque
- [ ] Internazionalizzazione dei testi (oggi solo italiano)

## Sicurezza

- Le credenziali vivono **solo** in `.env` (mai committato) e nelle chiavi sensibili della tabella `config`
- Il backend usa password bcrypt, token CSRF e rate limiting sul login
- Segnalazioni di vulnerabilità: apri una issue privata o scrivi al maintainer

## Licenza

Rilasciato sotto licenza [MIT](LICENSE).
