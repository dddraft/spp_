<?php
// pages/daftar_surat.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db        = getDB();
$user      = getCurrentUser();
$satker_id = $_SESSION['user']['satker_id'] ?? $_SESSION['satker_id'] ?? 1;

// Filter
$filter_jenis  = $_GET['jenis']  ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_tahun  = (int)($_GET['tahun'] ?? date('Y'));
$filter_cari   = trim($_GET['cari'] ?? '');

// Query
$where = ["sp.satker_id = ?"];
$params = [$satker_id];

if ($filter_jenis)  { $where[] = "js.kategori = ?";      $params[] = $filter_jenis; }
if ($filter_status) { $where[] = "sp.status = ?";         $params[] = $filter_status; }
if ($filter_tahun)  { $where[] = "sp.tahun = ?";          $params[] = $filter_tahun; }
if ($filter_cari)   { $where[] = "(sp.nomor_surat LIKE ? OR sp.nama_penerima LIKE ? OR sp.uraian LIKE ?)";
                      $cari = "%$filter_cari%";
                      $params[] = $cari; $params[] = $cari; $params[] = $cari; }

$sql = "
    SELECT sp.id, sp.nomor_surat, sp.tanggal_surat, sp.jumlah_uang,
           sp.nama_penerima, sp.kode_output, sp.sub_komponen, sp.kode_akun,
           sp.bulan_gaji, sp.ke_gaji, sp.status, sp.created_at,
           js.nama AS jenis_nama, js.kode AS jenis_kode, js.kategori,
           (SELECT COUNT(*) FROM daftar_gaji dg WHERE dg.surat_id = sp.id) AS ada_rekap_gaji,
           (SELECT COUNT(*) FROM daftar_perjadin dp WHERE dp.surat_id = sp.id) AS ada_perjadin
    FROM surat_pp sp
    JOIN jenis_surat js ON sp.jenis_id = js.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY sp.tanggal_surat DESC, sp.id DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$surats = $stmt->fetchAll();

// Statistik ringkas
$statStmt = $db->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) AS draft,
        SUM(CASE WHEN status='dikirim' THEN 1 ELSE 0 END) AS dikirim,
        SUM(CASE WHEN status='disetujui' THEN 1 ELSE 0 END) AS disetujui,
        SUM(CASE WHEN status='dibayar' THEN 1 ELSE 0 END) AS dibayar,
        SUM(jumlah_uang) AS total_nilai
    FROM surat_pp WHERE satker_id = ? AND tahun = ?
");
$statStmt->execute([$satker_id, $filter_tahun]);
$stat = $statStmt->fetch();

$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
function tglIdn($tgl){ global $bln_names; if(!$tgl)return'-';
  [$y,$m,$d]=explode('-',$tgl); return(int)$d.' '.$bln_names[(int)$m].' '.$y; }
function rupiah($n){ return 'Rp '.number_format((float)$n,0,',','.'); }

$status_label = [
  'draft'=>['label'=>'Draft','cls'=>'s-draft'],
  'dikirim'=>['label'=>'Dikirim','cls'=>'s-dikirim'],
  'diverifikasi'=>['label'=>'Diverifikasi','cls'=>'s-verif'],
  'revisi'=>['label'=>'Revisi','cls'=>'s-revisi'],
  'disetujui'=>['label'=>'Disetujui','cls'=>'s-setuju'],
  'dibayar'=>['label'=>'Dibayar','cls'=>'s-bayar'],
  'batal'=>['label'=>'Batal','cls'=>'s-batal'],
];
$kategori_icon = [
  'gaji'=>'💼','perjadin'=>'✈️','atk'=>'🖊️','lainnya'=>'📋'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar SPP — SAKURA PUPR</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;font-size:12px;background:#e8edf2;color:#222}

/* NAV */
#topbar{background:#1a3c6e;color:#fff;padding:8px 20px;
  display:flex;justify-content:space-between;align-items:center}
#topbar .ttl{font-size:14px;font-weight:bold}
#topbar .ttl small{font-size:11px;opacity:.75;margin-left:8px}
.tbtn{border:none;border-radius:4px;padding:6px 16px;font-size:12px;
  font-weight:bold;cursor:pointer;text-decoration:none;display:inline-block}
.tbtn:hover{opacity:.82}
.tbtn-new{background:#27ae60;color:#fff}
.tbtn-back{background:#7f8c8d;color:#fff}

#content{max-width:1150px;margin:20px auto 50px;padding:0 16px}

/* STAT CARDS */
.stat-row{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px}
.stat-card{background:#fff;border-radius:8px;padding:14px 16px;
  box-shadow:0 1px 6px rgba(0,0,0,.1);text-align:center}
.stat-num{font-size:22px;font-weight:bold;color:#1a3c6e}
.stat-lbl{font-size:10.5px;color:#888;margin-top:2px}
.stat-card.total .stat-num{color:#1a3c6e}
.stat-card.dikirim .stat-num{color:#2980b9}
.stat-card.disetujui .stat-num{color:#27ae60}
.stat-card.dibayar .stat-num{color:#16a085}
.stat-card.nilai .stat-num{font-size:15px}

/* FILTER BAR */
.filter-bar{background:#fff;border-radius:8px;padding:12px 16px;
  box-shadow:0 1px 6px rgba(0,0,0,.1);margin-bottom:16px;
  display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.filter-bar select,.filter-bar input{
  border:1px solid #ccc;border-radius:4px;padding:5px 8px;
  font-size:11.5px;background:#fff}
.filter-bar select:focus,.filter-bar input:focus{outline:2px solid #1a3c6e}
.btn-filter{background:#1a3c6e;color:#fff;border:none;border-radius:4px;
  padding:6px 14px;font-size:11.5px;cursor:pointer;font-weight:bold}
.btn-reset{background:#95a5a6;color:#fff;border:none;border-radius:4px;
  padding:6px 12px;font-size:11.5px;cursor:pointer}

/* TABEL */
.tbl-wrap{background:#fff;border-radius:8px;box-shadow:0 1px 6px rgba(0,0,0,.1);overflow:hidden}
.tbl-head{padding:12px 16px;border-bottom:1px solid #eee;
  display:flex;justify-content:space-between;align-items:center}
.tbl-head .ttl2{font-size:13px;font-weight:bold;color:#1a3c6e}
table{width:100%;border-collapse:collapse;font-size:11.5px}
thead th{background:#f4f6fa;padding:9px 10px;text-align:left;
  font-weight:bold;font-size:11px;color:#555;border-bottom:2px solid #e0e5ec}
tbody tr{border-bottom:1px solid #f0f0f0;transition:background .1s}
tbody tr:hover{background:#f8faff}
tbody td{padding:9px 10px;vertical-align:middle}

/* STATUS BADGE */
.sbadge{display:inline-block;padding:2px 9px;border-radius:12px;
  font-size:10px;font-weight:bold;white-space:nowrap}
.s-draft     {background:#fff3cd;color:#856404}
.s-dikirim   {background:#cce5ff;color:#004085}
.s-verif     {background:#d1ecf1;color:#0c5460}
.s-revisi    {background:#f8d7da;color:#721c24}
.s-setuju    {background:#d4edda;color:#155724}
.s-bayar     {background:#d4edda;color:#0c5460}
.s-batal     {background:#e2e3e5;color:#383d41}

/* KATEGORI BADGE */
.kbadge{font-size:11px;white-space:nowrap}

/* DOK STATUS DOTS */
.dok-dots{display:flex;gap:4px;flex-wrap:wrap;margin-top:3px}
.dot{width:8px;height:8px;border-radius:50%;display:inline-block}
.dot-ok{background:#27ae60}
.dot-empty{background:#ddd}
.dot-warn{background:#e67e22}

/* AKSI */
.aksi-btn{display:inline-block;padding:3px 9px;border-radius:4px;
  font-size:10.5px;font-weight:bold;text-decoration:none;cursor:pointer;
  border:none;transition:opacity .1s}
.aksi-btn:hover{opacity:.8}
.aksi-detail{background:#1a3c6e;color:#fff}
.aksi-print{background:#2980b9;color:#fff}
.aksi-del{background:#e74c3c;color:#fff}

/* EMPTY STATE */
.empty{text-align:center;padding:40px;color:#aaa}
.empty .eicon{font-size:48px;margin-bottom:10px}

@media(max-width:900px){
  .stat-row{grid-template-columns:repeat(2,1fr)}
  table{font-size:10.5px}
}
</style>
</head>
<body>

<div id="topbar">
  <div class="ttl">📋 Daftar SPP
    <small>TA <?= $filter_tahun ?></small>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <a href="dashboard.php" class="tbtn tbtn-back">🏠 Dashboard</a>
    <a href="buat_surat_gaji.php" class="tbtn tbtn-new">＋ Buat SPP Gaji</a>
  </div>
</div>

<div id="content">

  <!-- STAT CARDS -->
  <div class="stat-row">
    <div class="stat-card total">
      <div class="stat-num"><?= $stat['total'] ?? 0 ?></div>
      <div class="stat-lbl">Total SPP</div>
    </div>
    <div class="stat-card dikirim">
      <div class="stat-num"><?= $stat['dikirim'] ?? 0 ?></div>
      <div class="stat-lbl">Dikirim</div>
    </div>
    <div class="stat-card disetujui">
      <div class="stat-num"><?= $stat['disetujui'] ?? 0 ?></div>
      <div class="stat-lbl">Disetujui</div>
    </div>
    <div class="stat-card dibayar">
      <div class="stat-num"><?= $stat['dibayar'] ?? 0 ?></div>
      <div class="stat-lbl">Dibayar</div>
    </div>
    <div class="stat-card nilai">
      <div class="stat-num"><?= $stat['total_nilai'] ? 'Rp '.number_format($stat['total_nilai']/1000000,1,',','.').'jt' : '-' ?></div>
      <div class="stat-lbl">Total Nilai TA<?= $filter_tahun ?></div>
    </div>
  </div>

  <!-- FILTER -->
  <form method="GET" class="filter-bar">
    <input type="text" name="cari" value="<?= htmlspecialchars($filter_cari) ?>"
           placeholder="🔍 Nomor / Nama / Uraian..." style="width:220px">
    <select name="jenis">
      <option value="">Semua Jenis</option>
      <option value="gaji"     <?= $filter_jenis=='gaji'?'selected':'' ?>>💼 Gaji</option>
      <option value="perjadin" <?= $filter_jenis=='perjadin'?'selected':'' ?>>✈️ Perjadin</option>
      <option value="atk"      <?= $filter_jenis=='atk'?'selected':'' ?>>🖊️ ATK</option>
    </select>
    <select name="status">
      <option value="">Semua Status</option>
      <?php foreach ($status_label as $k => $v): ?>
      <option value="<?= $k ?>" <?= $filter_status==$k?'selected':'' ?>><?= $v['label'] ?></option>
      <?php endforeach; ?>
    </select>
    <select name="tahun">
      <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
      <option value="<?= $y ?>" <?= $filter_tahun==$y?'selected':'' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <button type="submit" class="btn-filter">Cari</button>
    <a href="daftar_surat.php" class="btn-reset">Reset</a>
  </form>

  <!-- TABEL -->
  <div class="tbl-wrap">
    <div class="tbl-head">
      <div class="ttl2">📄 <?= count($surats) ?> Surat ditemukan</div>
    </div>

    <?php if (empty($surats)): ?>
    <div class="empty">
      <div class="eicon">📭</div>
      <div>Belum ada SPP yang dibuat</div>
      <div style="margin-top:8px">
        <a href="buat_surat_gaji.php" class="tbtn tbtn-new" style="font-size:12px">
          ＋ Buat SPP Gaji Pertama
        </a>
      </div>
    </div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th style="width:30px">No</th>
          <th>Nomor SPP</th>
          <th>Tanggal</th>
          <th>Jenis</th>
          <th>Penerima</th>
          <th>Kode Akun</th>
          <th style="text-align:right">Jumlah</th>
          <th>Status</th>
          <th>Dokumen</th>
          <th style="text-align:center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($surats as $i => $s):
          $kat   = $s['kategori'] ?? 'lainnya';
          $icon  = $kategori_icon[$kat] ?? '📋';
          $st    = $status_label[$s['status']] ?? ['label'=>$s['status'],'cls'=>'s-draft'];
          $kode  = implode('.', array_filter([$s['kode_output'],$s['sub_komponen'],$s['kode_akun']]));
        ?>
        <tr>
          <td style="text-align:center;color:#aaa"><?= $i+1 ?></td>
          <td>
            <a href="detail_surat.php?id=<?= $s['id'] ?>"
               style="color:#1a3c6e;font-weight:bold;text-decoration:none">
              <?= htmlspecialchars($s['nomor_surat']) ?>
            </a>
            <?php if ($s['bulan_gaji']): ?>
            <div style="font-size:10px;color:#888;margin-top:1px">
              Ke-<?= $s['ke_gaji'] ?> <?= htmlspecialchars($s['bulan_gaji']) ?>
            </div>
            <?php endif; ?>
          </td>
          <td style="white-space:nowrap"><?= tglIdn($s['tanggal_surat']) ?></td>
          <td><span class="kbadge"><?= $icon ?> <?= htmlspecialchars($s['jenis_kode']) ?></span></td>
          <td>
            <div style="font-weight:500"><?= htmlspecialchars($s['nama_penerima'] ?? '-') ?></div>
          </td>
          <td style="font-size:10.5px;color:#555"><?= htmlspecialchars($kode) ?></td>
          <td style="text-align:right;font-weight:bold;white-space:nowrap">
            <?= rupiah($s['jumlah_uang']) ?>
          </td>
          <td>
            <span class="sbadge <?= $st['cls'] ?>"><?= $st['label'] ?></span>
          </td>
          <td>
            <!-- Indikator kelengkapan dokumen -->
            <div class="dok-dots" title="Kelengkapan dokumen">
              <span class="dot dot-ok" title="SPP ✓"></span>
              <span class="dot dot-ok" title="SPLS ✓"></span>
              <span class="dot dot-ok" title="SPTB ✓"></span>
              <span class="dot dot-ok" title="Ringkasan Kontrak ✓"></span>
              <?php if ($kat === 'gaji'): ?>
              <span class="dot <?= $s['ada_rekap_gaji'] ? 'dot-ok' : 'dot-warn' ?>"
                    title="Rekap Gaji <?= $s['ada_rekap_gaji'] ? '✓' : '⚠ Belum' ?>"></span>
              <?php elseif ($kat === 'perjadin'): ?>
              <span class="dot <?= $s['ada_perjadin'] ? 'dot-ok' : 'dot-warn' ?>"
                    title="Nominatif Perjadin <?= $s['ada_perjadin'] ? '✓' : '⚠ Belum' ?>"></span>
              <?php endif; ?>
            </div>
          </td>
          <td style="text-align:center;white-space:nowrap">
            <a href="detail_surat.php?id=<?= $s['id'] ?>" class="aksi-btn aksi-detail">Detail</a>
            <a href="#" class="aksi-btn aksi-print"
               onclick="printAll(<?= $s['id'] ?>, '<?= $kat ?>');return false">🖨</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

</div>

<script>
function printAll(id, kat) {
  const docs = [
    'spls_gaji.php?id='+id,
    'sptb_gaji.php?id='+id,
    'ringkasan_kontrak.php?id='+id,
  ];
  if (kat === 'gaji')     docs.push('rekap_gaji.php?id='+id);
  if (kat === 'perjadin') docs.push('nominatif_perjadin.php?id='+id);
  docs.forEach(url => window.open(url, '_blank'));
}
</script>
</body>
</html>