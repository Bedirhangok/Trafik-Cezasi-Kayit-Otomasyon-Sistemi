# 🚦 Trafik Cezası Kayıt Otomasyon Sistemi

PHP, MySQL, Bootstrap 5 ve saf (vanilla) JavaScript kullanılarak geliştirilmiş, rol tabanlı erişim kontrolüne sahip kapsamlı bir trafik cezası kayıt ve yönetim sistemi.

---

## 📋 Proje Hakkında

Bu uygulama, trafik polislerinin ceza kayıtlarını dijital ortamda tutmasına ve komiser seviyesindeki yetkililerin tüm kayıtlara erişerek yönetmesine olanak tanır. Proje; kullanıcı kaydı, şifreli giriş, CRUD (Oluştur / Oku / Güncelle / Sil) işlemleri ve rol tabanlı yetkilendirme gibi temel web uygulama özelliklerini kapsamaktadır.

---

## ✨ Özellikler

| Özellik | Açıklama |
|---|---|
| 👤 Kullanıcı Kaydı | `password_hash()` ile bcrypt hashlenmiş şifre |
| 🔐 Oturum Açma/Kapama | PHP Sessions ile güvenli oturum yönetimi |
| ➕ Ceza Kaydı Oluşturma | Tek seferde birden fazla ceza satırı eklenebilir |
| 📋 Kayıt Listeleme | Rol'e göre filtrelenmiş liste görünümü |
| ✏️ Kayıt Düzenleme | Tarih, adet ve tutar güncellenebilir |
| 🗑️ Kayıt Silme | Onay diyaloğu ile güvenli silme |
| 🛡️ Rol Tabanlı Yetki | Polis: kendi kayıtları — Komiser: tüm kayıtlar |

---

## 🛠️ Teknoloji Yığını

- **Backend:** Saf PHP 8.x (framework kullanılmadı)
- **Veritabanı:** MySQL / MariaDB (MySQLi + Prepared Statements)
- **Frontend:** HTML5, Bootstrap 5.3, Vanilla JavaScript
- **Stil:** Özel CSS (Glassmorphism tasarım dili, Google Inter fontu)
- **Güvenlik:** `password_hash`, `session_start`, `htmlspecialchars`, Prepared Statements

---

## 📁 Dosya Yapısı

```
Trafik-Cezasi-Kayit-Otomasyon-Sistemi-main/
│
├── baglanti.php          # Veritabanı bağlantı yapılandırması
├── index.php             # Ana dashboard (CREATE + READ)
├── login.php             # Kullanıcı giriş sayfası
├── kayit.php             # Yeni kullanıcı kayıt sayfası
├── duzenle.php           # Kayıt düzenleme (UPDATE)
├── sil.php               # Kayıt silme (DELETE)
├── cikis.php             # Oturum sonlandırma
├── style.css             # Özel CSS stilleri
├── veritabani.sql        # Veritabanı şeması ve örnek veriler
├── README.md             # Bu dosya
├── Teknik.md             # Teknik değişiklik günlüğü
└── özet.md               # Proje özeti ve kural uyum raporu
```

---

## ⚙️ Kurulum ve Çalıştırma

### Gereksinimler
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache/Nginx (XAMPP, WAMP veya canlı hosting)

### Adım 1 — Veritabanını Kur

phpMyAdmin'de veya MySQL CLI'da aşağıdaki komutu çalıştırın:

```bash
mysql -u root -p < veritabani.sql
```

Veya `veritabani.sql` dosyasının içeriğini phpMyAdmin > SQL sekmesine yapıştırın.

### Adım 2 — Bağlantı Ayarlarını Yap

`baglanti.php` dosyasını açın ve kendi ortamınıza göre güncelleyin:

```php
$host       = "localhost";
$kullanici  = "root";          // MySQL kullanıcı adınız
$sifre      = "";              // MySQL şifreniz
$veritabani = "trafik_db";    // Veritabanı adı
```

> ⚠️ **Hosting'e alırken** bu değerleri hosting sağlayıcınızın verdiği bilgilerle değiştirmeyi unutmayın!

### Adım 3 — Çalıştır

Proje klasörünü XAMPP'ın `htdocs/` klasörüne kopyalayın ve tarayıcıdan erişin:

```
http://localhost/Trafik-Cezasi-Kayit-Otomasyon-Sistemi-main/login.php
```

---

## 🖼️ Ekran Görüntüleri

> *(Uygulamayı çalıştırdıktan sonra kendi ekran görüntülerinizi buraya ekleyin)*

**Giriş Sayfası**

![Giriş Sayfası](screenshots/login.png)

**Ana Dashboard**

![Dashboard](screenshots/dashboard.png)

---

## 🎬 Tanıtım Videosu

> *(YouTube veya açık erişimli Google Drive bağlantınızı buraya ekleyin)*

📹 [Uygulamayı YouTube'da İzle](#)

---

## 🗄️ Veritabanı Şeması

```
kullanicilar
├── id (PK, AUTO_INCREMENT)
├── kullanici_adi (UNIQUE)
├── sifre (bcrypt hash)
├── rol (ENUM: 'polis' | 'komiser')
└── olusturma_tar (TIMESTAMP)

ceza_maddeleri
├── id (PK)
├── madde_no (UNIQUE, örn: M-51/1)
├── aciklama
└── tutar

ceza_kayitlari
├── id (PK)
├── kullanici_id (FK → kullanicilar.id)
├── tarih
├── madde_id (FK → ceza_maddeleri.id)
├── adet
├── toplam_tutar
└── kayit_tar (TIMESTAMP)
```

---

## 🔒 Güvenlik Önlemleri

- **Şifre Hashleme:** `password_hash($sifre, PASSWORD_DEFAULT)` — bcrypt algoritması
- **Şifre Doğrulama:** `password_verify()` ile karşılaştırma
- **Session Yönetimi:** `session_start()` + `session_regenerate_id(true)` (session fixation koruması)
- **SQL Injection Koruması:** Tüm sorgular MySQLi Prepared Statements kullanır
- **XSS Koruması:** Çıktılar `htmlspecialchars()` ile temizlenir
- **Yetki Kontrolü:** Her işlemde rol ve sahiplik doğrulaması

---

## 📝 Lisans

Bu proje eğitim amaçlı geliştirilmiştir.
