<?php
require 'baglanti.php';

// ceza_kayitlari tablosundaki indeksleri al
$result = $db->query("SHOW INDEX FROM ceza_kayitlari WHERE Non_unique = 0 AND Key_name != 'PRIMARY'");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $index_name = $row['Key_name'];
        // İndeksi sil
        $drop_sql = "ALTER TABLE ceza_kayitlari DROP INDEX `$index_name`";
        if ($db->query($drop_sql)) {
            echo "Basarili: '$index_name' kaldirildi.\n";
        } else {
            echo "Hata: " . $db->error . "\n";
        }
    }
} else {
    echo "Kaldirilacak unique index bulunamadi.\n";
}
?>
