<?php
$pageTitle = 'Verifikasi Surat';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$pending = $db->query("
    SELECT s.*, j.nama AS jenis_nama, j.kode
    FROM surat_pp s
    JOIN jenis_surat j ON s.jenis_id=j.id
    WHERE s.status='verifikasi'
    ORDER BY s.created_at ASC
")->fetchAll();

$icons = ['GAJI'=>'💼','ATK'=>'🖊️','PERJADIN'=>'✈️'];
?>

<?php if(empty($pending)): ?>
<div class="alert alert-success" style="font-size:15px">
  ✅ Tidak ada surat yang perlu diverifikasi saat ini.
</div>
<?php else: ?>
<div class="alert alert-info">⏳ Ada <strong><?=count($pending)?> surat</strong> menunggu verifikasi Anda.</div>
<?php endif ?>

<div style="display:flex;flex-direction:column;gap:16px">
<?php foreach($pending as $s): ?>
<div class="card">
  <div class="card-body" style="display:flex;align-items:flex-start;gap:20px">
    <div style="font-size:32px;flex-shrink:0"><?=$icons[$s['kode']]?></div>
    <div style="flex:1">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
          <div style="font-size:15px;font-weight:700;color:var(--navy)"><?=htmlspecialchars($s['nomor_surat'])?></div>
          <div style="font-size:13px;color:var(--gray-500)"><?=$s['jenis_nama']?> • <?=date('d/m/Y',strtotime($s['tanggal_surat']))?></div>
        </div>
        <span class="status-badge status-verifikasi">Menunggu</span>
      </div>
      <div style="margin-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px">
        <div><span style="color:var(--gray-500)">Pemohon:</span> <strong><?=htmlspecialchars($s['nama_pemohon'])?></strong></div>
        <div><span style="color:var(--gray-500)">Jumlah:</span> <strong style="color:var(--blue)"><?=formatRupiah($s['jumlah_uang'])?></strong></div>
        <div><span style="color:var(--gray-500)">Unit Kerja:</span> <?=htmlspecialchars($s['unit_kerja']?:'-')?></div>
        <div><span style="color:var(--gray-500)">Masuk:</span> <?=date('d/m/Y H:i',strtotime($s['created_at']))?></div>
      </div>
      <div style="margin-top:14px;display:flex;gap:10px">
        <a href="detail_surat.php?id=<?=$s['id']?>" class="btn btn-primary btn-sm">👁 Periksa Detail</a>
        <form method="POST" action="detail_surat.php?id=<?=$s['id']?>" style="display:inline">
          <input type="hidden" name="action" value="setujui">
          <button class="btn btn-success btn-sm" onclick="return confirm('Setujui surat ini?')">✅ Setujui</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endforeach ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
