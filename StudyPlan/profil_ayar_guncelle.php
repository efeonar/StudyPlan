<?php
session_start();
include 'db.php';

// Güvenlik: Giriş yapmamışsa durdur
if (!isset($_SESSION['user_id'])) { exit; }

if (isset($_POST['gizlilik'])) {
    $durum = $_POST['gizlilik']; // JS'den '1' veya '0' gelir
    $user_id = $_SESSION['user_id'];

    // Veritabanını güncelle
    $guncelle = $db->prepare("UPDATE kullanicilar SET profil_gizlilik = ? WHERE id = ?");
    $guncelle->execute([$durum, $user_id]);
    
    echo "success";
}
?>