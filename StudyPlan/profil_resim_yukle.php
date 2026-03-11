<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: giris.php"); exit; }

if ($_FILES["profil_resmi"]) {
    $user_id = $_SESSION['user_id'];
    $target_dir = "uploads/";
    
    // Dosya uzantısını al (jpg, png vs.)
    $imageFileType = strtolower(pathinfo($_FILES["profil_resmi"]["name"], PATHINFO_EXTENSION));
    
    // Yeni dosya ismi oluştur: profil_ID.uzanti (Örn: profil_15.jpg)
    // Bu sayede her kullanıcının tek bir resmi olur, eskisi silinir/üzerine yazılır.
    $new_file_name = "profil_" . $user_id . "." . $imageFileType;
    $target_file = $target_dir . $new_file_name;
    
    $uploadOk = 1;

    // 1. Resim mi kontrolü
    $check = getimagesize($_FILES["profil_resmi"]["tmp_name"]);
    if($check === false) {
        header("Location: anasayfa.php?error=dosya_resim_degil");
        exit;
    }

    // 2. Dosya boyutu kontrolü (Max 2MB)
    if ($_FILES["profil_resmi"]["size"] > 2000000) {
        header("Location: anasayfa.php?error=dosya_cok_buyuk");
        exit;
    }

    // 3. Dosya formatı kontrolü
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        header("Location: anasayfa.php?error=uzanti_hatali");
        exit;
    }

    // --- YÜKLEME İŞLEMİ ---
    if (move_uploaded_file($_FILES["profil_resmi"]["tmp_name"], $target_file)) {
        
        // Veritabanını güncelle
        // Rastgele sayı ekliyoruz (?v=...) ki tarayıcı eski resmi önbellekten okumasın.
        $db->prepare("UPDATE kullanicilar SET profil_resmi = ? WHERE id = ?")->execute([$new_file_name, $user_id]);
        
        header("Location: anasayfa.php?msg=resim_yuklendi");
    } else {
        header("Location: anasayfa.php?error=yukleme_basarisiz");
    }
}
?>