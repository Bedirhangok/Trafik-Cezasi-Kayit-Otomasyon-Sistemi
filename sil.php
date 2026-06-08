<?php
/**
 * Ceza Kaydı Silme İşlemi (DELETE)
 * 
 * Güvenlik:
 * - Oturum kontrolü
 * - Rol tabanlı yetkilendirme (polis sadece kendi verisini silebilir)
 * - SQL Injection koruması: Tüm parametreler prepared statement ile bağlanır
 */
session_start();
require_once 'baglanti.php';

if (isset($_SESSION['kullanici_id']) && isset($_GET['id'])) {
    $id       = intval($_GET['id']);
    $aktif_id = (int)$_SESSION['kullanici_id'];
    $rol      = $_SESSION['rol'];

    // SİLME (DELETE İşlemi) ve rol tabanlı yetki kontrolü
    if ($rol == 'polis') {
        // Polis: Sadece kendi eklediği kaydı silebilir
        // Güvenlik: $aktif_id de parametre olarak bağlanıyor (SQL injection önlemi)
        $sql  = "DELETE FROM ceza_kayitlari WHERE id = ? AND kullanici_id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $id, $aktif_id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Komiser: Herhangi bir kaydı silebilir
        $sql  = "DELETE FROM ceza_kayitlari WHERE id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// İşlem bitince ana sayfaya dön
header("Location: index.php");
exit;
?>