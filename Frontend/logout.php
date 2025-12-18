<?php

/**
 * Logout Standar - Membersihkan semua data session
 */
require_once dirname(__DIR__) . '/Backend/config.php';

// Hapus semua data di $_SESSION
$_SESSION = array();

// Jika menggunakan cookie session, hapus juga cookienya
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Kembali ke login
header("Location: login.php");
exit();
