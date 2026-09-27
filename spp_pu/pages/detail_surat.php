<?php
// pages/detail_surat.php — Hub Dokumen per SPP
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db       = getDB();
$surat_id = (int)($_GET['id'] ?? 0);
if (!$surat_id) { header('Location: daftar_surat.php'); exit; }

$stmt = $db->prepare("
    SELECT sp.*,
           js.nama AS jenis_nama, js.kode AS jenis_kode, js.kategori,
           s.nama AS satker_nama, s.kode AS satker_kode,
           s.nama_kementerian, s.nama_unit_org,
           s.nomor_dipa, s.tanggal_dipa,
           s.nama_ppk, s.tempat
    FROM surat_pp sp
    JOIN jenis_surat js ON sp.jenis_id = js.id
    JOIN satker s ON sp.satker_id = s.id
    WHERE sp.id = ?
");
$stmt->execute([$surat_id]);
$spp = $stmt->fetch();
if (!$spp) { die('<p style="font-family:Arial;padding:30px;color:red">SPP tidak ditemukan. <a href="daftar_surat.php">Kembali</a></p>'); }

// Cek kelengkapan
$cekGaji = $db->prepare("SELECT COUNT(*) FROM daftar_gaji WHERE surat_id = ?");
$cekGaji->execute([$surat_id]);
$adaGaji = (int)$cekGaji->fetchColumn() > 0;

$cekPD = $db->prepare("SELECT COUNT(*) FROM daftar_perjadin WHERE surat_id = ?");
$cekPD->execute([$surat_id]);
$adaPerjadin = (int)$cekPD->fetchColumn() > 0;

$cekAkun = $db->prepare("SELECT COUNT(*) FROM surat_pp_akun WHERE surat_id = ?");
$cekAkun->execute([$surat_id]);
$adaAkun = (int)$cekAkun->fetchColumn() > 0;

$kat       = $spp['kategori'] ?? 'gaji';
$is_gaji   = $kat === 'gaji';
$is_pj     = $kat === 'perjadin';

$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
function tglIdn($t){ global $bln_names; if(!$t)return'-';
  [$y,$m,$d]=explode('-',$t); return(int)$d.' '.$bln_names[(int)$m].' '.$y; }
function rp($n){ return 'Rp '.number_format((float)$n,0,',','.'); }

$status_label = [
  'draft'=>'Draft','dikirim'=>'Dikirim','diverifikasi'=>'Diverifikasi',
  'revisi'=>'Revisi','disetujui'=>'Disetujui','dibayar'=>'Dibayar','batal'=>'Batal'
];
$statusOrder = ['draft'=>0,'dikirim'=>1,'diverifikasi'=>2,'disetujui'=>3,'dibayar'=>4];
$curIdx = $statusOrder[$spp['status']] ?? 0;
$kode = implode('.', array_filter([$spp['kode_kegiatan'],$spp['kode_output'],$spp['sub_komponen'],$spp['kode_akun']]));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail SPP — <?= htmlspecialchars($spp['nomor_surat']) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;font-size:12px;background:#e8edf2;color:#222}
#topbar{background:#1a3c6e;color:#fff;padding:9px 20px;
  display:flex;justify-content:space-between;align-items:center;
  box-shadow:0 2px 8px rgba(0,0,0,.3)}
.ttl{font-size:13px;font-weight:bold}
.tbtn{border:none;border-radius:4px;padding:6px 14px;font-size:11.5px;
  font-weight:bold;cursor:pointer;text-decoration:none;display:inline-block}
.tbtn:hover{opacity:.82}
.tbtn-back{background:#7f8c8d;color:#fff}
.tbtn-print{background:#2980b9;color:#fff}

#content{max-width:960px;margin:20px auto 50px;padding:0 16px}

/* HEADER CARD */
.hcard{background:#fff;border-radius:10px;padding:20px 24px;
  box-shadow:0 2px 10px rgba(0,0,0,.1);margin-bottom:18px;
  border-left:5px solid #1a3c6e}
.spp-no{font-size:17px;font-weight:bold;color:#1a3c6e}
.spp-meta{display:flex;flex-wrap:wrap;gap:14px;margin-top:8px;font-size:11.5px;color:#666}
.spp-meta .mi{display:flex;align-items:center;gap:4px}
.badge{display:inline-block;padding:3px 12px;border-radius:12px;font-size:11px;font-weight:bold}
.s-draft     {background:#fff3cd;color:#856404}
.s-dikirim   {background:#cce5ff;color:#004085}
.s-diverifikasi{background:#d1ecf1;color:#0c5460}
.s-disetujui {background:#d4edda;color:#155724}
.s-dibayar   {background:#d4edda;color:#0c5460}
.s-batal     {background:#e2e3e5;color:#383d41}
.s-revisi    {background:#f8d7da;color:#721c24}

.igrid{display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;margin-top:14px}
.ig-item .ig-lbl{font-size:10px;color:#999;margin-bottom:1px}
.ig-item .ig-val{font-size:11.5px;font-weight:500}

/* WORKFLOW */
.wf-card{background:#fff;border-radius:10px;padding:16px 20px;
  box-shadow:0 1px 6px rgba(0,0,0,.08);margin-bottom:18px}
.wf-title{font-size:12px;font-weight:bold;color:#555;margin-bottom:12px}
.wf-steps{display:flex;align-items:center;flex-wrap:wrap}
.ws{display:flex;flex-direction:column;align-items:center;min-width:95px}
.wdot{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;font-size:12px;font-weight:bold;
  border:2px solid #ddd;background:#f8f8f8;color:#bbb}
.wdot.done{background:#27ae60;color:#fff;border-color:#27ae60}
.wdot.active{background:#1a3c6e;color:#fff;border-color:#1a3c6e}
.wline{flex:1;height:2px;background:#ddd;min-width:25px}
.wline.done{background:#27ae60}
.wlabel{font-size:9.5px;margin-top:4px;color:#aaa;text-align:center}
.wlabel.done{color:#27ae60;font-weight:bold}
.wlabel.active{color:#1a3c6e;font-weight:bold}

/* DOKUMEN GRID */
.section-title{font-size:13px;font-weight:bold;color:#1a3c6e;
  margin-bottom:12px;display:flex;align-items:center;gap:6px}
.dok-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));
  gap:14px;margin-bottom:24px}
.dok-card{background:#fff;border-radius:10px;padding:16px 14px;
  box-shadow:0 1px 8px rgba(0,0,0,.08);text-align:center;
  text-decoration:none;color:#333;transition:all .15s;
  border:2px solid transparent;cursor:pointer;display:block}
.dok-card:hover{transform:translateY(-3px);box-shadow:0 4px 16px rgba(0,0,0,.15);
  border-color:#1a3c6e;text-decoration:none}
.dk-icon{font-size:30px;margin-bottom:7px}
.dk-name{font-size:12px;font-weight:bold;color:#1a3c6e;margin-bottom:3px}
.dk-desc{font-size:10px;color:#888;line-height:1.4}
.dk-status{font-size:10px;margin-top:6px;font-weight:bold}
.dok-ok{color:#27ae60}
.dok-warn{color:#e67e22}
.dok-auto{color:#2980b9}
.dok-special{background:linear-gradient(135deg,#f0f4ff,#e8f0fe);border-color:#4a86e8}

/* CETAK ALL */
.cetak-all-btn{
  display:flex;align-items:center;justify-content:center;gap:8px;
  background:#1a3c6e;color:#fff;border:none;border-radius:10px;
  padding:14px;font-size:13px;font-weight:bold;cursor:pointer;
  width:100%;box-shadow:0 2px 8px rgba(26,60,110,.3);
  transition:background .15s;text-decoration:none}
.cetak-all-btn:hover{background:#15305a}
</style>
</head>
<body>

<div id="topbar">
  <div class="ttl">📋 Detail SPP</div>
  <div style="display:flex;gap:8px">
    <a href="daftar_surat.php" class="tbtn tbtn-back">← Daftar SPP</a>
    <button class="tbtn tbtn-print" onclick="cetakSemua()">🖨 Cetak Semua Dok</button>
  </div>
</div>

<div id="content">

  <!-- HEADER -->
  <div class="hcard">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
      <div>
        <div class="spp-no"><?= htmlspecialchars($spp['nomor_surat']) ?></div>
        <div style="font-size:12px;color:#666;margin-top:2px"><?= htmlspecialchars($spp['jenis_nama'] ?? '') ?></div>
        <div class="spp-meta">
          <span class="mi">📅 <?= tglIdn($spp['tanggal_surat']) ?></span>
          <span class="mi">💰 <b><?= rp($spp['jumlah_uang']) ?></b></span>
          <span class="mi">👤 <?= htmlspecialchars($spp['nama_penerima'] ?? '-') ?></span>
          <?php if ($spp['bulan_gaji']): ?>
          <span class="mi">📆 Ke-<?= $spp['ke_gaji'] ?> <?= htmlspecialchars($spp['bulan_gaji']) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <span class="badge s-<?= $spp['status'] ?>"><?= strtoupper($spp['status']) ?></span>
    </div>
    <div class="igrid">
      <div class="ig-item">
        <div class="ig-lbl">Kode Akun</div>
        <div class="ig-val" style="font-size:11px"><?= htmlspecialchars($kode) ?></div>
      </div>
      <div class="ig-item">
        <div class="ig-lbl">DIPA</div>
        <div class="ig-val" style="font-size:11px"><?= htmlspecialchars($spp['nomor_dipa'] ?? '-') ?></div>
      </div>
      <div class="ig-item">
        <div class="ig-lbl">No. SK / SPK</div>
        <div class="ig-val"><?= htmlspecialchars($spp['no_sk_spk'] ?? '-') ?><?= $spp['tanggal_sk_spk'] ? ', '.tglIdn($spp['tanggal_sk_spk']) : '' ?></div>
      </div>
      <div class="ig-item">
        <div class="ig-lbl">Rekening Penerima</div>
        <div class="ig-val"><?= htmlspecialchars($spp['bank_penerima'] ?? '') ?> <?= $spp['no_rekening'] ? '— '.$spp['no_rekening'] : '' ?></div>
      </div>
      <div class="ig-item">
        <div class="ig-lbl">Uraian</div>
        <div class="ig-val" style="font-size:11px"><?= htmlspecialchars(mb_strimwidth($spp['uraian'] ?? '', 0, 120, '...')) ?></div>
      </div>
      <div class="ig-item">
        <div class="ig-lbl">Nilai SPK/Kontrak</div>
        <div class="ig-val"><?= $spp['nilai_spk'] ? rp($spp['nilai_spk']) : '-' ?></div>
      </div>
    </div>
  </div>

  <!-- WORKFLOW -->
  <div class="wf-card">
    <div class="wf-title">📊 Status Proses</div>
    <div class="wf-steps">
      <?php
      $steps = [['Draft',0],['Dikirim',1],['Diverifikasi',2],['Disetujui',3],['Dibayar',4]];
      foreach ($steps as $i => [$lbl, $idx]):
        $done   = $idx < $curIdx;
        $active = $idx === $curIdx;
      ?>
      <?php if ($i > 0): ?><div class="wline <?= $done?'done':'' ?>"></div><?php endif; ?>
      <div class="ws">
        <div class="wdot <?= $done?'done':($active?'active':'') ?>"><?= $done?'✓':($idx+1) ?></div>
        <div class="wlabel <?= $done?'done':($active?'active':'') ?>"><?= $lbl ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- DOKUMEN TERKAIT -->
  <div class="section-title">📄 Dokumen Terkait SPP Ini</div>
  <div class="dok-grid">

    <!-- SPP -->
    <a href="buat_surat_gaji.php?view=<?= $surat_id ?>" class="dok-card">
      <div class="dk-icon">📝</div>
      <div class="dk-name">SPP</div>
      <div class="dk-desc">Surat Permintaan Pembayaran</div>
      <div class="dk-status dok-ok">✓ Sudah dibuat</div>
    </a>

    <!-- SPLS -->
    <a href="spls_gaji.php?id=<?= $surat_id ?>" class="dok-card">
      <div class="dk-icon">📋</div>
      <div class="dk-name">SP-LS</div>
      <div class="dk-desc">Surat Pernyataan untuk SPP-LS</div>
      <div class="dk-status dok-auto">⚡ Otomatis dari SPP</div>
    </a>

    <!-- SPTB -->
    <a href="sptb_gaji.php?id=<?= $surat_id ?>" class="dok-card">
      <div class="dk-icon">📃</div>
      <div class="dk-name">SPTB</div>
      <div class="dk-desc">Surat Pernyataan Tanggung Jawab Belanja</div>
      <div class="dk-status dok-auto">⚡ Otomatis dari SPP</div>
    </a>

    <!-- RINGKASAN KONTRAK -->
    <a href="ringkasan_kontrak.php?id=<?= $surat_id ?>" class="dok-card">
      <div class="dk-icon">📑</div>
      <div class="dk-name">Ringkasan Kontrak</div>
      <div class="dk-desc">Ringkasan nilai & jadwal pembayaran kontrak</div>
      <div class="dk-status dok-auto">⚡ Otomatis dari SPP</div>
    </a>

    <!-- REKAPITULASI GAJI -->
    <?php if ($is_gaji): ?>
    <a href="rekap_gaji.php?id=<?= $surat_id ?>" class="dok-card">
      <div class="dk-icon">💼</div>
      <div class="dk-name">Rekapitulasi Gaji</div>
      <div class="dk-desc">Daftar nominatif tenaga pendukung &amp; gaji</div>
      <div class="dk-status <?= $adaGaji ? 'dok-ok' : 'dok-warn' ?>">
        <?= $adaGaji ? '✓ Data lengkap' : '⚠ Belum diisi — Klik untuk isi' ?>
      </div>
    </a>
    <?php endif; ?>

    <!-- NOMINATIF PERJADIN -->
    <?php if ($is_pj): ?>
    <a href="nominatif_perjadin.php?id=<?= $surat_id ?>" class="dok-card">
      <div class="dk-icon">✈️</div>
      <div class="dk-name">Nominatif Perjadin</div>
      <div class="dk-desc">Daftar nominatif perjalanan dinas</div>
      <div class="dk-status <?= $adaPerjadin ? 'dok-ok' : 'dok-warn' ?>">
        <?= $adaPerjadin ? '✓ Data lengkap' : '⚠ Belum diisi — Klik untuk isi' ?>
      </div>
    </a>
    <?php endif; ?>

  </div>

  <!-- CETAK SEMUA -->
  <button class="cetak-all-btn" onclick="cetakSemua()">
    🖨️ &nbsp; Cetak Semua Dokumen Sekaligus
  </button>

</div>

<script>
function cetakSemua(){
  const id = <?= $surat_id ?>;
  const kat = '<?= $kat ?>';
  // Buka semua dokumen di tab baru
  const docs = [
    'spls_gaji.php?id='+id,
    'sptb_gaji.php?id='+id,
    'ringkasan_kontrak.php?id='+id,
  ];
  if (kat === 'gaji')     docs.push('rekap_gaji.php?id='+id);
  if (kat === 'perjadin') docs.push('nominatif_perjadin.php?id='+id);
  docs.forEach((url, i) => {
    setTimeout(() => window.open(url, '_blank'), i * 300);
  });
}
</script>
</body>
</html>