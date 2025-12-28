# Tennis Scoreboard System

Sistem scoreboard tenis modern dengan 3 role user (Admin, Wasit, Penonton) yang mendukung multiple matches dan real-time score updates.

## Fitur

- ✅ **3 Role User**: Admin, Wasit, Penonton dengan akses berbeda
- ✅ **Match Management**: Pengaturan lengkap pertandingan (Single/Double, Set, Game, Deuce)
- ✅ **Player Management**: Kelola pemain dengan kategori (MS, WS, MD, WD, XD)
- ✅ **Real-time Score Updates**: Update skor secara real-time tanpa refresh
- ✅ **Modern UI**: Tampilan modern dengan Tailwind CSS
- ✅ **Scoreboard Display**: Tampilan fullscreen untuk scoreboard
- ✅ **Multiple Matches**: Support beberapa pertandingan berjalan bersamaan

## Requirements

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi (atau MariaDB)
- Apache Web Server
- Windows OS (untuk deployment di server lokal)

## Struktur Folder

```
tennis/
├── admin/              # Halaman Admin
│   ├── index.php
│   ├── match-settings.php
│   ├── match-activate.php
│   └── players.php
├── api/                # API Endpoints
│   ├── get-score.php
│   ├── update-score.php
│   ├── next-game.php
│   ├── next-set.php
│   └── finish-match.php
├── config/             # Konfigurasi
│   ├── config.php
│   └── database.php
├── database/           # Database Schema
│   └── schema.sql
├── includes/           # Helper Functions
│   ├── auth.php
│   └── functions.php
├── penonton/           # Halaman Penonton
│   ├── index.php
│   └── live-score.php
├── wasit/              # Halaman Wasit
│   ├── index.php
│   └── score-control.php
├── index.php           # Redirect
├── login.php           # Halaman Login
├── logout.php          # Logout
└── scoreboard.php      # Scoreboard Display (Fullscreen)
```

## Instalasi dan Setup

### 1. Persiapan Server

Pastikan Anda sudah menginstall:
- **XAMPP** (Apache + MySQL + PHP) atau
- **Laragon** (Apache + MySQL + PHP) atau
- **WAMP** (Apache + MySQL + PHP)

### 2. Clone/Download Project

1. Letakkan folder `tennis` di direktori web server:
   - **XAMPP**: `C:\xampp\htdocs\tennis`
   - **Laragon**: `C:\laragon\www\tennis`
   - **WAMP**: `C:\wamp64\www\tennis`

### 3. Setup Database

1. Buka **phpMyAdmin** (biasanya di `http://localhost/phpmyadmin`)
2. Buat database baru atau import file `database/schema.sql`
3. Atau jalankan SQL berikut di phpMyAdmin:

```sql
-- Import file database/schema.sql
```

File `database/schema.sql` sudah berisi:
- Struktur database lengkap
- Default users (admin, wasit, penonton)
- Default password: `password` (untuk semua user)

### 4. Konfigurasi Database

Edit file `config/database.php` dan sesuaikan dengan setting MySQL Anda:

```php
define('DB_HOST', 'localhost');    // Host database
define('DB_USER', 'root');         // Username database
define('DB_PASS', '');             // Password database (kosongkan jika tidak ada)
define('DB_NAME', 'tennis_scoreboard'); // Nama database
```

### 5. Konfigurasi Base URL

Edit file `config/config.php` dan sesuaikan BASE_URL:

```php
// Untuk XAMPP
define('BASE_URL', 'http://localhost/tennis/');

// Untuk Laragon
define('BASE_URL', 'http://tennis.test/');

// Atau sesuai dengan URL server Anda
```

### 6. Set Permissions (Jika diperlukan)

Pastikan folder `tennis` memiliki permission untuk read/write (biasanya sudah otomatis di Windows).

### 7. Akses Aplikasi

Buka browser dan akses:
- **XAMPP**: `http://localhost/tennis/`
- **Laragon**: `http://tennis.test/` (jika sudah setup virtual host)
- Atau sesuai dengan konfigurasi server Anda

## Default Login

Setelah setup database, gunakan kredensial berikut:

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `password` |
| Wasit | `wasit` | `password` |
| Penonton | `penonton` | `password` |

**⚠️ PENTING**: Ganti password default setelah login pertama kali!

## Cara Menggunakan

### 1. Login sebagai Admin

1. Login dengan username: `admin` dan password: `password`
2. **Tambah Pemain**:
   - Klik "Kelola Pemain"
   - Isi form: Nama, Kategori, Tim (opsional)
   - Klik "Tambah Pemain"

3. **Buat Pertandingan**:
   - Klik "Pengaturan Match"
   - Isi semua field:
     - Judul Pertandingan
     - Tanggal
     - Tipe Permainan (Single/Double)
     - Jumlah Set (1-5)
     - Game per Set (Normal/Best Of)
     - Deuce (Yes/No)
     - Pilih Pemain untuk Tim 1 dan Tim 2
   - Klik "Simpan Pertandingan"

4. **Aktifkan Pertandingan**:
   - Di halaman Dashboard Admin, klik "Aktifkan" pada pertandingan yang ingin dimulai
   - Setelah aktif, Wasit bisa mengontrol skor

### 2. Login sebagai Wasit

1. Login dengan username: `wasit` dan password: `password`
2. Pilih pertandingan aktif
3. **Kontrol Skor**:
   - Klik "Tambah" atau "Kurang" untuk menambah/mengurangi poin
   - Sistem otomatis menghitung game dan set
   - Klik "Game Berikutnya" untuk skip ke game berikutnya
   - Klik "Set Berikutnya" untuk skip ke set berikutnya
   - Klik "Selesai Pertandingan" untuk mengakhiri match

### 3. Login sebagai Penonton

1. Login dengan username: `penonton` dan password: `password`
2. Pilih pertandingan aktif
3. Lihat skor live yang update otomatis setiap 2 detik
4. Klik "Fullscreen" untuk melihat scoreboard fullscreen

### 4. Scoreboard Display

- Akses langsung: `http://localhost/tennis/scoreboard.php?match_id=1`
- Atau klik tombol "Scoreboard" dari halaman Admin/Wasit/Penonton
- Scoreboard akan otomatis fullscreen dan update real-time

## Troubleshooting

### Database Connection Error

1. Pastikan MySQL service berjalan
2. Cek username dan password di `config/database.php`
3. Pastikan database `tennis_scoreboard` sudah dibuat

### Page Not Found / 404 Error

1. Cek BASE_URL di `config/config.php`
2. Pastikan folder `tennis` ada di direktori web server yang benar
3. Pastikan Apache service berjalan

### Real-time Update Tidak Berfungsi

1. Pastikan JavaScript enabled di browser
2. Cek console browser untuk error (F12)
3. Pastikan API endpoints bisa diakses

### Password Default Tidak Bisa Login

1. Pastikan database sudah di-import dengan benar
2. Cek di phpMyAdmin apakah user sudah ada di tabel `users`
3. Jika perlu, reset password dengan query:

```sql
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
```

Password hash tersebut adalah untuk password: `password`

## Teknologi yang Digunakan

- **Backend**: PHP Native (tanpa framework)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Styling**: Tailwind CSS (CDN)
- **Icons**: Font Awesome 6.4.0
- **Real-time**: Polling dengan JavaScript (setInterval)

## Keamanan

- Password di-hash menggunakan `password_hash()` PHP
- Input di-sanitize untuk mencegah XSS
- Prepared statements untuk mencegah SQL Injection
- Session-based authentication

## Support Multiple Matches

Sistem mendukung beberapa pertandingan berjalan bersamaan:
- Admin bisa membuat multiple matches
- Wasit bisa memilih match yang ingin dikontrol
- Penonton bisa melihat multiple matches
- Scoreboard bisa ditampilkan per match dengan parameter `match_id`

## Customization

### Mengubah Tema Warna

Edit class Tailwind CSS di file-file PHP sesuai kebutuhan.

### Menambah Fitur

- File API ada di folder `api/`
- Helper functions ada di `includes/functions.php`
- Authentication logic ada di `includes/auth.php`

## License

Sistem ini dibuat untuk penggunaan internal perusahaan.

## Support

Jika ada pertanyaan atau masalah, silakan hubungi tim IT atau developer yang bertanggung jawab.

---

**Selamat menggunakan Tennis Scoreboard System! 🎾**

