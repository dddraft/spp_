<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';

$db  = getDB();
$uid = $user['id'];
$sid = $user['satker_id'];
$tahun = date('Y');
$bulan = date('n');

// ── DIPA & PAGU ──────────────────────────────────────────
$dipa = $db->prepare("SELECT * FROM dipa WHERE satker_id=? AND tahun=?");
$dipa->execute([$sid, $tahun]);
$pagu = $dipa->fetch();

// ── STATISTIK SURAT BULAN INI ────────────────────────────
$stats = $db->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status IN('dicairkan','diterbitkan_spm','disetujui_bendahara') THEN 1 ELSE 0 END) AS selesai,
        SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) AS draft,
        SUM(CASE WHEN status IN('dikirim','diverifikasi') THEN 1 ELSE 0 END) AS proses,
        SUM(CASE WHEN status='revisi' THEN 1 ELSE 0 END) AS revisi,
        SUM(jumlah_uang) AS total_nilai
    FROM surat_pp
    WHERE satker_id=? AND YEAR(created_at)=? AND MONTH(created_at)=?
");
$stats->execute([$sid, $tahun, $bulan]);
$st = $stats->fetch();

// ── PER KATEGORI ─────────────────────────────────────────
$per_kat = $db->prepare("
    SELECT j.kategori,
        COUNT(s.id) AS jumlah,
        SUM(s.jumlah_uang) AS total
    FROM jenis_surat j
    LEFT JOIN surat_pp s ON j.kategori=s.jenis_id AND s.satker_id=? AND YEAR(s.created_at)=?
    GROUP BY j.kategori
");
$per_kat->execute([$sid, $tahun]);

// ── SURAT TERBARU ─────────────────────────────────────────
$terbaru = $db->prepare("
    SELECT s.*, j.nama AS jenis_nama, j.kode AS jenis_kode, j.kategori
    FROM surat_pp s JOIN jenis_surat j ON s.jenis_id=j.id
    WHERE s.satker_id=?
    ORDER BY s.created_at DESC LIMIT 5
");
$terbaru->execute([$sid]);
$list_terbaru = $terbaru->fetchAll();

// Surat menunggu tindakan role ini
$menunggu = 0;
if($user['role']==='verifikator'){
    $q=$db->prepare("SELECT COUNT(*) FROM surat_pp WHERE satker_id=? AND status='dikirim'"); $q->execute([$sid]); $menunggu=$q->fetchColumn();
} elseif($user['role']==='bendahara'){
    $q=$db->prepare("SELECT COUNT(*) FROM surat_pp WHERE satker_id=? AND status='diverifikasi'"); $q->execute([$sid]); $menunggu=$q->fetchColumn();
} elseif($user['role']==='ppspm'){
    $q=$db->prepare("SELECT COUNT(*) FROM surat_pp WHERE satker_id=? AND status='disetujui_bendahara'"); $q->execute([$sid]); $menunggu=$q->fetchColumn();
} elseif($user['role']==='kppn'){
    $q=$db->prepare("SELECT COUNT(*) FROM surat_pp WHERE satker_id=? AND status='diterbitkan_spm'"); $q->execute([$sid]); $menunggu=$q->fetchColumn();
}

$bulan_nama = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$hari_nama  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$tgl_indo   = $hari_nama[date('w')].', '.date('j').' '.$bulan_nama[(int)date('n')].' '.date('Y');

$role_labels = [
    'pengelola_keuangan'=>'Pengelola Keuangan','verifikator'=>'Verifikator Keuangan',
    'bendahara'=>'Bendahara Pengeluaran','ppspm'=>'Pegawai PPSPM','kppn'=>'Pegawai KPPN','admin'=>'Administrator'
];
$role_colors = [
    'pengelola_keuangan'=>'#3b82f6','verifikator'=>'#10b981','bendahara'=>'#f59e0b',
    'ppspm'=>'#8b5cf6','kppn'=>'#ef4444','admin'=>'#1a237e'
];
$rc = $role_colors[$user['role']] ?? '#1a237e';
?>

<style>
/* ── BANNER PAGU ── */
.pagu-banner{
  background:linear-gradient(135deg,#1a237e 0%,#283593 60%,#1565c0 100%);
  border-radius:18px;padding:28px 36px;margin-bottom:28px;
  display:flex;align-items:center;justify-content:space-between;
  box-shadow:0 8px 32px rgba(26,35,126,.25);
  position:relative;overflow:hidden;
}
.pagu-banner::before{
  content:'';position:absolute;right:-60px;top:-60px;
  width:260px;height:260px;
  border:45px solid rgba(245,168,0,.12);border-radius:50%;
}
.pagu-banner::after{
  content:'';position:absolute;right:80px;bottom:-80px;
  width:200px;height:200px;
  border:35px solid rgba(255,255,255,.06);border-radius:50%;
}
.pagu-left{position:relative;z-index:1}
.pagu-badge{
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(245,168,0,.18);border:1px solid rgba(245,168,0,.3);
  color:#fbbf24;padding:5px 12px;border-radius:20px;
  font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
  margin-bottom:10px;
}
.pagu-title{font-size:26px;font-weight:800;color:white;margin-bottom:4px}
.pagu-sub{font-size:12px;color:rgba(255,255,255,.6);line-height:1.5}
.pagu-date{
  background:rgba(245,168,0,.15);border:1px solid rgba(245,168,0,.25);
  color:#fbbf24;padding:7px 14px;border-radius:10px;
  font-size:12px;font-weight:600;margin-top:12px;display:inline-block;
}
.pagu-right{position:relative;z-index:1;text-align:right}
.pagu-label{font-size:11px;color:rgba(255,255,255,.55);font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px}
.pagu-val{font-size:32px;font-weight:800;color:#fbbf24;letter-spacing:-.01em;line-height:1}
.pagu-note{font-size:10.5px;color:rgba(255,255,255,.45);margin-top:5px}

/* ── STAT CARDS ── */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:28px}
.scard{
  background:white;border-radius:16px;padding:22px 20px;
  border:1px solid #e8edf5;
  display:flex;align-items:flex-start;gap:14px;
  box-shadow:0 2px 8px rgba(0,0,0,.04);
  position:relative;overflow:hidden;
}
.scard::after{
  content:'';position:absolute;top:-20px;right:-20px;
  width:80px;height:80px;border-radius:50%;
  opacity:.06;
}
.scard.c1::after{background:#3b82f6}
.scard.c2::after{background:#10b981}
.scard.c3::after{background:#f59e0b}
.scard.c4::after{background:#8b5cf6}

.scard-icon{
  width:48px;height:48px;border-radius:13px;
  display:flex;align-items:center;justify-content:center;
  font-size:20px;flex-shrink:0;
}
.scard-val{font-size:28px;font-weight:800;color:#1a237e;line-height:1}
.scard-lbl{font-size:12px;color:#64748b;margin-top:4px}
.scard-badge{
  position:absolute;top:14px;right:14px;
  padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;
}
.badge-pos{background:#d1fae5;color:#065f46}
.badge-neg{background:#fee2e2;color:#991b1b}
.badge-neu{background:#f1f5f9;color:#475569}

/* ── AKSI CEPAT ── */
.aksi-section{margin-bottom:28px}
.aksi-title{
  font-size:16px;font-weight:700;color:#1a237e;
  margin-bottom:16px;padding-left:12px;
  border-left:3px solid #f5a800;
}
.aksi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.aksi-card{
  background:white;border-radius:16px;border:1px solid #e8edf5;
  overflow:hidden;cursor:pointer;
  box-shadow:0 2px 8px rgba(0,0,0,.04);
  transition:transform .2s,box-shadow .2s;
  text-decoration:none;
}
.aksi-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(26,35,126,.12)}
.aksi-thumb{height:90px;display:flex;align-items:center;justify-content:center;font-size:32px;position:relative;overflow:hidden}
.aksi-thumb.gaji{background:linear-gradient(135deg,#1a237e,#283593)}
.aksi-thumb.perjadin{background:linear-gradient(135deg,#0277bd,#0288d1)}
.aksi-thumb.atk{background:linear-gradient(135deg,#00695c,#00897b)}
.aksi-body{padding:16px}
.aksi-name{font-size:15px;font-weight:700;color:#1a237e;margin-bottom:3px}
.aksi-desc{font-size:12px;color:#64748b;margin-bottom:10px}
.aksi-count{font-size:12px;color:#f5a800;font-weight:600;display:flex;align-items:center;gap:5px}
.aksi-arrow{float:right;color:#94a3b8;font-size:14px}

/* Sub-dokumen pills */
.sub-docs{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}
.sub-doc{
  font-size:10.5px;font-weight:600;padding:3px 9px;border-radius:6px;
  background:#f0f4ff;color:#1a237e;border:1px solid #dbeafe;
}

/* ── BOTTOM GRID ── */
.bottom-grid{display:grid;grid-template-columns:1fr;gap:20px}

/* Antrian */
.antrian-card{background:white;border-radius:16px;border:1px solid #e8edf5;overflow:hidden}
.antrian-head{padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between}
.antrian-title{font-size:14px;font-weight:700;color:#1a237e}
.antrian-badge{background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}

/* Terbaru */
.terbaru-card{background:white;border-radius:16px;border:1px solid #e8edf5;overflow:hidden}
.terbaru-head{padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between}

/* Role chip */
.role-chip{
  display:inline-flex;align-items:center;gap:6px;
  padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;
  text-transform:uppercase;letter-spacing:.04em;color:white;
  margin-bottom:0;
}
</style>

<!-- ── BANNER PAGU ── -->
<div class="pagu-banner">
  <div class="pagu-left">
    <div class="pagu-badge">✦ Lock Pagu <?= $tahun ?></div>
    <div class="pagu-title">
      <?= htmlspecialchars($user['satker_singkatan'] ?? $user['satker_nama']) ?>
    </div>
    <div class="pagu-sub">
      <?php if($pagu): ?>
        <?= htmlspecialchars($pagu['nomor_dipa']) ?><br>
        <span style="font-size:11px">Sesuai Laporan Ketersediaan Dana Detail TA <?= $tahun ?></span>
      <?php else: ?>
        Data DIPA belum tersedia untuk tahun <?= $tahun ?>
      <?php endif ?>
    </div>
    <div class="pagu-date">📅 <?= $tgl_indo ?></div>
  </div>
  <div class="pagu-right">
    <div class="pagu-label">Total Pagu <?= $tahun ?></div>
    <div class="pagu-val"><?= $pagu ? 'Rp '.number_format($pagu['pagu_total']/1000000,0,',','.').' Jt' : 'Rp —' ?></div>
    <div class="pagu-note">Gaji + Perjadin + ATK</div>
    <div style="margin-top:14px;display:flex;gap:10px;justify-content:flex-end">
      <?php if($pagu): ?>
      <div style="text-align:right">
        <div style="font-size:10px;color:rgba(255,255,255,.45)">Gaji</div>
        <div style="font-size:13px;font-weight:700;color:rgba(255,255,255,.8)"><?= 'Rp '.number_format($pagu['pagu_gaji']/1000000,0,',','.').'jt' ?></div>
      </div>
      <div style="text-align:right">
        <div style="font-size:10px;color:rgba(255,255,255,.45)">Perjadin</div>
        <div style="font-size:13px;font-weight:700;color:rgba(255,255,255,.8)"><?= 'Rp '.number_format($pagu['pagu_perjadin']/1000000,0,',','.').'jt' ?></div>
      </div>
      <div style="text-align:right">
        <div style="font-size:10px;color:rgba(255,255,255,.45)">ATK</div>
        <div style="font-size:13px;font-weight:700;color:rgba(255,255,255,.8)"><?= 'Rp '.number_format($pagu['pagu_atk']/1000000,0,',','.').'jt' ?></div>
      </div>
      <?php endif ?>
    </div>
  </div>
</div>

<!-- ── STAT CARDS ── -->
<div class="stats-row">
  <div class="scard c1">
    <div class="scard-icon" style="background:#eff6ff">📄</div>
    <div>
      <div class="scard-val"><?= $st['total']??0 ?></div>
      <div class="scard-lbl">Total Dokumen Bulan Ini</div>
    </div>
    <span class="scard-badge badge-pos">+<?= max(0,$st['total']??0) ?></span>
  </div>
  <div class="scard c2">
    <div class="scard-icon" style="background:#f0fdf4">✅</div>
    <div>
      <div class="scard-val"><?= $st['selesai']??0 ?></div>
      <div class="scard-lbl">Dokumen Selesai</div>
    </div>
    <span class="scard-badge badge-pos">+<?= $st['selesai']??0 ?></span>
  </div>
  <div class="scard c3">
    <div class="scard-icon" style="background:#fffbeb">⏳</div>
    <div>
      <div class="scard-val"><?= $st['draft']??0 ?></div>
      <div class="scard-lbl">Dokumen Draft</div>
    </div>
    <span class="scard-badge badge-neu"><?= $st['draft']??0 ?></span>
  </div>
  <div class="scard c4">
    <div class="scard-icon" style="background:#f5f3ff">💰</div>
    <div>
      <div class="scard-val" style="font-size:18px">Rp <?= number_format(($st['total_nilai']??0)/1000000,1,',','.') ?>jt</div>
      <div class="scard-lbl">Total Pembayaran</div>
    </div>
    <span class="scard-badge badge-pos">+<?= $st['selesai']??0 ?></span>
  </div>
</div>

<!-- ── AKSI CEPAT ── -->
<?php if(in_array($user['role'],['pengelola_keuangan','admin'])): ?>
<div class="aksi-section">
  <div class="aksi-title">Pilih Jenis Dokumen</div>
  <div class="aksi-grid">

    <!-- GAJI -->
    <div class="aksi-card">
      <div class="aksi-thumb gaji">📋</div>
      <div class="aksi-body">
        <div class="aksi-name">Gaji</div>
        <div class="aksi-desc">Pengelolaan dokumen pembayaran gaji pegawai</div>
        <div class="sub-docs">
          <a href="buat_surat_spp.php?kategori=gaji&jenis=SPP-GAJI" class="sub-doc">SPP</a>
          <a href="buat_surat_spp.php?kategori=gaji&jenis=SPLS-GAJI" class="sub-doc">SPLS</a>
          <a href="buat_surat_spp.php?kategori=gaji&jenis=SPTB-GAJI" class="sub-doc">SPTB</a>
          <a href="buat_surat_spp.php?kategori=gaji&jenis=NOM-GAJI" class="sub-doc">Nominatif</a>
        </div>
        <div class="aksi-count" style="margin-top:10px">
          📈 <?= $db->prepare("SELECT COUNT(*) FROM surat_pp s JOIN jenis_surat j ON s.jenis_id=j.id WHERE s.satker_id=$sid AND j.kategori='gaji' AND MONTH(s.created_at)=$bulan")->execute()||'' ?><?= $db->query("SELECT COUNT(*) FROM surat_pp s JOIN jenis_surat j ON s.jenis_id=j.id WHERE s.satker_id=$sid AND j.kategori='gaji' AND MONTH(s.created_at)=$bulan AND YEAR(s.created_at)=$tahun")->fetchColumn() ?> bulan ini
          <span class="aksi-arrow">→</span>
        </div>
      </div>
    </div>

    <!-- PERJADIN -->
    <div class="aksi-card">
      <div class="aksi-thumb perjadin">✈️</div>
      <div class="aksi-body">
        <div class="aksi-name">Perjalanan Dinas</div>
        <div class="aksi-desc">Pengelolaan dokumen perjalanan dinas pegawai</div>
        <div class="sub-docs">
          <a href="buat_surat.php?kategori=perjadin&jenis=SPP-PRJ" class="sub-doc">SPP</a>
          <a href="buat_surat.php?kategori=perjadin&jenis=SPLS-PRJ" class="sub-doc">SPLS</a>
          <a href="buat_surat.php?kategori=perjadin&jenis=SPTB-PRJ" class="sub-doc">SPTB</a>
          <a href="buat_surat.php?kategori=perjadin&jenis=NOM-PRJ" class="sub-doc">Nominatif</a>
        </div>
        <div class="aksi-count" style="margin-top:10px">
          📈 <?= $db->query("SELECT COUNT(*) FROM surat_pp s JOIN jenis_surat j ON s.jenis_id=j.id WHERE s.satker_id=$sid AND j.kategori='perjadin' AND MONTH(s.created_at)=$bulan AND YEAR(s.created_at)=$tahun")->fetchColumn() ?> bulan ini
          <span class="aksi-arrow">→</span>
        </div>
      </div>
    </div>

    <!-- ATK -->
    <div class="aksi-card">
      <div class="aksi-thumb atk">🖊️</div>
      <div class="aksi-body">
        <div class="aksi-name">ATK</div>
        <div class="aksi-desc">Pengelolaan dokumen pengadaan alat tulis kantor</div>
        <div class="sub-docs">
          <a href="buat_surat.php?kategori=atk&jenis=SPP-ATK" class="sub-doc">SPP</a>
          <a href="buat_surat.php?kategori=atk&jenis=SPLS-ATK" class="sub-doc">SPLS</a>
          <a href="buat_surat.php?kategori=atk&jenis=SPTB-ATK" class="sub-doc">SPTB</a>
          <a href="buat_surat.php?kategori=atk&jenis=NOM-ATK" class="sub-doc">Nominatif</a>
        </div>
        <div class="aksi-count" style="margin-top:10px">
          📈 <?= $db->query("SELECT COUNT(*) FROM surat_pp s JOIN jenis_surat j ON s.jenis_id=j.id WHERE s.satker_id=$sid AND j.kategori='atk' AND MONTH(s.created_at)=$bulan AND YEAR(s.created_at)=$tahun")->fetchColumn() ?> bulan ini
          <span class="aksi-arrow">→</span>
        </div>
      </div>
    </div>

  </div>
</div>
<?php endif ?>

<?php if($menunggu > 0): ?>
<div class="alert alert-info" style="margin-bottom:20px">
  ⏰ Ada <strong><?= $menunggu ?> surat</strong> menunggu tindakan Anda.
  <a href="verifikasi.php" style="color:var(--blue);font-weight:700;margin-left:8px">Proses Sekarang →</a>
</div>
<?php endif ?>

  <!-- Surat Terbaru -->
  <div class="terbaru-card">
    <div class="terbaru-head">
      <span class="antrian-title">Surat Terbaru</span>
      <a href="daftar_surat.php" class="btn btn-outline btn-sm">Lihat Semua</a>
    </div>
    <div style="overflow:auto">
      <table class="data-table">
        <thead><tr><th>Nomor</th><th>Jenis</th><th>Jumlah</th><th>Status</th></tr></thead>
        <tbody>
          <?php if(empty($list_terbaru)): ?>
          <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:28px">Belum ada surat</td></tr>
          <?php else: ?>
          <?php
          $kat_icon=['gaji'=>'💼','perjadin'=>'✈️','atk'=>'🖊️'];
          foreach($list_terbaru as $s): ?>
          <tr onclick="location.href='detail_surat.php?id=<?=$s['id']?>'" style="cursor:pointer">
            <td style="font-size:11px;font-weight:700;color:#1a56db"><?= htmlspecialchars($s['nomor_surat']) ?></td>
            <td style="font-size:12px"><?=$kat_icon[$s['kategori']]??'📄'?> <?=htmlspecialchars($s['jenis_nama'])?></td>
            <td style="font-size:12px;font-weight:600"><?=formatRupiah($s['jumlah_uang'])?></td>
            <td><span class="status-badge status-<?=$s['status']?>"><?=$s['status']?></span></td>
          </tr>
          <?php endforeach ?>
          <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
