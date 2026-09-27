<?php
// ============================================
// KONFIGURASI DATABASE — SAKURA | PUPR
// ============================================

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'spp_pupr');
define('APP_NAME',   'SAKURA');
define('APP_INSTANSI', 'Kementerian Pekerjaan Umum dan Perumahan Rakyat');
define('APP_VERSION', '3.0.0');

// Session
if (session_status() === PHP_SESSION_NONE) session_start();

// ── KONEKSI PDO ──────────────────────────────────────────
$GLOBALS['_db_error'] = null;

function getDB(): ?PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        $GLOBALS['_db_error'] = $e->getMessage();
        return null;
    }
    return $pdo;
}

function dbOk(): bool {
    return getDB() !== null;
}

// ── AUTH ─────────────────────────────────────────────────
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /spp_pu/index.php');
        exit;
    }
}

function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

// ── HELPERS ──────────────────────────────────────────────
function formatRupiah(float $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function terbilang(float $angka): string {
    $angka = (int)abs($angka);
    $baca  = ['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
    if ($angka < 12)        return $baca[$angka];
    if ($angka < 20)        return $baca[$angka - 10] . ' belas';
    if ($angka < 100)       return $baca[(int)($angka/10)] . ' puluh ' . terbilang($angka % 10);
    if ($angka < 200)       return 'seratus ' . terbilang($angka - 100);
    if ($angka < 1000)      return $baca[(int)($angka/100)] . ' ratus ' . terbilang($angka % 100);
    if ($angka < 2000)      return 'seribu ' . terbilang($angka - 1000);
    if ($angka < 1000000)   return terbilang((int)($angka/1000)) . ' ribu ' . terbilang($angka % 1000);
    if ($angka < 1000000000)return terbilang((int)($angka/1000000)) . ' juta ' . terbilang($angka % 1000000);
    return terbilang((int)($angka/1000000000)) . ' miliar ' . terbilang($angka % 1000000000);
}

function terbilangRupiah(float $angka): string {
    return ucfirst(trim(terbilang($angka))) . ' Rupiah';
}
