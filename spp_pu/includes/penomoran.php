<?php
/**
 * PENOMORAN SURAT — Logika Reuse Void
 * =====================================
 * - Status DRAFT   : Nomor sudah di-assign, dokumen belum disimpan final
 * - Status AKTIF   : Dokumen sudah disimpan oleh PengKeu
 * - Status PENDING_HAPUS : PengKeu minta hapus, menunggu ACC verifikator
 * - Status VOID    : Sudah di-ACC hapus oleh verifikator → nomor kembali ke pool
 *
 * Logika ambil nomor:
 *   1. Cek apakah ada nomor VOID (belum terbit) → pakai nomor terkecil
 *   2. Kalau tidak ada → ambil MAX(nomor_urut) + 1
 *
 * Logika hapus:
 *   PengKeu → request_hapus() → status: PENDING_HAPUS
 *   Verifikator → acc_hapus()  → status: VOID (nomor kembali ke pool)
 */

class PenomoranSurat {

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // ═══════════════════════════════════════════════
    // AMBIL NOMOR — reuse void terkecil, atau MAX+1
    // ═══════════════════════════════════════════════
    public function ambilNomor(int $satker_id, string $kode_jenis, int $user_id): array {

        $jenis = $this->getJenis($kode_jenis);
        if (!$jenis) throw new Exception("Jenis surat '$kode_jenis' tidak ditemukan.");

        $tahun = date('Y');

        // 1. Cek apakah user sudah punya draft aktif untuk jenis ini
        $cek = $this->db->prepare("
            SELECT id, nomor_urut, nomor_lengkap
            FROM nomor_surat
            WHERE satker_id = ? AND jenis_id = ? AND tahun = ?
              AND diambil_oleh = ? AND status = 'draft'
            LIMIT 1
        ");
        $cek->execute([$satker_id, $jenis['id'], $tahun, $user_id]);
        $existing = $cek->fetch(PDO::FETCH_ASSOC);
        if ($existing) return ['nomor_id' => $existing['id'], 'nomor_urut' => $existing['nomor_urut'], 'nomor_lengkap' => $existing['nomor_lengkap'], 'reused' => false];

        // 2. Cek apakah ada nomor VOID yang bisa dipakai ulang (terkecil dulu)
        $cekVoid = $this->db->prepare("
            SELECT id, nomor_urut
            FROM nomor_surat
            WHERE satker_id = ? AND jenis_id = ? AND tahun = ? AND status = 'void'
            ORDER BY nomor_urut ASC
            LIMIT 1
        ");
        $cekVoid->execute([$satker_id, $jenis['id'], $tahun]);
        $void = $cekVoid->fetch(PDO::FETCH_ASSOC);

        if ($void) {
            // ♻️ Reuse nomor void terkecil
            $nomor_urut   = $void['nomor_urut'];
            $nomor_lengkap = $this->generateNomor($jenis, $nomor_urut, $satker_id, $tahun);

            $upd = $this->db->prepare("
                UPDATE nomor_surat
                SET status = 'draft', diambil_oleh = ?, diambil_at = NOW(),
                    nomor_lengkap = ?,
                    void_oleh = NULL, void_at = NULL, void_alasan = NULL,
                    req_hapus_oleh = NULL, req_hapus_at = NULL, req_hapus_alasan = NULL,
                    dokumen_id = NULL, dokumen_tabel = NULL
                WHERE id = ?
            ");
            $upd->execute([$user_id, $nomor_lengkap, $void['id']]);

            return ['nomor_id' => $void['id'], 'nomor_urut' => $nomor_urut, 'nomor_lengkap' => $nomor_lengkap, 'reused' => true];
        }

        // 3. Tidak ada void → ambil MAX+1 (termasuk semua status)
        $maxQ = $this->db->prepare("
            SELECT COALESCE(MAX(nomor_urut), 0) + 1 AS next
            FROM nomor_surat
            WHERE satker_id = ? AND jenis_id = ? AND tahun = ?
        ");
        $maxQ->execute([$satker_id, $jenis['id'], $tahun]);
        $nomor_urut    = (int)$maxQ->fetchColumn();
        $nomor_lengkap = $this->generateNomor($jenis, $nomor_urut, $satker_id, $tahun);

        $ins = $this->db->prepare("
            INSERT INTO nomor_surat
                (satker_id, jenis_id, tahun, nomor_urut, nomor_lengkap, status, diambil_oleh, diambil_at)
            VALUES (?, ?, ?, ?, ?, 'draft', ?, NOW())
        ");
        $ins->execute([$satker_id, $jenis['id'], $tahun, $nomor_urut, $nomor_lengkap, $user_id]);

        return ['nomor_id' => (int)$this->db->lastInsertId(), 'nomor_urut' => $nomor_urut, 'nomor_lengkap' => $nomor_lengkap, 'reused' => false];
    }

    // ═══════════════════════════════════════════════
    // SIMPAN / KONFIRMASI → status: aktif
    // ═══════════════════════════════════════════════
    public function konfirmasiNomor(int $nomor_id, int $dokumen_id, string $tabel, int $user_id): bool {
        $upd = $this->db->prepare("
            UPDATE nomor_surat
            SET status = 'aktif', dokumen_id = ?, dokumen_tabel = ?
            WHERE id = ? AND diambil_oleh = ? AND status = 'draft'
        ");
        $upd->execute([$dokumen_id, $tabel, $nomor_id, $user_id]);
        return $upd->rowCount() > 0;
    }

    // ═══════════════════════════════════════════════
    // REQUEST HAPUS (oleh PengKeu) → status: pending_hapus
    // Butuh ACC verifikator sebelum nomor kembali ke pool
    // ═══════════════════════════════════════════════
    public function requestHapus(int $nomor_id, int $user_id, string $alasan): bool {
        // Hanya bisa request hapus kalau status aktif
        $upd = $this->db->prepare("
            UPDATE nomor_surat
            SET status = 'pending_hapus',
                req_hapus_oleh = ?, req_hapus_at = NOW(), req_hapus_alasan = ?
            WHERE id = ? AND status = 'aktif'
        ");
        $upd->execute([$user_id, $alasan, $nomor_id]);
        return $upd->rowCount() > 0;
    }

    // ═══════════════════════════════════════════════
    // ACC HAPUS (oleh Verifikator) → status: void
    // Nomor KEMBALI ke pool, bisa dipakai ulang
    // ═══════════════════════════════════════════════
    public function accHapus(int $nomor_id, int $verifikator_id, string $catatan = ''): bool {
        $upd = $this->db->prepare("
            UPDATE nomor_surat
            SET status = 'void',
                void_oleh = ?, void_at = NOW(), void_alasan = ?,
                dokumen_id = NULL, dokumen_tabel = NULL
            WHERE id = ? AND status = 'pending_hapus'
        ");
        $upd->execute([$verifikator_id, $catatan ?: 'Disetujui hapus oleh verifikator', $nomor_id]);
        return $upd->rowCount() > 0;
    }

    // ═══════════════════════════════════════════════
    // TOLAK HAPUS (oleh Verifikator) → kembali aktif
    // ═══════════════════════════════════════════════
    public function tolakHapus(int $nomor_id, int $verifikator_id, string $alasan): bool {
        $upd = $this->db->prepare("
            UPDATE nomor_surat
            SET status = 'aktif',
                req_hapus_oleh = NULL, req_hapus_at = NULL, req_hapus_alasan = NULL
            WHERE id = ? AND status = 'pending_hapus'
        ");
        $upd->execute([$nomor_id]);
        return $upd->rowCount() > 0;
    }

    // ═══════════════════════════════════════════════
    // VOID DRAFT (draft dibatalkan langsung tanpa approval)
    // Nomor langsung kembali ke pool karena belum disimpan
    // ═══════════════════════════════════════════════
    public function voidDraft(int $nomor_id, int $user_id): bool {
        $upd = $this->db->prepare("
            UPDATE nomor_surat
            SET status = 'void', void_oleh = ?, void_at = NOW(),
                void_alasan = 'Draft dibatalkan sebelum disimpan'
            WHERE id = ? AND diambil_oleh = ? AND status = 'draft'
        ");
        $upd->execute([$user_id, $nomor_id, $user_id]);
        return $upd->rowCount() > 0;
    }

    // ═══════════════════════════════════════════════
    // GET BUKU PENOMORAN
    // ═══════════════════════════════════════════════
    public function getBukuNomor(int $satker_id, ?int $jenis_id = null, int $tahun = 0): array {
        if (!$tahun) $tahun = (int)date('Y');
        $where = "WHERE n.satker_id = ? AND n.tahun = ?";
        $params = [$satker_id, $tahun];
        if ($jenis_id) { $where .= " AND n.jenis_id = ?"; $params[] = $jenis_id; }

        $q = $this->db->prepare("
            SELECT n.*, j.kode AS jenis_kode, j.nama AS jenis_nama,
                   u.nama AS diambil_nama,
                   v.nama AS void_nama,
                   r.nama AS req_hapus_nama
            FROM nomor_surat n
            LEFT JOIN jenis_surat j ON j.id = n.jenis_id
            LEFT JOIN users u ON u.id = n.diambil_oleh
            LEFT JOIN users v ON v.id = n.void_oleh
            LEFT JOIN users r ON r.id = n.req_hapus_oleh
            $where
            ORDER BY j.kode, n.nomor_urut ASC
        ");
        $q->execute($params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════════
    // GET PENDING HAPUS (untuk verifikator)
    // ═══════════════════════════════════════════════
    public function getPendingHapus(int $satker_id): array {
        $q = $this->db->prepare("
            SELECT n.*, j.kode AS jenis_kode, j.nama AS jenis_nama,
                   u.nama AS diambil_nama, r.nama AS req_hapus_nama
            FROM nomor_surat n
            LEFT JOIN jenis_surat j ON j.id = n.jenis_id
            LEFT JOIN users u ON u.id = n.diambil_oleh
            LEFT JOIN users r ON r.id = n.req_hapus_oleh
            WHERE n.satker_id = ? AND n.status = 'pending_hapus'
            ORDER BY n.req_hapus_at DESC
        ");
        $q->execute([$satker_id]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════════
    // GENERATE NOMOR dari template
    // ═══════════════════════════════════════════════
    private function generateNomor(array $jenis, int $urut, int $satker_id, int $tahun): string {
        $satker = $this->getSatker($satker_id);
        $bulan_romawi = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][(int)date('m') - 1];

        $map = [
            '{prefix}'        => $jenis['prefix'] ?? '',
            '{nomor_urut}'    => $urut,
            '{nomor_urut_pad}'=> str_pad($urut, 3, '0', STR_PAD_LEFT),
            '{kode_satker}'   => $satker['kode'] ?? '',
            '{singkatan}'     => $satker['singkatan'] ?? '',
            '{tahun}'         => $tahun,
            '{bulan_romawi}'  => $bulan_romawi,
        ];
        return str_replace(array_keys($map), array_values($map), $jenis['format_nomor']);
    }

    private function getJenis(string $kode): ?array {
        $q = $this->db->prepare("SELECT * FROM jenis_surat WHERE kode = ? AND active = 1");
        $q->execute([$kode]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function getSatker(int $id): ?array {
        $q = $this->db->prepare("SELECT * FROM satker WHERE id = ?");
        $q->execute([$id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

function getPenomoran(): PenomoranSurat {
    global $pdo;
    static $instance = null;
    if (!$instance) $instance = new PenomoranSurat($pdo);
    return $instance;
}
