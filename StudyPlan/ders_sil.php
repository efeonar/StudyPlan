<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: anasayfa.php");
    exit;
}

$ders_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 1. Önce bu dersin GEÇMİŞTEKİ tüm kayıtlarını temizle (Senin istediğin özellik)
$sil_gecmis = $db->prepare("DELETE FROM ders_gecmisi WHERE ders_id = ? AND kullanici_id = ?");
$sil_gecmis->execute([$ders_id, $user_id]);

// 2. Sonra dersin kendisini programdan sil
$sil_program = $db->prepare("DELETE FROM ders_programlari WHERE id = ? AND kullanici_id = ?");
$sil_program->execute([$ders_id, $user_id]);

header("Location: anasayfa.php");
exit;
?>