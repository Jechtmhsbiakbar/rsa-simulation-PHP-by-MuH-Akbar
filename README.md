# 🔐 Simulasi RSA: Pengiriman Pesan Satu Arah (Alice & Bob)

Aplikasi web interaktif yang mensimulasikan enkripsi dan dekripsi pesan menggunakan algoritma **RSA (Rivest–Shamir–Adleman)** dengan skenario komunikasi antara Alice dan Bob.

---

## 📸 Preview Aplikasi

### Hasil Tampilan dari Localhost (Laptop Pribadi)

Berikut adalah screenshot aplikasi saat berjalan di server lokal:

![Simulasi RSA Alice dan Bob - Localhost View](image/localhost_rsa-simulation_.png)

**Keterangan Gambar:**
- **Sidebar Kiri**: Identity card dengan informasi pembuat (Muhammad Hasbih Akbar, NIM 231220075, TI 31)
- **Header**: Judul aplikasi dengan deskripsi singkat
- **Flow Bar**: 3 langkah proses simulasi RSA
- **Section 1**: Menampilkan Public Key Alice (format PEM)
- **Section 2**: Plaintext dari Bob dan Ciphertext hasil enkripsi (Base64)
- **Section 3**: Hasil dekripsi dengan verifikasi kesesuaian pesan asli

> 💡 **Catatan**: Gambar ini adalah hasil eksekusi aplikasi di localhost `http://localhost:8000` atau `http://localhost/rsa-simulation/` di laptop pribadi pembuat. Interface responsif ini juga dapat diakses melalui mobile dengan layout yang optimal.

---

## 📖 Deskripsi Proyek

Proyek ini merupakan **tugas mata kuliah Kriptografi** yang mengimplementasikan algoritma RSA secara praktis untuk mendemonstrasikan bagaimana enkripsi asimetrik bekerja dalam komunikasi aman antara dua pihak.

### 🎯 Tujuan
- Memahami konsep enkripsi dan dekripsi menggunakan RSA
- Mempelajari penggunaan Public Key dan Private Key
- Mendemonstrasikan komunikasi aman satu arah dengan RSA

### 📚 Skenario Simulasi

```
┌─────────────┐                              ┌─────────────┐
│   ALICE     │                              │     BOB     │
│             │                              │             │
│ • Generate  │         Public Key           │ • Encrypt   │
│   Keypair   │◄─────────────────────────────│   Message   │
│ (RSA 2048)  │                              │   (with PK) │
│             │                              │             │
│ • Decrypt   │      Ciphertext (Base64)     │             │
│   Message   │◄─────────────────────────────│             │
│ (with SK)   │                              │             │
└─────────────┘                              └─────────────┘
```

#### Alur Kerja:
1. **Alice** membuat pasangan kunci RSA (2048-bit)
   - Public Key → dibagikan ke Bob
   - Private Key → dijaga rahasia

2. **Bob** mengenkripsi pesan menggunakan Public Key Alice
   - Pesan asli (plaintext) dienkripsi
   - Hasil enkripsi dikodekan dalam Base64

3. **Alice** mendekripsi pesan menggunakan Private Key-nya
   - Ciphertext didekripsi
   - Pesan asli pulih dengan aman

---

## 🛠️ Fitur Utama

✅ **Pembangkitan Kunci RSA Dinamis**
- Menghasilkan pasangan kunci RSA 2048-bit secara real-time
- Format PEM (Privacy Enhanced Mail)

✅ **Enkripsi Pesan**
- Menggunakan `openssl_public_encrypt()`
- Padding scheme: PKCS#1
- Output: Base64-encoded

✅ **Dekripsi Pesan**
- Menggunakan `openssl_private_decrypt()`
- Verifikasi kesesuaian pesan asli

✅ **Antarmuka yang Responsif**
- Desktop: Layout sidebar (identity card tetap di kiri)
- Mobile: Layout yang optimal untuk layar kecil
- Toggle expand/collapse untuk key panjang

✅ **Error Handling**
- Penanganan error generation kunci
- Penanganan error enkripsi/dekripsi
- Pesan error yang informatif

---

## 🖥️ Teknologi yang Digunakan

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP 7.0+ |
| Enkripsi | OpenSSL (PHP Extension) |
| Frontend | HTML5, CSS3 |
| Interaktif | JavaScript Vanilla |
| Environment | XAMPP / Laragon / Linux Server |

---

## 📋 Requirements

### Sistem
- PHP 7.0 atau lebih baru
- OpenSSL PHP Extension (`php_openssl`)
- Web Server (Apache/Nginx)

### Kompatibilitas
✅ Windows (XAMPP, Laragon)
✅ Linux/macOS
✅ Cloud Hosting (Shared Hosting)

---

## ⚙️ Instalasi & Setup

### 1. Clone Repository
```bash
git clone <repository-url>
cd rsa-simulation-PHP-by-MuH-Akbar
```

### 2. Konfigurasi OpenSSL (Windows XAMPP)
Aplikasi secara otomatis mencari `openssl.cnf` di lokasi umum:
```
C:/xampp/apache/conf/openssl.cnf
C:/xampp/php/extras/openssl/openssl.cnf
C:/laragon/bin/apache/openssl.cnf
/etc/ssl/openssl.cnf (Linux/macOS)
```

Jika tidak ditemukan, set manual:
```php
putenv("OPENSSL_CONF=/path/to/openssl.cnf");
```

### 3. Jalankan di Local Server
```bash
# Gunakan PHP built-in server
php -S localhost:8000

# Atau via XAMPP
# Letakkan di: C:\xampp\htdocs\rsa-simulation-PHP-by-MuH-Akbar
# Akses: http://localhost/rsa-simulation-PHP-by-MuH-Akbar
```

---

## 🚀 Penggunaan

1. Buka aplikasi di browser
2. Halaman akan otomatis:
   - Membuat pasangan kunci RSA
   - Mengenkripsi pesan demo dari Bob
   - Mendekripsi pesan menggunakan Private Key Alice
3. Lihat hasil lengkap di ketiga section:
   - **Section 1**: Public Key Alice (dapat diexpand)
   - **Section 2**: Pesan asli dan Ciphertext
   - **Section 3**: Hasil dekripsi dan verifikasi

---

## 📊 Struktur Kode

### Bagian 1: Setup Alice (Generate Keypair)
```php
$config = [
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];
$keyPair = openssl_pkey_new($config);
```

### Bagian 2: Bob Encrypt
```php
$plaintext = "Halo Alice, ini pesan rahasia dari Bob";
openssl_public_encrypt($plaintext, $ciphertext, $publicKey);
```

### Bagian 3: Alice Decrypt
```php
openssl_private_decrypt($ciphertext, $decrypted, $privateKey);
```

---

## 🎨 Antarmuka Pengguna

### Desktop View
- **Identity Card**: Fixed sidebar di kiri (210px)
- **Main Content**: Flow bar + 3 section card
- **Key Box**: Scrollable, collapsible jika diexpand

### Mobile View
- **Identity Card**: Horizontal topbar dengan chips
- **Main Content**: Full width layout
- **Key Box**: Collapsed by default, expandable dengan button

### Fitur Interaktif
- Toggle button untuk expand/collapse public key
- Responsive design untuk berbagai ukuran layar
- Color scheme profesional (blue theme)

---

## 📝 Informasi Pembuat

| Item | Keterangan |
|------|-----------|
| **Nama** | Muhammad Hasbih Akbar |
| **NIM** | 231220075 |
| **Kelas** | TI 31 (Teknik Informatika) |
| **Mata Kuliah** | Kriptografi |
| **Dosen** | Sucipto, M.Kom |
| **Institusi** | Universitas |

---

## 🌐 Live Demo

Aplikasi ini telah di-deploy dan dapat diakses di:

🔗 **[https://muhakbar-rsa-simulation.infinityfree.me/](https://muhakbar-rsa-simulation.infinityfree.me/)**

### ⚠️ Catatan Hosting - InfinityFree

**Keterbatasan SSL di InfinityFree:**

InfinityFree adalah layanan free hosting yang memiliki beberapa keterbatasan, khususnya:

1. **SSL/TLS Support Terbatas**
   - InfinityFree tidak menyediakan SSL certificate untuk subdomain custom secara native
   - Domain custom (`.infinityfree.me`) tidak support HTTPS penuh
   - Aplikasi ini berjalan di protokol HTTP (tidak terenkripsi)

2. **Implikasi untuk Aplikasi RSA**
   - ✅ Enkripsi RSA tetap berfungsi dengan baik di aplikasi
   - ✅ Semua proses enkripsi terjadi di **server-side PHP**, bukan di browser
   - ⚠️ Transmisi data antara browser dan server tidak terenkripsi oleh SSL
   - ⚠️ Hanya cocok untuk **keperluan edukasi/demonstrasi**, bukan production

3. **Solusi Alternatif Hosting**
   Untuk mendapatkan SSL support penuh, gunakan:
   - **Netlify** / **Vercel** (untuk static sites)
   - **Heroku** / **Render** (free tier dengan SSL)
   - **PythonAnywhere** (untuk Python+Web apps)
   - **000webhost** (free hosting dengan SSL gratis)
   - **VPS Murah** (DigitalOcean, Linode, Vultr - ~$5/bulan)

4. **Best Practice untuk Production**
   ```
   ┌─────────────────────────────────┐
   │ Client (HTTPS) ◄──────► Server  │
   │                  SSL/TLS         │
   │                                 │
   │ Browser  ──encrypt──►  RSA      │
   │ (HTTPS)               Encrypt   │
   └─────────────────────────────────┘
   ```
   - Gunakan HTTPS untuk seluruh komunikasi
   - Simpan private key di server dengan permission terbatas
   - Jangan expose private key di source code publik

5. **Keamanan Aplikasi Ini**
   - ✅ Aman untuk tujuan **pembelajaran/demonstrasi**
   - ✅ Cocok untuk **tugas akademik**
   - ❌ **Tidak aman untuk data sensitif real-world**
   - ❌ **Jangan gunakan untuk transaksi/data pribadi**

---

## 📚 Referensi & Teori RSA

### Konsep Dasar
**RSA** adalah algoritma enkripsi asimetrik yang menggunakan sepasang kunci:
- **Public Key (n, e)**: Digunakan untuk enkripsi, boleh dibagikan
- **Private Key (n, d)**: Digunakan untuk dekripsi, harus dirahasiakan

### Rumus Matematika
- **Enkripsi**: C ≡ M^e (mod n)
- **Dekripsi**: M ≡ C^d (mod n)

### Keamanan RSA
- Kesulitan memfaktorkan bilangan besar (Factoring Problem)
- 2048-bit RSA dianggap aman hingga tahun 2030-an
- Tidak rentan terhadap serangan brute-force dalam waktu praktis

### Padding Scheme
- **PKCS#1 v1.5**: Standar yang digunakan dalam aplikasi ini
- Menambahkan random padding untuk keamanan tambahan
- Mencegah known plaintext attack

---

## ⚠️ Catatan Penting

1. **Security Notice**: 
   - Aplikasi ini untuk tujuan **edukasi dan demonstrasi**
   - Untuk production, gunakan library cryptography yang telah teruji
   - Jangan simpan private key di log atau file tanpa enkripsi

2. **Performance**:
   - Pembangkitan kunci 2048-bit memerlukan waktu ~1-2 detik
   - Enkripsi/dekripsi cukup cepat (<100ms)

3. **Kompatibilitas Browser**:
   - Chrome, Firefox, Safari, Edge (semua versi modern)
   - IE11 tidak fully supported

---

## 📄 Lisensi

Proyek ini adalah tugas akademik dan tersedia untuk keperluan edukasi.

---

## 🤝 Kontribusi

Untuk perbaikan atau saran:
1. Fork repository
2. Buat branch fitur (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buka Pull Request

---

## 📧 Kontak

Untuk pertanyaan atau diskusi lebih lanjut mengenai proyek ini, silakan hubungi pembuat melalui institusi.

---

**Dibuat dengan ❤️ untuk Mata Kuliah Kriptografi**