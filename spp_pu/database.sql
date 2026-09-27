-- ============================================
-- DATABASE: Sistem Surat Permintaan Pembayaran
-- Kementerian PUPR
-- ============================================

CREATE DATABASE IF NOT EXISTS spp_pupr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spp_pupr;

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(20) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    jabatan VARCHAR(100),
    unit_kerja VARCHAR(150),
    role ENUM('admin', 'operator', 'verifikator', 'ppk') DEFAULT 'operator',
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Jenis Surat
CREATE TABLE IF NOT EXISTS jenis_surat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(10) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    prefix_nomor VARCHAR(10) NOT NULL
);

INSERT INTO jenis_surat (kode, nama, prefix_nomor) VALUES
('GAJI', 'Pembayaran Gaji', 'GAJ'),
('ATK', 'Pengadaan ATK', 'ATK'),
('PERJADIN', 'Perjalanan Dinas', 'PRJ');

-- Tabel Nomor Surat (auto-increment per jenis per tahun)
CREATE TABLE IF NOT EXISTS nomor_surat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis_id INT NOT NULL,
    tahun YEAR NOT NULL,
    urutan INT DEFAULT 0,
    UNIQUE KEY (jenis_id, tahun),
    FOREIGN KEY (jenis_id) REFERENCES jenis_surat(id)
);

-- Tabel Surat Permintaan Pembayaran
CREATE TABLE IF NOT EXISTS surat_pp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor_surat VARCHAR(50) UNIQUE NOT NULL,
    jenis_id INT NOT NULL,
    tahun YEAR NOT NULL,
    tanggal_surat DATE NOT NULL,
    -- Data pemohon
    nama_pemohon VARCHAR(100) NOT NULL,
    nip_pemohon VARCHAR(20),
    jabatan_pemohon VARCHAR(100),
    unit_kerja VARCHAR(150),
    -- Rincian pembayaran
    uraian TEXT NOT NULL,
    jumlah_uang DECIMAL(15,2) NOT NULL,
    mata_anggaran VARCHAR(100),
    -- Untuk Perjadin
    tujuan VARCHAR(200),
    tanggal_berangkat DATE,
    tanggal_kembali DATE,
    -- Untuk Gaji
    bulan_gaji VARCHAR(20),
    -- Status
    status ENUM('draft','verifikasi','disetujui','ditolak') DEFAULT 'draft',
    -- Checklist verifikasi
    cek_gaji TINYINT(1) DEFAULT 0,
    cek_atk TINYINT(1) DEFAULT 0,
    cek_perjadin TINYINT(1) DEFAULT 0,
    cek_nomor_atk TINYINT(1) DEFAULT 0,
    cek_nomor_perjadin TINYINT(1) DEFAULT 0,
    verifikasi_selesai TINYINT(1) DEFAULT 0,
    catatan_verifikasi TEXT,
    -- Metadata
    dibuat_oleh INT,
    diverifikasi_oleh INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (jenis_id) REFERENCES jenis_surat(id),
    FOREIGN KEY (dibuat_oleh) REFERENCES users(id),
    FOREIGN KEY (diverifikasi_oleh) REFERENCES users(id)
);

-- Default users
INSERT INTO users (nip, nama, jabatan, unit_kerja, role, password) VALUES
('199001012020011001', 'Admin PUPR', 'Administrator', 'Sekretariat Jenderal', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('198505152010011002', 'Budi Santoso', 'Operator Keuangan', 'Biro Keuangan', 'operator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('197803202005011003', 'Sari Dewi, SE', 'Verifikator', 'Biro Keuangan', 'verifikator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- Default password: password

-- Laporan view
CREATE VIEW v_laporan_surat AS
SELECT 
    s.id, s.nomor_surat, j.nama AS jenis, s.tanggal_surat,
    s.nama_pemohon, s.unit_kerja, s.uraian,
    s.jumlah_uang, s.status, u.nama AS dibuat_oleh_nama,
    s.created_at
FROM surat_pp s
JOIN jenis_surat j ON s.jenis_id = j.id
LEFT JOIN users u ON s.dibuat_oleh = u.id;
