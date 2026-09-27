<?php
$pageTitle    = 'Kop Surat';
$pageSubtitle = 'Pilih dan atur kop surat yang digunakan pada setiap jenis dokumen';
$breadcrumb   = [['label'=>'Data Master','url'=>'#'], 'Kop Surat'];
require_once __DIR__ . '/../includes/header.php';

$kops = [
  ['id'=>'dirjen',     'label'=>'Kop Direktorat Jenderal',  'file'=>'kop_dirjen.jpg',     'desc'=>'Untuk surat tingkat Ditjen Prasarana Strategis'],
  ['id'=>'direktorat', 'label'=>'Kop Direktorat',           'file'=>'kop_direktorat.jpg', 'desc'=>'Untuk surat tingkat Direktorat (Dit. SSPPS, dll.)'],
  ['id'=>'satker',     'label'=>'Kop Satuan Kerja (SATKER)', 'file'=>'kop_satker.jpg',     'desc'=>'Untuk SPP, SPLS, SPTB dan dokumen keuangan SATKER'],
];
$activeKop = $_SESSION['kop_aktif'] ?? 'satker';
?>

<style>
.kop-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:24px;}
.kop-card{
  border:2px solid #e2e8f0;border-radius:14px;overflow:hidden;
  cursor:pointer;transition:all .2s;background:#fff;
}
.kop-card:hover{border-color:#4d7cf6;box-shadow:0 4px 16px rgba(77,124,246,.15);}
.kop-card.selected{border-color:#4d7cf6;box-shadow:0 0 0 3px rgba(77,124,246,.15);}
.kop-img-wrap{background:#f8fafc;padding:12px;line-height:0;}
.kop-img-wrap img{width:100%;height:auto;border-radius:6px;}
.kop-info{padding:14px 16px;border-top:1.5px solid #f1f5f9;}
.kop-info-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;}
.kop-nama{font-size:13.5px;font-weight:800;color:#1a2540;}
.kop-check{
  width:22px;height:22px;border-radius:6px;
  border:2px solid #e2e8f0;
  display:flex;align-items:center;justify-content:center;
  transition:all .18s;
}
.kop-card.selected .kop-check{background:#4d7cf6;border-color:#4d7cf6;}
.kop-desc{font-size:11.5px;color:#64748b;}
.badge-aktif{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;}
</style>

<div class="card">
  <div class="card-header">
    <div style="width:36px;height:36px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2055d0" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
    </div>
    <h2>Pilih Kop Surat Aktif</h2>
  </div>

  <div class="kop-grid">
    <?php foreach($kops as $k): ?>
    <div class="kop-card <?= $k['id']===$activeKop ? 'selected' : '' ?>" onclick="pilihKop('<?= $k['id'] ?>', this)">
      <div class="kop-img-wrap">
        <img src="/spp_pu/assets/img/kop/<?= $k['file'] ?>" alt="<?= htmlspecialchars($k['label']) ?>">
      </div>
      <div class="kop-info">
        <div class="kop-info-top">
          <div>
            <div class="kop-nama"><?= htmlspecialchars($k['label']) ?></div>
            <?php if($k['id']===$activeKop): ?>
            <span class="badge-aktif">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
              Aktif
            </span>
            <?php endif ?>
          </div>
          <div class="kop-check">
            <?php if($k['id']===$activeKop): ?>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            <?php endif ?>
          </div>
        </div>
        <div class="kop-desc"><?= htmlspecialchars($k['desc']) ?></div>
      </div>
    </div>
    <?php endforeach ?>
  </div>

  <div style="display:flex;gap:10px">
    <button class="btn btn-primary" onclick="simpanKop()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      Simpan Pengaturan Kop
    </button>
    <a href="/spp_pu/pages/dashboard.php" class="btn btn-secondary">Batal</a>
  </div>
</div>

<!-- Preview area -->
<div class="card" id="previewCard">
  <div class="card-header">
    <div style="width:36px;height:36px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
    </div>
    <h2>Preview Kop Aktif</h2>
  </div>
  <div style="background:#f8fafc;border-radius:10px;padding:16px;border:1.5px solid #e8edf5;">
    <img id="kopPreviewImg" src="/spp_pu/assets/img/kop/kop_<?= $activeKop ?>.jpg" style="width:100%;border-radius:6px;display:block;">
    <div style="height:2px;background:#000;margin-top:0;border-radius:0 0 6px 6px;"></div>
  </div>
</div>

<script>
var selectedKop = '<?= $activeKop ?>';

function pilihKop(id, card) {
  selectedKop = id;
  document.querySelectorAll('.kop-card').forEach(function(c){ c.classList.remove('selected'); });
  card.classList.add('selected');
  // update preview
  document.getElementById('kopPreviewImg').src = '/spp_pu/assets/img/kop/kop_' + id + '.jpg';
}

function simpanKop() {
  fetch('/spp_pu/pages/master_kop.php?set_kop='+selectedKop)
    .then(function(r){ return r.json(); })
    .then(function(d){
      if(d.ok) {
        document.querySelectorAll('.badge-aktif').forEach(function(b){ b.remove(); });
        document.querySelectorAll('.kop-check').forEach(function(c){ c.innerHTML=''; c.style.background=''; c.style.borderColor=''; });
        var activeCard = document.querySelector('.kop-card.selected');
        if(activeCard){
          var info = activeCard.querySelector('.kop-nama');
          info.insertAdjacentHTML('afterend','<span class="badge-aktif"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Aktif</span>');
          var chk = activeCard.querySelector('.kop-check');
          chk.style.background='#4d7cf6'; chk.style.borderColor='#4d7cf6';
          chk.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
        }
        alert('✅ Kop surat berhasil disimpan!');
      }
    });
}
</script>

<?php
if(isset($_GET['set_kop'])){
  $_SESSION['kop_aktif'] = $_GET['set_kop'];
  echo json_encode(['ok'=>true]);
  exit;
}
require_once __DIR__ . '/../includes/footer.php';
?>
