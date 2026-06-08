<?php
session_start();
require_once 'baglanti.php';

// Kural: Oturum kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit;
}

// Veritabanına plaka sütunu yoksa otomatik ekle
$check_plaka = $db->query("SHOW COLUMNS FROM ceza_kayitlari LIKE 'plaka'");
if ($check_plaka && $check_plaka->num_rows == 0) {
    $db->query("ALTER TABLE ceza_kayitlari ADD COLUMN plaka VARCHAR(20) NULL AFTER madde_id");
}

$aktif_id = $_SESSION['kullanici_id'];
$rol = $_SESSION['rol'];
$mesaj = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tarih = $_POST['tarih'];
    $madde_ids = $_POST['madde_id'];
    $manuel_maddeler = $_POST['manuel_madde_no'] ?? [];
    $plakalar = $_POST['plaka'];
    $adetler = $_POST['adet'];
    $tutarlar = $_POST['toplam_tutar'];

    $sql = "INSERT INTO ceza_kayitlari (kullanici_id, tarih, madde_id, plaka, adet, toplam_tutar) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $basarili = 0;
        $hata = 0;
        for ($i = 0; $i < count($madde_ids); $i++) {
            $m_id = $madde_ids[$i];
            
            // Manuel madde girilmişse DB'de ara, yoksa yeni madde oluştur
            if ($m_id === 'manuel') {
                $manuel_madde = strtoupper(trim($manuel_maddeler[$i] ?? ''));
                if (!empty($manuel_madde)) {
                    $chk = $db->prepare("SELECT id FROM ceza_maddeleri WHERE madde_no = ?");
                    $chk->bind_param("s", $manuel_madde);
                    $chk->execute();
                    $res = $chk->get_result();
                    if ($r = $res->fetch_assoc()) {
                        $m_id = $r['id'];
                    } else {
                        $aciklama = "Sistemden manuel eklendi";
                        $ins = $db->prepare("INSERT INTO ceza_maddeleri (madde_no, aciklama) VALUES (?, ?)");
                        $ins->bind_param("ss", $manuel_madde, $aciklama);
                        $ins->execute();
                        $m_id = $ins->insert_id;
                        $ins->close();
                    }
                    $chk->close();
                } else {
                    $hata++;
                    continue; // Manuel seçilip boş bırakıldıysa bu satırı atla
                }
            }
            
            $p = strtoupper(trim($plakalar[$i]));
            $a = $adetler[$i];
            $t = $tutarlar[$i];
            
            if(!empty($m_id) && !empty($a) && !empty($t) && !empty($p)) {
                $stmt->bind_param("isisid", $aktif_id, $tarih, $m_id, $p, $a, $t);
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

// Admin için onay bekleyen kullanıcıları çek
$bekleyen_kullanicilar = null;
if ($rol == 'admin') {
    $bekleyen_sorgu = "SELECT id, kullanici_adi, sicil_no, rol FROM kullanicilar WHERE is_approved = 0 ORDER BY id DESC";
    $bekleyen_kullanicilar = $db->query($bekleyen_sorgu);
}

// VERİLERİ LİSTELE (READ İşlemi)
$liste_sorgusu = "SELECT c.id, k.kullanici_adi, k.sicil_no, c.tarih, c.plaka, m.madde_no, c.adet, c.toplam_tutar 
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
        <div>
            <?php if($rol == 'admin'): ?>
                <a href="kayit.php" class="btn btn-success btn-sm px-3 me-2" style="border-radius: 8px;">+ Personel Ekle</a>
            <?php endif; ?>
            <a href="cikis.php" class="btn btn-danger btn-sm px-3" style="border-radius: 8px;">Çıkış Yap</a>
        </div>
    </div>

    <?php if ($rol == 'admin' && $bekleyen_kullanicilar && $bekleyen_kullanicilar->num_rows > 0): ?>
    <div class="alert alert-warning mb-4 shadow-sm border-warning" style="background-color: rgba(255, 243, 205, 0.9);">
        <h5 class="mb-3" style="color: #856404;">📋 Onay Bekleyen Kullanıcılar</h5>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle" style="background: transparent;">
                <thead>
                    <tr style="border-bottom: 2px solid #ffeeba;">
                        <th style="color: #856404;">Kullanıcı Adı</th>
                        <th style="color: #856404;">Sicil No</th>
                        <th style="color: #856404;">Rol</th>
                        <th class="text-end" style="color: #856404;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($bekleyen = $bekleyen_kullanicilar->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #ffeeba;">
                        <td><strong><?= htmlspecialchars($bekleyen['kullanici_adi']) ?></strong></td>
                        <td><?= htmlspecialchars($bekleyen['sicil_no']) ?></td>
                        <td><span class="badge bg-secondary"><?= strtoupper($bekleyen['rol']) ?></span></td>
                        <td class="text-end">
                            <a href="kullanici_islem.php?id=<?= $bekleyen['id'] ?>&islem=onayla" class="btn btn-success btn-sm px-3" onclick="return confirm('Kullanıcıyı onaylamak istediğinize emin misiniz?')">Onayla</a>
                            <a href="kullanici_islem.php?id=<?= $bekleyen['id'] ?>&islem=reddet" class="btn btn-danger btn-sm px-3 ms-1" onclick="return confirm('Kullanıcıyı reddedip silmek istediğinize emin misiniz?')">Reddet</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

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
                                <select name="madde_id[]" class="form-select madde-select" required>
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
                                    <option value="manuel" style="font-weight:bold; color:var(--primary);">+ Diğer / Manuel Gir...</option>
                                </select>
                                <input type="text" name="manuel_madde_no[]" class="form-control mt-2 manuel-input" placeholder="Ceza maddesini yazın (Örn: 47/1-B)" style="display:none; text-transform:uppercase;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Araç Plakası</label>
                                <input type="text" name="plaka[]" class="form-control" required placeholder="Örn: 16 ABC 123" style="text-transform: uppercase;">
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
                                    <th>Plaka</th>
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
                                        <?php if($rol == 'komiser') { 
                                            $gosterilecek_isim = htmlspecialchars($row['kullanici_adi']);
                                            if (!empty($row['sicil_no'])) {
                                                $gosterilecek_isim .= " (" . htmlspecialchars($row['sicil_no']) . ")";
                                            }
                                            echo "<td><span class='badge' style='background: #cbd5e1; color: #1e293b;'>".$gosterilecek_isim."</span></td>"; 
                                        } ?>
                                        <td><span style="font-weight: 500;"><?= date("d.m.Y", strtotime($row['tarih'])) ?></span></td>
                                        <td><span class="badge bg-warning text-dark border"><?= htmlspecialchars($row['plaka'] ?? '-') ?></span></td>
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

    // Delegasyon ile manuel giriş kutusunu göster/gizle
    container.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('madde-select')) {
            const input = e.target.nextElementSibling;
            if (e.target.value === 'manuel') {
                input.style.display = 'block';
                input.required = true;
            } else {
                input.style.display = 'none';
                input.required = false;
                input.value = '';
            }
        }
    });

    satirEkleBtn.addEventListener('click', function() {
        satirSayisi++;
        const yeniSatir = document.createElement('div');
        yeniSatir.className = 'ceza-satiri border p-3 mb-3 rounded';
        yeniSatir.style.background = 'rgba(255,255,255,0.5)';
        
        yeniSatir.innerHTML = `
            <div class="mb-2 d-flex justify-content-between align-items-center">
                <span class="badge bg-secondary">Ceza #${satirSayisi}</span>
                <button type="button" class="btn btn-sm btn-outline-danger satir-sil">Sil</button>
            </div>
            <div class="mb-3">
                <label class="form-label">Ceza Maddesi</label>
                <select name="madde_id[]" class="form-select madde-select" required>
                    <option value="">Lütfen seçiniz...</option>
                    ${secenekler}
                    <option value="manuel" style="font-weight:bold; color:var(--primary);">+ Diğer / Manuel Gir...</option>
                </select>
                <input type="text" name="manuel_madde_no[]" class="form-control mt-2 manuel-input" placeholder="Ceza maddesini yazın (Örn: 47/1-B)" style="display:none; text-transform:uppercase;">
            </div>
            <div class="mb-3">
                <label class="form-label">Araç Plakası</label>
                <input type="text" name="plaka[]" class="form-control" required placeholder="Örn: 16 ABC 123" style="text-transform: uppercase;">
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