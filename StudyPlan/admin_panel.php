<?php
session_start();
include 'db.php';

// Güvenlik: Sadece admin girebilir!
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') {
    die("Bu sayfaya erişim yetkiniz yok.");
}

// Öğrencileri listele
$sorgu = $db->query("SELECT * FROM kullanicilar WHERE rol = 'ogrenci'");
$ogrenciler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Öğretmen Paneli</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Arama Kutusu Stili */
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        #searchInput {
            width: 100%;
            padding: 12px 20px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: var(--bg-surface);
            color: var(--text-main);
            outline: none;
            transition: all 0.3s;
        }
        #searchInput:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 8px rgba(230, 110, 0, 0.2);
        }
        
        /* Tablo Düzeni */
        table { width:100%; border-collapse:collapse; background: var(--bg-surface); border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { background-color: #333; color: white; }
        tr:hover { background-color: rgba(255,255,255,0.05); }

        /* YENİ ÇIKIŞ BUTONU STİLİ (KARE FORMAT) */
        .btn-logout {
            background-color: #e74c3c; /* Kırmızı ton */
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px; /* Hafif oval köşeli kare */
            font-weight: bold;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
            border: 1px solid #c0392b;
        }
        .btn-logout:hover {
            background-color: #c0392b; /* Üzerine gelince koyulaş */
        }
    </style>
</head>
<body style="padding: 20px; max-width: 1000px; margin: 0 auto;">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; border-bottom:1px solid var(--border-color); padding-bottom:15px;">
        <div>
            <h1 style="margin:0; color:var(--text-main); font-size: 24px;">🎓 Rehber Öğretmen Paneli</h1>
            <p style="color:var(--text-muted); margin-top:5px; font-size:14px;">Öğrenci listesi ve program yönetimi</p>
        </div>
        
        <a href="cikis.php" class="btn-logout">
            Çıkış Yap 🚪
        </a>
    </div>

    <div class="search-container">
        <input type="text" id="searchInput" onkeyup="tabloFiltrele()" placeholder="🔍 Öğrenci ID veya İsim ile ara...">
    </div>

    <table id="ogrenciTablosu">
        <thead>
            <tr>
                <th style="width: 80px;">ID</th>
                <th>Öğrenci Adı</th>
                <th style="text-align:right;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($ogrenciler as $ogr): ?>
            <tr>
                <td style="font-weight:bold; color:var(--primary-color);">
                    #<?php echo $ogr['id']; ?>
                </td>
                
                <td>
                    <?php echo htmlspecialchars($ogr['kullanici_adi']); ?>
                </td>
                
                <td style="text-align:right;">
                    <a href="rehber_program_ekle.php?ogrenci_id=<?php echo $ogr['id']; ?>" 
                       style="background:var(--primary-color); color:white; padding:8px 15px; text-decoration:none; border-radius:6px; margin-right:5px; font-size:13px; font-weight:500;">
                       Program Ekle ✏️
                    </a>

                    <a href="rehber_ogrenci_detay.php?ogrenci_id=<?php echo $ogr['id']; ?>" 
                       style="background:#3498db; color:white; padding:8px 15px; text-decoration:none; border-radius:6px; font-size:13px; font-weight:500;">
                       Programı Gör 👀
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        function tabloFiltrele() {
            var input = document.getElementById("searchInput");
            var filter = input.value.toUpperCase();
            var table = document.getElementById("ogrenciTablosu");
            var tr = table.getElementsByTagName("tr");

            for (var i = 0; i < tr.length; i++) {
                var tdId = tr[i].getElementsByTagName("td")[0];
                var tdName = tr[i].getElementsByTagName("td")[1];
                
                if (tdId || tdName) {
                    var idValue = tdId.textContent || tdId.innerText;
                    var nameValue = tdName.textContent || tdName.innerText;

                    if (idValue.toUpperCase().indexOf(filter) > -1 || nameValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }       
            }
        }
    </script>

</body>
</html>