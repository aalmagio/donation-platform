<?php
/**
 * CSRF Token Protection
 * Generates and validates tokens to prevent Cross-Site Request Forgery
 */

function csrf_token() {
    if (!isset($_SESSION)) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_validate($token) {
    if (!isset($_SESSION)) {
        session_start();
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_regenerate() {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
