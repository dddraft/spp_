# 🏛️ Sistem SPP — Kementerian PUPR
### Surat Permintaan Pembayaran (Gaji, ATK, Perjalanan Dinas)

---

## 📋 CARA INSTALL DI XAMPP

### Langkah 1 — Copy Folder
Ekstrak/copy folder `spp_kementerianpu` ke:
```
C:\xampp\htdocs\spp_pupr\
```
Sehingga struktur menjadi:
```
C:\xampp\htdocs\spp_pupr\
  ├── index.php          ← Halaman login
  ├── logout.php
  ├── database.sql       ← File SQL database
  ├── includes\
  │   ├── config.php
  │   ├── header.php
  │   └── footer.php
  └── pages\
      ├── dashboard.php
      ├── buat_surat.php
      ├── detail_surat.php
      ├── daftar_surat.php
      ├── verifikasi.php
      └── laporan.php
```

### Langkah 2 — Setup Database
1. Buka XAMPP → Start **Apache** dan **MySQL**
2. Buka browser → ke `http://localhost/phpmyadmin`
3. Klik **Import** → pilih file `database.sql` → klik **Go**
4. Database `spp_pupr` akan terbuat otomatis

### Langkah 3 — Akses Sistem
Buka browser → `http://localhost/spp_pupr/`

---

## 🔑 AKUN LOGIN

| Role | NIP | Password |
|------|-----|----------|
| Admin | 199001012020011001 | password |
| Operator | 198505152010011002 | password |
| Verifikator | 197803202005011003 | password |

---

## ✨ FITUR LENGKAP

| Fitur | Keterangan |
|-------|------------|
| 🔐 Login NIP + Password | Multi-role: Admin, Operator, Verifikator, PPK |
| 📄 3 Jenis Surat | Gaji, ATK, Perjalanan Dinas |
| 🔢 Nomor Otomatis | Format: GAJ/0001/2025/PUPR |
| ✅ Checklist Verifikasi | 6 item wajib sebelum simpan |
| 🖨️ Cetak PDF | Print langsung dari browser |
| 📋 Copy ke Kemenkeu | Teks terformat siap paste |
| 📊 Dashboard | Statistik & grafik real-time |
| 📈 Laporan | Rekap per bulan, per jenis, per tahun |
| 🔍 Filter & Cari | By jenis, status, tahun, nama |

---

## ⚙️ KONFIGURASI

Edit `includes/config.php` jika database Anda berbeda:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Isi password MySQL jika ada
define('DB_NAME', 'spp_pupr');
```

---

## 🚨 TROUBLESHOOTING

**PHP tidak dikenali di CMD:**
- Tambahkan `C:\xampp\php` ke System PATH (lihat instruksi sebelumnya)

**Database gagal konek:**
- Pastikan MySQL di XAMPP sudah Running (hijau)
- Pastikan database sudah di-import dari `database.sql`

**Halaman 404:**
- Pastikan folder ada di `htdocs/spp_pupr/` (bukan subfolder lain)
