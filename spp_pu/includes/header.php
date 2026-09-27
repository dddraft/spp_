<?php
require_once __DIR__ . '/../includes/config.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
requireLogin();
$user = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'Sistem SPP' ?> — Kementerian PUPR</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --navy: #0a1628;
    --navy-mid: #112240;
    --sidebar-w: 260px;
    --blue: #1a56db;
    --blue-light: #3b82f6;
    --gold: #f59e0b;
    --gold-light: #fbbf24;
    --white: #ffffff;
    --bg: #f0f4f8;
    --gray-100: #f1f5f9;
    --gray-200: #e2e8f0;
    --gray-300: #cbd5e1;
    --gray-500: #64748b;
    --gray-700: #334155;
    --green: #10b981;
    --red: #ef4444;
    --yellow: #f59e0b;
    --purple: #8b5cf6;
  }

  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--bg);
    color: var(--gray-700);
    display: flex;
    min-height: 100vh;
  }

  /* ══════════════════════════════════════
     SIDEBAR
  ══════════════════════════════════════ */
  .sidebar {
    width: var(--sidebar-w);
    background: var(--navy);
    height: 100vh;
    position: fixed;
    top: 0; left: 0;
    display: flex;
    flex-direction: column;
    z-index: 100;
    box-shadow: 4px 0 24px rgba(0,0,0,0.2);
    overflow: hidden;
  }
  .sidebar-nav {
    overflow-y: auto !important;
    overflow-x: hidden;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,.1) transparent;
    flex: 1;
  }
  .sidebar-nav::-webkit-scrollbar { width: 3px; }
  .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 3px; }

  /* LOGO */
  .sidebar-logo {
    padding: 20px 18px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
  }
  .sidebar-logo .logo-circle {
    width: 44px; height: 44px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 0 0 2.5px rgba(255,255,255,.25), 0 4px 14px rgba(0,0,0,.4);
    background: white;
    display: flex; align-items: center; justify-content: center;
    padding: 3px;
  }
  .sidebar-logo .logo-circle img {
    width: 100%; height: 100%;
    object-fit: contain; display: block;
  }

  .sidebar-logo .text {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.75);
    line-height: 1.5;
  }
  .sidebar-logo .text strong {
    display: block;
    font-size: 13px;
    color: white;
    font-weight: 800;
    letter-spacing: .03em;
  }

  /* NAV */
  .sidebar-nav { padding: 12px 0; flex: 1; }

  .nav-section-label {
    padding: 12px 18px 4px;
    font-size: 9.5px;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.25);
    margin-top: 4px;
  }
  .nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 18px;
    color: rgba(255,255,255,0.58);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.15s;
    border-left: 3px solid transparent;
  }
  .nav-item:hover { background: rgba(255,255,255,0.06); color: white; }
  .nav-item.active {
    background: rgba(26,86,219,0.22);
    color: white;
    border-left-color: #3b82f6;
    font-weight: 600;
  }
  .nav-icon { width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; opacity: 0.7; }
  .nav-item.active .nav-icon, .nav-item:hover .nav-icon { opacity: 1; }
  .nav-group-header.group-active .nav-icon { opacity: 1; }
  .nav-text { flex: 1; }

  /* GROUP */
  .nav-group-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 18px;
    color: rgba(255,255,255,0.58);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    border-left: 3px solid transparent;
    transition: all 0.15s;
    user-select: none;
  }
  .nav-group-header:hover { background: rgba(255,255,255,0.06); color: white; }
  .nav-group-header.group-active {
    color: white;
    background: rgba(26,86,219,0.15);
    border-left-color: #3b82f6;
  }
  .chevron { font-size: 11px; color: rgba(255,255,255,0.3); transition: transform 0.2s; }
  .nav-sub { background: rgba(0,0,0,0.15); overflow: hidden; }
  .nav-sub-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 18px 8px 36px;
    color: rgba(255,255,255,0.48);
    text-decoration: none;
    font-size: 12.5px;
    font-weight: 400;
    transition: all 0.15s;
    border-left: 3px solid transparent;
  }
  .nav-sub-item:hover { color: rgba(255,255,255,0.85); background: rgba(255,255,255,0.04); }
  .nav-sub-item.sub-active {
    color: #60a5fa;
    font-weight: 600;
    background: rgba(59,130,246,0.1);
  }
  .sub-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: rgba(255,255,255,0.25);
    flex-shrink: 0;
  }
  .nav-sub-item.sub-active .sub-dot { background: #60a5fa; }

  /* USER */
  .sidebar-user {
    padding: 14px 18px;
    border-top: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
  }
  .user-avatar {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 14px; color: var(--navy);
    flex-shrink: 0;
  }
  .user-info { flex: 1; min-width: 0; }
  .user-info .name { font-size: 12px; font-weight: 700; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .user-info .role { font-size: 10px; color: rgba(255,255,255,0.4); text-transform: capitalize; }
  .logout-btn { color: rgba(255,255,255,0.4); text-decoration: none; font-size: 16px; transition: color 0.2s; line-height:1; }
  .logout-btn:hover { color: var(--red); }

  /* ── USER POPUP ── */
  .user-popup-wrap { position: relative; }
  .user-trigger {
    display: flex; align-items: center; gap: 12px; flex: 1;
    cursor: pointer; min-width: 0;
    border-radius: 8px; padding: 2px 4px; margin: -2px -4px;
    transition: background .15s;
  }
  .user-trigger:hover { background: rgba(255,255,255,.06); }

  .user-popup {
    position: absolute;
    bottom: calc(100% + 12px);
    left: 0; right: 0;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0,0,0,.22), 0 2px 8px rgba(0,0,0,.1);
    overflow: hidden;
    opacity: 0; pointer-events: none;
    transform: translateY(8px);
    transition: opacity .2s, transform .2s;
    z-index: 200;
    min-width: 230px;
  }
  .user-popup.open { opacity: 1; pointer-events: auto; transform: translateY(0); }

  .up-head {
    background: linear-gradient(135deg, #0a1628, #1a3a6b);
    padding: 16px;
    display: flex; align-items: center; gap: 12px;
  }
  .up-av {
    width: 42px; height: 42px; border-radius: 50%;
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 800; color: #0a1628; flex-shrink: 0;
    box-shadow: 0 0 0 2px rgba(255,255,255,.2);
  }
  .up-head-name { font-size: 13.5px; font-weight: 800; color: #fff; }
  .up-head-role {
    display: inline-flex; margin-top: 4px;
    padding: 2px 8px; border-radius: 20px;
    font-size: 10px; font-weight: 700; letter-spacing: .04em;
    background: rgba(255,255,255,.15); color: rgba(255,255,255,.85);
  }
  .up-body { padding: 10px 0; }
  .up-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 16px; font-size: 12.5px;
    border-bottom: 1px solid #f1f5f9;
  }
  .up-row:last-child { border-bottom: none; }
  .up-label { color: #94a3b8; font-weight: 500; }
  .up-val { color: #1a2540; font-weight: 700; text-align: right; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .up-val.gold { color: #d97706; }
  .up-val.mono { font-family: monospace; font-size: 11px; letter-spacing: .02em; }
  .up-footer {
    padding: 10px 12px 12px;
    border-top: 1px solid #f1f5f9;
    display: flex; gap: 8px;
  }
  .up-btn {
    flex: 1; padding: 8px; border-radius: 8px; border: none; cursor: pointer;
    font-size: 12px; font-weight: 700; font-family: inherit;
    display: flex; align-items: center; justify-content: center; gap: 5px;
    text-decoration: none; transition: all .15s;
  }
  .up-btn-profile { background: #eff6ff; color: #1d4ed8; }
  .up-btn-profile:hover { background: #dbeafe; }
  .up-btn-logout { background: #fee2e2; color: #dc2626; }
  .up-btn-logout:hover { background: #fecaca; }

  /* arrow pointer */
  .user-popup::after {
    content: '';
    position: absolute; bottom: -6px; left: 24px;
    width: 12px; height: 12px;
    background: #fff;
    transform: rotate(45deg);
    box-shadow: 2px 2px 4px rgba(0,0,0,.08);
  }

  /* ══════════════════════════════════════
     MAIN CONTENT
  ══════════════════════════════════════ */
  .main-content {
    margin-left: var(--sidebar-w);
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  .topbar {
    background: white;
    border-bottom: 1px solid var(--gray-200);
    padding: 16px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 50;
  }
  .topbar-title { font-size: 18px; font-weight: 700; color: var(--navy); }
  .topbar-sub { font-size: 12px; color: var(--gray-500); margin-top: 2px; }
  .topbar-right { display: flex; align-items: center; gap: 12px; }

  .page-body { padding: 28px 32px; flex: 1; }

  /* ══ CARDS & COMPONENTS (sama seperti sebelumnya) ══ */
  .card { background: white; border-radius: 16px; border: 1px solid var(--gray-200); box-shadow: 0 1px 4px rgba(0,0,0,0.04); margin-bottom: 20px; }
  .card-header { padding: 20px 24px; border-bottom: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: space-between; }
  .card-title { font-size: 15px; font-weight: 700; color: var(--navy); }
  .card-body { padding: 24px; }

  /* Buttons */
  .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; border: none; transition: all 0.15s; text-decoration: none; }
  .btn-primary { background: linear-gradient(135deg, var(--blue), #2563eb); color: white; box-shadow: 0 2px 8px rgba(26,86,219,0.3); }
  .btn-primary:hover { box-shadow: 0 4px 16px rgba(26,86,219,0.4); transform: translateY(-1px); }
  .btn-success { background: var(--green); color: white; }
  .btn-danger { background: var(--red); color: white; }
  .btn-outline { background: white; color: var(--gray-700); border: 1.5px solid var(--gray-300); }
  .btn-outline:hover { border-color: var(--blue); color: var(--blue); }
  .btn-gold { background: var(--gold); color: var(--navy); font-weight: 700; }
  .btn-sm { padding: 7px 14px; font-size: 12px; }

  /* Status */
  .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
  .status-draft { background: #f1f5f9; color: #64748b; }
  .status-verifikasi { background: #fef3c7; color: #92400e; }
  .status-disetujui { background: #d1fae5; color: #065f46; }
  .status-ditolak { background: #fee2e2; color: #991b1b; }

  /* Table */
  .data-table { width: 100%; border-collapse: collapse; }
  .data-table th { padding: 12px 16px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--gray-500); background: var(--gray-100); border-bottom: 1px solid var(--gray-200); text-align: left; }
  .data-table td { padding: 14px 16px; font-size: 13px; border-bottom: 1px solid var(--gray-200); color: var(--gray-700); }
  .data-table tr:last-child td { border-bottom: none; }
  .data-table tr:hover td { background: var(--gray-100); }

  /* Form */
  .form-row { display: grid; gap: 20px; margin-bottom: 20px; }
  .form-row.col-2 { grid-template-columns: 1fr 1fr; }
  .form-row.col-3 { grid-template-columns: 1fr 1fr 1fr; }
  .form-group label { display: block; font-size: 12px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--navy); margin-bottom: 7px; }
  .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 11px 14px; border: 1.5px solid var(--gray-300); border-radius: 10px; font-size: 14px; font-family: 'Plus Jakarta Sans', sans-serif; color: var(--navy); transition: border-color 0.2s, box-shadow 0.2s; outline: none; background: white; }
  .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(26,86,219,0.1); }
  .form-group textarea { resize: vertical; min-height: 80px; }

  /* Alert */
  .alert { padding: 14px 18px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 10px; }
  .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
  .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
  .alert-info { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }

  /* Stats */
  .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
  .stat-card { background: white; border-radius: 16px; padding: 22px; border: 1px solid var(--gray-200); display: flex; align-items: center; gap: 16px; }
  .stat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
  .stat-value { font-size: 26px; font-weight: 800; color: var(--navy); line-height: 1; }
  .stat-label { font-size: 12px; color: var(--gray-500); margin-top: 4px; }

  @media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-content { margin-left: 0 !important; }
    body { background: white; }
  }
</style>
</head>
<body>

<!-- ════════════════════════════════════════
     SIDEBAR
════════════════════════════════════════ -->
<nav class="sidebar">
  <!-- LOGO — bulat sempurna -->
  <div class="sidebar-logo">
    <div class="logo-circle">
      <img src="/spp_pu/assets/logo_pu.png" alt="Logo Kementerian PU">
    </div>
    <div class="text">
      <strong>Sistem SPP</strong>
      <?= htmlspecialchars($user['satker_kode'] ?? '691278') ?> — <?= htmlspecialchars($user['satker_singkatan'] ?? 'DIT. SSPPS') ?>
    </div>
  </div>

  <!-- NAV -->
  <div class="sidebar-nav">

    <div class="nav-section-label">Utama</div>
    <a href="/spp_pu/pages/dashboard.php" class="nav-item <?= $currentPage==='dashboard'?'active':'' ?>">
      <span class="nav-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg></span>
      <span class="nav-text">Dashboard</span>
    </a>
    <a href="/spp_pu/pages/laporan.php" class="nav-item <?= $currentPage==='laporan'?'active':'' ?>">
      <span class="nav-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg></span>
      <span class="nav-text">Laporan &amp; Rekap</span>
    </a>

    <div class="nav-section-label">Buat Dokumen</div>

    <!-- Gaji -->
    <div class="nav-group">
      <div class="nav-group-header <?= in_array($currentPage,['gaji_spp','gaji_spls','gaji_sptb','gaji_nominatif'])?'group-active':'' ?>" onclick="toggleGroup('gaji')">
        <div style="display:flex;align-items:center;gap:10px">
          <span class="nav-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></span>
          <span class="nav-text">Gaji</span>
        </div>
        <span class="chevron" id="chev-gaji">▸</span>
      </div>
      <div class="nav-sub" id="sub-gaji" style="display:<?= in_array($currentPage,['gaji_spp','gaji_spls','gaji_sptb','gaji_nominatif'])?'block':'none' ?>">
        <a href="/spp_pu/pages/gaji_spp.php" class="nav-sub-item <?= $currentPage==='gaji_spp'?'sub-active':'' ?>"><span class="sub-dot"></span> SPP</a>
        <a href="/spp_pu/pages/gaji_spls.php" class="nav-sub-item <?= $currentPage==='gaji_spls'?'sub-active':'' ?>"><span class="sub-dot"></span> SPLS (SPP-LS)</a>
        <a href="/spp_pu/pages/gaji_sptb.php" class="nav-sub-item <?= $currentPage==='gaji_sptb'?'sub-active':'' ?>"><span class="sub-dot"></span> SPTB</a>
        <a href="/spp_pu/pages/gaji_nominatif.php" class="nav-sub-item <?= $currentPage==='gaji_nominatif'?'sub-active':'' ?>"><span class="sub-dot"></span> Nominatif</a>
      </div>
    </div>

    <!-- Perjadin -->
    <div class="nav-group">
      <div class="nav-group-header <?= in_array($currentPage,['perjadin_spp','perjadin_spls','perjadin_sptb','perjadin_nominatif'])?'group-active':'' ?>" onclick="toggleGroup('perjadin')">
        <div style="display:flex;align-items:center;gap:10px">
          <span class="nav-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg></span>
          <span class="nav-text">Perjalanan Dinas</span>
        </div>
        <span class="chevron" id="chev-perjadin">▸</span>
      </div>
      <div class="nav-sub" id="sub-perjadin" style="display:<?= in_array($currentPage,['perjadin_spp','perjadin_spls','perjadin_sptb','perjadin_nominatif'])?'block':'none' ?>">
        <a href="/spp_pu/pages/perjadin_spp.php" class="nav-sub-item <?= $currentPage==='perjadin_spp'?'sub-active':'' ?>"><span class="sub-dot"></span> SPP</a>
        <a href="/spp_pu/pages/perjadin_spls.php" class="nav-sub-item <?= $currentPage==='perjadin_spls'?'sub-active':'' ?>"><span class="sub-dot"></span> SPLS (SPP-LS)</a>
        <a href="/spp_pu/pages/perjadin_sptb.php" class="nav-sub-item <?= $currentPage==='perjadin_sptb'?'sub-active':'' ?>"><span class="sub-dot"></span> SPTB</a>
        <a href="/spp_pu/pages/perjadin_nominatif.php" class="nav-sub-item <?= $currentPage==='perjadin_nominatif'?'sub-active':'' ?>"><span class="sub-dot"></span> Nominatif</a>
      </div>
    </div>

    <!-- ATK -->
    <div class="nav-group">
      <div class="nav-group-header <?= in_array($currentPage,['atk_spp','atk_spls','atk_sptb','atk_rincian'])?'group-active':'' ?>" onclick="toggleGroup('atk')">
        <div style="display:flex;align-items:center;gap:10px">
          <span class="nav-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></span>
          <span class="nav-text">ATK &amp; Bahan</span>
        </div>
        <span class="chevron" id="chev-atk">▸</span>
      </div>
      <div class="nav-sub" id="sub-atk" style="display:<?= in_array($currentPage,['atk_spp','atk_spls','atk_sptb','atk_rincian'])?'block':'none' ?>">
        <a href="/spp_pu/pages/atk_spp.php" class="nav-sub-item <?= $currentPage==='atk_spp'?'sub-active':'' ?>"><span class="sub-dot"></span> SPP</a>
        <a href="/spp_pu/pages/atk_spls.php" class="nav-sub-item <?= $currentPage==='atk_spls'?'sub-active':'' ?>"><span class="sub-dot"></span> SPLS (SPP-LS)</a>
        <a href="/spp_pu/pages/atk_sptb.php" class="nav-sub-item <?= $currentPage==='atk_sptb'?'sub-active':'' ?>"><span class="sub-dot"></span> SPTB</a>
        <a href="/spp_pu/pages/atk_rincian.php" class="nav-sub-item <?= $currentPage==='atk_rincian'?'sub-active':'' ?>"><span class="sub-dot"></span> Rincian ATK</a>
      </div>
    </div>

    <div class="nav-section-label">Daftar Dokumen</div>
    <div class="nav-group">
      <div class="nav-group-header <?= in_array($currentPage,['daftar_gaji','daftar_perjadin','daftar_atk'])?'group-active':'' ?>" onclick="toggleGroup('daftar')">
        <div style="display:flex;align-items:center;gap:10px">
          <span class="nav-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg></span>
          <span class="nav-text">Semua Dokumen</span>
        </div>
        <span class="chevron" id="chev-daftar">▸</span>
      </div>
      <div class="nav-sub" id="sub-daftar" style="display:<?= in_array($currentPage,['daftar_gaji','daftar_perjadin','daftar_atk'])?'block':'none' ?>">
        <a href="/spp_pu/pages/daftar_gaji.php" class="nav-sub-item <?= $currentPage==='daftar_gaji'?'sub-active':'' ?>"><span class="sub-dot"></span> Dokumen Gaji</a>
        <a href="/spp_pu/pages/daftar_perjadin.php" class="nav-sub-item <?= $currentPage==='daftar_perjadin'?'sub-active':'' ?>"><span class="sub-dot"></span> Dokumen Perjadin</a>
        <a href="/spp_pu/pages/daftar_atk.php" class="nav-sub-item <?= $currentPage==='daftar_atk'?'sub-active':'' ?>"><span class="sub-dot"></span> Dokumen ATK</a>
      </div>
    </div>
    <a href="/spp_pu/pages/penomoran.php" class="nav-item <?= $currentPage==='penomoran'?'active':'' ?>">
      <span class="nav-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></span>
      <span class="nav-text">Register Nomor</span>
    </a>

    <div class="nav-section-label">Data Master</div>
    <div class="nav-group">
      <div class="nav-group-header <?= in_array($currentPage,['master_pegawai','master_kategori','master_pejabat','master_golongan','master_satker','master_dipa','master_kop','master_rekening'])?'group-active':'' ?>" onclick="toggleGroup('master')">
        <div style="display:flex;align-items:center;gap:10px">
          <span class="nav-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg></span>
          <span class="nav-text">Data Master</span>
        </div>
        <span class="chevron" id="chev-master">▸</span>
      </div>
      <div class="nav-sub" id="sub-master" style="display:<?= in_array($currentPage,['master_pegawai','master_kategori','master_pejabat','master_golongan','master_satker','master_dipa','master_kop','master_rekening'])?'block':'none' ?>">
        <a href="/spp_pu/pages/master_pegawai.php" class="nav-sub-item <?= $currentPage==='master_pegawai'?'sub-active':'' ?>"><span class="sub-dot"></span> Pegawai</a>
        <a href="/spp_pu/pages/master_kategori.php" class="nav-sub-item <?= $currentPage==='master_kategori'?'sub-active':'' ?>"><span class="sub-dot"></span> Kategori Pegawai</a>
        <a href="/spp_pu/pages/master_pejabat.php" class="nav-sub-item <?= $currentPage==='master_pejabat'?'sub-active':'' ?>"><span class="sub-dot"></span> Pejabat Penandatangan</a>
        <a href="/spp_pu/pages/master_golongan.php" class="nav-sub-item <?= $currentPage==='master_golongan'?'sub-active':'' ?>"><span class="sub-dot"></span> Golongan</a>
        <a href="/spp_pu/pages/master_satker.php" class="nav-sub-item <?= $currentPage==='master_satker'?'sub-active':'' ?>"><span class="sub-dot"></span> Satuan Kerja</a>
        <a href="/spp_pu/pages/master_dipa.php" class="nav-sub-item <?= $currentPage==='master_dipa'?'sub-active':'' ?>"><span class="sub-dot"></span> DIPA &amp; MAK</a>
        <a href="/spp_pu/pages/master_kop.php" class="nav-sub-item <?= $currentPage==='master_kop'?'sub-active':'' ?>"><span class="sub-dot"></span> Kop Surat</a>
        <a href="/spp_pu/pages/master_rekening.php" class="nav-sub-item <?= $currentPage==='master_rekening'?'sub-active':'' ?>"><span class="sub-dot"></span> Bank &amp; Rekening</a>
      </div>
    </div>

    <?php if(in_array($user['role'] ?? '', ['admin','ppspm','kppn'])): ?>
    <div class="nav-section-label">Admin</div>
    <div class="nav-group">
      <div class="nav-group-header <?= in_array($currentPage,['user_list','user_role','user_log'])?'group-active':'' ?>" onclick="toggleGroup('usermgmt')">
        <div style="display:flex;align-items:center;gap:10px">
          <span class="nav-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></span>
          <span class="nav-text">Kelola Pengguna</span>
        </div>
        <span class="chevron" id="chev-usermgmt">▸</span>
      </div>
      <div class="nav-sub" id="sub-usermgmt" style="display:<?= in_array($currentPage,['user_list','user_role','user_log'])?'block':'none' ?>">
        <a href="/spp_pu/pages/user_list.php" class="nav-sub-item <?= $currentPage==='user_list'?'sub-active':'' ?>"><span class="sub-dot"></span> Daftar User</a>
        <a href="/spp_pu/pages/user_role.php" class="nav-sub-item <?= $currentPage==='user_role'?'sub-active':'' ?>"><span class="sub-dot"></span> Role &amp; Akses</a>
        <a href="/spp_pu/pages/user_log.php" class="nav-sub-item <?= $currentPage==='user_log'?'sub-active':'' ?>"><span class="sub-dot"></span> Log Aktivitas</a>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <script>
  /* USER POPUP */
  function toggleUserPopup() {
    var popup = document.getElementById('userPopup');
    popup.classList.toggle('open');
  }
  // Tutup popup kalau klik di luar
  document.addEventListener('click', function(e) {
    var wrap = document.getElementById('userPopupWrap');
    if (wrap && !wrap.contains(e.target)) {
      var popup = document.getElementById('userPopup');
      if (popup) popup.classList.remove('open');
    }
  });

  function toggleGroup(g) {
    var sub  = document.getElementById('sub-'+g);
    var chev = document.getElementById('chev-'+g);
    if (!sub) return;
    var open = sub.style.display !== 'none';
    sub.style.display = open ? 'none' : 'block';
    if (chev) chev.textContent = open ? '▸' : '▾';
  }
  document.querySelectorAll('.nav-sub').forEach(function(el){
    var id   = el.id.replace('sub-','');
    var chev = document.getElementById('chev-'+id);
    if (el.style.display !== 'none' && chev) chev.textContent = '▾';
  });
  </script>

  <!-- USER ROW -->
  <?php
  $rl_full = ['pengelola_keuangan'=>'Pengelola Keuangan','verifikator'=>'Verifikator','bendahara'=>'Bendahara','ppspm'=>'PPSPM','kppn'=>'Pegawai KPPN','admin'=>'Administrator'];
  $u_role_label = $rl_full[$user['role'] ?? ''] ?? ($user['role'] ?? '');
  $u_nip   = htmlspecialchars($user['nip'] ?? '-');
  $u_nama  = htmlspecialchars($user['nama'] ?? 'User');
  $u_jabatan = htmlspecialchars($user['jabatan'] ?? $u_role_label);
  $u_satker  = htmlspecialchars($_SESSION['satker_singkatan'] ?? $user['satker_singkatan'] ?? 'Dit. SSPPS');
  $u_kode    = htmlspecialchars($_SESSION['satker_kode'] ?? $user['satker_kode'] ?? '691278');
  // Email: nip@pu.go.id (standar email PU)
  $u_email   = htmlspecialchars(($user['email'] ?? '') ?: strtolower(str_replace(' ','.', $user['nama'] ?? 'user')) . '@pu.go.id');
  $u_inisial = strtoupper(substr($user['nama'] ?? 'U', 0, 1));
  ?>
  <div class="sidebar-user user-popup-wrap" id="userPopupWrap">
    <!-- Trigger: klik area nama -->
    <div class="user-trigger" onclick="toggleUserPopup()" title="Lihat info pengguna">
      <div class="user-avatar"><?= $u_inisial ?></div>
      <div class="user-info">
        <div class="name"><?= $u_nama ?></div>
        <div class="role"><?= $u_role_label ?></div>
      </div>
    </div>
    <!-- Tombol logout tetap di kanan -->
    <a href="/spp_pu/logout.php" class="logout-btn" title="Keluar" onclick="event.stopPropagation()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>

    <!-- POPUP INFO PENGGUNA -->
    <div class="user-popup" id="userPopup">
      <!-- Header -->
      <div class="up-head">
        <div class="up-av"><?= $u_inisial ?></div>
        <div>
          <div class="up-head-name"><?= $u_nama ?></div>
          <span class="up-head-role"><?= $u_role_label ?></span>
        </div>
      </div>
      <!-- Detail rows -->
      <div class="up-body">
        <div class="up-row">
          <span class="up-label">NIP</span>
          <span class="up-val mono"><?= $u_nip ?></span>
        </div>
        <div class="up-row">
          <span class="up-label">Jabatan</span>
          <span class="up-val"><?= $u_jabatan ?></span>
        </div>
        <div class="up-row">
          <span class="up-label">Satker</span>
          <span class="up-val"><?= $u_satker ?></span>
        </div>
        <div class="up-row">
          <span class="up-label">Kode Satker</span>
          <span class="up-val gold"><?= $u_kode ?></span>
        </div>
        <div class="up-row">
          <span class="up-label">Email</span>
          <span class="up-val" style="font-size:11px"><?= $u_email ?></span>
        </div>
      </div>
      <!-- Footer buttons -->
      <div class="up-footer">
        <a href="/spp_pu/pages/profile.php" class="up-btn up-btn-profile">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Edit Profil
        </a>
        <a href="/spp_pu/logout.php" class="up-btn up-btn-logout">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Keluar
        </a>
      </div>
    </div>
  </div>
</nav>

<!-- ════════════════════════════════════════
     MAIN CONTENT
════════════════════════════════════════ -->
<div class="main-content">
  <div class="topbar">
    <div>
      <div class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></div>
      <div class="topbar-sub">Kementerian Pekerjaan Umum dan Perumahan Rakyat</div>
    </div>
    <div class="topbar-right">
      <?php
      $rl  = ['pengelola_keuangan'=>'Pengelola Keuangan','verifikator'=>'Verifikator','bendahara'=>'Bendahara','ppspm'=>'PPSPM','kppn'=>'KPPN','admin'=>'Admin'];
      $rc2 = ['pengelola_keuangan'=>'#1a56db','verifikator'=>'#10b981','bendahara'=>'#f59e0b','ppspm'=>'#8b5cf6','kppn'=>'#ef4444','admin'=>'#1a237e'];
      $r   = $user['role'] ?? 'admin';
      ?>
      <span style="padding:5px 16px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;background:<?= $rc2[$r] ?? '#1a237e' ?>;color:white;letter-spacing:.05em">
        <?= $rl[$r] ?? $r ?>
      </span>
      <span style="font-size:12px;color:var(--gray-500);font-weight:600"><?= $user['satker_kode'] ?? '691278' ?></span>
      <span style="font-size:12px;color:var(--gray-500)"><?= date('d M Y') ?></span>
    </div>
  </div>

  <div class="page-body">

  <?php if (!dbOk()): ?>
  <!-- DB ERROR BANNER -->
  <div style="background:#fef2f2;border:1.5px solid #fecaca;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:flex-start;gap:14px;">
    <div style="font-size:24px;flex-shrink:0">⚠️</div>
    <div>
      <div style="font-size:14px;font-weight:800;color:#dc2626;margin-bottom:6px">Database tidak terhubung — MySQL belum aktif</div>
      <div style="font-size:13px;color:#7f1d1d;margin-bottom:8px">Buka <strong>XAMPP Control Panel</strong>, lalu klik <strong>Start</strong> pada <strong>Apache</strong> dan <strong>MySQL</strong> sampai lampu hijau menyala, kemudian refresh halaman ini.</div>
      <?php if(!empty($GLOBALS['_db_error'])): ?>
      <code style="font-size:11.5px;background:#fee2e2;padding:3px 8px;border-radius:5px;color:#991b1b"><?= htmlspecialchars($GLOBALS['_db_error']) ?></code>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if(isset($_SESSION['flash_success'])): ?>
  <div class="alert alert-success">✅ <?= htmlspecialchars($_SESSION['flash_success']) ?></div>
  <?php unset($_SESSION['flash_success']); endif; ?>

  <?php if(isset($_SESSION['flash_error'])): ?>
  <div class="alert alert-danger">⚠️ <?= htmlspecialchars($_SESSION['flash_error']) ?></div>
  <?php unset($_SESSION['flash_error']); endif; ?>

