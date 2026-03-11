<?php
session_start();
include 'db.php';

// Güvenlik: Admin kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') { die("Bu sayfaya erişim yetkiniz yok."); }
if (!isset($_GET['ogrenci_id'])) { header("Location: admin_panel.php"); exit; }

$hedef_id = $_GET['ogrenci_id'];

// Öğrenci bilgilerini çek
$k_sorgu = $db->prepare("SELECT kullanici_adi, profil_resmi FROM kullanicilar WHERE id = ?");
$k_sorgu->execute([$hedef_id]);
$ogrenci = $k_sorgu->fetch(PDO::FETCH_ASSOC);

if (!$ogrenci) { die("Öğrenci bulunamadı."); }

// Dersleri çek
$ders_programi = $db->prepare("SELECT * FROM ders_programlari WHERE kullanici_id = ? ORDER BY FIELD(gun, 'Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar')");
$ders_programi->execute([$hedef_id]);
$dersler = $ders_programi->fetchAll(PDO::FETCH_ASSOC);

// Silme İşlemi
if (isset($_GET['sil_id'])) {
    $sil_id = $_GET['sil_id'];
    $sil = $db->prepare("DELETE FROM ders_programlari WHERE id = ?");
    $sil->execute([$sil_id]);
    header("Location: rehber_ogrenci_detay.php?ogrenci_id=" . $hedef_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $ogrenci['kullanici_adi']; ?> - Programı</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <style>
        .admin-view-wrapper { max-width: 900px; margin: 30px auto; background: var(--bg-surface); padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; color: var(--text-main); }
        th, td { padding: 12px; border-bottom: 1px solid var(--border-color); text-align: left; }
        th { background: rgba(255,255,255,0.05); }
        .tag { padding: 4px 8px; border-radius: 4px; font-size: 12px; background: rgba(255,255,255,0.1); }
        .topic-tag { font-size: 12px; color: #aaa; font-style: italic; display: block; margin-top: 4px; }
        
        /* Sekme Stilleri (Admin Paneli İçin) */
        .tab-container { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px; }
        .tab-btn { background: none; border: none; color: var(--text-muted); font-size: 15px; cursor: pointer; padding: 10px 15px; font-weight: bold; transition: 0.3s; }
        .tab-btn.active { color: var(--primary-color); border-bottom: 2px solid var(--primary-color); margin-bottom: -12px; }
        .tab-content { display: none; animation: fadeIn 0.3s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>

<div class="admin-view-wrapper">
    
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div style="display:flex; align-items:center; gap:15px;">
            <?php if(!empty($ogrenci['profil_resmi'])): ?>
                <img src="uploads/<?php echo $ogrenci['profil_resmi']; ?>" style="width:50px; height:50px; border-radius:50%; object-fit:cover;">
            <?php else: ?>
                <div style="width:50px; height:50px; background:#e66e00; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold;">
                    <?php echo strtoupper(mb_substr($ogrenci['kullanici_adi'],0,1)); ?>
                </div>
            <?php endif; ?>
            
            <div>
                <h2 style="margin:0; color:var(--text-main);"><?php echo htmlspecialchars($ogrenci['kullanici_adi']); ?></h2>
                <span style="color:var(--text-muted); font-size:13px;">Öğrenci Yönetimi</span>
            </div>
        </div>

        <div style="display:flex; gap:10px;">
            <a href="rehber_program_ekle.php?ogrenci_id=<?php echo $hedef_id; ?>" class="btn-primary" style="font-size:13px;">+ Ders Ekle</a>
            <a href="admin_panel.php" style="padding:10px; color:var(--text-muted); text-decoration:none;">Geri Dön</a>
        </div>
    </div>

    <hr style="border:0; border-top:1px solid var(--border-color); margin:20px 0;">

    <div class="tab-container">
        <button class="tab-btn active" onclick="openAdminTab('tab-program')">📅 Haftalık Program</button>
        <button class="tab-btn" onclick="openAdminTab('tab-gecmis')">✅ Tamamlananlar (Geçmiş)</button>
    </div>

    <div id="tab-program" class="tab-content active">
        <?php if (count($dersler) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Gün</th>
                        <th>Ders / Konu</th>
                        <th>Hedef / Süre</th>
                        <th style="text-align:right;">Yönet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dersler as $ders): ?>
                        <tr>
                            <td style="color:var(--primary-color); font-weight:600;"><?php echo htmlspecialchars($ders['gun']); ?></td>
                            <td>
                                <span style="font-weight:500; font-size:15px;"><?php echo htmlspecialchars($ders['ders_adi']); ?></span>
                                <?php if(!empty($ders['konu'])): ?>
                                    <span class="topic-tag">📌 <?php echo htmlspecialchars($ders['konu']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="tag"><?php echo $ders['hedef_soru']; ?> Soru</span> 
                                <span class="tag"><?php echo $ders['calisma_suresi']; ?> dk</span>
                            </td>
                            <td style="text-align:right;">
                                <a href="rehber_ogrenci_detay.php?ogrenci_id=<?php echo $hedef_id; ?>&sil_id=<?php echo $ders['id']; ?>" 
                                   style="color:#e74c3c; text-decoration:none; font-size:20px;"
                                   onclick="return confirm('Silmek istiyor musun?')">🗑</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center; padding:30px; color:var(--text-muted);">Henüz ders eklenmemiş.</p>
        <?php endif; ?>
    </div>

    <div id="tab-gecmis" class="tab-content">
        <div style="background:var(--bg-body); padding:15px; border-radius:8px; display:flex; justify-content:center; gap:10px; margin-bottom:20px; border:1px solid var(--border-color);">
            <input type="date" id="gecmis-tarih" onchange="adminGecmisGetir()" style="padding:5px; border-radius:5px; border:1px solid #ccc;">
            <button onclick="adminSifirlaGetir()" style="background:#666; color:white; border:none; padding:8px 15px; border-radius:6px; cursor:pointer;">Tümünü Göster</button>
        </div>
        
        <div id="gecmis-sonuclar" style="min-height:100px; text-align:center; color:#888;">
            Yükleniyor...
        </div>
    </div>

</div>

<script>
    // Sekme Değiştirme
    function openAdminTab(tabId) {
        var contents = document.getElementsByClassName('tab-content');
        for(var i=0; i<contents.length; i++) contents[i].classList.remove('active');
        
        var btns = document.getElementsByClassName('tab-btn');
        for(var i=0; i<btns.length; i++) btns[i].classList.remove('active');
        
        document.getElementById(tabId).classList.add('active');
        event.currentTarget.classList.add('active');

        // Eğer Geçmiş sekmesi açıldıysa verileri çek
        if(tabId === 'tab-gecmis') {
            adminGecmisGetir();
        }
    }

    // Geçmiş Verileri Çekme (Admin Özel)
    function adminGecmisGetir() {
        var tarih = document.getElementById('gecmis-tarih').value;
        var ogrenciId = <?php echo $hedef_id; ?>; // PHP'den gelen ID

        fetch('gecmis_getir.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                tarih: tarih,
                ogrenci_id: ogrenciId // Admin olduğumuz için bu ID'yi gönderiyoruz
            })
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('gecmis-sonuclar').innerHTML = html;
        });
    }

    function adminSifirlaGetir() {
        document.getElementById('gecmis-tarih').value = "";
        adminGecmisGetir();
    }
</script>

</body>
</html>