<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli("localhost", "dbusr23360859046", "t331cKpeJRYW", "dbstorage23360859046");
    $db->set_charset("utf8mb4");
    $res = $db->query("SHOW CREATE TABLE ceza_kayitlari");
    if ($res) {
        $row = $res->fetch_assoc();
        echo $row['Create Table'];
    } else {
        echo "Hata: " . $db->error;
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>
