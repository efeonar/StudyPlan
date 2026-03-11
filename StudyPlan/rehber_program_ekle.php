<?php
session_start();
include 'db.php';

// Güvenlik: Sadece Admin
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') { die("Yetkisiz erişim."); }
if (!isset($_GET['ogrenci_id'])) { die("Öğrenci seçilmedi."); }

$hedef_ogrenci_id = $_GET['ogrenci_id'];

// Öğrenci Adını Çek
$ogr_sorgu = $db->prepare("SELECT kullanici_adi FROM kullanicilar WHERE id = ?");
$ogr_sorgu->execute([$hedef_ogrenci_id]);
$ogrenci = $ogr_sorgu->fetch(PDO::FETCH_ASSOC);

if ($_POST) {
    $gun = $_POST['gun'];
    $ders = $_POST['ders_adi'];
    $konu = $_POST['konu']; // YENİ: Konu verisi
    $hedef = $_POST['hedef_soru'];
    $sure = $_POST['calisma_suresi'];

    // SQL güncellemesi: Konu eklendi
    $ekle = $db->prepare("INSERT INTO ders_programlari (kullanici_id, gun, ders_adi, konu, hedef_soru, calisma_suresi) VALUES (?, ?, ?, ?, ?, ?)");
    $ekle->execute([$hedef_ogrenci_id, $gun, $ders, $konu, $hedef, $sure]);

    // Bildirim Gönder
    $konu_mesaji = !empty($konu) ? "($konu)" : "";
    $msg = "Rehber öğretmenin senin için yeni bir ders ekledi: $ders $konu_mesaji";
    $db->prepare("INSERT INTO bildirimler (kullanici_id, mesaj, tur, okundu_mu) VALUES (?, ?, 'bilgi', 0)")->execute([$hedef_ogrenci_id, $msg]);

    echo "<script>alert('Program başarıyla eklendi!'); window.location.href='admin_panel.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Program Hazırla</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-wrapper">
        <h2>Program Hazırla</h2>
        <p>Seçilen Öğrenci: <strong><?php echo htmlspecialchars($ogrenci['kullanici_adi']); ?></strong></p>
        
        <form method="POST">
            <select name="gun" class="input-group" required>
                <option value="Pazartesi">Pazartesi</option>
                <option value="Salı">Salı</option>
                <option value="Çarşamba">Çarşamba</option>
                <option value="Perşembe">Perşembe</option>
                <option value="Cuma">Cuma</option>
                <option value="Cumartesi">Cumartesi</option>
                <option value="Pazar">Pazar</option>
            </select>
            
            <input type="text" name="ders_adi" placeholder="Ders Adı (Örn: Fizik)" class="input-group" required>
            
            <input type="text" name="konu" placeholder="Konu (Örn: Atışlar - Opsiyonel)" class="input-group">
            
            <input type="number" name="hedef_soru" placeholder="Hedef Soru Sayısı" class="input-group">
            <input type="number" name="calisma_suresi" placeholder="Çalışma Süresi (dk)" class="input-group">
            
            <button type="submit" class="btn-primary">Programa Ekle</button>
            <br><br>
            <a href="admin_panel.php" style="color:#aaa;">Geri Dön</a>
        </form>
    </div>
</body>
</html>