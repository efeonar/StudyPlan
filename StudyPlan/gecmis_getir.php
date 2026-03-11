<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { exit; }

$data = json_decode(file_get_contents('php://input'), true);
$tarih = isset($data['tarih']) ? $data['tarih'] : '';

$hedef_user_id = $_SESSION['user_id']; 
if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin' && isset($data['ogrenci_id'])) {
    $hedef_user_id = $data['ogrenci_id'];
}

$sql = "SELECT * FROM ders_gecmisi WHERE kullanici_id = ?";
$params = [$hedef_user_id];

if (!empty($tarih)) {
    $sql .= " AND DATE(tamamlanma_tarihi) = ?";
    $params[] = $tarih;
}

$sql .= " ORDER BY tamamlanma_tarihi DESC LIMIT 50";

$sorgu = $db->prepare($sql);
$sorgu->execute($params);
$sonuclar = $sorgu->fetchAll(PDO::FETCH_ASSOC);

if (count($sonuclar) > 0) {
    echo '<table style="width:100%; border-collapse:collapse; margin-top:10px;">';
    echo '<thead>
            <tr style="background:rgba(255,255,255,0.05); text-align:left;">
                <th style="padding:10px;">Tarih</th>
                <th style="padding:10px;">Ders</th>
                <th style="padding:10px;">Sonuç Detayı</th>
                <th style="padding:10px;">NET</th>
            </tr>
          </thead><tbody>';
    
    foreach ($sonuclar as $satir) {
        $tarih_tr = date("d.m.Y H:i", strtotime($satir['tamamlanma_tarihi']));
        $bos = isset($satir['bos_sayisi']) ? $satir['bos_sayisi'] : 0;
        
        // Net Sayısı (Veritabanından gelen veya hesaplanan)
        $net = isset($satir['net_sayisi']) ? $satir['net_sayisi'] : ($satir['dogru_sayisi'] - ($satir['yanlis_sayisi'] / 4));

        echo '<tr style="border-bottom:1px solid var(--border-color);">';
        echo '<td style="padding:10px; color:var(--text-muted); font-size:13px;">' . $tarih_tr . '</td>';
        echo '<td style="padding:10px; color:var(--text-main); font-weight:500;">' . htmlspecialchars($satir['ders_adi']) . '</td>';
        
        // Detaylar
        echo '<td style="padding:10px; font-size:12px;">
                <span style="color:#27ae60;">D: ' . $satir['dogru_sayisi'] . '</span> / 
                <span style="color:#e74c3c;">Y: ' . $satir['yanlis_sayisi'] . '</span> / 
                <span style="color:#7f8c8d;">B: ' . $bos . '</span>
              </td>';
              
        // NET GÖSTERİMİ (Kalın ve Renkli)
        echo '<td style="padding:10px; font-weight:bold; color:var(--primary-color); font-size:14px;">
                ' . number_format($net, 2) . ' Net
              </td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<div style="padding:20px; text-align:center; color:#888;">Bu tarih için kayıt bulunamadı.</div>';
}
?>