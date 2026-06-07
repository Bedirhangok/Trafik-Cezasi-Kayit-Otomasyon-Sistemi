<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$kullanici = "";
$sifre = "";
$veritabani = "";

try {
    $db = new mysqli($host, $kullanici, $sifre, $veritabani);
    $db->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Veritabanı bağlantı hatası. Lütfen sistem yöneticisi ile iletişime geçin.");
}
?>