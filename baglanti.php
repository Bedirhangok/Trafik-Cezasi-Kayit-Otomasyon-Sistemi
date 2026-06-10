<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host      = "localhost";   // Veritabanı sunucu adresi
$kullanici = "root";        // MySQL kullanıcı adı 
$sifre     = "";            // MySQL şifresi  
$veritabani = "trafik_db"; // Veritabanı adı 

try {
    $db = new mysqli($host, $kullanici, $sifre, $veritabani);
    $db->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Güvenlik: Hata detayını kullanıcıya gösterme
    die("Veritabanı bağlantı hatası. Lütfen sistem yöneticisi ile iletişime geçin.");
}
?>