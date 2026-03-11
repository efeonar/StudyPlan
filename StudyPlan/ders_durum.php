<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { exit; }

$data = json_decode(file_get_contents('php://input'), true);

$ders_id = $data['id'];
$durum = $data['durum'];
$user_id = $_SESSION['user_id'];

// Verileri al
$dogru = isset($data['dogru']) ? intval($data['dogru']) : 0;
$yanlis = isset($data['yanlis']) ? intval($data['yanlis']) : 0;
$bos = isset($data['bos']) ? intval($data['bos']) : 0;

if ($durum == 1) {
    $bilgi = $db->prepare("SELECT ders_adi, calisma_suresi, hedef_soru FROM ders_programlari WHERE id = ?");
    $bilgi->execute([$ders_id]);
    $ders = $bilgi->fetch(PDO::FETCH_ASSOC);

    if ($ders) {
        // --- NET HESAPLAMA FORMÜLÜ ---
        // Net = Doğru - (Yanlış / 4)
        $net = $dogru - ($yanlis / 4);

        $ekle = $db->prepare("INSERT INTO ders_gecmisi (kullanici_id, ders_id, ders_adi, calisma_suresi, hedef_soru, dogru_sayisi, yanlis_sayisi, bos_sayisi, net_sayisi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ekle->execute([$user_id, $ders_id, $ders['ders_adi'], $ders['calisma_suresi'], $ders['hedef_soru'], $dogru, $yanlis, $bos, $net]);
    }
} else {
    $sil = $db->prepare("DELETE FROM ders_gecmisi WHERE kullanici_id = ? AND ders_id = ? AND DATE(tamamlanma_tarihi) = CURDATE()");
    $sil->execute([$user_id, $ders_id]);
}
?>