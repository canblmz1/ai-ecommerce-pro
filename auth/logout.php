<?php
require_once '../includes/functions.php';

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş yapmış mı kontrol et
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Güvenli çıkış işlemi
$_SESSION = array();

// Session cookie'sini sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session'ı sonlandır
session_destroy();

// Başarı mesajı ile giriş sayfasına yönlendir
header('Location: login.php?logout=success');
exit();
?>