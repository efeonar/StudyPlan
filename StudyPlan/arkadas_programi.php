<?php
session_start();
include 'db.php';

// 1. GİRİŞ KONTROLÜ
if (!isset($_SESSION['user_id'])) {
    header("Location: giris.php");
    exit;
}

// 2. ID KONTROLÜ (URL'de ?id=1 gibi bir şey var mı?)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: anasayfa.php");
    exit;
}

$hedef_id = $_GET['id'];
$benim_id = $_SESSION['user_id'];

// 3. KULLANICI BİLGİLERİNİ VE GİZLİLİK AYARINI ÇEK
$k_sorgu = $db->prepare("SELECT kullanici_adi, profil_gizlilik FROM kullanicilar WHERE id = ?");
$k_sorgu->execute([$hedef_id]);
$hedef_user = $k_sorgu->fetch(PDO::FETCH_ASSOC);

if (!$hedef_user) {
    echo "<script>alert('Kullanıcı bulunamadı!'); window.location.href='anasayfa.php';</script>";
    exit;
}

$hedef_adi = $hedef_user['kullanici_adi'];
$gizlilik_durumu = $hedef_user['profil_gizlilik']; // 0: Açık, 1: Gizli

// --- 4. GÜVENLİK VE GİZLİLİK KONTROLLERİ ---

// Eğer kendi profilin değilse kontrol et
if ($hedef_id != $benim_id) {
    
    // A) Profil Gizli mi?
    if ($gizlilik_durumu == 1) {
        echo "<script>
            alert('Bu kullanıcı profilini gizli tutuyor! Görüntüleme yetkiniz yok.');
            window.location.href = 'anasayfa.php';
        </script>";
        exit;
    }

    // B) Arkadaş mıyız? (Status = 1 olmalı)
    $arkadas_mi = $db->prepare("SELECT * FROM friends WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) AND status = 1");
    $arkadas_mi->execute([$benim_id, $hedef_id, $hedef_id, $benim_id]);
    
    if ($arkadas_mi->rowCount() == 0) {
        echo "<script>
            alert('Bu kullanıcının programını görmek için arkadaş olmalısınız.');
            window.location.href = 'anasayfa.php';
        </script>";
        exit;
    }
}

// 5. DERS PROGRAMINI ÇEK (Sadece Görüntüleme)
$prog_sorgu = $db->prepare("SELECT * FROM ders_programlari WHERE kullanici_id = ? ORDER BY FIELD(gun, 'Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar')");
$prog_sorgu->execute([$hedef_id]);
$dersler = $prog_sorgu->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($hedef_adi); ?> - Ders Programı</title>
    
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Bu sayfaya özel ufak düzenlemeler */
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), #f39c12);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-md);
        }
        .profile-info h1 { margin: 0; font-size: 24px; }
        .profile-info p { margin: 5px 0 0; opacity: 0.9; }
        
        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            backdrop-filter: blur(5px);
            transition: 0.3s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.4); }

        table { width: 100%; border-collapse: separate; border-spacing: 0; background: var(--bg-surface); border-radius: 8px; overflow: hidden; }
        th { background: var(--bg-body); color: var(--text-muted); padding: 15px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid var(--border-color); }
        td { padding: 15px; border-bottom: 1px solid var(--border-color); color: var(--text-main); }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    
    <div class="sidebar">
        <h2 style="color:var(--primary-color);">StudyPlan</h2>
        
        <div style="margin-bottom: 20px; font-size: 14px; color: var(--text-muted);">
            Arkadaş Profili Görüntüleniyor
        </div>

        <nav style="display:flex; flex-direction:column; gap:5px;">
            <a href="anasayfa.php" style="color:white; padding:12px; border-radius:8px; text-decoration:none; background:rgba(255,255,255,0.1);">
                <i class="fa fa-arrow-left"></i> Geri Dön
            </a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="profile-header">
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($hedef_adi); ?></h1>
                <p>Kullanıcısının Çalışma Programı</p>
            </div>
            <a href="anasayfa.php" class="back-btn"><i class="fa fa-home"></i> Ana Sayfa</a>
        </div>

        <div class="card">
            <h3 style="margin-top:0; border-bottom:1px solid var(--border-color); padding-bottom:15px; color:var(--text-main);">Haftalık Program</h3>
            
            <?php if (count($dersler) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Gün</th>
                            <th>Ders Adı</th>
                            <th>Hedef Soru</th>
                            <th>Süre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dersler as $ders): ?>
                            <tr>
                                <td>
                                    <span style="color:var(--primary-color); font-weight:bold;">
                                        <?php echo htmlspecialchars($ders['gun']); ?>
                                    </span>
                                </td>
                                <td style="font-weight:500;"><?php echo htmlspecialchars($ders['ders_adi']); ?></td>
                                <td><?php echo $ders['hedef_soru']; ?> Soru</td>
                                <td><?php echo $ders['calisma_suresi']; ?> dk</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align:center; padding:50px; color:var(--text-muted);">
                    <i class="fa fa-calendar-times" style="font-size:40px; margin-bottom:15px; opacity:0.5;"></i>
                    <p>Bu arkadaşımız henüz bir ders programı oluşturmamış.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
    if(localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
    }
</script>

</body>
</html>