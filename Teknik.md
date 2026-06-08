# Teknik.md — Değişiklik Günlüğü

Bu dosya, "Trafik Cezası Kayıt Otomasyon Sistemi" projesinde yapılan tüm teknik değişiklikleri,
değişiklik yapılan dosyaları ve değiştirilen kod bloklarını belgeler.

---

## 1. `baglanti.php` — Veritabanı Bağlantı Dosyası

### Ne Değişti?
- Boş bırakılan bağlantı bilgilerine (`root`, `trafik_db`) varsayılan yerel değerler atandı.
- Güvenlik yorumları ve dosya açıklamasını içeren docblock eklendi.
- Hata mesajının kullanıcıya hassas bilgi sızdırmadığını vurgulayan yorum satırı eklendi.

### Değişen Kod

**Öncesi:**
```php
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$kullanici = "";
$sifre = "";
$veritabani = "";

try {
    $db = new mysqli($host, $kullanici, $sifre, $veritabani);
    $db->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Veritabanı bağlantı hatası. Lütfen sistem yöneticisi ile iletişime geçin.");
}
?>
```

**Sonrası:**
```php
<?php
/**
 * Veritabanı Bağlantı Dosyası
 * 
 * KURULUM: Aşağıdaki bilgileri kendi ortamınıza göre doldurun.
 * Canlı ortama (hosting) alırken bu değerleri güncelleyin.
 * Bu dosyayı asla Github'a hassas bilgilerle birlikte yüklemeyin!
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host      = "localhost";   // Veritabanı sunucu adresi
$kullanici = "root";        // MySQL kullanıcı adı (hosting'de değişecek)
$sifre     = "";            // MySQL şifresi   (hosting'de değişecek)
$veritabani = "trafik_db"; // Veritabanı adı  (hosting'de değişecek)

try {
    $db = new mysqli($host, $kullanici, $sifre, $veritabani);
    $db->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Güvenlik: Hata detayını kullanıcıya gösterme
    die("Veritabanı bağlantı hatası. Lütfen sistem yöneticisi ile iletişime geçin.");
}
?>
```

---

## 2. `sil.php` — Kayıt Silme (DELETE)

### Ne Değişti?
**Kritik güvenlik açığı giderildi:** `$aktif_id` değişkeni doğrudan SQL sorgusuna string interpolasyon ile ekleniyordu. Bu, oturum değişkenlerinin manipüle edilebileceği senaryolarda SQL Injection'a yol açabilir.

Çözüm: `$rol == 'polis'` dalında, `$aktif_id` de artık prepared statement'a `bind_param("ii", ...)` ile bağlanmaktadır.

Ayrıca: Komiser ve polis için **ayrı** SQL dalları oluşturuldu; kodun okunabilirliği ve güvenliği artırıldı.

### Değişen Kod

**Öncesi (güvenlik açığı):**
```php
// Hatalı: $aktif_id direkt SQL'e ekleniyor
$sql = "DELETE FROM ceza_kayitlari WHERE id = ?";
if ($rol == 'polis') {
    $sql .= " AND kullanici_id = $aktif_id"; // ← SQL Injection riski
}
$stmt = $db->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $id); // Sadece $id bağlanıyor, $aktif_id değil!
    $stmt->execute();
    $stmt->close();
}
```

**Sonrası (güvenli):**
```php
if ($rol == 'polis') {
    // Polis: Sadece kendi eklediği kaydı silebilir
    // Güvenlik: $aktif_id de parametre olarak bağlanıyor
    $sql  = "DELETE FROM ceza_kayitlari WHERE id = ? AND kullanici_id = ?";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $id, $aktif_id); // Her iki parametre de güvenli
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
```

---

## 3. `duzenle.php` — Kayıt Düzenleme (UPDATE)

### Ne Değişti?
`sil.php` ile aynı tip SQL Injection güvenlik açığı mevcuttu: SELECT sorgusunda `$aktif_id` direkt olarak string interpolasyon ile ekleniyor, ancak sadece `$id` parametre olarak bağlanıyordu.

Çözüm: Polis ve komiser için ayrı hazırlanmış `prepare()` çağrıları oluşturuldu; her iki parametreye de `bind_param` uygulandı. Ayrıca `$mevcut_veri` kontrolü `if` bloğunun dışına taşınarak her iki rol için de çalışacak şekilde düzenlendi.

### Değişen Kod

**Öncesi (güvenlik açığı):**
```php
$sql = "SELECT * FROM ceza_kayitlari WHERE id = ?";
if ($rol == 'polis') {
    $sql .= " AND kullanici_id = $aktif_id"; // ← SQL Injection riski
}
$stmt = $db->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $id); // $aktif_id bağlanmıyor!
    ...
}
```

**Sonrası (güvenli):**
```php
if ($rol == 'polis') {
    $sql  = "SELECT * FROM ceza_kayitlari WHERE id = ? AND kullanici_id = ?";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $id, $aktif_id); // Her iki parametre güvenli
        $stmt->execute();
        $sonuc       = $stmt->get_result();
        $mevcut_veri = $sonuc->fetch_assoc();
        $stmt->close();
    } else {
        die("Sistem hatası.");
    }
} else {
    $sql  = "SELECT * FROM ceza_kayitlari WHERE id = ?";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $sonuc       = $stmt->get_result();
        $mevcut_veri = $sonuc->fetch_assoc();
        $stmt->close();
    } else {
        die("Sistem hatası.");
    }
}
```

---

## 4. `index.php` — Ana Dashboard (READ + CREATE)

### Ne Değişti?
Listeleme sorgusunda `$aktif_id` doğrudan SQL'e ekleniyordu. Polis rolü için listeleme sorgusu artık prepared statement ile çalışmaktadır.

### Değişen Kod

**Öncesi (güvenlik açığı):**
```php
if ($rol == 'polis') {
    $liste_sorgusu .= " WHERE c.kullanici_id = $aktif_id "; // ← Direkt interpolasyon
}
$liste_sorgusu .= " ORDER BY c.id DESC";
$kayitlar = $db->query($liste_sorgusu);
```

**Sonrası (güvenli):**
```php
if ($rol == 'polis') {
    $liste_sorgusu .= " WHERE c.kullanici_id = ? ORDER BY c.id DESC";
    $liste_stmt = $db->prepare($liste_sorgusu);
    $liste_stmt->bind_param("i", $aktif_id); // $aktif_id güvenli şekilde bağlanıyor
    $liste_stmt->execute();
    $kayitlar = $liste_stmt->get_result();
} else {
    $liste_sorgusu .= " ORDER BY c.id DESC";
    $kayitlar = $db->query($liste_sorgusu);
}
```

---

## 5. Yeni Oluşturulan Dosyalar

### `veritabani.sql`
- Veritabanı ve tabloları oluşturan tam SQL scripti.
- `CREATE DATABASE IF NOT EXISTS trafik_db` ile başlar.
- `kullanicilar`, `ceza_maddeleri`, `ceza_kayitlari` tablolarını tanımlar.
- Foreign key ilişkileri ve `utf8mb4` karakter seti içerir.
- `INSERT IGNORE` ile 8 adet örnek ceza maddesi seed datası ekler.

### `README.md`
- Projeyi tanımlayan kapsamlı dokümantasyon.
- Özellikler tablosu, teknoloji yığını, dosya yapısı.
- Kurulum adımları, veritabanı şeması, güvenlik önlemleri.
- Ekran görüntüsü ve video bağlantısı için yer tutucular.

### `Teknik.md` (Bu dosya)
- Her dosyada yapılan değişikliklerin detaylı açıklaması.
- Öncesi / sonrası kod karşılaştırmaları.

### `özet.md`
- Projenin kısa özeti.
- Kural uyum kontrol listesi.

---

## Özet: Değişiklik Tablosu

| Dosya | Değişiklik Türü | Konu |
|---|---|---|
| `baglanti.php` | İyileştirme | Docblock, varsayılan değerler, yorum satırları |
| `sil.php` | **Güvenlik Düzeltmesi** | SQL Injection: `$aktif_id` artık `bind_param` ile bağlanıyor |
| `duzenle.php` | **Güvenlik Düzeltmesi** | SQL Injection: `$aktif_id` artık `bind_param` ile bağlanıyor |
| `index.php` | **Güvenlik Düzeltmesi** | SQL Injection: Listeleme sorgusu prepared statement'a taşındı |
| `veritabani.sql` | **Yeni Dosya** | Veritabanı şeması ve seed data |
| `README.md` | **Yeniden Yazıldı** | Kapsamlı proje dokümantasyonu |
| `Teknik.md` | **Yeni Dosya** | Bu teknik değişiklik günlüğü |
| `özet.md` | **Yeni Dosya** | Proje özeti ve kural uyum raporu |
