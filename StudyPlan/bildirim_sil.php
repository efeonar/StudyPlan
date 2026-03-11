<?php
session_start();
include 'db.php';

// Güvenlik: Giriş yapmamışsa işlem yapma
if (!isset($_SESSION['user_id'])) { exit; }

$user_id = $_SESSION['user_id'];

// Kullanıcının TÜM bildirimlerini sil
$sil = $db->prepare("DELETE FROM bildirimler WHERE kullanici_id = ?");
$sil->execute([$user_id]);

echo "success";
?>