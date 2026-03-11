<?php
session_start();

// 1. Oturumu bitir
session_destroy();

// 2. Cookie'leri sil (Süresini geçmiş zamana ayarlayarak yok ediyoruz)
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, '/');
}
if (isset($_COOKIE['kadi'])) {
    setcookie('kadi', '', time() - 3600, '/');
}

// 3. Girişe yönlendir
header("Location: giris.php");
exit;
?>