<?php
session_start();
require_once 'baglanti.php';

// Kural: Oturum kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit;
}

$aktif_id = $_SESSION['kullanici_id'];
$rol = $_SESSION['rol'];
$mesaj = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tarih = $_POST['tarih'];
    $madde_ids = $_POST['madde_id'];
    $adetler = $_POST['adet'];
    $tutarlar = $_POST['toplam_tutar'];

    $sql = "INSERT INTO ceza_kayitlari (kullanici_id, tarih, madde_id, adet, toplam_tutar) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $basarili = 0;
        $hata = 0;
        for ($i = 0; $i < count($madde_ids); $i++) {
            $m_id = $madde_ids[$i];
            $a = $adetler[$i];
            $t = $tutarlar[$i];
            
            if(!empty($m_id) && !empty($a) && !empty($t)) {
                $stmt->bind_param("isiid", $aktif_id, $tarih, $m_id, $a, $t);
                if ($stmt->execute()) {
                    $basarili++;
                } else {
                    $hata++;
                }
            }
        }
        
        if ($hata == 0 && $basarili > 0) {
            $mesaj = "<div class='alert alert-success p-3 mb-4'>✅ $basarili adet ceza kaydı başarıyla eklendi!</div>";
        } elseif ($basarili > 0 && $hata > 0) {
            $mesaj = "<div class='alert alert-warning p-3 mb-4'>⚠️ $basarili kayıt eklendi, ancak $hata kayıt eklenirken hata oluştu. (Aynı günde aynı madde için kısıtlama olabilir)</div>";
        } else {
            $mesaj = "<div class='alert alert-danger p-3 mb-4'>❌ Kayıtlar eklenirken bir hata oluştu.</div>";
        }
        $stmt->close();
    }
}

// Form için ceza maddelerini çek
$maddeler = $db->query("SELECT * FROM ceza_maddeleri ORDER BY madde_no ASC");

// VERİLERİ LİSTELE (READ İşlemi)
$liste_sorgusu = "SELECT c.id, k.kullanici_adi, c.tarih, m.madde_no, c.adet, c.toplam_tutar 
                  FROM ceza_kayitlari c 
                  JOIN ceza_maddeleri m ON c.madde_id = m.id 
                  JOIN kullanicilar k ON c.kullanici_id = k.id ";

// Yetki kontrolü: Polis sadece kendi eklediklerini görür
if ($rol == 'polis') {
    $liste_sorgusu .= " WHERE c.kullanici_id = $aktif_id ";
}
$liste_sorgusu .= " ORDER BY c.id DESC";
$kayitlar = $db->query($liste_sorgusu);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Trafik Cezası Otomasyonu - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    
    <div class="dash-header">
        <h5>Hoşgeldin, <?= htmlspecialchars($_SESSION['kullanici_adi']) ?> <span class="badge bg-primary ms-2"><?= htmlspecialchars(strtoupper($rol)) ?></span></h5>
        <a href="cikis.php" class="btn btn-danger btn-sm px-3" style="border-radius: 8px;">Çıkış Yap</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="glass-card p-4 h-100">
                <h5 class="mb-4" style="font-weight: 600; color: var(--dark);">📝 Yeni Ceza Girişi</h5>
                <?= $mesaj ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">İşlem Tarihi</label>
                        <input type="date" name="tarih" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div id="ceza-satirlari">
                        <div class="ceza-satiri border p-3 mb-3 rounded" style="background: rgba(255,255,255,0.5);">
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="badge bg-secondary">Ceza #1</span>
                                <button type="button" class="btn btn-sm btn-outline-danger satir-sil" style="display:none;">Sil</button>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ceza Maddesi</label>
                                <select name="madde_id[]" class="form-select" required>
                                    <option value="">Lütfen seçiniz...</option>
                                    <?php 
                                    // Yeniden kullanılabilir bir seçenekler string'i oluştur
                                    $options = "";
                                    if ($maddeler) { 
                                        while($m = $maddeler->fetch_assoc()) {
                                            $options .= "<option value='".htmlspecialchars($m['id'])."'>".htmlspecialchars($m['madde_no'])." - ".htmlspecialchars($m['aciklama'])."</option>";
                                        } 
                                    }
                                    echo $options; 
                                    ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ceza Adedi</label>
                                    <input type="number" name="adet[]" class="form-control" min="1" value="1" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Toplam Tutar (₺)</label>
                                    <input type="number" step="0.01" name="toplam_tutar[]" class="form-control" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" id="satir-ekle" class="btn btn-outline-secondary w-100 mb-3">+ Yeni Ceza Ekle</button>
                    <button type="submit" class="btn btn-primary w-100" style="font-weight: 600;">Sisteme Kaydet</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-card p-4 h-100">
                <h5 class="mb-4" style="font-weight: 600; color: var(--dark);">📋 Ceza Kayıtları Listesi</h5>
                <div class="table-container shadow-sm border">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <?php if($rol == 'komiser') echo "<th>Personel</th>"; ?>
                                    <th>Tarih</th>
                                    <th>Madde</th>
                                    <th>Adet</th>
                                    <th>Tutar</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($kayitlar && $kayitlar->num_rows > 0): ?>
                                    <?php while($row = $kayitlar->fetch_assoc()): ?>
                                    <tr>
                                        <?php if($rol == 'komiser') echo "<td><span class='badge' style='background: #cbd5e1; color: #1e293b;'>".htmlspecialchars($row['kullanici_adi'])."</span></td>"; ?>
                                        <td><span style="font-weight: 500;"><?= date("d.m.Y", strtotime($row['tarih'])) ?></span></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['madde_no']) ?></span></td>
                                        <td><?= htmlspecialchars($row['adet']) ?></td>
                                        <td style="font-weight: 600; color: var(--primary);"><?= number_format($row['toplam_tutar'], 2, ',', '.') ?> ₺</td>
                                        <td class="text-end">
                                            <a href="duzenle.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm" style="background: #fbbf24; color: #78350f; border: none;">Düzenle</a>
                                            <a href="sil.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-danger ms-1" onclick="return confirm('Bu kaydı silmek istediğinize emin misiniz?')">Sil</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center p-4 text-muted">Henüz sisteme eklenmiş bir ceza kaydı bulunmamaktadır.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let satirSayisi = 1;
    const container = document.getElementById('ceza-satirlari');
    const satirEkleBtn = document.getElementById('satir-ekle');
    
    // PHP'den maddeleri JS formatına aktar
    const secenekler = `<?= $options ?>`;

    satirEkleBtn.addEventListener('click', function() {
        satirSayisi++;
        const yeniSatir = document.createElement('div');
        yeniSatir.className = 'ceza-satiri border p-3 mb-3 rounded';
        yeniSatir.style.background = 'rgba(255,255,255,0.5)';
        
        yeniSatir.innerHTML = `
            <div class="mb-2 d-flex justify-content-between align-items-center">
                <span class="badge bg-secondary">Ceza #\${satirSayisi}</span>
                <button type="button" class="btn btn-sm btn-outline-danger satir-sil">Sil</button>
            </div>
            <div class="mb-3">
                <label class="form-label">Ceza Maddesi</label>
                <select name="madde_id[]" class="form-select" required>
                    <option value="">Lütfen seçiniz...</option>
                    \${secenekler}
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ceza Adedi</label>
                    <input type="number" name="adet[]" class="form-control" min="1" value="1" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Toplam Tutar (₺)</label>
                    <input type="number" step="0.01" name="toplam_tutar[]" class="form-control" min="0" placeholder="0.00" required>
                </div>
            </div>
        `;
        
        // Silme butonuna event ekle
        yeniSatir.querySelector('.satir-sil').addEventListener('click', function() {
            yeniSatir.remove();
            guncelleSiraNumaralari();
        });
        
        container.appendChild(yeniSatir);
        guncelleSiraNumaralari();
    });

    function guncelleSiraNumaralari() {
        const satirlar = container.querySelectorAll('.ceza-satiri');
        satirlar.forEach((satir, index) => {
            satir.querySelector('.badge').textContent = 'Ceza #' + (index + 1);
            const silBtn = satir.querySelector('.satir-sil');
            // İlk satırın silinmesini engelle
            if (index === 0 && satirlar.length === 1) {
                silBtn.style.display = 'none';
            } else {
                silBtn.style.display = 'inline-block';
            }
        });
    }
});
</script>
</body>
</html>