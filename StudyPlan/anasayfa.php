<?php
session_start();
include 'db.php';

// 1. GÜVENLİK KONTROLÜ
if (!isset($_SESSION['user_id'])) { header("Location: giris.php"); exit; }

$user_id = $_SESSION['user_id'];
$toast_script = ""; 

// --- KULLANICI BİLGİLERİNİ ÇEK ---
$k_sorgu = $db->prepare("SELECT kullanici_adi, profil_gizlilik, profil_resmi FROM kullanicilar WHERE id = ?");
$k_sorgu->execute([$user_id]);
$kullanici_bilgisi = $k_sorgu->fetch(PDO::FETCH_ASSOC);

$kadi = $kullanici_bilgisi['kullanici_adi'];
$gizlilik_durumu = $kullanici_bilgisi['profil_gizlilik'];
$profil_resmi = $kullanici_bilgisi['profil_resmi']; 

// --- ARKADAŞ İSTEĞİ İŞLEMLERİ ---
if (isset($_GET['arkadas_ekle_id']) && !empty($_GET['arkadas_ekle_id'])) {
    $hedef_id = $_GET['arkadas_ekle_id'];
    
    if ($hedef_id == $user_id) {
        $toast_script = "Toastify({ text: 'Kendini arkadaş ekleyemezsin!', style: { background: '#e74c3c' } }).showToast();";
    } else {
        $kontrol = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
        $kontrol->execute([$hedef_id]);
        
        if ($kontrol->fetch()) {
            $arkadas_mi = $db->prepare("SELECT * FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $arkadas_mi->execute([$user_id, $hedef_id, $hedef_id, $user_id]);

            if ($arkadas_mi->rowCount() == 0) {
                $ekle = $db->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 0)");
                if ($ekle->execute([$user_id, $hedef_id])) {
                    $msg = "<strong>" . $kadi . "</strong> sana arkadaşlık isteği gönderdi.";
                    $bildirim_ekle = $db->prepare("INSERT INTO bildirimler (kullanici_id, mesaj, tur, okundu_mu) VALUES (?, ?, 'arkadas_istegi', 0)");
                    $bildirim_ekle->execute([$hedef_id, $msg]);
                    $toast_script = "Toastify({ text: 'İstek gönderildi!', style: { background: 'linear-gradient(to right, #00b09b, #96c93d)' } }).showToast();";
                }
            } else {
                $toast_script = "Toastify({ text: 'Zaten ekli veya istek var.', style: { background: '#f39c12' } }).showToast();";
            }
        } else {
            $toast_script = "Toastify({ text: 'Kullanıcı bulunamadı.', style: { background: '#e74c3c' } }).showToast();";
        }
    }
}

// --- VERİLERİ ÇEK ---
$b_sayi = $db->prepare("SELECT COUNT(*) FROM bildirimler WHERE kullanici_id = ? AND okundu_mu = 0");
$b_sayi->execute([$user_id]);
$bildirim_sayisi = $b_sayi->fetchColumn();

$b_liste = $db->prepare("SELECT * FROM bildirimler WHERE kullanici_id = ? ORDER BY tarih DESC LIMIT 10");
$b_liste->execute([$user_id]);
$bildirimler = $b_liste->fetchAll(PDO::FETCH_ASSOC);

$arkadaslar = $db->prepare("SELECT u.id, u.kullanici_adi FROM friends f JOIN kullanicilar u ON (u.id = f.friend_id OR u.id = f.user_id) WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 1 AND u.id != ?");
$arkadaslar->execute([$user_id, $user_id, $user_id]);
$arkadas_listesi = $arkadaslar->fetchAll(PDO::FETCH_ASSOC);

$ders_programi = $db->prepare("SELECT * FROM ders_programlari WHERE kullanici_id = ? ORDER BY FIELD(gun, 'Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar')");
$ders_programi->execute([$user_id]);
$dersler = $ders_programi->fetchAll(PDO::FETCH_ASSOC);

$tamamlananlar = $db->prepare("SELECT ders_id FROM ders_gecmisi WHERE kullanici_id = ? AND YEARWEEK(tamamlanma_tarihi, 1) = YEARWEEK(NOW(), 1)");
$tamamlananlar->execute([$user_id]);
$tamamlanan_listesi = $tamamlananlar->fetchAll(PDO::FETCH_COLUMN);

if(isset($_GET['msg']) && $_GET['msg']=='resim_yuklendi') $toast_script = "Toastify({ text: 'Profil fotoğrafı güncellendi!', style: { background: '#27ae60' } }).showToast();";
if(isset($_GET['error']) && $_GET['error']=='dosya_cok_buyuk') $toast_script = "Toastify({ text: 'Dosya çok büyük! (Max 2MB)', style: { background: '#e74c3c' } }).showToast();";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Anasayfa - StudyPlan</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* --- GEREKLİ STİLLER --- */
        #focus-overlay-bg { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(0, 0, 0, 0.95); z-index: 9998; backdrop-filter: blur(5px); }
        body.focus-mode-active { overflow: hidden !important; }
        .card.focus-mode-card { position: fixed !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; z-index: 9999 !important; margin: 0 !important; width: 90% !important; max-width: 500px !important; background-color: #1a1a1a !important; border: 2px solid #27ae60 !important; box-shadow: 0 0 50px rgba(39, 174, 96, 0.5) !important; color: white !important; border-radius: 20px !important; }
        .focus-mode-card h2 { color: #ffffff !important; }
        .focus-mode-card span, .focus-mode-card p { color: #cccccc !important; }
        .focus-mode-card #timer-display { color: #27ae60 !important; font-size: 80px !important; text-shadow: 0 0 20px rgba(39, 174, 96, 0.6) !important; }
        .focus-mode-card #exit-focus-btn { display: inline-block !important; margin-top: 20px; background: transparent; border: 1px solid #777; color: #aaa; padding: 8px 20px; border-radius: 30px; cursor: pointer; }
        .focus-mode-card #exit-focus-btn:hover { border-color: #fff; color: #fff; }
        
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .notification-area { position: relative; cursor: pointer; margin-right: 10px; }
        .notif-bell { position: relative; padding: 10px; background: var(--bg-surface); border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition:0.3s;}
        .notif-bell:hover { transform: scale(1.05); }
        .notif-badge { position: absolute; top: -2px; right: -2px; background: #e74c3c; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; border: 2px solid var(--bg-body); }
        .notif-dropdown { display: none; position: absolute; right: 0; top: 50px; width: 320px; background: var(--bg-surface); border: 1px solid var(--border-color); box-shadow: 0 10px 40px rgba(0,0,0,0.2); border-radius: 12px; z-index: 10000; overflow: hidden; }
        .notif-dropdown.aktif { display: block; }
        
        /* GÜNCELLENEN BİLDİRİM BAŞLIĞI */
        .notif-header { padding: 12px 15px; border-bottom: 1px solid var(--border-color); font-weight: bold; font-size: 14px; background: var(--bg-body); color: var(--text-main); display: flex; justify-content: space-between; align-items: center; }
        .clear-notifs-btn { font-size: 11px; color: #e74c3c; cursor: pointer; text-decoration: none; border: 1px solid #e74c3c; padding: 2px 6px; border-radius: 4px; transition: 0.2s; }
        .clear-notifs-btn:hover { background: #e74c3c; color: white; }

        .notif-list { max-height: 300px; overflow-y: auto; }
        .notif-item { padding: 12px 15px; border-bottom: 1px solid var(--border-color); font-size: 13px; color: var(--text-main); display: flex; flex-direction: column; gap: 5px; }
        .notif-item.unread { background-color: rgba(255, 122, 0, 0.08); border-left: 3px solid var(--primary-color); }
        .notif-actions { display: flex; gap: 8px; margin-top: 5px; }
        .btn-approve { background: #27ae60; color: white; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 11px; }
        .btn-reject { background: #e74c3c; color: white; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 11px; }

        .profile-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin: 0 auto 10px; display: block; border: 3px solid var(--primary-color); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .profile-placeholder { width: 70px; height: 70px; background: linear-gradient(135deg, #e66e00, #f39c12); color:white; border-radius: 50%; margin: 0 auto 10px; line-height:70px; font-size:28px; font-weight:bold; }
        .friend-actions { display: flex; gap: 5px; margin-top: 5px; }
        .btn-mini-view { background-color: var(--primary-color); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; flex: 1; text-align: center; text-decoration: none;}
        .btn-mini-delete { background-color: transparent; border: 1px solid #e74c3c; color: #e74c3c; padding: 4px 8px; border-radius: 4px; font-size: 11px; cursor: pointer; text-decoration: none;}
        .btn-mini-delete:hover { background-color: #e74c3c; color: white; }

        .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 20px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary-color); }
        input:checked + .slider:before { transform: translateX(20px); }
        
        .app-footer { margin-top: 50px; padding: 20px 0; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; color: var(--text-muted); font-size: 13px; }
        .footer-right { display: flex; gap: 15px; align-items: center; }
        .footer-right .version { background: rgba(128,128,128,0.1); padding: 2px 6px; border-radius: 4px; font-size: 11px; font-family: monospace; }
        .file-upload-wrapper { border: 1px dashed var(--border-color); padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 15px; background: var(--bg-body); }
        .file-upload-wrapper input[type="file"] { font-size: 12px; margin-top: 5px; width: 100%; }

        /* MODAL STİLLERİ */
        .result-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center; backdrop-filter: blur(3px); }
        .result-modal { background: var(--bg-surface); padding: 25px; border-radius: 12px; width: 350px; text-align: center; border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .result-input-group { display: flex; gap: 10px; margin: 15px 0; }
        .result-input-group input { flex: 1; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-body); color: var(--text-main); text-align: center; }
        .btn-save-result { background: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; }
        
        #liveNetDisplay { margin: 10px 0; font-size: 16px; font-weight: bold; color: var(--primary-color); background: rgba(255, 255, 255, 0.05); padding: 5px; border-radius: 5px; }

        /* GRAFİK STİLLERİ */
        .chart-controls { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .chart-btn { padding: 8px 15px; border: 1px solid var(--border-color); background: var(--bg-body); color: var(--text-muted); border-radius: 6px; cursor: pointer; transition: 0.3s; }
        .chart-btn.active { background: var(--primary-color); color: white; border-color: var(--primary-color); }
        .date-range-inputs { display: none; gap: 10px; align-items: center; }
        .date-range-inputs.active { display: flex; }
        .date-input { padding: 8px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-body); color: var(--text-main); }
    </style>
</head>
<body>

<div id="focus-overlay-bg"></div>

<div class="dashboard-wrapper">
    <div class="sidebar">
        <div style="text-align: center; margin-bottom: 40px; margin-top: 10px;">
            <?php if (!empty($profil_resmi)): ?>
                <img src="uploads/<?php echo $profil_resmi; ?>?v=<?php echo time(); ?>" class="profile-avatar" alt="Profil">
            <?php else: ?>
                <div class="profile-placeholder">
                    <?php echo strtoupper(mb_substr($kadi, 0, 1)); ?>
                </div>
            <?php endif; ?>

            <h3 style="margin:0; font-size:18px; color:var(--sidebar-text);"><?php echo htmlspecialchars($kadi); ?></h3>
            <div style="font-size:11px; color:#777; margin-top:5px;">ID: <?php echo $user_id; ?></div>
        </div>

        <nav style="display:flex; gap:10px;">
            <a href="anasayfa.php" style="flex:1; text-align:center; color:white; padding:10px; border-radius:8px; text-decoration:none; background:rgba(255,255,255,0.1); font-size:12px;">
                🏠 Ana Sayfa
            </a>
            <a href="program_ekle.php" style="flex:1; text-align:center; color:white; padding:10px; border-radius:8px; text-decoration:none; background:rgba(255,255,255,0.1); font-size:12px;">
                📅 Ders Ekle
            </a>
        </nav>

        <hr style="border:0; border-top:1px solid rgba(255,255,255,0.1); margin: 20px 0;">

        <div class="sidebar-search-box">
            <h4 style="font-size:13px; color:#aaa; margin-bottom:10px; text-transform:uppercase;">Arkadaş Ekle</h4>
            <form method="GET" action="anasayfa.php" class="search-input-group">
                <input type="number" name="arkadas_ekle_id" placeholder="ID No Gir" required style="width: 65%; border:none; padding:8px; border-radius:4px;">
                <button type="submit" style="width: 30%; border:none; background:#e66e00; color:white; border-radius:4px; cursor:pointer;">İstek</button>
            </form>
        </div>

        <div style="margin-top: 20px; flex:1;">
            <h4 style="font-size:13px; color:#aaa; margin-bottom:10px; text-transform:uppercase;">Arkadaşlar (<?php echo count($arkadas_listesi); ?>)</h4>
            <?php if (count($arkadas_listesi) > 0): ?>
                <ul class="friend-list">
                    <?php foreach ($arkadas_listesi as $arkadas): ?>
                        <li>
                            <div style="width:100%;">
                                <div class="info" style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong style="font-size:13px; color:#fff;"><?php echo htmlspecialchars($arkadas['kullanici_adi']); ?></strong>
                                </div>
                                <div class="friend-actions">
                                    <a href="arkadas_programi.php?id=<?php echo $arkadas['id']; ?>" class="btn-mini-view">Göz At</a>
                                    <a href="arkadas_sil.php?id=<?php echo $arkadas['id']; ?>" class="btn-mini-delete" onclick="return confirm('Silmek istediğine emin misin?')">X</a>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="font-size:12px; color:#666;">Listeniz boş.</p>
            <?php endif; ?>
        </div>

        <button class="settings-trigger-btn" onclick="openSettings()" style="margin-top:auto;">⚙ Ayarlar</button>
    </div>

    <div class="main-content">
        
        <div class="header-top">
            <div>
                <h2 style="margin:0; color:var(--text-main);">Hoş Geldin, <?php echo $kadi; ?> </h2>
                <p style="margin:5px 0 0; color:var(--text-muted); font-size:14px;">Ders programını planlamaya hazır mısın?</p>
            </div>

            <div class="notification-area" onclick="toggleBildirimMenu(event)">
                <div class="notif-bell">
                    <i class="fa-solid fa-bell" style="font-size: 20px; color: var(--primary-color);"></i>
                    <?php if ($bildirim_sayisi > 0): ?>
                        <span class="notif-badge"><?php echo $bildirim_sayisi; ?></span>
                    <?php endif; ?>
                </div>

                <div class="notif-dropdown" id="bildirimKutusu" onclick="event.stopPropagation()">
                    <div class="notif-header">
                        <span>Bildirimler</span>
                        <span class="clear-notifs-btn" onclick="bildirimleriTemizle(event)">Hepsini Temizle</span>
                    </div>
                    <div class="notif-list" id="notifListContainer">
                        <?php if (count($bildirimler) > 0): ?>
                            <?php foreach ($bildirimler as $b): ?>
                                <div class="notif-item <?php echo $b['okundu_mu']==0 ? 'unread' : ''; ?>">
                                    <span><?php echo $b['mesaj']; ?></span>
                                    
                                    <?php if ($b['tur'] == 'arkadas_istegi'): ?>
                                        <div class="notif-actions">
                                            <a href="arkadas_onay.php?islem=kabul&id=<?php echo $b['id']; ?>" class="btn-approve"><i class="fa fa-check"></i> Kabul</a>
                                            <a href="arkadas_onay.php?islem=ret&id=<?php echo $b['id']; ?>" class="btn-reject"><i class="fa fa-times"></i> Red</a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <small style="color:#999; font-size:10px; margin-top:3px; text-align:right;">
                                        <?php echo date("H:i", strtotime($b['tarih'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding:20px; text-align:center; color:#999;">Hiç bildirim yok.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card text-center" id="timer-card" style="max-width: 450px; margin: 0 auto 30px; position:relative; overflow:hidden;">
            <div style="background:var(--primary-color); height:4px; width:100%; position:absolute; top:0; left:0;"></div>
            
            <div style="margin-bottom: 10px;">
                <span style="font-size:11px; text-transform:uppercase; color:#999; letter-spacing:1px; font-weight:600;">AKTİF ÇALIŞMA</span>
                <h2 id="active-lesson-name" style="color:var(--text-main); margin: 5px 0; font-size:22px;">Serbest Mod</h2>
                <div style="font-size:13px; color:var(--text-muted); background:var(--bg-body); display:inline-block; padding:3px 12px; border-radius:15px; margin-top:5px; border:1px solid var(--border-color);">
                    Hedef: <span id="target-time-display">25</span> dk
                </div>
            </div>

            <div id="timer-display" style="font-size: 64px; font-weight: 700; margin: 10px 0; color: var(--primary-color); font-family: 'Courier New', monospace; letter-spacing:-2px;">25:00</div>
            
            <button id="pomodoro-btn" onclick="togglePomodoro()" style="font-size:16px; padding:12px 40px; border-radius:30px; background:var(--primary-color); color:white; border:none; cursor:pointer; font-weight:bold; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">BAŞLAT</button>
            
            <p id="status-text" style="font-size:13px; color:#888; margin-top:15px; min-height:18px;"></p>

            <button id="exit-focus-btn" onclick="exitFocusMode()" style="display:none;">Odak Modundan Çık</button>
        </div>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:15px;">
                <div class="tab-container" style="margin:0; border:none;">
                    <button class="tab-btn active" onclick="openTab('tab-haftalik')">Bu Hafta</button>
                    <button class="tab-btn" onclick="openTab('tab-gecmis')">Geçmiş</button>
                    <button class="tab-btn" onclick="openTab('tab-istatistik'); grafikCiz('hafta');">📊 İstatistikler</button>
                </div>
                <a href="program_ekle.php" style="background:var(--text-main); color:var(--bg-surface); padding:8px 15px; border-radius:6px; text-decoration:none; font-size:13px;">+ Ders Ekle</a>
            </div>

            <div id="tab-haftalik" class="tab-content active">
                <?php if (count($dersler) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px; text-align:center;">Drm</th>
                            <th>Gün</th>
                            <th>Ders</th>
                            <th>Hedef / Süre</th>
                            <th style="text-align:right;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dersler as $ders): ?>
                            <?php $is_done = in_array($ders['id'], $tamamlanan_listesi); ?>
                            <tr id="row-<?php echo $ders['id']; ?>" style="<?php echo $is_done ? 'opacity:0.5; filter:grayscale(100%);' : ''; ?>">
                                <td style="text-align:center;">
                                    <input type="checkbox" class="custom-checkbox" 
                                           onchange="updateStatus(<?php echo $ders['id']; ?>, this)"
                                           <?php echo $is_done ? 'checked' : ''; ?>>
                                </td>
                                <td><span style="color:var(--primary-color); font-weight:600;"><?php echo htmlspecialchars($ders['gun']); ?></span></td>
                                
                                <td style="font-weight:500; color:var(--text-main);">
                                    <?php echo htmlspecialchars($ders['ders_adi']); ?>
                                    <?php if(!empty($ders['konu'])): ?>
                                        <div style="font-size:11px; color:#aaa; margin-top:3px; font-style:italic;">
                                            📌 <?php echo htmlspecialchars($ders['konu']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td style="color:var(--text-muted);">
                                    <span style="color:var(--text-main); font-weight:bold;"><?php echo $ders['hedef_soru']; ?> Soru</span> 
                                    <span style="font-size:12px;"> / <?php echo $ders['calisma_suresi']; ?> dk</span>
                                </td>
                                <td>
                                    <div class="btn-action-group">
                                        <button class="btn-start" onclick="selectLesson(<?php echo $ders['id']; ?>, '<?php echo htmlspecialchars($ders['ders_adi']); ?>', <?php echo $ders['calisma_suresi']; ?>)">
                                            <span>▶</span> Odaklan
                                        </button>
                                        <a href="ders_sil.php?id=<?php echo $ders['id']; ?>" class="btn-delete" onclick="return confirm('Silmek istediğine emin misin?')">🗑</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div style="text-align:center; padding:40px; color:var(--text-muted);">Henüz ders eklenmemiş.</div>
                <?php endif; ?>
            </div>

            <div id="tab-gecmis" class="tab-content">
                <div style="background:var(--bg-body); padding:15px; border-radius:8px; display:flex; justify-content:center; gap:10px; margin-bottom:20px; border:1px solid var(--border-color);">
                    <input type="date" id="gecmis-tarih" onchange="gecmisGetir()">
                    <button onclick="sifirlaVeGetir()" style="background:#666; color:white; border:none; padding:8px 15px; border-radius:6px; cursor:pointer;">Tümünü Göster</button>
                </div>
                <div id="gecmis-sonuclar" style="min-height:100px;"></div>
            </div>

            <div id="tab-istatistik" class="tab-content">
                <div class="chart-controls">
                    <button class="chart-btn active" onclick="setChartFilter('hafta', this)">Bu Hafta</button>
                    <button class="chart-btn" onclick="setChartFilter('ay', this)">Bu Ay</button>
                    <button class="chart-btn" onclick="setChartFilter('yil', this)">Bu Yıl</button>
                    <button class="chart-btn" onclick="toggleDateInputs(this)">Tarih Seç</button>
                    
                    <div class="date-range-inputs" id="customDateRange">
                        <input type="date" id="startDate" class="date-input">
                        <span style="color:#aaa;">-</span>
                        <input type="date" id="endDate" class="date-input">
                        <button class="chart-btn" onclick="setChartFilter('ozel', this)">Getir</button>
                    </div>
                </div>

                <div style="position: relative; height:400px; width:100%;">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>

        <footer class="app-footer">
            <div class="footer-left">
                &copy; <?php echo date("Y"); ?> <strong>StudyPlan</strong>. Tüm hakları saklıdır.
            </div>
            <div class="footer-right">
                <span>Gizlilik</span>
                <span>Yardım</span>
                <span class="version">v1.0.9</span>
            </div>
        </footer>

    </div> 
</div> 

<div class="settings-overlay" id="settings-overlay" onclick="closeSettings()"></div>
<div class="settings-panel" id="settings-panel">
    <h3>Ayarlar</h3>
    <button id="darkModeBtn" onclick="toggleDarkMode()">🌙 Karanlık Mod</button> <br><br>
    
    <div class="file-upload-wrapper">
        <label style="font-size:13px; font-weight:bold; color:var(--text-main);">Profil Fotoğrafı</label>
        <form action="profil_resim_yukle.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="profil_resmi" accept="image/*" required>
            <button type="submit" style="margin-top:10px; padding:6px; font-size:12px;">Güncelle</button>
        </form>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-body); padding: 10px; border-radius: 8px; margin-bottom: 15px;">
        <span style="font-size: 14px; color: var(--text-main);">Profili Gizle</span>
        <label class="switch">
            <?php $check_attr = ($gizlilik_durumu == 1) ? 'checked' : ''; ?>
            <input type="checkbox" id="privacySwitch" onchange="togglePrivacy()" <?php echo $check_attr; ?>>
            <span class="slider round"></span>
        </label>
    </div>

    <a href="cikis.php" class="btn-red" style="width:100%; display:block; padding:10px; box-sizing:border-box; text-decoration:none;">Çıkış Yap</a>
    <br>
    <button onclick="closeSettings()" style="background:transparent; color:var(--text-muted); border:none; cursor:pointer; text-decoration:underline;">Kapat</button>
</div>

<div class="result-modal-overlay" id="resultModal">
    <div class="result-modal">
        <h3 style="margin-top:0; color:var(--text-main);">Dersi Tamamla</h3>
        <p style="color:var(--text-muted); font-size:13px; margin-bottom:15px;">Bu çalışma için test sonucunu gir:</p>
        
        <div class="result-input-group">
            <div>
                <label style="font-size:12px; color:#27ae60;">Doğru</label>
                <input type="number" id="modalDogru" placeholder="0" oninput="calculateNet()">
            </div>
            <div>
                <label style="font-size:12px; color:#e74c3c;">Yanlış</label>
                <input type="number" id="modalYanlis" placeholder="0" oninput="calculateNet()">
            </div>
            <div>
                <label style="font-size:12px; color:#7f8c8d;">Boş</label>
                <input type="number" id="modalBos" placeholder="0">
            </div>
        </div>

        <div id="liveNetDisplay">Net: 0.00</div>
        
        <button class="btn-save-result" onclick="saveLessonResult()">KAYDET VE BİTİR</button>
        <button onclick="closeResultModal()" style="background:transparent; border:none; color:#777; margin-top:10px; cursor:pointer; font-size:12px;">İptal Et</button>
    </div>
</div>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<script>
    // --- GRAFİK İŞLEMLERİ ---
    let myChart = null;

    function toggleDateInputs(btn) {
        document.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const container = document.getElementById('customDateRange');
        container.style.display = (container.style.display === 'flex') ? 'none' : 'flex';
    }

    function setChartFilter(filtre, btn) {
        if(btn) {
            document.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if(filtre !== 'ozel') document.getElementById('customDateRange').style.display = 'none';
        }
        grafikCiz(filtre);
    }

    function grafikCiz(filtre) {
        let payload = { filtre: filtre };
        if (filtre === 'ozel') {
            let start = document.getElementById('startDate').value;
            let end = document.getElementById('endDate').value;
            if(!start || !end) { alert("Lütfen tarih aralığı seçin."); return; }
            payload.baslangic = start;
            payload.bitis = end;
        }

        fetch('grafik_veri_getir.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            renderChart(data);
        });
    }

    function renderChart(data) {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const labels = data.map(item => item.tarih);
        const dogruData = data.map(item => item.toplam_dogru);
        const yanlisData = data.map(item => item.toplam_yanlis);
        const bosData = data.map(item => item.toplam_bos);

        if (myChart) myChart.destroy();

        myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Doğru', data: dogruData, backgroundColor: 'rgba(39, 174, 96, 0.7)', borderColor: '#27ae60', borderWidth: 1 },
                    { label: 'Yanlış', data: yanlisData, backgroundColor: 'rgba(231, 76, 60, 0.7)', borderColor: '#e74c3c', borderWidth: 1 },
                    { label: 'Boş', data: bosData, backgroundColor: 'rgba(127, 140, 141, 0.7)', borderColor: '#7f8c8d', borderWidth: 1 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true }, x: { grid: { display: false } } },
                plugins: { legend: { labels: { color: '#999' } } }
            }
        });
    }

    // --- ANLIK NET HESAPLAMA ---
    function calculateNet() {
        let d = parseFloat(document.getElementById('modalDogru').value) || 0;
        let y = parseFloat(document.getElementById('modalYanlis').value) || 0;
        let net = d - (y / 4);
        document.getElementById('liveNetDisplay').innerText = "Net: " + net.toFixed(2);
    }

    function togglePrivacy() {
        var isPrivate = document.getElementById("privacySwitch").checked ? 1 : 0;
        fetch('profil_ayar_guncelle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'gizlilik=' + isPrivate
        })
        .then(response => response.text())
        .then(data => {
            if(data.trim() === 'success') {
                Toastify({ text: isPrivate ? "Profil gizlendi." : "Profil herkese açıldı.", style: { background: isPrivate ? "#e74c3c" : "#27ae60" } }).showToast();
            } else {
                alert("Ayar kaydedilemedi!");
                document.getElementById("privacySwitch").checked = !isPrivate;
            }
        });
    }

    function toggleBildirimMenu(event) {
        var menu = document.getElementById("bildirimKutusu");
        var isClosed = !menu.classList.contains("aktif"); 

        menu.classList.toggle("aktif");
        event.stopPropagation();

        if (isClosed) {
            var badge = document.querySelector('.notif-badge');
            if (badge) badge.style.display = 'none';

            var unreadItems = document.querySelectorAll('.notif-item.unread');
            unreadItems.forEach(function(item) {
                item.classList.remove('unread');
                item.style.backgroundColor = 'transparent';
                item.style.borderLeft = 'none';
            });

            fetch('bildirim_okundu.php');
        }
    }
    
    // YENİ: BİLDİRİM SİLME FONKSİYONU
    function bildirimleriTemizle(event) {
        event.stopPropagation(); // Menünün kapanmasını engelle
        if(confirm("Tüm bildirimleri silmek istediğine emin misin?")) {
            fetch('bildirim_sil.php')
            .then(res => res.text())
            .then(data => {
                if(data.trim() === 'success') {
                    document.getElementById('notifListContainer').innerHTML = '<div style="padding:20px; text-align:center; color:#999;">Hiç bildirim yok.</div>';
                    Toastify({ text: 'Bildirimler temizlendi!', style: { background: '#e74c3c' } }).showToast();
                }
            });
        }
    }

    window.onclick = function(event) {
        if (!event.target.closest('.notification-area')) {
            var menu = document.getElementById("bildirimKutusu");
            if (menu.classList.contains('aktif')) menu.classList.remove('aktif');
        }
    }

    // --- MODAL VE DERS İŞLEMLERİ ---
    let pendingLessonId = null;
    let pendingCheckbox = null;

    function updateStatus(id, checkbox) {
        if (checkbox.checked) {
            pendingLessonId = id;
            pendingCheckbox = checkbox;
            
            document.getElementById('resultModal').style.display = 'flex';
            document.getElementById('modalDogru').value = '';
            document.getElementById('modalYanlis').value = '';
            document.getElementById('modalBos').value = '';
            document.getElementById('liveNetDisplay').innerText = "Net: 0.00"; 
            document.getElementById('modalDogru').focus();
        } else {
            sendLessonUpdate(id, 0, 0, 0, 0);
            document.getElementById('row-' + id).style.opacity = "1";
            document.getElementById('row-' + id).style.filter = "none";
        }
    }

    function saveLessonResult() {
        var d = document.getElementById('modalDogru').value || 0;
        var y = document.getElementById('modalYanlis').value || 0;
        var b = document.getElementById('modalBos').value || 0;
        sendLessonUpdate(pendingLessonId, 1, d, y, b);
        if(pendingCheckbox) {
            document.getElementById('row-' + pendingLessonId).style.opacity = "0.5";
            document.getElementById('row-' + pendingLessonId).style.filter = "grayscale(100%)";
        }
        closeResultModal();
    }

    function closeResultModal() {
        document.getElementById('resultModal').style.display = 'none';
        if (pendingCheckbox && document.getElementById('row-' + pendingLessonId).style.opacity === "1") {
            pendingCheckbox.checked = false; 
        }
    }

    function sendLessonUpdate(id, durum, dogru, yanlis, bos) {
        fetch('ders_durum.php', {
            method: 'POST',
            body: JSON.stringify({ id: id, durum: durum, dogru: dogru, yanlis: yanlis, bos: bos })
        });
    }

    // --- SAYAÇ ---
    let timerInterval, currentLessonId = 'default', timeLeft = 25 * 60, isRunning = false;
    function selectLesson(id, name, duration) {
        if (isRunning) clearInterval(timerInterval);
        isRunning = false;
        document.getElementById('pomodoro-btn').innerText = "BAŞLAT";
        document.getElementById('pomodoro-btn').style.background = "var(--primary-color)";
        document.getElementById('active-lesson-name').innerText = name;
        document.getElementById('target-time-display').innerText = duration;
        currentLessonId = id;
        const saved = localStorage.getItem('timer_' + id);
        if (saved) {
            timeLeft = parseInt(saved);
            document.getElementById('status-text').innerText = "Kaldığın yerden devam...";
        } else {
            timeLeft = duration * 60;
            document.getElementById('status-text').innerText = "Hazır.";
        }
        updateDisplay();
        enterFocusMode();
    }

    function togglePomodoro() {
        const btn = document.getElementById('pomodoro-btn');
        if (isRunning) {
            clearInterval(timerInterval);
            isRunning = false;
            btn.innerText = "DEVAM ET";
            btn.style.background = "#f39c12";
            saveTime();
        } else {
            isRunning = true;
            btn.innerText = "DURDUR";
            btn.style.background = "#c0392b";
            if(!document.body.classList.contains('focus-mode-active')) enterFocusMode();
            timerInterval = setInterval(() => {
                timeLeft--;
                updateDisplay();
                if (timeLeft % 5 === 0) saveTime();
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    isRunning = false;
                    localStorage.removeItem('timer_' + currentLessonId);
                    document.getElementById('timer-display').innerText = "00:00";
                    btn.innerText = "YENİDEN BAŞLAT";
                    btn.style.background = "var(--primary-color)";
                    alert("Süre bitti!");
                    fetch('pomodoro_mail.php');
                    exitFocusMode();
                }
            }, 1000);
        }
    }

    function enterFocusMode() {
        document.getElementById('focus-overlay-bg').style.display = 'block';
        document.body.classList.add('focus-mode-active');
        document.getElementById('timer-card').classList.add('focus-mode-card');
        document.getElementById('exit-focus-btn').style.display = 'inline-block';
    }

    function exitFocusMode() {
        document.getElementById('focus-overlay-bg').style.display = 'none';
        document.body.classList.remove('focus-mode-active');
        document.getElementById('timer-card').classList.remove('focus-mode-card');
        document.getElementById('exit-focus-btn').style.display = 'none';
    }

    function saveTime() { if(currentLessonId !== 'default') localStorage.setItem('timer_' + currentLessonId, timeLeft); }
    function updateDisplay() {
        let m = Math.floor(timeLeft / 60), s = timeLeft % 60;
        document.getElementById('timer-display').innerText = (m<10?'0':'')+m + ":" + (s<10?'0':'')+s;
    }

    function openTab(t){var x=document.getElementsByClassName("tab-content");for(var i=0;i<x.length;i++)x[i].style.display="none";var b=document.getElementsByClassName("tab-btn");for(var i=0;i<b.length;i++)b[i].classList.remove("active");document.getElementById(t).style.display="block";event.currentTarget.classList.add("active");if(t==='tab-gecmis')gecmisGetir();}
    function gecmisGetir(){var t=document.getElementById('gecmis-tarih').value;fetch('gecmis_getir.php',{method:'POST',body:JSON.stringify({tarih:t})}).then(r=>r.text()).then(h=>document.getElementById('gecmis-sonuclar').innerHTML=h);}
    function sifirlaVeGetir(){document.getElementById('gecmis-tarih').value="";gecmisGetir();}
    function openSettings(){document.getElementById('settings-panel').style.display='block';document.getElementById('settings-overlay').style.display='block';}
    function closeSettings(){document.getElementById('settings-panel').style.display='none';document.getElementById('settings-overlay').style.display='none';}
    function toggleDarkMode(){document.body.classList.toggle('dark-mode');localStorage.setItem('theme',document.body.classList.contains('dark-mode')?'dark':'light');}
    document.addEventListener("DOMContentLoaded",function(){if(localStorage.getItem('theme')==='dark')toggleDarkMode();});
    
    <?php echo $toast_script; ?>
</script>

</body>
</html>