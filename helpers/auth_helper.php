<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Verifica si el usuario está logueado
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}

/**
 * Inicia sesión para un usuario
 * @param int $user_id
 * @param string $username
 */
function login($user_id, $username) {
    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['last_activity'] = time();
}

/**
 * Cierra la sesión del usuario actual
 */
function logout() {
    session_unset();
    session_destroy();
}

/**
 * Verifica si la sesión ha expirado
 */
function checkSessionExpiration() {
    $expiration_time = 30 * 60; // 30 minutos
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $expiration_time)) {
        logout();
        header("Location: login.php?expired=1");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Redirige al usuario si no está logueado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
    checkSessionExpiration();
}

/**
 * Genera un token CSRF
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica el token CSRF
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Sanitiza la entrada del usuario
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}