<?php
session_start();
include 'db.php';

$login_basarili = false;
$hata_mesaji = null;
$redirect_url = "anasayfa.php";

// 1. OTOMATİK GİRİŞ (Cookie Varsa)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $c_sorgu = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
    $c_sorgu->execute([$_COOKIE['user_id']]);
    $c_uye = $c_sorgu->fetch(PDO::FETCH_ASSOC);

    if ($c_uye) {
        $_SESSION['user_id'] = $c_uye['id'];
        $_SESSION['kadi'] = $c_uye['kullanici_adi'];
        $_SESSION['rol'] = $c_uye['rol'];
        
        header("Location: " . ($c_uye['rol'] == 'admin' ? 'admin_panel.php' : 'anasayfa.php'));
        exit;
    }
}

// 2. ZATEN GİRİŞ YAPILMIŞSA YÖNLENDİR
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['rol'] == 'admin' ? 'admin_panel.php' : 'anasayfa.php'));
    exit;
}

// 3. FORM GÖNDERİLDİĞİNDE
if ($_POST) {
    $kullanici = $_POST['kullanici_adi'];
    $sifre = $_POST['sifre'];
    $giris_turu = $_POST['giris_turu']; // 'ogrenci' veya 'ogretmen'

    $query = $db->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ?");
    $query->execute([$kullanici]);
    $uye = $query->fetch(PDO::FETCH_ASSOC);

    if ($uye && password_verify($sifre, $uye['sifre'])) {
        
        // --- ROL KONTROLÜ (KAPI BEKÇİSİ) ---
        $gercek_rol = $uye['rol']; // Veritabanındaki rol (admin/ogrenci)
        
        // Hata Senaryosu 1: Öğretmen yerinden girmeye çalışan öğrenci
        if ($giris_turu == 'ogretmen' && $gercek_rol != 'admin') {
            $hata_mesaji = "Bu bir öğrenci hesabı! Lütfen Öğrenci Girişi'ni kullanın.";
        }
        // Hata Senaryosu 2: Öğrenci yerinden girmeye çalışan öğretmen
        elseif ($giris_turu == 'ogrenci' && $gercek_rol == 'admin') {
            $hata_mesaji = "Bu bir öğretmen hesabı! Lütfen Öğretmen Girişi'ni kullanın.";
        }
        else {
            // --- GİRİŞ BAŞARILI ---
            $_SESSION['user_id'] = $uye['id'];
            $_SESSION['kadi'] = $uye['kullanici_adi'];
            $_SESSION['rol'] = $uye['rol'];

            if (isset($_POST['beni_hatirla'])) {
                setcookie('user_id', $uye['id'], time() + (86400 * 30), "/");
            }
            
            $redirect_url = ($uye['rol'] == 'admin') ? "admin_panel.php" : "anasayfa.php";
            $login_basarili = true;
        }
        
    } else {
        $hata_mesaji = "Kullanıcı adı veya şifre hatalı!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap - StudyPlan</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* GİRİŞ EKRANI ÖZEL STİLLERİ */
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: 'Segoe UI', sans-serif; }
        
        .auth-container { width: 100%; max-width: 400px; padding: 20px; }
        .auth-box { background: white; padding: 40px 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; transition: all 0.3s ease; }
        
        /* Sekme Yapısı */
        .role-tabs { display: flex; background: #eee; border-radius: 25px; padding: 5px; margin-bottom: 25px; position: relative; }
        .role-tab { flex: 1; padding: 10px; border-radius: 20px; cursor: pointer; font-weight: bold; color: #777; transition: 0.3s; z-index: 2; position: relative; border: none; background: transparent; }
        .role-tab.active { color: white; }
        
        /* Kayan Arka Plan (Slider) */
        .tab-slider { position: absolute; top: 5px; left: 5px; width: calc(50% - 5px); height: calc(100% - 10px); background: var(--primary-color); border-radius: 20px; transition: 0.3s ease-in-out; z-index: 1; }
        
        /* Renk Temaları - İKİSİ DE TURUNCU YAPILDI */
        .auth-box.student-mode { border-top: 5px solid #e66e00; }
        .auth-box.student-mode .tab-slider { background: #e66e00; left: 5px; }
        .auth-box.student-mode button[type="submit"] { background: #e66e00; }
        .auth-box.student-mode h1 { color: #e66e00; }

        /* Öğretmen modu da artık turuncu (#e66e00) */
        .auth-box.teacher-mode { border-top: 5px solid #e66e00; }
        .auth-box.teacher-mode .tab-slider { background: #e66e00; left: 50%; } /* Sadece slider konumu farklı */
        .auth-box.teacher-mode button[type="submit"] { background: #e66e00; }
        .auth-box.teacher-mode h1 { color: #e66e00; }

        /* Form Elemanları */
        input[type="text"], input[type="password"] { width: 100%; padding: 12px 15px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 14px; background: #fafafa; }
        input:focus { outline: none; border-color: #999; }
        
        button[type="submit"] { width: 100%; padding: 12px; margin-top: 15px; border: none; border-radius: 8px; color: white; font-weight: bold; font-size: 16px; cursor: pointer; transition: 0.3s; }
        button[type="submit"]:hover { opacity: 0.9; }

        .remember-me { display: flex; align-items: center; justify-content: flex-start; margin: 10px 0; font-size: 13px; color: #666; gap: 5px; }
        .remember-me input { width: auto; margin: 0; }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-box student-mode" id="loginBox">
            
            <h1 id="loginTitle"><i class="fa-solid fa-graduation-cap"></i> Öğrenci Girişi</h1>
            
            <div class="role-tabs">
                <div class="tab-slider"></div>
                <button class="role-tab active" onclick="switchRole('ogrenci')">Öğrenci</button>
                <button class="role-tab" onclick="switchRole('ogretmen')">Öğretmen</button>
            </div>

            <form method="post">
                <input type="hidden" name="giris_turu" id="girisTuruInput" value="ogrenci">
                
                <input type="text" name="kullanici_adi" placeholder="Kullanıcı Adı" required>
                <input type="password" name="sifre" placeholder="Şifre" required>
                
                <label class="remember-me">
                    <input type="checkbox" name="beni_hatirla" value="1"> 
                    Beni Hatırla
                </label>
                
                <button type="submit">Giriş Yap</button>
            </form>
            
            <p style="margin-top:20px; font-size:13px; color:#888;">
                Hesabın yok mu? <a href="kayit.php" style="color:#555; font-weight:bold;">Kayıt Ol</a>
            </p>
        </div>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <script>
        function switchRole(role) {
            const box = document.getElementById('loginBox');
            const title = document.getElementById('loginTitle');
            const input = document.getElementById('girisTuruInput');
            const tabs = document.querySelectorAll('.role-tab');

            if (role === 'ogrenci') {
                box.classList.remove('teacher-mode');
                box.classList.add('student-mode');
                title.innerHTML = '<i class="fa-solid fa-graduation-cap"></i> Öğrenci Girişi';
                input.value = 'ogrenci';
                tabs[0].classList.add('active');
                tabs[1].classList.remove('active');
            } else {
                box.classList.remove('student-mode');
                box.classList.add('teacher-mode');
                title.innerHTML = '<i class="fa-solid fa-chalkboard-user"></i> Öğretmen Girişi';
                input.value = 'ogretmen';
                tabs[0].classList.remove('active');
                tabs[1].classList.add('active');
            }
        }

        // --- PHP BİLDİRİMLERİ ---
        <?php if ($login_basarili): ?>
            Toastify({
                text: "Giriş Başarılı! Yönlendiriliyorsunuz...",
                duration: 2000,
                gravity: "top",
                position: "right",
                style: { background: "#27ae60" }
            }).showToast();

            setTimeout(function() {
                window.location.href = "<?php echo $redirect_url; ?>";
            }, 1500);
        <?php endif; ?>

        <?php if ($hata_mesaji): ?>
            Toastify({
                text: "<?php echo $hata_mesaji; ?>",
                duration: 3000,
                gravity: "top",
                position: "right",
                style: { background: "#c0392b" }
            }).showToast();
        <?php endif; ?>
    </script>
</body>
</html>