<?php
// En üste autoloader'ı eklemek iyi bir pratiktir
require 'vendor/autoload.php'; 
include 'db.php';

$mesaj = "";

if ($_POST) {
    $kullanici = $_POST['kullanici_adi'];
    $email = $_POST['email'];
    // Şifre hashleme
    $sifre = password_hash($_POST['sifre'], PASSWORD_DEFAULT);

    try {
        // SQL Sorgusu
        $query = $db->prepare("INSERT INTO kullanicilar (kullanici_adi, email, sifre) VALUES (?, ?, ?)");
        $ekle = $query->execute([$kullanici, $email, $sifre]);

        if ($ekle) {
            // --- MAİL GÖNDERME İŞLEMİ BAŞLIYOR ---
            
            // 1. Mail dosyasına gidecek değişkenleri hazırla
            $gonderilecek_email = $email;
            $gonderilecek_isim  = $kullanici;

            // 2. Mail dosyasını çağır (include, oradaki kodları buraya yapıştırmış gibi çalışır)
            include 'mail_gonder.php';

            // --- MAİL İŞLEMİ BİTTİ ---

            $mesaj = "<div class='alert alert-success'>Kayıt başarılı! Yönlendiriliyorsunuz...</div>";
            header("Refresh: 2; url=giris.php");
        }
    } catch (PDOException $e) {
        // Hata kodunu kontrol et (Duplicate entry hatası genelde 23000 kodudur)
        if ($e->getCode() == 23000) {
             $mesaj = "<div class='alert alert-error'>Bu kullanıcı adı veya email zaten kayıtlı.</div>";
        } else {
             $mesaj = "<div class='alert alert-error'>Bir hata oluştu: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol - StudyPlan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1 style="color:var(--primary-color);">Aramıza Katıl</h1>
            <?php echo $mesaj; ?>
            <form method="post">
                <input type="text" name="kullanici_adi" placeholder="Kullanıcı Adı" required>
                <input type="email" name="email" placeholder="E-posta Adresi" required>
                <input type="password" name="sifre" placeholder="Şifre" required>
                <button type="submit">Üye Ol</button>
            </form>
            <p style="margin-top:15px; font-size:14px;">
                Zaten üye misin? <a href="giris.php">Giriş Yap</a>
            </p>
        </div>
    </div>
</body>
</html>