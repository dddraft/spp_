<?php
// pages/ringkasan_kontrak.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db       = getDB();
$surat_id = (int)($_GET['id'] ?? 0);
if (!$surat_id) { header('Location: daftar_surat.php'); exit; }

$stmt = $db->prepare("
    SELECT sp.*,
           s.nama AS satker_nama, s.kode AS satker_kode,
           s.nama_kementerian, s.nama_unit_org,
           s.nomor_dipa, s.tanggal_dipa,
           s.nama_ppk, s.jabatan_ppk, s.tempat
    FROM surat_pp sp
    JOIN satker s ON sp.satker_id = s.id
    WHERE sp.id = ?
");
$stmt->execute([$surat_id]);
$spp = $stmt->fetch();
if (!$spp) die('Data tidak ditemukan.');

$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
function tglIdn($tgl){ global $bln_names; if(!$tgl)return'-'; [$y,$m,$d]=explode('-',$tgl); return(int)$d.' '.$bln_names[(int)$m].' '.$y; }
function rupiah($n){ return number_format((float)$n,0,',','.'); }

$tgl_surat  = tglIdn($spp['tanggal_surat']);
$tgl_sk     = tglIdn($spp['tanggal_sk_spk']);
$jumlah     = (float)$spp['jumlah_uang'];
$nilai_spk  = (float)($spp['nilai_spk'] ?? 0);
$terbilang  = ucfirst(trim(terbilang($jumlah))) . ' rupiah';
$nama_ppk   = $spp['nama_ppk'] ?? 'Citra Mayasari';
$ke_gaji    = (int)($spp['ke_gaji'] ?? 1);
$bulan_gaji = $spp['bulan_gaji'] ?? '';

$kode_anggaran = implode('.', array_filter([
    $spp['kode_kegiatan'], $spp['kode_output'],
    $spp['sub_komponen'],  $spp['kode_akun']
]));

// Hitung jadwal pembayaran dari nilai SPK
// Asumsi gaji tetap per bulan = jumlah_uang, kecuali bulan pertama
$gaji_per_bln = $nilai_spk > 0 ? round($nilai_spk / 12) : $jumlah;
$jadwal_bayar = [];
for ($i = 1; $i <= 12; $i++) {
    $bayar = ($i == 1) ? $jumlah : $gaji_per_bln;
    // Koreksi total supaya pas dengan nilai SPK
    if ($i == 12 && $nilai_spk > 0) {
        $sudah = array_sum($jadwal_bayar);
        $bayar = $nilai_spk - $sudah;
    }
    $jadwal_bayar[$i] = $bayar;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Ringkasan Kontrak — <?= htmlspecialchars($spp['nomor_surat']) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;font-size:12px;background:#e0e5ec}
#toolbar{position:sticky;top:0;z-index:200;background:#1a3c6e;color:#fff;
  display:flex;justify-content:space-between;align-items:center;
  padding:8px 20px;box-shadow:0 2px 8px rgba(0,0,0,.4)}
.ttl{font-size:13px;font-weight:bold}
.tbtn{border:none;border-radius:4px;padding:6px 16px;font-size:12px;font-weight:bold;cursor:pointer}
.tbtn:hover{opacity:.82}
.tbtn-print{background:#2980b9;color:#fff}
.tbtn-back{background:#7f8c8d;color:#fff}
#wrap{max-width:780px;margin:18px auto 50px;background:#fff;box-shadow:0 2px 14px rgba(0,0,0,.2)}
.surat{padding:35px 45px;line-height:1.7}
.judul-box{text-align:center;margin-bottom:20px}
.judul{font-size:14px;font-weight:bold;text-transform:uppercase;text-decoration:underline}
.irow{display:flex;gap:4px;margin-bottom:4px;font-size:12px;align-items:flex-start}
.ino{min-width:30px;flex-shrink:0}
.ilb{min-width:240px;flex-shrink:0}
.isep{width:14px;flex-shrink:0}
.ival{flex:1}
.jadwal-box{margin-left:14px;font-size:12px;columns:2;column-gap:30px}
.jadwal-row{break-inside:avoid;padding:1px 0}
.ttd-wrap{display:flex;justify-content:flex-end;margin-top:22px;font-size:12px}
.ttd-r{text-align:center;width:260px}
.ttd-nama{font-weight:bold;margin-top:55px;border-top:1px solid #000;padding-top:3px;display:inline-block;min-width:200px}
@media print{
  #toolbar{display:none!important}
  #wrap{box-shadow:none;margin:0}
  .surat{padding:20px 25px}
  body{background:#fff}
}
</style>
</head>
<body>
<div id="toolbar">
  <div class="ttl">📄 Ringkasan Kontrak <small><?= htmlspecialchars($spp['nomor_surat']) ?></small></div>
  <div style="display:flex;gap:8px">
    <button class="tbtn tbtn-back" onclick="history.back()">← Kembali</button>
    <button class="tbtn tbtn-print" onclick="window.print()">🖨 Cetak</button>
  </div>
</div>

<div id="wrap"><div class="surat">

  <div class="judul-box">
    <div class="judul">Ringkasan Kontrak</div>
  </div>

  <div style="font-size:12px">

    <div class="irow">
      <span class="ino">1.</span>
      <span class="ilb">Nomor dan tanggal DIPA</span>
      <span class="isep">:</span>
      <span class="ival">
        <?= htmlspecialchars($spp['nomor_dipa'] ?? '') ?>
        Tanggal <?= tglIdn($spp['tanggal_dipa']) ?>
      </span>
    </div>

    <div class="irow">
      <span class="ino">2.</span>
      <span class="ilb">Kode Kegiatan/Output/Akun</span>
      <span class="isep">:</span>
      <span class="ival"><strong><?= htmlspecialchars($kode_anggaran) ?></strong></span>
    </div>

    <div class="irow">
      <span class="ino">3.</span>
      <span class="ilb">No. dan Tanggal SPK</span>
      <span class="isep">:</span>
      <span class="ival">
        <?= htmlspecialchars($spp['no_sk_spk'] ?? '-') ?>
        <?= $spp['tanggal_sk_spk'] ? ', tanggal ' . $tgl_sk : '' ?>
      </span>
    </div>

    <div class="irow">
      <span class="ino">4.</span>
      <span class="ilb">Nama</span>
      <span class="isep">:</span>
      <span class="ival"><?= htmlspecialchars($spp['nama_penerima'] ?? '') ?></span>
    </div>

    <div class="irow">
      <span class="ino">5.</span>
      <span class="ilb">Alamat</span>
      <span class="isep">:</span>
      <span class="ival"><?= htmlspecialchars($spp['alamat_penerima'] ?? '') ?></span>
    </div>

    <div class="irow">
      <span class="ino">6.</span>
      <span class="ilb">Nilai SPK/Kontrak</span>
      <span class="isep">:</span>
      <span class="ival">
        <strong>Rp. <?= rupiah($nilai_spk) ?>.00</strong>
      </span>
    </div>

    <div class="irow">
      <span class="ino">7.</span>
      <span class="ilb">Jumlah yang dimintakan</span>
      <span class="isep">:</span>
      <span class="ival"><strong>Rp. <?= rupiah($jumlah) ?>.00</strong></span>
    </div>

    <div class="irow">
      <span class="ino">8.</span>
      <span class="ilb">Uraian dan Volume Pekerjaan</span>
      <span class="isep">:</span>
      <span class="ival" style="font-style:italic">
        <?= nl2br(htmlspecialchars($spp['uraian'])) ?>
        <br><em><?= htmlspecialchars(ucfirst($terbilang)) ?></em>
      </span>
    </div>

    <div class="irow">
      <span class="ino">9.</span>
      <span class="ilb">Cara Pembayaran</span>
      <span class="isep">:</span>
      <span class="ival">
        Perbulan dengan<br>
        <div class="jadwal-box">
          <?php foreach ($jadwal_bayar as $ke => $nominal): ?>
          <div class="jadwal-row">
            - Pembayaran ke-<?= $ke ?> = Rp.<?= number_format($nominal, 0, ',', '.') ?>,-
          </div>
          <?php endforeach; ?>
        </div>
      </span>
    </div>

    <div class="irow">
      <span class="ino">10.</span>
      <span class="ilb">Jangka Waktu Pelaksanaan</span>
      <span class="isep">:</span>
      <span class="ival">
        <?= $tgl_sk ?> - 31 Desember <?= date('Y', strtotime($spp['tanggal_surat'])) ?>
      </span>
    </div>

    <div class="irow">
      <span class="ino">11.</span>
      <span class="ilb">Tanggal Penyelesaian Pekerjaan</span>
      <span class="isep">:</span>
      <span class="ival">31 Desember <?= date('Y', strtotime($spp['tanggal_surat'])) ?></span>
    </div>

    <div class="irow">
      <span class="ino">12.</span>
      <span class="ilb">Jangka Waktu Pemeliharaan</span>
      <span class="isep">:</span>
      <span class="ival">-</span>
    </div>

    <div class="irow">
      <span class="ino">13.</span>
      <span class="ilb">Ketentuan Sanksi</span>
      <span class="isep">:</span>
      <span class="ival">-</span>
    </div>

  </div>

  <!-- TTD -->
  <div class="ttd-wrap">
    <div class="ttd-r">
      <div>Jakarta, <?= $tgl_surat ?></div>
      <div style="margin-top:4px">a.n. Kuasa Pengguna Anggaran</div>
      <div>Pejabat Pembuat Komitmen</div>
      <br>
      <span class="ttd-nama"><?= htmlspecialchars($nama_ppk) ?></span>
    </div>
  </div>

</div></div>
</body>
</html>
