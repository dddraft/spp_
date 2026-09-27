<?php
$pageTitle = 'Laporan & Rekap';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$jenis = $_GET['jenis'] ?? '';

// Rekap per bulan
$rekap_bulan = $db->prepare("
    SELECT MONTH(tanggal_surat) AS bulan, COUNT(*) AS jumlah, SUM(jumlah_uang) AS total
    FROM surat_pp
    WHERE YEAR(tanggal_surat)=? AND status='disetujui' " . ($jenis ? "AND jenis_id=(SELECT id FROM jenis_surat WHERE kode=?)" : "") . "
    GROUP BY MONTH(tanggal_surat) ORDER BY bulan
");
$rekap_bulan->execute($jenis ? [$tahun,$jenis] : [$tahun]);
$bulan_data = $rekap_bulan->fetchAll();

// Rekap per jenis
$rekap_jenis = $db->prepare("
    SELECT j.nama, j.kode, COUNT(s.id) AS jumlah, SUM(s.jumlah_uang) AS total,
    SUM(CASE WHEN s.status='disetujui' THEN 1 ELSE 0 END) AS disetujui,
    SUM(CASE WHEN s.status='ditolak' THEN 1 ELSE 0 END) AS ditolak
    FROM jenis_surat j LEFT JOIN surat_pp s ON j.id=s.jenis_id AND YEAR(s.tanggal_surat)=?
    GROUP BY j.id
");
$rekap_jenis->execute([$tahun]);
$jenis_data = $rekap_jenis->fetchAll();

// Total keseluruhan
$grand_total = $db->prepare("SELECT SUM(jumlah_uang) FROM surat_pp WHERE YEAR(tanggal_surat)=? AND status='disetujui'");
$grand_total->execute([$tahun]);
$grand = $grand_total->fetchColumn();

$nama_bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

// Buat array lengkap 12 bulan
$chart_data = array_fill(1, 12, 0);
foreach ($bulan_data as $b) $chart_data[$b['bulan']] = (float)$b['total'];
$max_chart = max($chart_data) ?: 1;
?>

<!-- Filter -->
<div class="card" style="margin-bottom:24px">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" style="display:flex;gap:12px;align-items:flex-end">
      <div class="form-group" style="margin:0">
        <label>Tahun</label>
        <select name="tahun">
          <?php for($y=date('Y');$y>=2022;$y--): ?>
          <option value="<?=$y?>" <?=$tahun==$y?'selected':''?>><?=$y?></option>
          <?php endfor ?>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label>Jenis</label>
        <select name="jenis">
          <option value="">Semua</option>
          <option value="GAJI" <?=$jenis==='GAJI'?'selected':''?>>Gaji</option>
          <option value="ATK" <?=$jenis==='ATK'?'selected':''?>>ATK</option>
          <option value="PERJADIN" <?=$jenis==='PERJADIN'?'selected':''?>>Perjadin</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Tampilkan</button>
      <button type="button" onclick="window.print()" class="btn btn-outline">🖨️ Cetak</button>
    </form>
  </div>
</div>

<!-- Grand Total Banner -->
<div style="background:linear-gradient(135deg,#0a1628,#112240);border-radius:16px;padding:28px 32px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between">
  <div>
    <div style="color:rgba(255,255,255,0.5);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:6px">Total Pembayaran Disetujui <?=$tahun?></div>
    <div style="font-size:36px;font-weight:800;color:white"><?=formatRupiah($grand??0)?></div>
  </div>
  <div style="font-size:60px;opacity:0.2">💰</div>
</div>

<!-- Chart Batang -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><span class="card-title">📊 Grafik Pembayaran Per Bulan (<?=$tahun?>)</span></div>
  <div class="card-body">
    <div style="display:flex;align-items:flex-end;gap:6px;height:180px;padding-bottom:24px;border-bottom:2px solid var(--gray-200);position:relative">
      <?php foreach($chart_data as $bulan => $nilai): ?>
      <?php $pct = ($nilai/$max_chart)*100; ?>
      <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px">
        <?php if($nilai > 0): ?>
        <div style="font-size:9px;color:var(--gray-500);font-weight:600"><?=number_format($nilai/1000000,1)?>jt</div>
        <?php else: ?><div style="font-size:9px"></div><?php endif ?>
        <div style="width:100%;background:<?=$pct>0?'linear-gradient(to top,#1a56db,#3b82f6)':'var(--gray-200)'?>;border-radius:4px 4px 0 0;height:<?=max($pct,2)?>%;transition:height 0.3s"></div>
        <div style="font-size:10px;color:var(--gray-500);font-weight:700;margin-top:6px"><?=$nama_bulan[$bulan]?></div>
      </div>
      <?php endforeach ?>
    </div>
  </div>
</div>

<!-- Rekap per Jenis -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><span class="card-title">Rekap Per Jenis Surat</span></div>
  <div style="overflow:auto">
    <table class="data-table">
      <thead>
        <tr>
          <th>Jenis Surat</th>
          <th>Total Surat</th>
          <th>Disetujui</th>
          <th>Ditolak</th>
          <th>Total Nilai</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $icons = ['GAJI'=>'💼','ATK'=>'🖊️','PERJADIN'=>'✈️'];
        $grand_jumlah=0; $grand_val=0;
        foreach($jenis_data as $j):
          $grand_jumlah += $j['jumlah'];
          $grand_val += $j['total'];
        ?>
        <tr>
          <td><strong><?=$icons[$j['kode']]?> <?= htmlspecialchars($j['nama']) ?></strong></td>
          <td style="font-weight:700"><?=$j['jumlah']?></td>
          <td><span style="color:var(--green);font-weight:700"><?=$j['disetujui']?></span></td>
          <td><span style="color:var(--red);font-weight:700"><?=$j['ditolak']?></span></td>
          <td style="font-weight:700"><?=formatRupiah($j['total']??0)?></td>
        </tr>
        <?php endforeach ?>
        <tr style="background:#f8fafc;font-weight:800">
          <td>TOTAL</td>
          <td><?=$grand_jumlah?></td>
          <td colspan="2">—</td>
          <td style="color:var(--blue)"><?=formatRupiah($grand_val??0)?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Detail Bulanan -->
<div class="card">
  <div class="card-header"><span class="card-title">Detail Per Bulan</span></div>
  <div style="overflow:auto">
    <table class="data-table">
      <thead><tr><th>Bulan</th><th>Jumlah Surat</th><th>Total Nilai</th></tr></thead>
      <tbody>
        <?php if(empty($bulan_data)): ?>
        <tr><td colspan="3" style="text-align:center;color:var(--gray-500);padding:24px">Belum ada data</td></tr>
        <?php else: ?>
        <?php foreach($bulan_data as $b): ?>
        <tr>
          <td style="font-weight:600"><?=$nama_bulan[$b['bulan']].' '.$tahun?></td>
          <td><?=$b['jumlah']?> surat</td>
          <td style="font-weight:700"><?=formatRupiah($b['total']??0)?></td>
        </tr>
        <?php endforeach ?>
        <?php endif ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
