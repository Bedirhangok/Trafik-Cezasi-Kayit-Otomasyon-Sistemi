<?php
/**
 * Veritabanı Bağlantı Dosyası
 * 
 * KURULUM: Aşağıdaki bilgileri kendi ortamınıza göre doldurun.
 * Canlı ortama (hosting) alırken bu değerleri güncelleyin.
 * Bu dosyayı asla Github'a hassas bilgilerle birlikte yüklemeyin!
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host      = "localhost";   // Veritabanı sunucu adresi
$kullanici = "root";        // MySQL kullanıcı adı (hosting'de değişecek)
$sifre     = "";            // MySQL şifresi   (hosting'de değişecek)
$veritabani = ""; // Veritabanı adı  (hosting'de değişecek)

try {
    $db = new mysqli($host, $kullanici, $sifre, $veritabani);
    $db->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Güvenlik: Hata detayını kullanıcıya gösterme
    die("Veritabanı bağlantı hatası. Lütfen sistem yöneticisi ile iletişime geçin.");
}
?>
