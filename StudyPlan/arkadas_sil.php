<<?php
session_start();
include 'db.php';

// Güvenlik kontrolleri
if (!isset($_SESSION['user_id'])) {
    header("Location: giris.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: anasayfa.php");
    exit;
}

$benim_id = $_SESSION['user_id'];
$silinecek_id = $_GET['id'];

try {
    // --- DÜZELTİLEN KISIM BURASI ---
    // İlişkiyi iki taraflı kontrol edip siliyoruz.
    // Ya ben eklemişimdir (user_id = ben) YA DA o beni eklemiştir (friend_id = ben).
    $sil = $db->prepare("DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
    $sonuc = $sil->execute([$benim_id, $silinecek_id, $silinecek_id, $benim_id]);

    if ($sonuc) {
        // İşlem başarılı, anasayfaya dön
        header("Location: anasayfa.php?msg=arkadas_silindi");
    } else {
        echo "Silme işlemi başarısız oldu.";
    }

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>