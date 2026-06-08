<?php
session_start();
require_once 'baglanti.php';

if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['islem'])) {
    $id = (int)$_GET['id'];
    $islem = $_GET['islem'];

    if ($islem === 'onayla') {
        $sql = "UPDATE kullanicilar SET is_approved = 1 WHERE id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($islem === 'reddet') {
        $sql = "DELETE FROM kullanicilar WHERE id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }
}
header("Location: index.php");
exit;
?>
