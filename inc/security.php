<?php
/**
 * Security utilities
 * - HMAC signature generation/validation (replaces simple MD5)
 * - Security headers
 * - Input sanitization
 */

/**
 * Generate HMAC-SHA256 signature
 * Retrocompatibile: se SALT_MAIL e definito, lo usa come chiave
 */
function generate_signature($data) {
    $salt = defined('SALT_MAIL') ? SALT_MAIL : '';
    return hash_hmac('sha256', $data, $salt);
}

/**
 * Verify HMAC-SHA256 signature.
 *
 * MD5 fallback: attivo finché HMAC_ONLY_MODE non è true nel DB/config.
 * Procedura per rimuoverlo: imposta HMAC_ONLY_MODE=1 nel DB una volta
 * certi che tutti i link firmati con MD5 (pre-migrazione) siano scaduti
 * o non più in circolazione.
 */
function verify_signature($data, $provided_signature) {
    $salt = defined('SALT_MAIL') ? SALT_MAIL : '';

    $expected_hmac = hash_hmac('sha256', $data, $salt);
    if (hash_equals($expected_hmac, $provided_signature)) {
        return true;
    }

    // Fallback MD5 — disabilitabile via HMAC_ONLY_MODE=1 in config DB
    if (!defined('HMAC_ONLY_MODE') || !HMAC_ONLY_MODE) {
        $expected_md5 = md5($data . $salt);
        if (hash_equals($expected_md5, $provided_signature)) {
            return true;
        }
    }

    return false;
}

/**
 * Send security headers
 */
function send_security_headers() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
