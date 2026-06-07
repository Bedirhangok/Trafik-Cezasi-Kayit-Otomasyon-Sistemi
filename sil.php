<?php
session_start();
require_once 'baglanti.php';

if (isset($_SESSION['kullanici_id']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $aktif_id = $_SESSION['kullanici_id'];
    $rol = $_SESSION['rol'];

    // SİLME (DELETE İşlemi) ve yetki kontrolü
    $sql = "DELETE FROM ceza_kayitlari WHERE id = ?";
    if ($rol == 'polis') {
        $sql .= " AND kullanici_id = $aktif_id"; // Polis başkasının verisini silemez
    }

    $stmt = $db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// İşlem bitince ana sayfaya dön
header("Location: index.php");
exit;
?>