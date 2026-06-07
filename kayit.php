<?php
session_start();
require_once 'baglanti.php';
$mesaj = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $sifre = $_POST['sifre'];
    $rol = $_POST['rol'];

    if (!empty($kullanici_adi) && !empty($sifre) && !empty($rol)) {
        // Kural: Şifreyi Hashle
        $hashli_sifre = password_hash($sifre, PASSWORD_DEFAULT);

        $sql = "INSERT INTO kullanicilar (kullanici_adi, sifre, rol) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("sss", $kullanici_adi, $hashli_sifre, $rol);
            
            if ($stmt->execute()) {
                $mesaj = "<div class='alert alert-success'>Kayıt başarılı! <a href='login.php' class='alert-link'>Giriş Yap</a></div>";
            } else {
                $mesaj = "<div class='alert alert-danger'>Hata: Bu kullanıcı adı zaten alınmış olabilir veya sistemsel bir sorun var.</div>";
            }
            $stmt->close();
        } else {
            $mesaj = "<div class='alert alert-danger'>Sistem hatası. Lütfen daha sonra tekrar deneyin.</div>";
        }
    } else {
        $mesaj = "<div class='alert alert-warning'>Lütfen tüm alanları doldurun.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sisteme Kayıt Ol - Trafik Cezası Otomasyonu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="auth-wrapper">
    <div class="glass-card auth-card">
        <h3 class="auth-title">Personel Kayıt</h3>
        <?= $mesaj ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Kullanıcı Adı</label>
                <input type="text" name="kullanici_adi" class="form-control" required placeholder="Kullanıcı adı belirleyin">
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
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
        </form>
        <div class="mt-4 text-center">
            <a href="login.php" class="text-decoration-none text-muted" style="font-weight: 500;">Zaten hesabın var mı? <span style="color: var(--primary);">Giriş Yap</span></a>
        </div>
    </div>
</body>
</html>