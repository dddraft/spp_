<?php
require_once __DIR__ . "/includes/config.php";

// No-cache — tombol Back kembali ke login bersih
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit;
}

$db = getDB();
$satker_list = $db->query("SELECT * FROM satker WHERE active=1 ORDER BY kode")->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip      = trim($_POST['nip'] ?? '');
    $password = $_POST['password'] ?? '';
    $satker_id = (int)($_POST['satker_id'] ?? 0);

    if (!$nip || !$password || !$satker_id) {
        $error = 'NIP, Satuan Kerja, dan password wajib diisi.';
    } else {
        // Validasi NIP + password saja. Satker = konteks akses, tidak divalidasi cocok.
        $stmt = $db->prepare("SELECT u.* FROM users u WHERE u.nip=? AND u.active=1 LIMIT 1");
        $stmt->execute([$nip]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Ambil data satker yang DIPILIH saat login
            $sk = $db->prepare("SELECT * FROM satker WHERE id=?");
            $sk->execute([$satker_id]);
            $satker_dipilih = $sk->fetch();

            $db->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);

            // Satker yang sedang diakses (bisa beda dari satker asli user)
            $user['satker_nama']      = $satker_dipilih['nama'] ?? '';
            $user['satker_kode']      = $satker_dipilih['kode'] ?? '';
            $user['satker_singkatan'] = $satker_dipilih['singkatan'] ?? '';
            $user['satker_akses_id']  = $satker_id;

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user']      = $user;
            $_SESSION['satker_id'] = $satker_id;

            header('Location: pages/dashboard.php');
            exit;
        } else {
            $error = 'NIP atau password salah.';
        }
    }
}

$role_labels = [
    'pengelola_keuangan' => 'Pengelola Keuangan',
    'verifikator'        => 'Verifikator Keuangan',
    'bendahara'          => 'Bendahara',
    'ppspm'              => 'Pegawai PPSPM',
    'kppn'               => 'Pegawai KPPN',
    'admin'              => 'Administrator',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Sistem SPP Ditjen Prasarana Strategis</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{
    --navy:#1a237e;--navy-dark:#0d1757;--navy-mid:#283593;
    --gold:#f5a800;--gold-light:#ffc107;
    --white:#fff;--bg:#f0f4fb;
    --gray-100:#f4f6fb;--gray-200:#e8edf5;--gray-300:#d1d9e6;
    --gray-400:#a0aec0;--gray-500:#64748b;--navy-text:#1a237e;
    --red:#e53e3e;--green:#10b981;
  }
  body{font-family:'Plus Jakarta Sans',sans-serif;min-height:100vh;display:flex;background:var(--bg);overflow:hidden}

  /* LEFT */
  /* Keyframe animations */
  @keyframes pulse-glow {
    0%,100%{transform:scale(1);opacity:.10}
    50%{transform:scale(1.25);opacity:.20}
  }
  @keyframes pulse-glow2 {
    0%,100%{transform:scale(1);opacity:.08}
    50%{transform:scale(1.35);opacity:.18}
  }
  @keyframes float-in {
    0%{transform:translateX(-60px);opacity:0}
    100%{transform:translateX(0);opacity:1}
  }
  @keyframes sparkle {
    0%,100%{opacity:0;transform:scale(0) rotate(0deg)}
    50%{opacity:1;transform:scale(1) rotate(180deg)}
  }

  @keyframes slide-up {
    0%{transform:translateY(20px);opacity:0}
    100%{transform:translateY(0);opacity:1}
  }

  .left-panel{
    width:46%;background:linear-gradient(155deg,var(--navy-dark) 0%,var(--navy) 55%,var(--navy-mid) 100%);
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    padding:48px 44px;position:relative;overflow:hidden;
    animation:float-in .6s ease both;
  }
  .left-panel::before{content:'';position:absolute;top:-100px;right:-80px;width:380px;height:380px;border:55px solid rgba(245,168,0,.08);border-radius:50%}
  .left-panel::after{content:'';position:absolute;bottom:-90px;left:-50px;width:300px;height:300px;border:45px solid rgba(255,255,255,.05);border-radius:50%}

  /* Static decorative rings (seperti awal) */
  .blob1{display:none}
  .blob2{display:none}
  .blob3{display:none}

  /* Static ring decorations */
  .deco1{position:absolute;bottom:70px;right:-30px;width:180px;height:180px;border:30px solid rgba(245,168,0,.06);border-radius:50%}
  .deco2{position:absolute;top:50px;left:-25px;width:140px;height:140px;border:25px solid rgba(255,255,255,.04);border-radius:50%}

  /* Sparkle dots around logo */
  .sparkle-wrap{position:relative;display:inline-block;margin-bottom:28px}
  .sparkle{
    position:absolute;width:10px;height:10px;
    background:#f5a800;clip-path:polygon(50% 0%,61% 35%,98% 35%,68% 57%,79% 91%,50% 70%,21% 91%,32% 57%,2% 35%,39% 35%);
    animation:sparkle 2s ease-in-out infinite;
  }
  .sp0{top:calc(50% + -55px);left:calc(50% + 0px);animation-delay:0s}
  .sp1{top:calc(50% + -27px);left:calc(50% + 48px);animation-delay:.33s}
  .sp2{top:calc(50% + 27px);left:calc(50% + 48px);animation-delay:.66s}
  .sp3{top:calc(50% + 55px);left:calc(50% + 0px);animation-delay:.99s}
  .sp4{top:calc(50% + 27px);left:calc(50% + -48px);animation-delay:1.32s}
  .sp5{top:calc(50% + -27px);left:calc(50% + -48px);animation-delay:1.65s}

  .left-content{position:relative;z-index:2;text-align:center;display:flex;flex-direction:column;align-items:center}
  .logo-circle{
    width:128px;height:128px;background:white;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    margin-bottom:28px;overflow:hidden;
    box-shadow:0 8px 36px rgba(0,0,0,.28),0 0 0 5px rgba(245,168,0,.2);
  }
  .logo-circle img{width:82px;height:82px;object-fit:contain}
  .inst-name{font-size:20px;font-weight:800;color:#fff;line-height:1.25;margin-bottom:8px;text-shadow:0 2px 10px rgba(0,0,0,.3);animation:slide-up .7s ease .3s both}
  .gold-bar{width:44px;height:3px;background:var(--gold);border-radius:2px;margin:10px auto 12px}
  .inst-sub{font-size:12px;font-weight:500;color:rgba(255,255,255,.68);letter-spacing:.02em;line-height:1.55;margin-bottom:36px;animation:slide-up .7s ease .5s both}
  .inst-desc{font-size:12px;font-weight:500;color:rgba(255,255,255,.68);letter-spacing:.02em;line-height:1.55;margin-bottom:36px;animation:slide-up .7s ease .5s both}

  /* Role pills */
  .role-pills{display:flex;flex-direction:column;gap:7px;width:100%;max-width:280px}
  .role-pill{
    display:flex;align-items:center;gap:10px;padding:9px 14px;
    background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);
    border-radius:10px;font-size:12px;color:rgba(255,255,255,.75);font-weight:500;
  }
  .role-pill .dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
  .dot-pk{background:#60a5fa}.dot-vk{background:#34d399}.dot-bd{background:#fbbf24}
  .dot-pp{background:#a78bfa}.dot-kp{background:#fb7185}

  /* RIGHT */
  .right-panel{flex:1;display:flex;align-items:center;justify-content:center;padding:36px;background:var(--bg)}
  .login-card{
    width:100%;max-width:440px;background:var(--white);border-radius:20px;
    padding:42px 38px 36px;
    box-shadow:0 4px 40px rgba(26,35,126,.1),0 1px 4px rgba(0,0,0,.06);
    border-top:4px solid var(--gold);
  }
  .card-title{font-size:24px;font-weight:800;color:var(--navy);margin-bottom:4px}
  .card-sub{font-size:13px;color:var(--gray-500);margin-bottom:28px}

  .err-box{background:#fff5f5;border:1.5px solid #fed7d7;color:var(--red);padding:11px 15px;border-radius:10px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:8px}

  .fg{margin-bottom:18px}
  .fg label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--navy);margin-bottom:7px}
  .iw{position:relative}
  .iw .ic{position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:15px;color:var(--gray-400);pointer-events:none}
  .fg input,.fg select{
    width:100%;padding:12px 14px 12px 40px;
    border:1.5px solid var(--gray-300);border-radius:11px;
    font-size:13.5px;font-family:'Plus Jakarta Sans',sans-serif;
    color:var(--navy);background:var(--gray-100);
    transition:border-color .2s,box-shadow .2s,background .2s;outline:none;
    appearance:none;-webkit-appearance:none;
  }
  .fg select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2364748b' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;background-color:var(--gray-100);padding-right:36px}
  .fg input:focus,.fg select:focus{border-color:var(--navy);background:white;box-shadow:0 0 0 3px rgba(26,35,126,.09)}
  .fg input::placeholder{color:var(--gray-400);font-size:13px}
  .fg select option:first-child{color:var(--gray-400)}

  .btn-login{
    width:100%;padding:14px;background:var(--navy);color:white;border:none;
    border-radius:11px;font-size:14px;font-weight:700;
    font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;
    letter-spacing:.06em;text-transform:uppercase;
    transition:background .2s,transform .15s,box-shadow .2s;
    box-shadow:0 4px 16px rgba(26,35,126,.25);margin-top:6px;
  }
  .btn-login:hover{background:var(--navy-dark);transform:translateY(-1px);box-shadow:0 8px 24px rgba(26,35,126,.32)}
  .btn-login:active{transform:translateY(0)}

  .demo-box{margin-top:20px;padding:12px 14px;background:#f0f4ff;border-radius:10px;font-size:11.5px;color:var(--gray-500);line-height:1.8;border-left:3px solid var(--navy)}
  .demo-box strong{color:var(--navy)}
  .foot{margin-top:20px;text-align:center;font-size:11px;color:var(--gray-400)}

  @media(max-width:820px){.left-panel{display:none}.right-panel{padding:20px}}
</style>
</head>
<body>

<div class="left-panel">
  <!-- Animated glow blobs -->
  <div class="blob1"></div>
  <div class="blob2"></div>
  <div class="blob3"></div>
  <div class="deco1"></div><div class="deco2"></div>

  <div class="left-content">
    <!-- Logo with sparkles -->
    <div class="sparkle-wrap">
      <div class="sparkle sp0"></div>
      <div class="sparkle sp1"></div>
      <div class="sparkle sp2"></div>
      <div class="sparkle sp3"></div>
      <div class="sparkle sp4"></div>
      <div class="sparkle sp5"></div>
      <div class="logo-circle">
        <img src="assets/logo_pu.png" alt="Logo Kementerian PU">
      </div>
    </div>
    <div class="inst-name">Kementerian<br>Pekerjaan Umum</div>
    <div class="gold-bar"></div>
    <div class="inst-sub">Direktorat Jenderal Prasarana Strategis</div>
    <div class="inst-desc">Building The Future Together</div>


  </div>
</div>

<div class="right-panel">
  <div class="login-card">
    <div class="card-title">Selamat Datang</div>
    <div class="card-sub">Masuk ke Sistem SPP — pilih satker dan masukkan NIP Anda</div>

    <?php if($error): ?>
    <div class="err-box">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif ?>

    <form method="POST">
      <!-- Satker -->
      <div class="fg">
        <label>Satuan Kerja</label>
        <div class="iw">
          <span class="ic">🏢</span>
          <select name="satker_id" required>
            <option value="">-- Pilih Satuan Kerja --</option>
            <?php foreach($satker_list as $sk): ?>
            <option value="<?= $sk['id'] ?>" <?= ($_POST['satker_id']??'')==$sk['id']?'selected':'' ?>>
              (<?= $sk['kode'] ?>) <?= $sk['singkatan'] ?>
            </option>
            <?php endforeach ?>
          </select>
        </div>
      </div>

      <!-- NIP -->
      <div class="fg">
        <label>NIP</label>
        <div class="iw">
          <span class="ic">👤</span>
          <input type="text" name="nip" placeholder="Masukkan NIP Anda (18 digit)"
            value="<?= htmlspecialchars($_POST['nip']??'') ?>" required autocomplete="off" maxlength="20">
        </div>
      </div>

      <!-- Password -->
      <div class="fg">
        <label>Password</label>
        <div class="iw">
          <span class="ic">🔒</span>
          <input type="password" name="password" placeholder="Masukkan password" required>
        </div>
      </div>

      <button type="submit" class="btn-login">LOGIN</button>
    </form>


    <div class="foot">Sistem SPP Ditjen Prasarana Strategis &copy; <?= date('Y') ?></div>
  </div>
</div>

</body>
</html>
