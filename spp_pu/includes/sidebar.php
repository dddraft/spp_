<?php
// includes/sidebar.php
// Deteksi halaman aktif
$current = basename($_SERVER['PHP_SELF'], '.php');
$user    = getCurrentUser() ?? [];
$role    = $user['role'] ?? $_SESSION['role'] ?? 'pengelola_keuangan';

function sideLink($href, $icon, $label, $current_page, $match) {
    $active = ($current_page === $match || strpos($current_page, $match) !== false);
    $cls    = $active ? ' active' : '';
    echo "<a href=\"$href\" class=\"nav-link$cls\">$icon <span>$label</span></a>";
}
?>
<style>
/* ══ SIDEBAR ══ */
#sidebar {
  width: 220px;
  min-height: 100vh;
  background: #1a3c6e;
  position: fixed;
  top: 0; left: 0;
  display: flex;
  flex-direction: column;
  z-index: 100;
  box-shadow: 2px 0 10px rgba(0,0,0,.2);
}
.sidebar-logo {
  padding: 16px 14px 12px;
  border-bottom: 1px solid rgba(255,255,255,.1);
  display: flex;
  align-items: center;
  gap: 10px;
}
.sidebar-logo img {
  width: 36px; height: 36px;
  object-fit: contain;
}
.sidebar-logo .app-name {
  font-size: 15px;
  font-weight: bold;
  color: #fff;
  line-height: 1.2;
}
.sidebar-logo .app-sub {
  font-size: 9px;
  color: rgba(255,255,255,.6);
  text-transform: uppercase;
  letter-spacing: .5px;
}

/* USER INFO */
.sidebar-user {
  padding: 10px 14px;
  border-bottom: 1px solid rgba(255,255,255,.08);
  font-size: 11px;
  color: rgba(255,255,255,.7);
}
.sidebar-user .uname {
  font-weight: bold;
  color: #fff;
  font-size: 12px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sidebar-user .urole {
  font-size: 10px;
  color: rgba(255,255,255,.5);
  text-transform: capitalize;
  margin-top: 1px;
}

/* NAV */
.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 8px 0;
}
.nav-section {
  padding: 8px 14px 4px;
  font-size: 9.5px;
  font-weight: bold;
  color: rgba(255,255,255,.35);
  text-transform: uppercase;
  letter-spacing: .8px;
  margin-top: 4px;
}
.nav-link {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 8px 14px;
  color: rgba(255,255,255,.75);
  text-decoration: none;
  font-size: 12px;
  transition: background .15s, color .15s;
  border-left: 3px solid transparent;
}
.nav-link:hover {
  background: rgba(255,255,255,.08);
  color: #fff;
}
.nav-link.active {
  background: rgba(255,255,255,.12);
  color: #fff;
  font-weight: bold;
  border-left-color: #5dade2;
}
.nav-link .badge-count {
  margin-left: auto;
  background: #e74c3c;
  color: #fff;
  font-size: 9px;
  padding: 1px 5px;
  border-radius: 8px;
  font-weight: bold;
}

/* DIVIDER */
.nav-divider {
  height: 1px;
  background: rgba(255,255,255,.08);
  margin: 6px 14px;
}

/* BOTTOM */
.sidebar-bottom {
  padding: 10px 14px;
  border-top: 1px solid rgba(255,255,255,.1);
}
.sidebar-bottom a {
  display: flex;
  align-items: center;
  gap: 8px;
  color: rgba(255,255,255,.6);
  text-decoration: none;
  font-size: 11.5px;
  padding: 6px 0;
  transition: color .15s;
}
.sidebar-bottom a:hover { color: #fff; }

/* TOGGLE MOBILE */
#sidebar-toggle {
  display: none;
  position: fixed;
  top: 10px; left: 10px;
  z-index: 200;
  background: #1a3c6e;
  color: #fff;
  border: none;
  border-radius: 4px;
  padding: 6px 10px;
  font-size: 16px;
  cursor: pointer;
}
@media(max-width:768px){
  #sidebar{transform:translateX(-100%);transition:transform .25s}
  #sidebar.open{transform:translateX(0)}
  #sidebar-toggle{display:block}
}
</style>

<button id="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>

<div id="sidebar">
  <!-- LOGO -->
  <div class="sidebar-logo">
    <img src="/spp_pu/assets/img/logo_pu.png" alt="PU" onerror="this.style.display='none'">
    <div>
      <div class="app-name">SAKURA</div>
      <div class="app-sub">Sistem Keuangan PUPR</div>
    </div>
  </div>

  <!-- USER -->
  <div class="sidebar-user">
    <div class="uname">
      👤 <?= htmlspecialchars($user['nama'] ?? $_SESSION['nama'] ?? 'Pengguna') ?>
    </div>
    <div class="urole">
      <?= str_replace('_', ' ', htmlspecialchars($role)) ?>
    </div>
  </div>

  <!-- NAV -->
  <nav class="sidebar-nav">

    <!-- UTAMA -->
    <div class="nav-section">Utama</div>
    <?php sideLink('/spp_pu/pages/dashboard.php', '🏠', 'Dashboard', $current, 'dashboard') ?>

    <!-- SPP -->
    <div class="nav-section">Surat Permintaan Pembayaran</div>
    <?php sideLink('/spp_pu/pages/daftar_surat.php', '📋', 'Daftar Semua SPP', $current, 'daftar_surat') ?>
    <?php sideLink('/spp_pu/pages/buat_surat_gaji.php', '💼', 'Buat SPP Gaji', $current, 'buat_surat_gaji') ?>
    <?php sideLink('/spp_pu/pages/buat_surat.php', '✈️', 'Buat SPP Perjadin', $current, 'buat_surat') ?>
    <?php sideLink('/spp_pu/pages/daftar_gaji.php', '📊', 'Daftar Gaji', $current, 'daftar_gaji') ?>

    <div class="nav-divider"></div>

    <!-- DOKUMEN -->
    <div class="nav-section">Dokumen Per SPP</div>
    <div style="padding: 4px 14px; font-size: 10.5px; color: rgba(255,255,255,.4); font-style:italic">
      Buka dari Detail SPP →
    </div>
    <div style="padding: 3px 14px">
      <?php
      // Quick links ke dokumen jika ada id di URL
      $qid = (int)($_GET['id'] ?? 0);
      if ($qid):
      ?>
      <a href="/spp_pu/pages/spls_gaji.php?id=<?= $qid ?>" class="nav-link" style="padding:5px 0;font-size:11px">
        📋 SPLS Gaji
      </a>
      <a href="/spp_pu/pages/sptb_gaji.php?id=<?= $qid ?>" class="nav-link" style="padding:5px 0;font-size:11px">
        📃 SPTB
      </a>
      <a href="/spp_pu/pages/ringkasan_kontrak.php?id=<?= $qid ?>" class="nav-link" style="padding:5px 0;font-size:11px">
        📑 Ringkasan Kontrak
      </a>
      <a href="/spp_pu/pages/rekap_gaji.php?id=<?= $qid ?>" class="nav-link" style="padding:5px 0;font-size:11px">
        💼 Rekapitulasi Gaji
      </a>
      <a href="/spp_pu/pages/nominatif_perjadin.php?id=<?= $qid ?>" class="nav-link" style="padding:5px 0;font-size:11px">
        ✈️ Nominatif Perjadin
      </a>
      <?php else: ?>
      <span style="color:rgba(255,255,255,.3);font-size:10.5px;display:block;padding:3px 0">
        Pilih SPP dari Daftar SPP
      </span>
      <?php endif; ?>
    </div>

    <div class="nav-divider"></div>

    <!-- LAPORAN -->
    <div class="nav-section">Laporan</div>
    <?php sideLink('/spp_pu/pages/laporan.php', '📈', 'Laporan Realisasi', $current, 'laporan') ?>

    <!-- MASTER -->
    <?php if (in_array($role, ['admin', 'bendahara', 'ppk', 'kpa'])): ?>
    <div class="nav-divider"></div>
    <div class="nav-section">Master Data</div>
    <?php sideLink('/spp_pu/pages/master_kop.php', '🏛️', 'Master Kop Surat', $current, 'master_kop') ?>
    <?php sideLink('/spp_pu/pages/penomoran.php', '🔢', 'Penomoran Surat', $current, 'penomoran') ?>
    <?php endif; ?>

    <!-- VERIFIKASI (khusus verifikator) -->
    <?php if (in_array($role, ['verifikator', 'bendahara', 'ppk', 'kpa', 'admin'])): ?>
    <div class="nav-divider"></div>
    <div class="nav-section">Verifikasi</div>
    <?php sideLink('/spp_pu/pages/verifikasi.php', '✅', 'Verifikasi SPP', $current, 'verifikasi') ?>
    <?php endif; ?>

  </nav>

  <!-- BOTTOM -->
  <div class="sidebar-bottom">
    <a href="/spp_pu/index.php?logout=1">🚪 Keluar</a>
  </div>
</div>