<?php
// Composer autoloader'ı dahil et
require 'vendor/autoload.php';

// Namespace kullanımı
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Yeni bir PHPMailer nesnesi oluştur
$mail = new PHPMailer(true);

try {
    // --- Sunucu Ayarları (Mailtrap Bilgileri) ---
    $mail->isSMTP();                                            
    $mail->Host       = 'sandbox.smtp.mailtrap.io';             
    $mail->SMTPAuth   = true;                                   
    $mail->Username   = '1c743bcd4d3fac';      // Mailtrap Username
    $mail->Password   = 'e32a6296b7d4ed';      // Mailtrap Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
    $mail->Port       = 2525;                                   
    
    // --- TÜRKÇE KARAKTER AYARI ---
    $mail->CharSet    = 'UTF-8';  // <--- Bu satır Türkçe karakterleri düzeltir
    // -----------------------------

    if (isset($gonderilecek_email) && isset($gonderilecek_isim)) {
        $mail->setFrom('no-reply@studyplan.com', 'StudyPlan');
        $mail->addAddress($gonderilecek_email, $gonderilecek_isim);

        // --- İçerik ---
        $mail->isHTML(true);
        $mail->Subject = 'StudyPlan\'a Hoş Geldin!';
        
        // HTML içeriğinde de UTF-8 olduğunu belirtmek iyi bir önlemdir
        $mail->Body    = "
            <html>
            <head><meta charset='UTF-8'></head>
            <body>
                <h1>Merhaba $gonderilecek_isim,</h1>
                <p>StudyPlan'a hoş geldin! Bu uygulamada planladığın haftalık ders programını burada hazırlayıp düzenleyebilirsin.
                 Ayrıca pomodoro sayacıyla çalışmak verimliliğini arttıracaktır. Eklediğin arkadaşlarınla rekabet etmeyi unutma ;) 
                 İyi çalışmalar! </p>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Merhaba $gonderilecek_isim, aramıza hoş geldin.";

        $mail->send();
    }

} catch (Exception $e) {
    // Hata oluşursa sessizce devam et veya logla
    // error_log("Mail Hatası: {$mail->ErrorInfo}");
}
?>