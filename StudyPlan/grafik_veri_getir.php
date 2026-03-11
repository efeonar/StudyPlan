<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { 
    echo json_encode([]); 
    exit; 
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$filtre = isset($input['filtre']) ? $input['filtre'] : 'hafta'; // Varsayılan: Hafta

// SQL Sorgusunu Başlat
$sql = "SELECT DATE(tamamlanma_tarihi) as tarih, 
               SUM(dogru_sayisi) as toplam_dogru, 
               SUM(yanlis_sayisi) as toplam_yanlis, 
               SUM(bos_sayisi) as toplam_bos 
        FROM ders_gecmisi 
        WHERE kullanici_id = ?";

$params = [$user_id];

// --- FİLTRELEME MANTIĞI ---
if ($filtre == 'hafta') {
    // Bu hafta (Pazartesi'den Pazara)
    $sql .= " AND YEARWEEK(tamamlanma_tarihi, 1) = YEARWEEK(CURDATE(), 1)";
} 
elseif ($filtre == 'ay') {
    // Bu ay
    $sql .= " AND MONTH(tamamlanma_tarihi) = MONTH(CURDATE()) AND YEAR(tamamlanma_tarihi) = YEAR(CURDATE())";
} 
elseif ($filtre == 'yil') {
    // Bu yıl
    $sql .= " AND YEAR(tamamlanma_tarihi) = YEAR(CURDATE())";
} 
elseif ($filtre == 'ozel') {
    // Özel Tarih Aralığı
    if (!empty($input['baslangic']) && !empty($input['bitis'])) {
        $sql .= " AND DATE(tamamlanma_tarihi) BETWEEN ? AND ?";
        $params[] = $input['baslangic'];
        $params[] = $input['bitis'];
    }
}

// Tarihe göre grupla (Her günün toplamını al)
$sql .= " GROUP BY DATE(tamamlanma_tarihi) ORDER BY tarih ASC";

$sorgu = $db->prepare($sql);
$sorgu->execute($params);
$sonuclar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($sonuclar);
?>