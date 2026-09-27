<?php
$pageTitle    = 'Daftar Dokumen Gaji';
$pageSubtitle = 'Kelola dan pantau seluruh dokumen pembayaran gaji tenaga pendukung';
$breadcrumb   = [['label' => 'Daftar Dokumen', 'url' => '#'], 'Gaji'];
require_once __DIR__ . '/../includes/header.php';

/* Demo data — ganti dengan query DB saat produksi */
$docs = [
  ['id'=>1,'nomor'=>'SPP-2026-002-045','tanggal'=>'2026-02-08','perihal'=>'Honorarium Tenaga Pendukung','penerima'=>12,'total'=>45000000,'status'=>'disetujui'],
  ['id'=>2,'nomor'=>'SPP-2026-002-044','tanggal'=>'2026-02-07','perihal'=>'Uang Makan Pegawai','penerima'=>25,'total'=>32500000,'status'=>'menunggu'],
  ['id'=>3,'nomor'=>'SPP-2026-002-043','tanggal'=>'2026-02-06','perihal'=>'Lembur Pegawai','penerima'=>18,'total'=>28750000,'status'=>'disetujui'],
  ['id'=>4,'nomor'=>'SPP-2026-001-042','tanggal'=>'2026-01-28','perihal'=>'Honorarium Bulan Januari 2026','penerima'=>12,'total'=>45000000,'status'=>'diproses'],
  ['id'=>5,'nomor'=>'SPP-2026-001-041','tanggal'=>'2026-01-15','perihal'=>'Insentif Kinerja Q4 2025','penerima'=>30,'total'=>60000000,'status'=>'draft'],
];
$statusMap = [
  'disetujui' => ['label'=>'Disetujui', 'cls'=>'badge-green'],
  'menunggu'  => ['label'=>'Menunggu',  'cls'=>'badge-yellow'],
  'diproses'  => ['label'=>'Diproses',  'cls'=>'badge-blue'],
  'draft'     => ['label'=>'Draft',     'cls'=>'badge-gray'],
  'ditolak'   => ['label'=>'Ditolak',   'cls'=>'badge-red'],
];
$total_all   = array_sum(array_column($docs,'total'));
$cnt_ok      = count(array_filter($docs, fn($d)=>$d['status']==='disetujui'));
$cnt_proses  = count(array_filter($docs, fn($d)=>in_array($d['status'],['menunggu','diproses'])));
$cnt_draft   = count(array_filter($docs, fn($d)=>$d['status']==='draft'));
?>

<style>
/* ── STAT CARDS ── */
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;}
.stat-card{background:#fff;border-radius:13px;border:1.5px solid #e8edf5;padding:18px 20px;display:flex;align-items:center;gap:14px;}
.stat-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.stat-icon.blue{background:#eff6ff;color:#2055d0;}
.stat-icon.gold{background:#fffbeb;color:#d97706;}
.stat-icon.green{background:#f0fdf4;color:#16a34a;}
.stat-icon.yellow{background:#fefce8;color:#b45309;}
.stat-label{font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;}
.stat-val{font-size:20px;font-weight:800;color:#1a2540;line-height:1;}
.stat-val.gold{color:#d97706;}
.stat-val.green{color:#16a34a;}

/* ── FILTER BAR ── */
.filter-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px;}
.filter-bar .filters{display:flex;align-items:center;gap:10px;flex:1;flex-wrap:wrap;}
.fi{padding:8px 13px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;font-family:inherit;color:#1a2540;background:#fff;outline:none;transition:border-color .15s;}
.fi:focus{border-color:#4d7cf6;box-shadow:0 0 0 3px rgba(77,124,246,.1);}
.fi-search{min-width:230px;}
.btn-new-doc{
  display:inline-flex;align-items:center;gap:7px;
  padding:9px 18px;border-radius:9px;border:none;
  font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;
  background:linear-gradient(135deg,#2055d0,#4d7cf6);color:#fff;
  box-shadow:0 3px 10px rgba(77,124,246,.3);text-decoration:none;
  white-space:nowrap;transition:all .18s;
}
.btn-new-doc:hover{transform:translateY(-1px);box-shadow:0 5px 16px rgba(77,124,246,.4);}

/* ── TABLE ── */
.doc-table-wrap{background:#fff;border-radius:14px;border:1.5px solid #e8edf5;overflow:hidden;}
table.dt{width:100%;border-collapse:collapse;}
table.dt thead tr{background:#1c2d4a;}
table.dt th{
  padding:13px 16px;text-align:left;
  color:#b0c4de;font-size:12px;font-weight:700;letter-spacing:.04em;white-space:nowrap;
}
table.dt th.gold{color:#f59e0b;}
table.dt th.c{text-align:center;}
table.dt th.r{text-align:right;}
table.dt tbody tr{border-bottom:1px solid #f1f5f9;transition:background .12s;}
table.dt tbody tr:last-child{border-bottom:none;}
table.dt tbody tr:hover{background:#f8fafc;}
table.dt td{padding:14px 16px;vertical-align:middle;}
table.dt td.c{text-align:center;}
table.dt td.r{text-align:right;}

/* ── NO CIRCLE ── */
.no-circle{width:36px;height:36px;border-radius:10px;background:#1c2d4a;color:#fff;font-size:13px;font-weight:800;display:flex;align-items:center;justify-content:center;}

/* ── NOMOR ── */
.nomor-code{font-family:monospace;font-size:13px;font-weight:800;color:#1a2540;display:flex;align-items:center;gap:6px;}
.nomor-dot{width:6px;height:6px;border-radius:50%;background:#f59e0b;flex-shrink:0;}
.nomor-sub{font-size:10.5px;color:#94a3b8;margin-top:2px;}

/* ── DATE ── */
.tgl-day{font-size:18px;font-weight:800;color:#1a2540;line-height:1.1;}
.tgl-mon{font-size:11px;color:#64748b;display:flex;align-items:center;gap:4px;margin-top:2px;}

/* ── PERIHAL ── */
.perihal-text{font-size:13px;font-weight:600;color:#2055d0;}
.perihal-sub{font-size:11px;color:#94a3b8;margin-top:2px;}

/* ── PENERIMA ── */
.penerima-wrap{display:flex;flex-direction:column;align-items:center;gap:2px;}
.penerima-icon-row{display:flex;align-items:center;gap:5px;color:#64748b;}
.penerima-jml{font-size:14px;font-weight:800;color:#1a2540;}
.penerima-orang{font-size:10.5px;color:#94a3b8;}

/* ── TOTAL ── */
.total-wrap{display:flex;align-items:center;gap:6px;justify-content:flex-end;}
.total-dollar{font-size:14px;color:#f59e0b;font-weight:800;}
.total-rp{font-size:13.5px;font-weight:800;color:#1a2540;}

/* ── AKSI BUTTONS ── */
.aksi-wrap{display:flex;align-items:center;justify-content:flex-end;gap:5px;}
.btn-ic{
  width:34px;height:34px;border-radius:9px;border:1.5px solid #e2e8f0;
  background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;
  color:#64748b;transition:all .15s;text-decoration:none;
}
.btn-ic.view:hover{border-color:#3b82f6;background:#eff6ff;color:#2055d0;}
.btn-ic.edit:hover{border-color:#f59e0b;background:#fffbeb;color:#d97706;}

/* ── DROPDOWN ── */
.dd-wrap{position:relative;}
.dd-menu{
  position:absolute;right:0;top:calc(100% + 5px);
  background:#fff;border:1.5px solid #e2e8f0;border-radius:11px;
  box-shadow:0 8px 30px rgba(0,0,0,.13);z-index:60;
  min-width:170px;overflow:hidden;display:none;
}
.dd-menu.open{display:block;}
.dd-item{
  display:flex;align-items:center;gap:9px;
  padding:9px 14px;font-size:13px;color:#334155;
  text-decoration:none;transition:background .12s;cursor:pointer;border:none;
  background:none;width:100%;text-align:left;font-family:inherit;
}
.dd-item:hover{background:#f8fafc;}
.dd-item svg{flex-shrink:0;color:#64748b;}
.dd-item.danger{color:#dc2626;}
.dd-item.danger svg{color:#ef4444;}
.dd-sep{height:1px;background:#f1f5f9;margin:3px 0;}

/* ── MODAL ── */
.modal-bg{
  position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;
  display:flex;align-items:center;justify-content:center;padding:20px;
  opacity:0;pointer-events:none;transition:opacity .2s;
}
.modal-bg.open{opacity:1;pointer-events:auto;}
.modal-box{
  background:#fff;border-radius:16px;max-width:430px;width:100%;
  transform:translateY(16px);transition:transform .22s;
  box-shadow:0 20px 60px rgba(0,0,0,.2);
}
.modal-bg.open .modal-box{transform:translateY(0);}
.modal-head{padding:18px 22px;border-bottom:1.5px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;}
.modal-head h3{font-size:15px;font-weight:800;color:#1a2540;}
.modal-close{width:30px;height:30px;border-radius:8px;border:none;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:16px;transition:all .15s;}
.modal-close:hover{background:#fee2e2;color:#dc2626;}
.modal-body{padding:22px;}

/* ── EMPTY ── */
.empty-row td{text-align:center;padding:56px 20px!important;color:#94a3b8;font-size:14px;}
</style>

<!-- STAT CARDS -->
<div class="stat-row">
  <div class="stat-card">
    <div class="stat-icon blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
    <div><div class="stat-label">Total Dokumen</div><div class="stat-val"><?= count($docs) ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon gold"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
    <div><div class="stat-label">Total Pembayaran</div><div class="stat-val gold">Rp <?= number_format($total_all,0,',','.') ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg></div>
    <div><div class="stat-label">Disetujui</div><div class="stat-val green"><?= $cnt_ok ?> Dok.</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
    <div><div class="stat-label">Dalam Proses</div><div class="stat-val"><?= $cnt_proses ?> Dok.</div></div>
  </div>
</div>

<!-- FILTER BAR -->
<div class="filter-bar">
  <div class="filters">
    <input type="search" class="fi fi-search" placeholder="🔍  Cari nomor atau perihal..." id="q" onkeyup="doFilter()">
    <select class="fi" id="fStatus" onchange="doFilter()">
      <option value="">Semua Status</option>
      <?php foreach($statusMap as $k=>$v): ?>
      <option value="<?= $k ?>"><?= $v['label'] ?></option>
      <?php endforeach ?>
    </select>
    <select class="fi" id="fBulan" onchange="doFilter()">
      <option value="">Semua Bulan</option>
      <?php foreach(['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'] as $num=>$nm): ?>
      <option value="<?= $num ?>"><?= $nm ?></option>
      <?php endforeach ?>
    </select>
  </div>
  <a href="/spp_pu/pages/gaji_spp.php?baru=1" class="btn-new-doc">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Buat Dokumen Gaji
  </a>
</div>

<!-- TABLE -->
<div class="doc-table-wrap">
  <table class="dt" id="docTable">
    <thead>
      <tr>
        <th style="width:56px">No</th>
        <th>Nomor SPP</th>
        <th>Tanggal</th>
        <th>Perihal</th>
        <th class="c">Penerima</th>
        <th class="r gold">Total Pembayaran</th>
        <th class="c">Status</th>
        <th class="r" style="width:110px">Aksi</th>
      </tr>
    </thead>
    <tbody id="docBody">
    <?php foreach ($docs as $i => $d):
      $tgl = new DateTime($d['tanggal']);
      $sc  = $statusMap[$d['status']] ?? $statusMap['draft'];
      $bln = $tgl->format('m');
      $ts  = strtolower($d['nomor'] . ' ' . $d['perihal']);
    ?>
    <tr data-status="<?= $d['status'] ?>" data-bulan="<?= $bln ?>" data-s="<?= htmlspecialchars($ts) ?>">

      <!-- NO -->
      <td><div class="no-circle"><?= $i+1 ?></div></td>

      <!-- NOMOR -->
      <td>
        <div class="nomor-code">
          <div class="nomor-dot"></div>
          <?= htmlspecialchars($d['nomor']) ?>
        </div>
        <div class="nomor-sub">Dokumen Gaji · TA <?= $tgl->format('Y') ?></div>
      </td>

      <!-- TANGGAL -->
      <td>
        <div class="tgl-day"><?= $tgl->format('d') ?></div>
        <div class="tgl-mon">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <?= $tgl->format('M Y') ?>
        </div>
      </td>

      <!-- PERIHAL -->
      <td>
        <div class="perihal-text"><?= htmlspecialchars($d['perihal']) ?></div>
        <div class="perihal-sub">SPP-LS Gaji Tenaga Pendukung</div>
      </td>

      <!-- PENERIMA -->
      <td class="c">
        <div class="penerima-wrap">
          <div class="penerima-icon-row">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            <span class="penerima-jml"><?= $d['penerima'] ?></span>
          </div>
          <span class="penerima-orang">orang</span>
        </div>
      </td>

      <!-- TOTAL -->
      <td class="r">
        <div class="total-wrap">
          <span class="total-dollar">$</span>
          <span class="total-rp">Rp <?= number_format($d['total'],0,',','.') ?></span>
        </div>
      </td>

      <!-- STATUS -->
      <td class="c">
        <span class="badge <?= $sc['cls'] ?>"><?= $sc['label'] ?></span>
      </td>

      <!-- AKSI -->
      <td>
        <div class="aksi-wrap">
          <a href="/spp_pu/pages/gaji_view.php?id=<?= $d['id'] ?>" class="btn-ic view" title="Lihat">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </a>
          <a href="/spp_pu/pages/gaji_spp.php?edit=<?= $d['id'] ?>" class="btn-ic edit" title="Edit">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </a>
          <div class="dd-wrap">
            <button class="btn-ic" title="Opsi Lainnya" onclick="toggleDD(this)">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
            </button>
            <div class="dd-menu" id="dd-<?= $d['id'] ?>">
              <a class="dd-item" href="/spp_pu/pages/gaji_view.php?id=<?= $d['id'] ?>&tab=spp">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Lihat SPP
              </a>
              <a class="dd-item" href="/spp_pu/pages/gaji_view.php?id=<?= $d['id'] ?>&tab=spls">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>Lihat SPLS
              </a>
              <a class="dd-item" href="/spp_pu/pages/gaji_view.php?id=<?= $d['id'] ?>&tab=sptb">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><polyline points="9 15 12 18 15 15"/></svg>Lihat SPTB
              </a>
              <a class="dd-item" href="/spp_pu/pages/gaji_view.php?id=<?= $d['id'] ?>&tab=nominatif">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>Lihat Nominatif
              </a>
              <div class="dd-sep"></div>
              <a class="dd-item" href="/spp_pu/pages/gaji_print.php?id=<?= $d['id'] ?>" target="_blank">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>Cetak Dokumen
              </a>
              <div class="dd-sep"></div>
              <button class="dd-item danger" onclick="konfirmHapus(<?= $d['id'] ?>,'<?= addslashes($d['nomor']) ?>')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>Hapus
              </button>
            </div>
          </div>
        </div>
      </td>
    </tr>
    <?php endforeach ?>
    <tr id="emptyRow" style="display:none"><td colspan="8" style="text-align:center;padding:56px;color:#94a3b8">Tidak ada dokumen ditemukan.</td></tr>
    </tbody>
  </table>
</div>

<!-- MODAL HAPUS -->
<div class="modal-bg" id="modalHapus">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Konfirmasi Hapus Dokumen</h3>
      <button class="modal-close" onclick="tutupModal()">✕</button>
    </div>
    <div class="modal-body" style="text-align:center">
      <div style="font-size:46px;margin-bottom:14px">🗑️</div>
      <div style="font-size:14px;color:#334155;margin-bottom:8px">Yakin ingin menghapus dokumen:</div>
      <div id="hapusNomor" style="font-family:monospace;font-size:14px;font-weight:800;color:#dc2626;margin-bottom:6px"></div>
      <div style="font-size:12px;color:#94a3b8;margin-bottom:22px">Dokumen yang dihapus tidak dapat dipulihkan.</div>
      <div style="display:flex;gap:10px;justify-content:center">
        <button onclick="tutupModal()" style="padding:9px 24px;border-radius:9px;border:1.5px solid #e2e8f0;background:#fff;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">Batal</button>
        <button id="hapusBtn" style="padding:9px 24px;border-radius:9px;border:none;background:#ef4444;color:#fff;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px;vertical-align:middle"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>Ya, Hapus
        </button>
      </div>
    </div>
  </div>
</div>

<script>
/* DROPDOWN */
function toggleDD(btn) {
  var dd = btn.nextElementSibling;
  document.querySelectorAll('.dd-menu.open').forEach(function(m){ if(m!==dd) m.classList.remove('open'); });
  dd.classList.toggle('open');
}
document.addEventListener('click', function(e){
  if (!e.target.closest('.dd-wrap')) document.querySelectorAll('.dd-menu.open').forEach(function(m){ m.classList.remove('open'); });
});

/* FILTER */
function doFilter() {
  var q  = document.getElementById('q').value.toLowerCase();
  var fs = document.getElementById('fStatus').value;
  var fb = document.getElementById('fBulan').value;
  var vis = 0;
  document.querySelectorAll('#docBody tr[data-s]').forEach(function(row){
    var ok = (!q || row.dataset.s.includes(q))
          && (!fs || row.dataset.status === fs)
          && (!fb || row.dataset.bulan === fb);
    row.style.display = ok ? '' : 'none';
    if(ok) vis++;
  });
  document.getElementById('emptyRow').style.display = vis === 0 ? '' : 'none';
}

/* HAPUS MODAL */
function konfirmHapus(id, nomor) {
  document.getElementById('hapusNomor').textContent = nomor;
  document.getElementById('hapusBtn').onclick = function(){ location.href = '/spp_pu/pages/daftar_gaji.php?hapus='+id; };
  document.getElementById('modalHapus').classList.add('open');
  document.querySelectorAll('.dd-menu.open').forEach(function(m){ m.classList.remove('open'); });
}
function tutupModal() { document.getElementById('modalHapus').classList.remove('open'); }
document.getElementById('modalHapus').addEventListener('click', function(e){ if(e.target===this) tutupModal(); });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
