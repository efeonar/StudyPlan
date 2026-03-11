<?php
session_start();
include 'db.php';

// Güvenlik Kontrolleri
if (!isset($_SESSION['user_id'])) { header("Location: giris.php"); exit; }
if (!isset($_GET['id']) || !isset($_GET['islem'])) { header("Location: anasayfa.php"); exit; }

$bildirim_id = $_GET['id'];
$islem = $_GET['islem'];
$user_id = $_SESSION['user_id'];

// --- 1. İSTEĞİ GÖNDEREN KİŞİYİ BUL (Mesajı güncellemek için) ---
// Not: Sistemimizde "toplu onay" mantığı olduğu için bekleyen herkesin ismini alıyoruz.
$isimler = [];
$sorgu = $db->prepare("SELECT k.kullanici_adi FROM friends f JOIN kullanicilar k ON f.user_id = k.id WHERE f.friend_id = ? AND f.status = 0");
$sorgu->execute([$user_id]);
while($row = $sorgu->fetch(PDO::FETCH_ASSOC)) {
    $isimler[] = $row['kullanici_adi'];
}
// İsimleri virgülle birleştir (Örn: "Ahmet, Mehmet")
$kisi_listesi = implode(", ", $isimler);
if(empty($kisi_listesi)) { $kisi_listesi = "İstek"; } // Eğer isim bulamazsa genel yazar

// --- 2. İŞLEMİ YAP VE YENİ MESAJI BELİRLE ---
$yeni_mesaj = "";

if ($islem == 'kabul') {
    // Arkadaşlığı Onayla (Status = 1)
    $db->prepare("UPDATE friends SET status = 1 WHERE friend_id = ? AND status = 0")->execute([$user_id]);
    
    // Kalıcı olacak yeni bildirim mesajı
    $yeni_mesaj = "<strong>$kisi_listesi</strong> ile arkadaş oldunuz. 🎉";
} 
elseif ($islem == 'ret') {
    // İsteği Sil
    $db->prepare("DELETE FROM friends WHERE friend_id = ? AND status = 0")->execute([$user_id]);
    
    // Kalıcı olacak yeni bildirim mesajı
    $yeni_mesaj = "<strong>$kisi_listesi</strong> kişisinin isteğini reddettiniz.";
}

// --- 3. BİLDİRİMİ GÜNCELLE (KRİTİK NOKTA) ---
// Bildirimi silmek yerine; Türünü 'bilgi' yapıyoruz (Butonlar gidiyor) ve Mesajını değiştiriyoruz.
$guncelle = $db->prepare("UPDATE bildirimler SET mesaj = ?, tur = 'bilgi', okundu_mu = 1 WHERE id = ?");
$guncelle->execute([$yeni_mesaj, $bildirim_id]);

// --- 4. GERİ DÖN ---
header("Location: anasayfa.php");
exit;
?>