# Panduan Instalasi Tennis Scoreboard

## Langkah-langkah Instalasi di Windows + Apache + MySQL

### Step 1: Persiapan Environment

#### A. Install XAMPP (Recommended)

1. Download XAMPP dari: https://www.apachefriends.org/
2. Install XAMPP di `C:\xampp`
3. Pastikan Apache dan MySQL service berjalan
4. Buka `http://localhost` untuk verifikasi

#### B. Atau Install Laragon (Alternative)

1. Download Laragon dari: https://laragon.org/
2. Install Laragon
3. Start Laragon (Apache + MySQL akan otomatis running)

### Step 2: Setup Project

1. **Copy folder `tennis` ke web server directory:**
   ```
   XAMPP: C:\xampp\htdocs\tennis
   Laragon: C:\laragon\www\tennis
   ```

2. **Pastikan struktur folder lengkap:**
   ```
   tennis/
   ├── admin/
   ├── api/
   ├── config/
   ├── database/
   ├── includes/
   ├── penonton/
   ├── wasit/
   └── ... (file lainnya)
   ```

### Step 3: Setup Database

1. **Buka phpMyAdmin:**
   - XAMPP: `http://localhost/phpmyadmin`
   - Laragon: `http://localhost/phpmyadmin`

2. **Import Database:**
   - Klik tab "Import"
   - Pilih file `database/schema.sql`
   - Klik "Go" untuk import
   
   **ATAU**

3. **Manual Create Database:**
   - Klik "New" di sidebar kiri
   - Nama database: `tennis_scoreboard`
   - Collation: `utf8mb4_unicode_ci`
   - Klik "Create"
   - Pilih database `tennis_scoreboard`
   - Klik tab "SQL"
   - Copy-paste isi file `database/schema.sql`
   - Klik "Go"

### Step 4: Konfigurasi Database Connection

1. **Edit file `config/database.php`:**

   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');        // Sesuaikan dengan MySQL username
   define('DB_PASS', '');            // Sesuaikan dengan MySQL password
   define('DB_NAME', 'tennis_scoreboard');
   ```

   **Catatan:**
   - Default XAMPP: username `root`, password kosong
   - Jika MySQL punya password, isi di `DB_PASS`

### Step 5: Konfigurasi Base URL

1. **Edit file `config/config.php`:**

   ```php
   // Untuk XAMPP
   define('BASE_URL', 'http://localhost/tennis/');
   
   // Untuk Laragon (jika setup virtual host)
   define('BASE_URL', 'http://tennis.test/');
   
   // Atau sesuai dengan URL server Anda
   ```

### Step 6: Test Installation

1. **Buka browser dan akses:**
   ```
   http://localhost/tennis/
   ```

2. **Login dengan default credentials:**
   - Username: `admin`
   - Password: `password`

3. **Jika berhasil login, instalasi selesai!**

### Step 7: Setup Virtual Host (Optional - untuk Laragon)

Jika menggunakan Laragon dan ingin URL yang lebih clean:

1. Buka Laragon
2. Klik "Menu" > "Tools" > "Quick add" > "Virtual Host"
3. Pilih folder `tennis`
4. Akses dengan: `http://tennis.test/`

## Verifikasi Instalasi

### Checklist:

- [ ] Apache service running
- [ ] MySQL service running
- [ ] Database `tennis_scoreboard` sudah dibuat
- [ ] File `config/database.php` sudah dikonfigurasi
- [ ] File `config/config.php` sudah dikonfigurasi
- [ ] Bisa akses `http://localhost/tennis/`
- [ ] Bisa login dengan default credentials

## Troubleshooting

### Error: "Connection failed"

**Solusi:**
1. Pastikan MySQL service running
2. Cek username/password di `config/database.php`
3. Pastikan database `tennis_scoreboard` sudah dibuat

### Error: "Page not found" atau 404

**Solusi:**
1. Pastikan folder `tennis` ada di `htdocs` atau `www`
2. Cek BASE_URL di `config/config.php`
3. Pastikan Apache service running

### Error: "Call to undefined function"

**Solusi:**
1. Pastikan PHP version >= 7.4
2. Cek apakah semua file include ada
3. Pastikan path file benar

### Password default tidak bisa login

**Solusi:**
1. Jalankan script reset password:
   ```
   http://localhost/tennis/database/reset-password.php
   ```
2. Atau reset manual di phpMyAdmin:
   ```sql
   UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
   WHERE username = 'admin';
   ```
   (Password hash untuk password: `password`)

## Setelah Instalasi

1. **Ganti password default** untuk semua user
2. **Hapus file `database/reset-password.php`** untuk keamanan
3. **Setup pemain** melalui halaman Admin
4. **Buat pertandingan** pertama Anda

## Support

Jika masih ada masalah, cek:
- Error log Apache: `C:\xampp\apache\logs\error.log`
- Error log PHP: `C:\xampp\php\logs\php_error_log`
- MySQL error: cek di phpMyAdmin

---

**Selamat! Sistem Tennis Scoreboard siap digunakan! 🎾**

