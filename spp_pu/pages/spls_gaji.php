<?php
// pages/spls_gaji.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db      = getDB();
$surat_id = (int)($_GET['id'] ?? 0);
if (!$surat_id) { header('Location: daftar_surat.php'); exit; }

// Ambil data surat_pp lengkap
$stmt = $db->prepare("
    SELECT sp.*, 
           js.nama AS jenis_nama, js.kode AS jenis_kode,
           s.nama AS satker_nama, s.kode AS satker_kode,
           s.nama_kementerian, s.kode_kementerian,
           s.nama_unit_org, s.kode_unit_org,
           s.alamat, s.tempat, s.kewenangan,
           s.nomor_dipa, s.tanggal_dipa,
           s.nama_ppk, s.jabatan_ppk,
           s.nama_satker_singkat
    FROM surat_pp sp
    JOIN jenis_surat js ON sp.jenis_id = js.id
    JOIN satker s ON sp.satker_id = s.id
    WHERE sp.id = ?
");
$stmt->execute([$surat_id]);
$spp = $stmt->fetch();
if (!$spp) { die('Data SPP tidak ditemukan.'); }

// Format tanggal Indonesia
$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
function tglIdn($tgl) {
    global $bln_names;
    if (!$tgl) return '-';
    [$y,$m,$d] = explode('-', $tgl);
    return (int)$d . ' ' . $bln_names[(int)$m] . ' ' . $y;
}
function rupiah($n) {
    return number_format((float)$n, 0, ',', '.');
}

$tgl_surat   = tglIdn($spp['tanggal_surat']);
$tgl_dipa    = tglIdn($spp['tanggal_dipa']);
$tgl_sk      = tglIdn($spp['tanggal_sk_spk']);
$jumlah      = (float)$spp['jumlah_uang'];
$terbilang   = ucfirst(trim(terbilang($jumlah))) . ' rupiah';
$nama_ppk    = $spp['nama_ppk'] ?? 'Citra Mayasari';
$jabatan_ppk = 'Sistem dan Strategi Penyelenggaraan Prasarana Strategis';

// Kode anggaran lengkap
$kode_anggaran = implode('.', array_filter([
    $spp['kode_kegiatan'], $spp['kode_output'],
    $spp['sub_komponen'],  $spp['kode_akun']
]));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SPLS Gaji — <?= htmlspecialchars($spp['nomor_surat']) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;font-size:12px;background:#e0e5ec}

/* TOOLBAR */
#toolbar{position:sticky;top:0;z-index:200;background:#1a3c6e;color:#fff;
  display:flex;justify-content:space-between;align-items:center;
  padding:8px 20px;box-shadow:0 2px 8px rgba(0,0,0,.4)}
.ttl{font-size:13px;font-weight:bold}
.ttl small{font-weight:normal;font-size:11px;opacity:.75;margin-left:8px}
.tbtn{border:none;border-radius:4px;padding:6px 16px;font-size:12px;font-weight:bold;cursor:pointer}
.tbtn:hover{opacity:.82}
.tbtn-print{background:#2980b9;color:#fff}
.tbtn-back{background:#7f8c8d;color:#fff}

/* WRAPPER */
#wrap{max-width:780px;margin:18px auto 50px;background:#fff;
  box-shadow:0 2px 14px rgba(0,0,0,.2)}
.surat{padding:35px 45px;background:#fff;line-height:1.6}

/* JUDUL */
.judul-box{text-align:center;margin-bottom:20px}
.judul-box .judul{font-size:14px;font-weight:bold;text-decoration:underline;
  letter-spacing:.5px;text-transform:uppercase}
.judul-box .sub-judul{font-size:12px;font-weight:bold}
.judul-box .nomor{font-size:12px;margin-top:2px}

/* HEADER INSTANSI */
.instansi-box{font-size:12px;margin-bottom:18px}
.irow{display:flex;gap:4px;margin-bottom:2px}
.ilb{min-width:190px;flex-shrink:0}
.isep{width:15px;flex-shrink:0}
.ival{flex:1}

/* PEMBUKA */
.pembuka{font-size:12px;margin-bottom:14px;text-align:justify}

/* REF BOX */
.ref-box{margin:12px 0 12px 20px;font-size:12px}
.ref-row{display:flex;gap:4px;margin-bottom:3px}
.ref-lb{min-width:12px;flex-shrink:0}
.ref-sep{width:12px;flex-shrink:0}
.ref-val{flex:1}

/* URAIAN */
.uraian-box{font-size:12px;text-align:justify;margin:12px 0;
  padding:10px 14px;border:1px solid #ddd;background:#fafafa;
  line-height:1.8}

/* ISI PERNYATAAN */
.isi-box{font-size:12px;margin:12px 0;text-align:justify}

/* TTD */
.ttd-wrap{display:flex;justify-content:flex-end;margin-top:24px;font-size:12px}
.ttd-r{text-align:center;width:260px}
.ttd-nama{font-weight:bold;margin-top:58px;border-top:1px solid #000;
  padding-top:3px;display:inline-block;min-width:200px}
.ttd-jabatan{font-size:11px;color:#333;margin-top:2px}

/* PENUTUP */
.penutup{font-size:11px;margin-top:20px;text-align:justify;
  border-top:1px dashed #ccc;padding-top:10px;color:#444}

@media print{
  #toolbar{display:none!important}
  #wrap{box-shadow:none;margin:0}
  .surat{padding:20px 30px}
  body{background:#fff}
}
</style>
</head>
<body>

<div id="toolbar">
  <div class="ttl">📄 SPLS Gaji
    <small><?= htmlspecialchars($spp['nomor_surat']) ?></small>
  </div>
  <div style="display:flex;gap:8px">
    <button class="tbtn tbtn-back" onclick="history.back()">← Kembali</button>
    <button class="tbtn tbtn-print" onclick="window.print()">🖨 Cetak</button>
  </div>
</div>

<div id="wrap"><div class="surat">

  <!-- JUDUL -->
  <div class="judul-box">
    <div class="judul">Surat Pernyataan</div>
    <div class="sub-judul">Untuk SPP – LS</div>
    <div class="nomor">Nomor : <?= htmlspecialchars($spp['nomor_surat']) ?></div>
  </div>

  <!-- HEADER INSTANSI -->
  <div class="instansi-box">
    <div class="irow">
      <span class="ilb">Kementerian</span>
      <span class="isep">:</span>
      <span class="ival"><?= htmlspecialchars(strtoupper($spp['nama_kementerian'] ?? 'KEMENTERIAN PEKERJAAN UMUM')) ?></span>
    </div>
    <div class="irow">
      <span class="ilb">Unit Organisasi</span>
      <span class="isep">:</span>
      <span class="ival"><?= htmlspecialchars(strtoupper($spp['nama_unit_org'] ?? 'DIREKTORAT JENDERAL PRASARANA STRATEGIS')) ?></span>
    </div>
    <div class="irow">
      <span class="ilb">Satuan Kerja</span>
      <span class="isep">:</span>
      <span class="ival"><?= htmlspecialchars(strtoupper($spp['satker_nama'])) ?></span>
    </div>
  </div>

  <!-- KALIMAT PEMBUKA -->
  <div class="pembuka">
    Sehubungan dengan SPP Pembayaran Langsung yang diajukan :
  </div>

  <!-- REF SPP -->
  <div class="ref-box">
    <div class="ref-row">
      <span class="ref-lb">a.</span>
      <span style="min-width:290px">Kepada Pejabat yang melakukan Pengujian dan Perintah Pembayaran</span>
    </div>
    <div class="ref-row" style="padding-left:16px">
      <span style="min-width:80px">Tanggal</span>
      <span style="width:12px">:</span>
      <span><?= $tgl_surat ?></span>
      <span style="margin-left:30px;min-width:30px">No</span>
      <span style="width:12px">:</span>
      <span><strong><?= htmlspecialchars($spp['nomor_surat']) ?></strong></span>
    </div>

    <div class="ref-row" style="margin-top:6px">
      <span class="ref-lb">b.</span>
      <span style="min-width:80px">Sebesar</span>
      <span style="width:12px">:</span>
      <span><strong>Rp. <?= rupiah($jumlah) ?></strong></span>
    </div>

    <div class="ref-row" style="margin-top:2px;padding-left:28px">
      <span>Terbilang :</span>
    </div>
    <div style="margin-left:30px;font-style:italic;font-weight:bold;font-size:12px">
      <?= htmlspecialchars(ucfirst($terbilang)) ?>
    </div>

    <div class="ref-row" style="margin-top:8px">
      <span class="ref-lb">c.</span>
      <span style="min-width:150px">Untuk Pembayaran</span>
      <span style="width:12px">:</span>
    </div>
  </div>

  <!-- URAIAN PEMBAYARAN -->
  <div class="uraian-box">
    <?= nl2br(htmlspecialchars($spp['uraian'])) ?>
  </div>

  <!-- d. Kepada / Rekening -->
  <div class="ref-box">
    <div class="ref-row">
      <span class="ref-lb">d.</span>
      <span style="min-width:100px">Kepada</span>
      <span style="width:12px">:</span>
      <span><strong><?= htmlspecialchars($spp['nama_penerima'] ?? '') ?></strong></span>
    </div>
    <div class="ref-row" style="padding-left:16px">
      <span style="min-width:100px">&nbsp;</span>
      <span><?= htmlspecialchars($spp['bank_penerima'] ?? '') ?></span>
    </div>
    <div class="ref-row" style="padding-left:16px">
      <span style="min-width:100px">No. rekening</span>
      <span style="width:12px">:</span>
      <span><?= htmlspecialchars($spp['no_rekening'] ?? '-') ?></span>
    </div>

    <!-- e. Berdasarkan -->
    <div class="ref-row" style="margin-top:8px">
      <span class="ref-lb">e.</span>
      <span style="min-width:100px">Berdasarkan</span>
      <span style="width:12px">:</span>
    </div>
    <div style="margin-left:16px;font-size:12px">
      <div class="ref-row">
        <span style="min-width:12px">-</span>
        <span style="min-width:120px">SK</span>
        <span style="width:12px">:</span>
        <span><?= htmlspecialchars($spp['no_sk_spk'] ?? '-') ?><?= $spp['tanggal_sk_spk'] ? ', tanggal ' . $tgl_sk : '' ?></span>
      </div>
      <div class="ref-row">
        <span style="min-width:12px">-</span>
        <span style="min-width:120px">Kontrak</span>
        <span style="width:12px">:</span>
        <span>-</span>
      </div>
      <div class="ref-row">
        <span style="min-width:12px">-</span>
        <span style="min-width:120px">SK Menteri PUPR</span>
        <span style="width:12px">:</span>
        <span>No. 440/KPTS/M/2025 tanggal 10 April 2025</span>
      </div>
      <div class="ref-row">
        <span style="min-width:12px">-</span>
        <span style="min-width:120px">Peraturan Menteri PUPR</span>
        <span style="width:12px">:</span>
        <span>-</span>
      </div>
      <div class="ref-row">
        <span style="min-width:12px">-</span>
        <span style="min-width:120px">SK Dirjen PS</span>
        <span style="width:12px">:</span>
        <span>-</span>
      </div>
      <div class="ref-row">
        <span style="min-width:12px">-</span>
        <span style="min-width:120px">SK Direktur SSPPS</span>
        <span style="width:12px">:</span>
        <span>-</span>
      </div>
      <div class="ref-row">
        <span style="min-width:12px">-</span>
        <span style="min-width:120px">SK Pejabat Pembuat Komitmen</span>
        <span style="width:12px">:</span>
        <span>-</span>
      </div>
      <div class="ref-row">
        <span style="min-width:12px">-</span>
        <span style="min-width:120px">Pembayaran</span>
        <span style="width:12px">:</span>
        <span><?= htmlspecialchars($spp['jenis_belanja_nama'] ?? 'Belanja Barang') ?></span>
      </div>
    </div>
  </div>

  <!-- PERNYATAAN -->
  <div class="isi-box" style="margin-top:16px">
    Demikian surat pernyataan ini untuk melengkapi persyaratan Pembayaran Langsung untuk kegiatan Satker kami.
  </div>

  <!-- KALIMAT DENGAN INI -->
  <div style="margin:12px 0;font-size:12px;text-align:justify">
    Dengan ini kami menyatakan bahwa <strong><?= nl2br(htmlspecialchars($spp['uraian'])) ?></strong>
    yang digunakan untuk Kegiatan tersebut telah dilakukan dengan :
  </div>

  <!-- TTD -->
  <div class="ttd-wrap">
    <div class="ttd-r">
      <div>Jakarta, <?= $tgl_surat ?></div>
      <div style="margin-top:4px">Pejabat Pembuat Komitmen</div>
      <div><?= htmlspecialchars($jabatan_ppk) ?></div>
      <div>
        <span class="ttd-nama"><?= htmlspecialchars($nama_ppk) ?></span>
      </div>
    </div>
  </div>

  <!-- PENUTUP (kecil) -->
  <div class="penutup">
    * Bukti-bukti pengeluaran anggaran dan asli setoran pajak (SSP/BPN) tersebut di atas disimpan oleh 
    Pengguna Anggaran/Kuasa Pengguna Anggaran untuk kelengkapan administrasi dan pemeriksaan aparat pengawasan fungsional.
  </div>

</div></div>
</body>
</html>