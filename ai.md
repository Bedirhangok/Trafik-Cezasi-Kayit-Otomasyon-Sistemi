# Geliştirici Günlüğü: Yapay Zeka ile Soru-Cevap

Bu dosya, projenin geliştirme sürecinde yapay zeka ile yapılan teknik fikir alışverişlerini (kodları doğrudan kopyalamadan) ufak detaylar halinde not etmek için oluşturulmuştur.

---

**Geliştirici:** Sistemi kodlarken `baglanti.php` ve `scratch_check.php` arasındaki temel fark nedir? Neden iki farklı dosya var?

**Yapay Zeka (AI):** `baglanti.php` sistemin kalbidir diyebiliriz. Uygulamanın veritabanı bilgilerini tutar ve tüm sayfaların veritabanına güvenle bağlanmasını sağlar. İçerisinde özel bir hata yakalama yapısı vardır; bir sorun çıkarsa kullanıcılara güvenlik amacıyla hatanın kaynağını göstermez, sadece uyarı verir.
`scratch_check.php` ise tamamen arka planda bizim veritabanı tablolarının (örneğin ceza kayıtları) yapısını hızlıca görmek için oluşturduğumuz geçici bir "karalama" dosyasıdır. Sistemin çalışmasında hiçbir rolü yoktur, test amaçlıdır.

---

**Geliştirici:** Peki sistemdeki işlemleri (`kayit.php`, `sil.php` vb.) modüllere ayırmamızın temel sebebi nedir? Çok fazla kod detayı vermeden özetler misin?

**Yapay Zeka (AI):** Tabi ki. Burada temel prensibimiz "her dosyanın tek bir sorumluluğu olmasıdır". Kayıt ekleme, listeleme veya silme işlemlerini tek bir devasa sayfaya yığmak yerine ayırdık. Böylece örneğin veri silerken bir hata alırsanız, sorunun sadece `sil.php` içerisinde olduğunu bilir ve doğrudan oraya müdahale edersiniz. Bu, kodun okunabilirliğini ve yönetimini inanılmaz derecede kolaylaştırır.

---

**Geliştirici:** Veritabanı mimarisindeki en ufak ama en kritik güvenlik detayı neydi?

**Yapay Zeka (AI):** En ufak ama en önemli detay; `mysqli` (veritabanı bağlantı) hatalarını (Exception) hiçbir zaman son kullanıcıya olduğu gibi yansıtmamamızdır. Bir hata oluştuğunda sunucu dizinleri veya SQL yapısı asla ekrana basılmaz, bunun yerine maskelenmiş temiz mesajlar verilir. Şeffaf hata gösterimini sadece `scratch_check` gibi izole edilmiş test dosyalarında bilerek açık bıraktık ki geliştirme yaparken hatayı anında görebilelim.

---

**Geliştirici:** Tüm sistemi incelediğinde şifre güvenliği ve oturum yönetimi için ne gibi ekstra adımlar attık?

**Yapay Zeka (AI):** En dikkat çekici olanlardan biri şifrelerin veritabanında asla düz metin (örneğin "123456" gibi) tutulmamasıdır. Şifreler özel bir algoritmayla şifrelenir ve giriş yaparken sadece doğruluğu kontrol edilir. Ayrıca giriş başarılı olduğunda `session_regenerate_id(true)` kullanarak oturum kimliğini (session ID) yeniliyoruz. Bu çok ufak gibi görünen ama "Session Fixation" (Oturum Çalma) saldırılarını engelleyen hayati bir güvenlik adımıdır.

---

**Geliştirici:** Peki ya roller, yetkilendirmeler ve veritabanının kendi kendini onarma özelliği hakkında ne düşünüyorsun?

**Yapay Zeka (AI):** Harika bir yapı kurduk. Öncelikle "Polis" ve "Admin" arasında kesin bir sınır var. Her memur sadece kendi kestiği cezaları görebiliyor, admin ise herkesi ve onay bekleyen yeni kayıtları yönetebiliyor.
En sevdiğim "kendi kendini onarma" detayı ise şu: Sisteme yeni bir özellik eklediğimizde (örneğin araç "plaka" sütunu veya "onay" durumu), uygulama çalışırken veritabanına bakıp *"Bu sütunlar eksik mi?"* diye kontrol ediyor. Eğer eksikse hata verip çökmek yerine `ALTER TABLE` komutuyla o sütunu veritabanına kendi kendine ekliyor. Bu da sistemi çok sağlam (robust) kılıyor.

---

**Geliştirici:** Son olarak arayüz tasarımı (UI) hakkında ne diyorsun?

**Yapay Zeka (AI):** Sadece işlevsel değil, görsel olarak da iddialı olmuş. CSS tarafında standart, sıkıcı tablolar kullanmak yerine "Glassmorphism" (Buzlu Cam) efektleri kullandık. Arka plandaki renk geçişleri ve pencerelerin arkasını hafifçe flu göstermesi (`backdrop-filter: blur`), bu otomasyon sistemini sıradan bir devlet/kurum uygulamasından çıkarıp oldukça premium ve modern bir görünüme kavuşturmuş.
