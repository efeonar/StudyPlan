<?php
$host = 'localhost';
$dbname = 'planstudy';
$username = 'root';
$password = ''; // Şifren yoksa boş bırak, varsa '4545' yaz

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>