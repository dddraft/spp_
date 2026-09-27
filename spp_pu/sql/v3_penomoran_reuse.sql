-- ══════════════════════════════════════════════════════
-- MIGRATION v3: Sistem Penomoran dengan Reuse Void
-- ══════════════════════════════════════════════════════

-- Update ENUM status — tambah 'pending_hapus'
ALTER TABLE nomor_surat
  MODIFY COLUMN status ENUM('draft','aktif','pending_hapus','void') DEFAULT 'draft';

-- Tambah kolom untuk request hapus
ALTER TABLE nomor_surat
  ADD COLUMN IF NOT EXISTS req_hapus_oleh   INT NULL        COMMENT 'User yang request hapus',
  ADD COLUMN IF NOT EXISTS req_hapus_at     TIMESTAMP NULL  COMMENT 'Waktu request hapus',
  ADD COLUMN IF NOT EXISTS req_hapus_alasan VARCHAR(255) NULL COMMENT 'Alasan request hapus';

-- Index untuk mempercepat query void reuse
CREATE INDEX IF NOT EXISTS idx_void_reuse ON nomor_surat (satker_id, jenis_id, tahun, status, nomor_urut);
