<?php
session_start();
require_once 'baglanti.php';

if (!isset($_SESSION['kullanici_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = intval($_GET['id']);
$aktif_id = $_SESSION['kullanici_id'];
$rol = $_SESSION['rol'];
$mesaj = "";

// Kaydı veritabanından çek ve yetkiyi kontrol et
$sql = "SELECT * FROM ceza_kayitlari WHERE id = ?";
if ($rol == 'polis') {
    $sql .= " AND kullanici_id = $aktif_id"; 
}

$stmt = $db->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $sonuc = $stmt->get_result();
    $mevcut_veri = $sonuc->fetch_assoc();
    $stmt->close();

    if (!$mevcut_veri) {
        die("<!DOCTYPE html><html lang='tr'><head><meta charset='UTF-8'><title>Hata</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'><link href='style.css' rel='stylesheet'></head><body class='auth-wrapper'><div class='glass-card auth-card text-center'><h4 class='text-danger mb-3'>Yetkisiz Erişim</h4><p>Bu kaydı düzenleme yetkiniz yok veya kayıt bulunamadı.</p><a href='index.php' class='btn btn-primary mt-3'>Geri Dön</a></div></body></html>");
    }
} else {
    die("Sistem hatası.");
}

// Form gönderildiğinde veriyi GÜNCELLE (UPDATE İşlemi)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tarih = $_POST['tarih'];
    $adet = $_POST['adet'];
    $tutar = $_POST['toplam_tutar'];

    $guncelle_sql = "UPDATE ceza_kayitlari SET tarih=?, adet=?, toplam_tutar=? WHERE id=?";
    $g_stmt = $db->prepare($guncelle_sql);
    if ($g_stmt) {
        $g_stmt->bind_param("sidi", $tarih, $adet, $tutar, $id);
        
        if ($g_stmt->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $mesaj = "<div class='alert alert-danger'>Güncelleme sırasında bir hata oluştu.</div>";
        }
        $g_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Düzenle - Trafik Cezası Otomasyonu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="auth-wrapper">
    <div class="glass-card auth-card">
        <h4 class="auth-title mb-4">✏️ Kaydı Düzenle</h4>
        <?= $mesaj ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">İşlem Tarihi</label>
                <input type="date" name="tarih" class="form-control" value="<?= htmlspecialchars($mevcut_veri['tarih']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Ceza Adedi</label>
                <input type="number" name="adet" class="form-control" value="<?= htmlspecialchars($mevcut_veri['adet']) ?>" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Toplam Tutar (₺)</label>
                <input type="number" step="0.01" name="toplam_tutar" class="form-control" value="<?= htmlspecialchars($mevcut_veri['toplam_tutar']) ?>" required>
            </div>
            <button type="submit" class="btn w-100" style="background: #fbbf24; color: #78350f; font-weight: 600;">Değişiklikleri Kaydet</button>
            <a href="index.php" class="btn w-100 mt-2" style="background: #e2e8f0; color: #475569; border: none;">İptal Et ve Geri Dön</a>
        </form>
    </div>
</body>
</html>