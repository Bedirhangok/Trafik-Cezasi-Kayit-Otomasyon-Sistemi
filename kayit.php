<?php
session_start();
require_once 'baglanti.php';

// Veritabanına sicil_no sütunu yoksa otomatik ekle
$check_col = $db->query("SHOW COLUMNS FROM kullanicilar LIKE 'sicil_no'");
if ($check_col && $check_col->num_rows == 0) {
    $db->query("ALTER TABLE kullanicilar ADD COLUMN sicil_no VARCHAR(50) NULL AFTER kullanici_adi");
}

// Veritabanına is_approved sütunu yoksa otomatik ekle
$check_col_app = $db->query("SHOW COLUMNS FROM kullanicilar LIKE 'is_approved'");
if ($check_col_app && $check_col_app->num_rows == 0) {
    $db->query("ALTER TABLE kullanicilar ADD COLUMN is_approved TINYINT(1) DEFAULT 0 AFTER rol");
    // Mevcut kullanıcıları onaylı yap (sisteme girebilmeleri için)
    $db->query("UPDATE kullanicilar SET is_approved = 1");
}

$mesaj = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $sicil_no = trim($_POST['sicil_no']);
    $sifre = $_POST['sifre'];
    $rol = $_POST['rol'];

    if (!empty($kullanici_adi) && !empty($sifre) && !empty($rol) && !empty($sicil_no)) {
        // Kontrol: Kullanıcı adı veya sicil no zaten var mı?
        $check_sql = "SELECT id FROM kullanicilar WHERE kullanici_adi = ? OR sicil_no = ?";
        $check_stmt = $db->prepare($check_sql);
        $check_stmt->bind_param("ss", $kullanici_adi, $sicil_no);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $mesaj = "<div class='alert alert-danger'>Hata: Bu <strong>kullanıcı adı</strong> veya <strong>sicil numarası</strong> zaten sistemde kayıtlı. Lütfen farklı bilgiler girin.</div>";
            $check_stmt->close();
        } else {
            $check_stmt->close();

            // Kural: Şifreyi Hashle
            $hashli_sifre = password_hash($sifre, PASSWORD_DEFAULT);

            // Eğer admin ekliyorsa otomatik onaylı olsun, dışarıdan kayıt olunuyorsa onaysız (0) olsun
            $is_approved = 0;
            if (isset($_SESSION['kullanici_id']) && $_SESSION['rol'] === 'admin') {
                $is_approved = 1;
            }

            $sql = "INSERT INTO kullanicilar (kullanici_adi, sicil_no, sifre, rol, is_approved) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssssi", $kullanici_adi, $sicil_no, $hashli_sifre, $rol, $is_approved);
            
            if ($stmt->execute()) {
                if ($is_approved == 1) {
                    $mesaj = "<div class='alert alert-success'>Personel başarıyla kaydedildi!</div>";
                } else {
                    $mesaj = "<div class='alert alert-success'>Kayıt başarılı! Sistem yöneticisi onayından sonra giriş yapabileceksiniz.</div>";
                }
            } else {
                $mesaj = "<div class='alert alert-danger'>Hata: Bu kullanıcı adı zaten alınmış olabilir veya sistemsel bir sorun var.</div>";
            }
            $stmt->close();
        } else {
            $mesaj = "<div class='alert alert-danger'>Sistem hatası. Lütfen daha sonra tekrar deneyin.</div>";
        }
        } // else { (check_stmt.num_rows > 0) kapanışı
    } else {
        $mesaj = "<div class='alert alert-warning'>Lütfen tüm alanları doldurun.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sisteme Personel Ekle - Trafik Cezası Otomasyonu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="auth-wrapper">
    <div class="glass-card auth-card">
        <h3 class="auth-title">Sisteme Personel Ekle</h3>
        <?= $mesaj ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Kullanıcı Adı</label>
                <input type="text" name="kullanici_adi" class="form-control" required placeholder="Kullanıcı adı belirleyin">
            </div>
            <div class="mb-3">
                <label class="form-label">Sicil Numarası</label>
                <input type="text" name="sicil_no" class="form-control" required placeholder="Sicil numarasını girin">
            </div>
            <div class="mb-3">
                <label class="form-label">Şifre</label>
                <input type="password" name="sifre" class="form-control" required placeholder="Güçlü bir şifre girin">
            </div>
            <div class="mb-4">
                <label class="form-label">Yetki / Rol</label>
                <select name="rol" class="form-select" required>
                    <option value="polis">Polis (Sadece Kendi Verisi)</option>
                    <option value="komiser">Komiser (Tüm Yetki)</option>
                    <option value="admin">Sistem Yöneticisi (Admin)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Personeli Kaydet</button>
        </form>
        <div class="mt-4 text-center">
            <a href="index.php" class="btn btn-outline-secondary w-100" style="font-weight: 500;">Ana Sayfaya Dön</a>
        </div>
    </div>
</body>
</html>