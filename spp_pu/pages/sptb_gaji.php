<?php
// pages/sptb_gaji.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db       = getDB();
$surat_id = (int)($_GET['id'] ?? 0);
if (!$surat_id) { header('Location: daftar_surat.php'); exit; }

$stmt = $db->prepare("
    SELECT sp.*,
           s.nama AS satker_nama, s.kode AS satker_kode,
           s.nama_kementerian, s.kode_kementerian,
           s.nama_unit_org, s.kode_unit_org,
           s.nomor_dipa, s.tanggal_dipa,
           s.nama_ppk, s.jabatan_ppk
    FROM surat_pp sp
    JOIN satker s ON sp.satker_id = s.id
    WHERE sp.id = ?
");
$stmt->execute([$surat_id]);
$spp = $stmt->fetch();
if (!$spp) die('Data tidak ditemukan.');

// Ambil detail akun Tabel I dari surat_pp_akun
$stmtA = $db->prepare("
    SELECT spa.*, ka.kode_akun, ka.nama_akun, ka.kode_akun_lengkap
    FROM surat_pp_akun spa
    LEFT JOIN kegiatan_akun ka ON spa.kegiatan_akun_id = ka.id
    WHERE spa.surat_id = ? AND spa.tabel_type = 'I'
    ORDER BY spa.id
");
$stmtA->execute([$surat_id]);
$akun_list = $stmtA->fetchAll();

$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
function tglIdn($tgl){ global $bln_names; if(!$tgl)return'-'; [$y,$m,$d]=explode('-',$tgl); return(int)$d.' '.$bln_names[(int)$m].' '.$y; }
function rupiah($n){ return number_format((float)$n,0,',','.'); }

$tgl_surat = tglIdn($spp['tanggal_surat']);
$jumlah    = (float)$spp['jumlah_uang'];
$nama_ppk  = $spp['nama_ppk'] ?? 'Citra Mayasari';
$kode_anggaran = implode('.', array_filter([
    $spp['kode_kegiatan'], $spp['kode_output'],
    $spp['sub_komponen'],  $spp['kode_akun']
]));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SPTB — <?= htmlspecialchars($spp['nomor_surat']) ?></title>
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
.surat{padding:35px 45px;line-height:1.6}
.judul-box{text-align:center;margin-bottom:18px}
.judul{font-size:14px;font-weight:bold;text-transform:uppercase;text-decoration:underline}
.nomor{font-size:12px;margin-top:3px}
.pembuka{font-size:12px;margin-bottom:14px;text-align:justify}
.irow{display:flex;gap:4px;margin-bottom:2px;font-size:12px}
.ilb{min-width:200px;flex-shrink:0}
.isep{width:15px;flex-shrink:0}
/* TABEL */
.tbl{width:100%;border-collapse:collapse;font-size:11.5px;margin:14px 0}
.tbl th,.tbl td{border:1px solid #000;padding:4px 6px;vertical-align:middle}
.tbl thead th{background:#f0f0f0;text-align:center;font-weight:bold;font-size:11px}
.tbl .tar{text-align:right}
.tbl .tac{text-align:center}
.tbl .tal{text-align:left}
.tbl .bold{font-weight:bold}
.tbl .jumlah-row{font-weight:bold;background:#f8f8f8}
/* TTD */
.ttd-wrap{display:flex;justify-content:flex-end;margin-top:20px;font-size:12px}
.ttd-r{text-align:center;width:260px}
.ttd-nama{font-weight:bold;margin-top:55px;border-top:1px solid #000;padding-top:3px;display:inline-block;min-width:200px}
.penutup{font-size:10.5px;margin-top:18px;text-align:justify;
  border-top:1px dashed #ccc;padding-top:10px;color:#555}
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
  <div class="ttl">📄 SPTB <small><?= htmlspecialchars($spp['nomor_surat']) ?></small></div>
  <div style="display:flex;gap:8px">
    <button class="tbtn tbtn-back" onclick="history.back()">← Kembali</button>
    <button class="tbtn tbtn-print" onclick="window.print()">🖨 Cetak</button>
  </div>
</div>

<div id="wrap"><div class="surat">

  <div class="judul-box">
    <div class="judul">Surat Pernyataan Tanggung Jawab Belanja</div>
    <div class="nomor">Nomor : <?= htmlspecialchars($spp['nomor_surat']) ?></div>
  </div>

  <!-- Info satker -->
  <div style="margin-bottom:14px">
    <div class="irow"><span class="ilb">1. Kode Satuan Kerja</span><span class="isep">:</span>
      <span><?= htmlspecialchars($spp['satker_kode']) ?></span></div>
    <div class="irow"><span class="ilb">2. Nama Satuan Kerja</span><span class="isep">:</span>
      <span><?= htmlspecialchars($spp['satker_nama']) ?></span></div>
    <div class="irow"><span class="ilb">3. DIPA</span><span class="isep">:</span>
      <span><?= htmlspecialchars($spp['nomor_dipa'] ?? '') ?> Tanggal <?= tglIdn($spp['tanggal_dipa']) ?></span></div>
    <div class="irow"><span class="ilb">4. Klasifikasi Anggaran</span><span class="isep">:</span>
      <span><strong><?= htmlspecialchars($kode_anggaran) ?></strong></span></div>
  </div>

  <!-- KALIMAT PEMBUKA -->
  <div class="pembuka">
    Yang bertanda tangan di bawah ini atas nama Kuasa Pengguna Anggaran Satuan Kerja
    <?= htmlspecialchars($spp['satker_nama']) ?>
    menyatakan bahwa saya bertanggung jawab secara formal dan material dan kebenaran 
    perhitungan pemungutan pajak atas segala pembayaran tagihan yang telah kami perintahkan 
    dalam SPM ini dengan perincian sebagai berikut :
  </div>

  <!-- TABEL RINCIAN -->
  <table class="tbl">
    <thead>
      <tr>
        <th rowspan="2" style="width:35px">No.</th>
        <th rowspan="2" style="width:55px">Akun</th>
        <th rowspan="2">Penerima</th>
        <th rowspan="2" style="text-align:left">U r a i a n</th>
        <th colspan="2" style="width:160px">Pajak yang dipungut</th>
        <th rowspan="2" style="width:110px">Jumlah<br>Pembayaran</th>
      </tr>
      <tr>
        <th style="width:80px">PPN</th>
        <th style="width:80px">PPh</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;
      $total_jumlah = 0;
      $total_ppn = 0;
      $total_pph = 0;
      if (!empty($akun_list)):
        foreach ($akun_list as $akun):
          $jml = (float)$akun['spp_ini'];
          $total_jumlah += $jml;
      ?>
      <tr>
        <td class="tac"><?= $no++ ?></td>
        <td class="tac"><?= htmlspecialchars($akun['kode_akun']) ?></td>
        <td><?= htmlspecialchars($spp['nama_penerima'] ?? '') ?></td>
        <td class="tal" style="font-size:11px"><?= nl2br(htmlspecialchars($spp['uraian'])) ?></td>
        <td class="tar">-</td>
        <td class="tar">-</td>
        <td class="tar"><?= rupiah($jml) ?></td>
      </tr>
      <?php endforeach; else: ?>
      <tr>
        <td class="tac">1</td>
        <td class="tac"><?= htmlspecialchars($spp['kode_akun'] ?? '') ?></td>
        <td><?= htmlspecialchars($spp['nama_penerima'] ?? '') ?></td>
        <td class="tal" style="font-size:11px"><?= nl2br(htmlspecialchars($spp['uraian'])) ?></td>
        <td class="tar">-</td>
        <td class="tar">-</td>
        <td class="tar"><?= rupiah($jumlah) ?></td>
      </tr>
      <?php $total_jumlah = $jumlah; endif; ?>
    </tbody>
    <tfoot>
      <tr class="jumlah-row">
        <td colspan="4" class="tar bold">JUMLAH</td>
        <td class="tar bold">-</td>
        <td class="tar bold">-</td>
        <td class="tar bold"><?= rupiah($total_jumlah) ?></td>
      </tr>
    </tfoot>
  </table>

  <div class="pembuka" style="margin-top:12px">
    Demikian surat pernyataan ini dibuat dengan sebenarnya.
  </div>

  <!-- TTD -->
  <div class="ttd-wrap">
    <div class="ttd-r">
      <div>Jakarta, <?= $tgl_surat ?></div>
      <div style="margin-top:4px">Pejabat Pembuat Komitmen</div>
      <div><?= htmlspecialchars($spp['satker_nama'] ?? '') ?></div>
      <span class="ttd-nama"><?= htmlspecialchars($nama_ppk) ?></span>
    </div>
  </div>

  <div class="penutup">
    Bukti-bukti pengeluaran anggaran dan asli setoran pajak (SSP/BPN) tersebut di atas disimpan oleh 
    Pengguna Anggaran/Kuasa Pengguna Anggaran untuk kelengkapan administrasi dan pemeriksaan 
    aparat pengawasan fungsional.
  </div>

</div></div>
</body>
</html>