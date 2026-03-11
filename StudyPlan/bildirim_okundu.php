<?php
session_start();
include 'db.php';

// Güvenlik: Giriş yapmamışsa dur
if (!isset($_SESSION['user_id'])) { exit; }

$user_id = $_SESSION['user_id'];

// Kullanıcının TÜM bildirimlerini 'okundu' (1) yap
$guncelle = $db->prepare("UPDATE bildirimler SET okundu_mu = 1 WHERE kullanici_id = ?");
$guncelle->execute([$user_id]);

echo "success";
?>