<?php
session_start();
include 'db.php';

// Güvenlik
if (!isset($_SESSION['user_id'])) { header("Location: giris.php"); exit; }

if ($_POST) {
    $gun = $_POST['gun'];
    $ders = $_POST['ders_adi'];
    $konu = $_POST['konu']; // YENİ: Konu verisini alıyoruz
    $hedef = $_POST['hedef_soru'];
    $sure = $_POST['calisma_suresi'];
    $user_id = $_SESSION['user_id'];

    // SQL sorgusuna 'konu' alanını ekledik
    $ekle = $db->prepare("INSERT INTO ders_programlari (kullanici_id, gun, ders_adi, konu, hedef_soru, calisma_suresi) VALUES (?, ?, ?, ?, ?, ?)");
    $ekle->execute([$user_id, $gun, $ders, $konu, $hedef, $sure]);

    header("Location: anasayfa.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ders Ekle</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-wrapper">
        <h2>Ders Programı Ekle</h2>
        <form method="POST">
            <select name="gun" class="input-group">
                <option value="Pazartesi">Pazartesi</option>
                <option value="Salı">Salı</option>
                <option value="Çarşamba">Çarşamba</option>
                <option value="Perşembe">Perşembe</option>
                <option value="Cuma">Cuma</option>
                <option value="Cumartesi">Cumartesi</option>
                <option value="Pazar">Pazar</option>
            </select>
            
            <input type="text" name="ders_adi" placeholder="Ders Adı (Örn: Matematik)" class="input-group" required>
            
            <input type="text" name="konu" placeholder="Konu (Örn: Türev, Opsiyonel)" class="input-group">
            
            <input type="number" name="hedef_soru" placeholder="Hedef Soru Sayısı" class="input-group">
            <input type="number" name="calisma_suresi" placeholder="Çalışma Süresi (dk)" class="input-group">
            
            <button type="submit" class="btn-primary">Ekle</button>
            <br><br>
            <a href="anasayfa.php" style="color:#aaa;">Geri Dön</a>
        </form>
    </div>
</body>
</html>