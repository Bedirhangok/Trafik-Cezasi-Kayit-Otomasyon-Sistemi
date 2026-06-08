<?php
session_start();
require_once 'baglanti.php';
$mesaj = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kullanici_adi = trim($_POST['kullanici_adi']);
    $sifre = trim($_POST['sifre']);

    if (!empty($kullanici_adi) && !empty($sifre)) {
        $sql = "SELECT id, kullanici_adi, sifre, rol FROM kullanicilar WHERE kullanici_adi = ?";
        $stmt = $db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $kullanici_adi);
            $stmt->execute();
            $sonuc = $stmt->get_result();

            if ($sonuc->num_rows > 0) {
                $kullanici = $sonuc->fetch_assoc();
                
                // Kural: Hashlenmiş şifreyi doğrula
                if (password_verify($sifre, $kullanici['sifre'])) {
                    // Güvenlik: Session Fixation koruması
                    session_regenerate_id(true);
                    
                    // Kural: Session oluştur
                    $_SESSION['kullanici_id'] = $kullanici['id'];
                    $_SESSION['kullanici_adi'] = $kullanici['kullanici_adi'];
                    $_SESSION['rol'] = $kullanici['rol'];
                    header("Location: index.php");
                    exit;
                } else {
                    $mesaj = "<div class='alert alert-danger'>Hatalı şifre girdiniz.</div>";
                }
            } else {
                $mesaj = "<div class='alert alert-danger'>Böyle bir kullanıcı bulunamadı.</div>";
            }
            $stmt->close();
        } else {
            $mesaj = "<div class='alert alert-danger'>Sistem hatası. Lütfen daha sonra tekrar deneyin.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sisteme Giriş - Trafik Cezası Otomasyonu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="auth-wrapper">
    <div class="glass-card auth-card">
        <h3 class="auth-title">Sisteme Giriş</h3>
        <?= $mesaj ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Kullanıcı Adı</label>
                <input type="text" name="kullanici_adi" class="form-control" required placeholder="Kullanıcı adınızı girin">
            </div>
            <div class="mb-4">
                <label class="form-label">Şifre</label>
                <input type="password" name="sifre" class="form-control" required placeholder="Şifrenizi girin">
            </div>
            <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
        </form>
    </div>
</body>
</html>