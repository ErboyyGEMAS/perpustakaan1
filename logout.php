<?php
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Hapus session cookie. Ini akan menghancurkan session,
// dan memaksa browser untuk menghapus session ID.
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

// Arahkan kembali ke halaman login atau beranda
header("Location: login.php");
exit;
