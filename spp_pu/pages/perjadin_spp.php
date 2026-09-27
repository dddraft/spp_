<?php
// ============================================================
// pages/buat_surat_gaji.php
// Taruh di: C:\xampp\htdocs\spp_pu\pages\buat_surat_gaji.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db        = getDB();
$user      = getCurrentUser();
$satker_id = $_SESSION['user']['satker_id'] ?? $_SESSION['satker_id'] ?? 1;
$user_id   = $_SESSION['user_id'] ?? 1;
$user_nama = $_SESSION['user']['nama'] ?? $_SESSION['nama'] ?? 'Pengelola Keuangan';

// ── Ambil data satker ────────────────────────────────────
$stmtS = $db->prepare("SELECT * FROM satker WHERE id = ?");
$stmtS->execute([$satker_id]);
$satker = $stmtS->fetch();

// ── Ambil DIPA aktif ─────────────────────────────────────
$stmtD = $db->prepare("SELECT * FROM dipa WHERE satker_id = ? AND tahun = YEAR(NOW()) ORDER BY id DESC LIMIT 1");
$stmtD->execute([$satker_id]);
$dipa = $stmtD->fetch();

// ── Ambil daftar program ─────────────────────────────────
$stmtP = $db->prepare("SELECT * FROM program WHERE satker_id = ? AND is_aktif = 1 ORDER BY kode_program");
$stmtP->execute([$satker_id]);
$programs = $stmtP->fetchAll();

// ── Nomor surat otomatis ─────────────────────────────────
$jenis_id_perjadin = $db->query("SELECT id FROM jenis_surat WHERE kode = 'SPP-PERJADIN' LIMIT 1")->fetchColumn();
if (!$jenis_id_perjadin) {
    $db->exec("INSERT IGNORE INTO jenis_surat (kode,nama,prefix_nomor,kategori) VALUES ('SPP-PERJADIN','SPP Perjalanan Dinas','LS','perjadin')");
    $jenis_id_perjadin = $db->lastInsertId();
}
$stmtN = $db->prepare("SELECT COALESCE(MAX(urutan),0)+1 FROM nomor_surat WHERE jenis_id=? AND satker_id=? AND tahun=YEAR(NOW())");
$stmtN->execute([$jenis_id_perjadin, $satker_id]);
$urutan_next = (int)$stmtN->fetchColumn();

$bln_romawi = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
$bln_names  = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$bln_idx    = (int)date('n') - 1;
$nomor_auto = sprintf('%03d-VII/LS/PERJADIN/%s/%d', $urutan_next, $bln_romawi[$bln_idx], date('Y'));
$today      = date('Y-m-d');
$today_idn  = date('d') . ' ' . $bln_names[$bln_idx] . ' ' . date('Y');

// ── AJAX ─────────────────────────────────────────────────
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $ajax = $_GET['ajax'];

    if ($ajax === 'get_kegiatan') {
        $s = $db->prepare("SELECT id, kode_kegiatan, kode_output, nama_output, pagu_output
                           FROM kegiatan WHERE program_id=? AND satker_id=? AND is_aktif=1
                           ORDER BY kode_output");
        $s->execute([(int)$_GET['program_id'], $satker_id]);
        echo json_encode($s->fetchAll()); exit;
    }

    if ($ajax === 'get_akun') {
        $s = $db->prepare("SELECT id, kode_akun_lengkap, kode_akun, nama_akun,
                                  subkomponen, nama_subkomponen, pagu
                           FROM kegiatan_akun WHERE kegiatan_id=? AND is_aktif=1
                           ORDER BY subkomponen, kode_akun");
        $s->execute([(int)$_GET['kegiatan_id']]);
        echo json_encode($s->fetchAll()); exit;
    }

    if ($ajax === 'get_tabel2') {
        $s = $db->prepare("
            SELECT ka.id, ka.kode_akun_lengkap, ka.kode_akun, ka.nama_akun,
                   ka.subkomponen, ka.pagu,
                   COALESCE((
                       SELECT SUM(spa.spp_ini) FROM surat_pp_akun spa
                       JOIN surat_pp sp2 ON spa.surat_id = sp2.id
                       WHERE spa.kode_akun_lengkap = ka.kode_akun_lengkap
                       AND sp2.satker_id = ? AND sp2.status NOT IN ('batal','draft')
                   ), 0) AS realisasi_lalu
            FROM kegiatan_akun ka
            JOIN kegiatan k ON ka.kegiatan_id = k.id
            WHERE k.kode_output = ? AND k.satker_id = ? AND ka.is_aktif = 1
            ORDER BY ka.subkomponen, ka.kode_akun
        ");
        $s->execute([$satker_id, $_GET['kode_output'], $satker_id]);
        echo json_encode($s->fetchAll()); exit;
    }
    exit;
}

// ── POST: Simpan ─────────────────────────────────────────
$save_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'simpan') {
    try {
        $db->beginTransaction();
        $jumlah    = (int)str_replace(['.','Rp',' '], '', $_POST['jumlah_uang'] ?? '0');
        $nilai_spk = (int)str_replace(['.','Rp',' '], '', $_POST['nilai_spk']   ?? '0');

        $ins = $db->prepare("
            INSERT INTO surat_pp
            (nomor_surat,jenis_id,satker_id,tahun,tanggal_surat,
             kode_program,kode_kegiatan,kode_output,sub_komponen,kode_akun,
             uraian,jumlah_uang,
             jenis_belanja_kode,jenis_belanja_nama,
             nama_penerima,alamat_penerima,bank_penerima,no_rekening,npwp,
             no_sk_spk,tanggal_sk_spk,nilai_spk,penjelasan,
             doc_pendukung,doc_surat_bukti,doc_tanda_setoran,
             bulan_gaji,ke_gaji,status,dibuat_oleh)
            VALUES (?,?,?,YEAR(NOW()),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $ins->execute([
            $_POST['nomor_surat'],      $jenis_id_perjadin,          $satker_id,
            $_POST['tanggal_surat'],    $_POST['kode_program'],   $_POST['kode_kegiatan'],
            $_POST['kode_output'],      $_POST['sub_komponen']??'', $_POST['kode_akun']??'',
            $_POST['uraian'],           $jumlah,
            $_POST['jenis_belanja_kode'], $_POST['jenis_belanja_nama'],
            $_POST['nama_penerima'],    $_POST['alamat_penerima']??'',
            $_POST['bank_penerima'],    $_POST['no_rekening'],    $_POST['npwp']??'',
            $_POST['no_sk_spk'],        $_POST['tanggal_sk_spk']?:null,
            $nilai_spk,                 $_POST['penjelasan']??'-',
            (int)($_POST['doc_pendukung']??0), (int)($_POST['doc_surat_bukti']??0),
            (int)($_POST['doc_tanda_setoran']??0),
            $_POST['bulan_perjadin'],       (int)($_POST['ke_gaji']??1),
            'dikirim',                  $user_id
        ]);
        $surat_id = $db->lastInsertId();

        $db->prepare("INSERT INTO nomor_surat (jenis_id,satker_id,tahun,urutan) VALUES (?,?,YEAR(NOW()),?)")
           ->execute([$jenis_id_perjadin, $satker_id, $urutan_next]);

        foreach (json_decode($_POST['tabel1_json']??'[]', true) as $r) {
            $sppI = (int)($r['spp_ini']??0); $pagu=(int)($r['pagu']??0);
            $db->prepare("INSERT INTO surat_pp_akun (surat_id,tabel_type,kegiatan_akun_id,kode_akun_lengkap,pagu_dipa,spm_lalu,spp_ini,jumlah_sd_spp,sisa_dana) VALUES (?,?,?,?,?,?,?,?,?)")
               ->execute([$surat_id,'I',(int)$r['id'],$r['kode_akun_lengkap'],$pagu,0,$sppI,$sppI,$pagu-$sppI]);
        }
        foreach (json_decode($_POST['tabel2_json']??'[]', true) as $r) {
            $sppI=(int)($r['spp_ini']??0); $spmL=(int)($r['realisasi_lalu']??0); $pagu=(int)($r['pagu']??0);
            $sd=$spmL+$sppI;
            $db->prepare("INSERT INTO surat_pp_akun (surat_id,tabel_type,kegiatan_akun_id,kode_akun_lengkap,pagu_dipa,spm_lalu,spp_ini,jumlah_sd_spp,sisa_dana) VALUES (?,?,?,?,?,?,?,?,?)")
               ->execute([$surat_id,'II',(int)($r['id']??0),$r['kode_akun_lengkap'],$pagu,$spmL,$sppI,$sd,$pagu-$sd]);
        }

        $db->commit();
        header('Location: detail_surat.php?id=' . $surat_id . '&success=1');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $save_error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Buat SPP Perjadin — SAKURA PUPR</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;font-size:12px;background:#e0e5ec;color:#000}

/* TOOLBAR */
#toolbar{position:sticky;top:0;z-index:200;background:#1a3c6e;color:#fff;
  display:flex;justify-content:space-between;align-items:center;
  padding:8px 20px;box-shadow:0 2px 8px rgba(0,0,0,.4)}
#toolbar .ttl{font-size:13px;font-weight:bold}
#toolbar .ttl small{font-weight:normal;font-size:11px;opacity:.75;margin-left:8px}
.tbtn{border:none;border-radius:4px;padding:6px 16px;font-size:12px;font-weight:bold;cursor:pointer}
.tbtn:hover{opacity:.82}
.tbtn-save{background:#27ae60;color:#fff}
.tbtn-print{background:#2980b9;color:#fff}
.tbtn-back{background:#7f8c8d;color:#fff}

/* WRAPPER */
#wrap{max-width:870px;margin:18px auto 50px;background:#fff;box-shadow:0 2px 14px rgba(0,0,0,.2)}
.surat{padding:22px 28px}

/* FIELDS */
.f{display:inline-block;border:none;border-bottom:1px dashed #aaa;outline:none;
   font-size:11px;font-family:Arial,sans-serif;padding:1px 4px;background:#fffff5;min-width:60px}
.f:focus{border-bottom:1.5px solid #1a3c6e;background:#eef4ff}
.f.auto{background:#f0fff0;font-style:italic}
select.f{border:1px solid #bbb;border-radius:2px;padding:2px 4px;background:#fffff5}
select.f:focus{outline:2px solid #1a3c6e}
textarea.f{border:1px dashed #aaa;resize:vertical;padding:3px 5px;width:100%}

/* HEADER SURAT */
.kode-box{font-size:10.5px;text-align:right;color:#666;margin-bottom:3px}
.kode-box b{color:#000}
.judul-wrap{display:flex;justify-content:space-between;align-items:flex-start;
  border-bottom:2.5px solid #000;padding-bottom:5px;margin-bottom:6px}
.judul{font-size:13.5px;font-weight:bold;letter-spacing:1px}
.nomor-tgl{font-size:11px;line-height:2.1;text-align:right}

/* GRID SATKER */
.g-satker{display:grid;grid-template-columns:1fr 1fr;gap:1px 16px;
  font-size:11px;padding:7px 10px;border:1px solid #ddd;background:#fafafa;margin:7px 0}
.sr{display:flex;align-items:baseline;gap:3px;padding:2px 0}
.sr-no{width:20px;flex-shrink:0;font-weight:bold}
.sr-lb{min-width:112px;flex-shrink:0;color:#444}
.sr-val{flex:1;font-weight:500}
.sr-span{grid-column:1/3}

/* ISI */
.isi{margin:6px 0;font-size:11px}
.ir{display:flex;gap:5px;padding:2px 0;align-items:flex-start}
.ir-no{width:18px;flex-shrink:0}
.ir-lb{min-width:168px;flex-shrink:0}
.ir-sep{width:12px;text-align:center;flex-shrink:0}
.ir-val{flex:1}
.terbilang{font-weight:bold;font-style:italic;color:#1a3c6e;font-size:11px;padding:1px 0 2px 204px}
.rek-sub{padding-left:204px;font-size:11px;margin-top:1px}
.npwp-line{text-align:right;font-size:11px;font-weight:bold;margin-top:4px}

/* PANEL AKUN */
#panel-akun{display:none;background:#e8f5e9;border:1px solid #43a047;
  border-radius:4px;padding:9px 12px;margin:10px 0;font-size:11px}
#panel-akun .pt{font-weight:bold;color:#2e7d32;margin-bottom:6px}
.pa-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}

/* TABEL */
.tbl-wrap{margin:12px 0}
.ts{width:100%;border-collapse:collapse;font-size:10.5px}
.ts th,.ts td{border:1px solid #000;padding:3px 5px;vertical-align:middle;text-align:center}
.ts thead th{background:#efefef;font-size:10px;font-weight:bold}
.tal{text-align:left}.tar{text-align:right}.bold{font-weight:bold}
.rj{font-weight:bold;background:#f8f8f8}
.rh{background:#fffff5}
.rd{background:#f0f0f0;font-weight:bold}
.rdel{cursor:pointer;color:#c0392b;font-size:10px;padding:1px 4px}

/* LAMPIRAN */
.lamp{display:flex;gap:20px;align-items:center;font-size:11px;
  border-top:1.5px solid #000;border-bottom:1.5px solid #000;padding:5px 0;margin:10px 0}
.lamp input[type=number]{width:38px;border:1px solid #999;padding:2px 3px;
  text-align:center;font-size:11px}

/* TTD */
.ttd-wrap{display:flex;justify-content:space-between;margin-top:14px;font-size:11px}
.ttd-l{width:46%}.ttd-r{width:46%;text-align:right}
.ttd-nama{font-weight:bold;margin-top:52px;border-top:1px solid #000;
  padding-top:2px;display:inline-block}

/* ALERT */
.alert-err{background:#fdecea;border:1px solid #f5c6cb;color:#721c24;
  padding:10px 15px;margin:10px 28px;border-radius:4px;font-size:12px}

@media print{
  #toolbar,.alert-err,#panel-akun,.rdel{display:none!important}
  #wrap{box-shadow:none;margin:0}
  .surat{padding:10px 15px}
  .f,textarea.f{border:none!important;background:transparent!important}
  select.f{border:none;background:transparent;-webkit-appearance:none}
}
</style>
</head>
<body>

<div id="toolbar">
  <div class="ttl">📄 Buat SPP Perjadin <small><?= htmlspecialchars($satker['singkatan'] ?? '') ?></small></div>
  <div style="display:flex;gap:8px">
    <button class="tbtn tbtn-back" onclick="history.back()">← Kembali</button>
    <button class="tbtn tbtn-print" onclick="window.print()">🖨 Cetak</button>
    <button class="tbtn tbtn-save" onclick="submitSPP()">💾 Simpan & Kirim</button>
  </div>
</div>

<?php if ($save_error): ?>
<div class="alert-err">⚠️ <?= htmlspecialchars($save_error) ?></div>
<?php endif; ?>

<form id="frmSPP" method="POST" autocomplete="off">
<input type="hidden" name="action"      value="simpan">
<input type="hidden" name="tabel1_json" id="j1">
<input type="hidden" name="tabel2_json" id="j2">
<input type="hidden" name="kode_kegiatan" id="h_keg">
<input type="hidden" name="kode_output"   id="h_out">
<input type="hidden" name="sub_komponen"  id="h_sub">
<input type="hidden" name="kode_akun"     id="h_akun">

<div id="wrap"><div class="surat">

  <div class="kode-box">Sifat Pembayaran <b>4</b> &nbsp;&nbsp; Jenis Pembayaran <b>1</b></div>

  <div class="judul-wrap">
    <div class="judul">SURAT PERMINTAAN PEMBAYARAN</div>
    <div class="nomor-tgl">
      Tanggal :
      <input type="date" name="tanggal_surat" class="f auto" value="<?= $today ?>" style="width:135px" required>
      <br>Nomor :
      <input type="text" name="nomor_surat" class="f auto" value="<?= htmlspecialchars($nomor_auto) ?>" style="width:195px" required>
    </div>
  </div>

  <div class="g-satker">
    <div class="sr"><span class="sr-no">1.</span><span class="sr-lb">Kementerian</span><span>:</span>
      <span class="sr-val"><?= htmlspecialchars($satker['nama_kementerian'] ?? 'Kementerian Pekerjaan Umum') ?> (<?= $satker['kode_kementerian'] ?? '145' ?>)</span></div>

    <div class="sr"><span class="sr-no">7.</span><span class="sr-lb">Kegiatan</span><span>:</span>
      <span class="sr-val">
        <select id="sel_keg" class="f" style="width:195px" onchange="onKegChange()" required>
          <option value="">— Pilih Output —</option>
        </select>
      </span>
    </div>

    <div class="sr"><span class="sr-no">2.</span><span class="sr-lb">Unit Organisasi</span><span>:</span>
      <span class="sr-val"><?= htmlspecialchars($satker['nama_unit_org'] ?? '') ?> (<?= $satker['kode_unit_org'] ?? '06' ?>)</span></div>
    <div class="sr"></div>

    <div class="sr"><span class="sr-no">3.</span><span class="sr-lb">Provinsi</span><span>:</span>
      <span class="sr-val"><?= htmlspecialchars($satker['nama_provinsi'] ?? 'DKI Jakarta') ?> (<?= $satker['kode_provinsi'] ?? '01' ?>)</span></div>

    <div class="sr"><span class="sr-no">8.</span><span class="sr-lb">Kode Kegiatan</span><span>:</span>
      <span class="sr-val" id="disp_keg">—</span></div>

    <div class="sr"><span class="sr-no">4.</span><span class="sr-lb">Satker</span><span>:</span>
      <span class="sr-val"><?= htmlspecialchars($satker['nama'] ?? '') ?> (<?= $satker['kode'] ?? '' ?>)</span></div>

    <div class="sr"><span class="sr-no">9.</span><span class="sr-lb">Kode Program</span><span>:</span>
      <span class="sr-val">
        <select name="kode_program" id="sel_prog" class="f" style="width:165px" onchange="onProgChange()" required>
          <option value="">— Pilih Program —</option>
          <?php foreach ($programs as $pr): ?>
          <option value="<?= htmlspecialchars($pr['kode_program']) ?>" data-id="<?= $pr['id'] ?>">
            <?= htmlspecialchars($pr['kode_program']) ?></option>
          <?php endforeach; ?>
        </select>
      </span>
    </div>

    <div class="sr"><span class="sr-no">5.</span><span class="sr-lb">Tempat</span><span>:</span>
      <span class="sr-val"><?= htmlspecialchars($satker['tempat'] ?? 'Jakarta Selatan') ?></span></div>

    <div class="sr"><span class="sr-no">10.</span><span class="sr-lb">Kewenangan</span><span>:</span>
      <span class="sr-val"><?= htmlspecialchars($satker['kewenangan'] ?? 'KP') ?></span></div>

    <div class="sr sr-span"><span class="sr-no">6.</span><span class="sr-lb">Alamat</span><span>:</span>
      <span class="sr-val"><?= htmlspecialchars($satker['alamat'] ?? '') ?></span></div>
  </div>

  <div style="margin:7px 0;font-size:11px;line-height:1.9">
    <div>Kepada</div>
    <div>Yth. Pejabat Penanda Tangan Surat Perintah Membayar</div>
    <div>Satuan Kerja <?= htmlspecialchars($satker['nama'] ?? '') ?></div>
    <div>di <?= htmlspecialchars($satker['tempat'] ?? 'Jakarta') ?></div>
  </div>

  <div style="font-size:11px;margin:7px 0;line-height:1.8;text-align:justify">
    Berdasarkan Surat Pengesahan Daftar Isian Pelaksanaan Anggaran Petikan Tahun Anggaran
    <b><?= date('Y') ?></b> Nomor :
    <b><?= htmlspecialchars($satker['nomor_dipa'] ?? $dipa['nomor_dipa'] ?? '') ?></b>
    Tanggal
    <select name="tgl_dipa" class="f" style="width:32px">
      <?php for($d=1;$d<=31;$d++){$s=($d==(int)date('j',strtotime($satker['tanggal_dipa']??'')))?'selected':'';echo"<option $s>$d</option>";}?>
    </select>
    <select name="bln_dipa" class="f" style="width:85px">
      <?php foreach($bln_names as $bi=>$bn){$s=(($bi+1)==(int)date('n',strtotime($satker['tanggal_dipa']??'')))?'selected':'';echo"<option value='".($bi+1)."' $s>$bn</option>";}?>
    </select>
    <select name="thn_dipa" class="f" style="width:55px">
      <?php for($y=2024;$y<=2030;$y++){$s=($y==(int)date('Y',strtotime($satker['tanggal_dipa']??'')))?'selected':'';echo"<option $s>$y</option>";}?>
    </select>,
    bersama ini kami ajukan permintaan pembayaran sebagai berikut :
  </div>

  <div class="isi">
    <div class="ir">
      <span class="ir-no">1.</span><span class="ir-lb">Jumlah pembayaran yang dimintakan</span>
      <span class="ir-sep">:</span>
      <span class="ir-val">Rp.&nbsp;
        <input type="text" name="jumlah_uang" id="inp_jml" class="f"
               style="width:180px;text-align:right" placeholder="0"
               oninput="fmtRp(this);updTb()" required>
      </span>
    </div>
    <div class="terbilang" id="tb_text">: &nbsp;</div>

    <div class="ir" style="margin-top:4px">
      <span class="ir-no">2.</span><span class="ir-lb">Untuk keperluan</span>
      <span class="ir-sep">:</span>
      <span class="ir-val">
        <textarea name="uraian" id="inp_uraian" class="f" rows="3"
                  placeholder="Pembayaran Perjalanan Dinas ke-... Bulan ..." required></textarea>
        <div style="display:flex;gap:8px;align-items:center;font-size:11px;margin-top:3px;color:#555">
          Ke-<input type="number" name="ke_perjadin" id="inp_ke" class="f" min="1" max="12" value="1"
                    style="width:42px" oninput="autoUraian()">
          Bulan:
          <select name="bulan_perjadin" id="inp_bln" class="f" style="width:92px" onchange="autoUraian()">
            <?php foreach($bln_names as $bi=>$bn)echo"<option".($bi+1==(int)date('n')?' selected':'').">$bn</option>";?>
          </select>
        </div>
      </span>
    </div>

    <div class="ir">
      <span class="ir-no">3.</span><span class="ir-lb">Jenis belanja</span>
      <span class="ir-sep">:</span>
      <span class="ir-val">
        <select name="jenis_belanja_kode" id="inp_jkode" class="f" style="width:210px" onchange="updJb()">
          <option value="52" data-nama="Belanja Barang">52 / Belanja Barang</option>
          <option value="51" data-nama="Belanja Pegawai">51 / Belanja Pegawai</option>
          <option value="53" data-nama="Belanja Modal">53 / Belanja Modal</option>
        </select>
        <input type="hidden" name="jenis_belanja_nama" id="inp_jnama" value="Belanja Barang">
      </span>
    </div>

    <div class="ir">
      <span class="ir-no">4.</span><span class="ir-lb">Atas nama</span>
      <span class="ir-sep">:</span>
      <span class="ir-val">
        <input type="text" name="nama_penerima" id="inp_nama" class="f" style="width:280px"
               placeholder="Nama penerima" required>
      </span>
    </div>

    <div class="ir">
      <span class="ir-no">5.</span><span class="ir-lb">Alamat</span>
      <span class="ir-sep">:</span>
      <span class="ir-val">
        <input type="text" name="alamat_penerima" class="f" style="width:280px" placeholder="Alamat penerima">
      </span>
    </div>

    <div class="ir">
      <span class="ir-no">6.</span><span class="ir-lb">Mempunyai rekening</span>
      <span class="ir-sep">:</span>
      <span class="ir-val">
        <input type="text" name="bank_penerima" class="f" style="width:130px" placeholder="Nama Bank">
      </span>
    </div>
    <div class="rek-sub">Nomor rekening &nbsp;:&nbsp;
      <input type="text" name="no_rekening" class="f" style="width:200px" placeholder="Nomor rekening">
    </div>

    <div class="ir">
      <span class="ir-no">7.</span><span class="ir-lb">Nomor dan tanggal SK</span>
      <span class="ir-sep">:</span>
      <span class="ir-val">
        <input type="text" name="no_sk_spk" class="f" style="width:195px" placeholder="No. SK / SPK">
        , tanggal <input type="date" name="tanggal_sk_spk" class="f" style="width:135px">
      </span>
    </div>

    <div class="ir">
      <span class="ir-no">8.</span><span class="ir-lb">Nilai SPK/Kontrak</span>
      <span class="ir-sep">:</span>
      <span class="ir-val">Rp.&nbsp;
        <input type="text" name="nilai_spk" class="f" style="width:180px;text-align:right"
               placeholder="0" oninput="fmtRp(this)">
      </span>
    </div>

    <div class="ir">
      <span class="ir-no">9.</span><span class="ir-lb">Dengan penjelasan</span>
      <span class="ir-sep">:</span>
      <span class="ir-val">
        <input type="text" name="penjelasan" class="f" style="width:300px" value="-">
      </span>
    </div>

    <div class="npwp-line">NPWP &nbsp;:&nbsp;
      <input type="text" name="npwp" class="f" style="width:165px" placeholder="00.000.000.0-000.000">
    </div>
  </div>

  <!-- PANEL AKUN -->
  <div id="panel-akun">
    <div class="pt">📋 Pilih MAK Bersangkutan untuk Tabel I :</div>
    <div class="pa-row">
      <select id="sel_akun" class="f" style="width:360px;font-size:11px">
        <option value="">— Pilih Kode Akun —</option>
      </select>
      <span>SPP ini (Rp):</span>
      <input type="text" id="inp_spp_ini" class="f" style="width:135px;text-align:right"
             placeholder="0" oninput="fmtRp(this)">
      <button type="button" class="tbtn tbtn-save" style="padding:4px 12px;font-size:11px"
              onclick="addAkun()">＋ Tambah</button>
    </div>
  </div>

  <!-- TABEL -->
  <div class="tbl-wrap">
    <table class="ts" id="tbl">
      <thead>
        <tr>
          <th rowspan="3" style="width:36px">NO.<br>Urut</th>
          <th rowspan="3" style="text-align:left;width:205px">
            I.&nbsp; KEGIATAN/OUTPUT/MAK BERSANGKUTAN<br>
            <span style="font-weight:normal">II. SEMUA KODE KEGIATAN DALAM DIPA</span>
          </th>
          <th rowspan="3" style="width:105px">PAGU<br>DALAM DIPA<br><small>(Rp.)</small></th>
          <th rowspan="3" style="width:105px">SPP/SPM SAMPAI<br>DGN YANG LALU<br><small>(Rp.)</small></th>
          <th rowspan="3" style="width:98px">SPP INI<br><small>(Rp.)</small></th>
          <th rowspan="3" style="width:105px">JUMLAH S/D<br>SPP INI<br><small>(Rp.)</small></th>
          <th rowspan="3" style="width:98px">SISA DANA<br><small>(Rp.)</small></th>
        </tr>
        <tr></tr><tr></tr>
        <tr>
          <td style="font-size:10px">1</td><td style="font-size:10px">2</td>
          <td style="font-size:10px">3</td><td style="font-size:10px">4</td>
          <td style="font-size:10px">5</td><td style="font-size:10px">6=(4+5)</td>
          <td style="font-size:10px">7=(3-6)</td>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr><td colspan="7" style="text-align:center;color:#999;padding:12px;font-size:11px">
          Pilih Program → Kegiatan untuk memuat data tabel
        </td></tr>
      </tbody>
    </table>
  </div>

  <div class="lamp">
    <b>LAMPIRAN</b>
    <span><input type="number" name="doc_pendukung" value="0" min="0"> Dokumen Pendukung ......... Berkas</span>
    <span><input type="number" name="doc_surat_bukti" value="0" min="0"> Surat Bukti Pengeluaran ......... Lembar</span>
    <span><input type="number" name="doc_tanda_setoran" value="0" min="0"> Surat Tanda Setoran ......... Lembar</span>
  </div>

  <div class="ttd-wrap">
    <div class="ttd-l">
      <div>Diterima oleh Penguji SPP/Penerbit SPM</div>
      <div>Satker SSPPS</div>
      <div>Pada tanggal ………………………………</div><br>
      <span class="ttd-nama"><?= htmlspecialchars($satker['nama_penguji_spp'] ?? 'Mohammad Rival') ?></span>
    </div>
    <div class="ttd-r">
      <div>Jakarta, <?= $today_idn ?></div><br>
      <div>Pejabat Pembuat Komitmen</div>
      <div><?= htmlspecialchars($satker['nama'] ?? '') ?></div><br>
      <span class="ttd-nama"><?= htmlspecialchars($satker['nama_ppk'] ?? 'Citra Mayasari') ?></span>
    </div>
  </div>

</div></div><!-- /surat /wrap -->
</form>

<script>
let akunList=[],tabel1=[],tabel2=[],kegId=null,kodeOut=null,kodeKeg=null;

// FORMAT
function fmtRp(el){let v=el.value.replace(/[^0-9]/g,'');el.value=v?parseInt(v).toLocaleString('id-ID'):'';}
function pRp(s){return parseInt((s||'0').replace(/[^0-9]/g,''))||0;}

// TERBILANG
const _a=['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
function _tb(n){n=Math.abs(parseInt(n)||0);
  if(n<12)return _a[n];if(n<20)return _a[n-10]+' belas';
  if(n<100)return _a[Math.floor(n/10)]+' puluh '+_tb(n%10);
  if(n<200)return 'seratus '+_tb(n-100);if(n<1000)return _a[Math.floor(n/100)]+' ratus '+_tb(n%100);
  if(n<2000)return 'seribu '+_tb(n-1000);if(n<1e6)return _tb(Math.floor(n/1000))+' ribu '+_tb(n%1000);
  if(n<1e9)return _tb(Math.floor(n/1e6))+' juta '+_tb(n%1e6);
  return _tb(Math.floor(n/1e9))+' miliar '+_tb(n%1e9);}
function updTb(){const v=pRp(document.getElementById('inp_jml').value);
  const t=v?_tb(v).trim().replace(/\s+/g,' '):'';
  document.getElementById('tb_text').textContent=': '+(t?t[0].toUpperCase()+t.slice(1)+' rupiah':'');}

function updJb(){const s=document.getElementById('inp_jkode');
  document.getElementById('inp_jnama').value=s.options[s.selectedIndex].dataset.nama;}

function autoUraian(){
  const ke=document.getElementById('inp_ke').value,
        bl=document.getElementById('inp_bln').value,
        th=new Date().getFullYear();
  const selK=document.getElementById('sel_keg');
  const namaO=selK.selectedIndex>0?selK.options[selK.selectedIndex].text.split(' – ')[1]||'':'';
  const satker=<?= json_encode($satker['nama'] ?? '') ?>;
  const el=document.getElementById('inp_uraian');
  if(ke&&bl){el.value=`Pembayaran Perjalanan Dinas ke-${ke} Bulan ${bl} ${th} ${namaO} di ${satker} TA. ${th} an. `;
    el.focus();el.setSelectionRange(el.value.length,el.value.length);}
}

function onProgChange(){
  const sel=document.getElementById('sel_prog');
  const progId=sel.options[sel.selectedIndex]?.dataset?.id;
  const selK=document.getElementById('sel_keg');
  selK.innerHTML='<option value="">— Memuat... —</option>';
  document.getElementById('panel-akun').style.display='none';
  tabel1=[];tabel2=[];renderTbl();
  if(!progId)return;
  fetch(`?ajax=get_kegiatan&program_id=${progId}`)
    .then(r=>r.json()).then(data=>{
      selK.innerHTML='<option value="">— Pilih Output Kegiatan —</option>';
      data.forEach(k=>{const o=document.createElement('option');
        o.value=k.id;o.text=k.kode_kegiatan+'.'+k.kode_output+' – '+k.nama_output;
        o.dataset.kode=k.kode_kegiatan;o.dataset.output=k.kode_output;selK.appendChild(o);});
    });
}

function onKegChange(){
  const sel=document.getElementById('sel_keg');
  const opt=sel.options[sel.selectedIndex];
  kegId=opt.value;kodeOut=opt.dataset?.output;kodeKeg=opt.dataset?.kode;
  document.getElementById('h_keg').value=kodeKeg||'';
  document.getElementById('h_out').value=kodeOut||'';
  document.getElementById('disp_keg').textContent=kodeKeg||'—';
  document.getElementById('panel-akun').style.display='none';
  tabel1=[];tabel2=[];renderTbl();
  if(!kegId)return;
  fetch(`?ajax=get_akun&kegiatan_id=${kegId}`)
    .then(r=>r.json()).then(data=>{
      akunList=data;
      const s=document.getElementById('sel_akun');
      s.innerHTML='<option value="">— Pilih Kode Akun —</option>';
      data.forEach(a=>{const o=document.createElement('option');o.value=a.id;
        o.text=a.kode_akun_lengkap+'  (Pagu: '+parseInt(a.pagu).toLocaleString('id-ID')+')';
        o.dataset.json=JSON.stringify(a);s.appendChild(o);});
      document.getElementById('panel-akun').style.display='block';
    });
  fetch(`?ajax=get_tabel2&kode_output=${kodeOut}`)
    .then(r=>r.json()).then(data=>{tabel2=data;renderTbl();});
}

function addAkun(){
  const sel=document.getElementById('sel_akun');
  const opt=sel.options[sel.selectedIndex];
  if(!opt.value){alert('Pilih kode akun!');return;}
  const akun=JSON.parse(opt.dataset.json);
  const sppI=pRp(document.getElementById('inp_spp_ini').value);
  if(tabel1.find(r=>r.id==akun.id)){alert('Akun ini sudah ditambahkan!');return;}
  akun.spp_ini=sppI;tabel1.push(akun);renderTbl();
  const tot=tabel1.reduce((s,r)=>s+(r.spp_ini||0),0);
  document.getElementById('inp_jml').value=tot.toLocaleString('id-ID');updTb();
  document.getElementById('inp_spp_ini').value='';
  document.getElementById('h_akun').value=akun.kode_akun;
  document.getElementById('h_sub').value=akun.subkomponen||'';
}

function delAkun(i){tabel1.splice(i,1);renderTbl();
  const tot=tabel1.reduce((s,r)=>s+(r.spp_ini||0),0);
  document.getElementById('inp_jml').value=tot?tot.toLocaleString('id-ID'):'';updTb();}

function fn(n){return parseInt(n||0).toLocaleString('id-ID');}
function ar(tb,html,cls=''){const tr=document.createElement('tr');if(cls)tr.className=cls;tr.innerHTML=html;tb.appendChild(tr);}

function renderTbl(){
  const tb=document.getElementById('tbody');tb.innerHTML='';
  if(!tabel1.length&&!tabel2.length){
    tb.innerHTML='<tr><td colspan="7" style="text-align:center;color:#999;padding:12px;font-size:11px">Pilih Program → Kegiatan untuk memuat data tabel</td></tr>';return;}

  if(tabel1.length){
    ar(tb,`<td colspan="7" class="tal bold rd">I.&nbsp; KEGIATAN/OUTPUT/MAK BERSANGKUTAN</td>`);
    ar(tb,`<td></td><td class="tal" colspan="6" style="font-size:10px;color:#555;font-style:italic">${kodeKeg||''}.${kodeOut||''}</td>`);
    let sP=0,sL=0,sI=0,sS=0,sR=0;
    tabel1.forEach((r,i)=>{
      const l=r.realisasi_lalu||0,ii=r.spp_ini||0,sd=l+ii,sisa=r.pagu-sd;
      sP+=r.pagu;sL+=l;sI+=ii;sS+=sd;sR+=sisa;
      ar(tb,`<td>${i+1}</td>
        <td class="tal">${r.kode_akun_lengkap}<br><b>${r.kode_akun}</b>
          <span class="rdel" onclick="delAkun(${i})">✕</span></td>
        <td class="tar">${fn(r.pagu)}</td>
        <td class="tar">${l?fn(l):''}</td>
        <td class="tar">${fn(ii)}</td>
        <td class="tar">${fn(sd)}</td>
        <td class="tar">${fn(sisa)}</td>`,'rh');
    });
    ar(tb,`<td colspan="2" class="tal bold">JUMLAH I</td>
      <td class="tar bold">${fn(sP)}</td><td class="tar bold">${sL?fn(sL):'-'}</td>
      <td class="tar bold">${fn(sI)}</td><td class="tar bold">${fn(sS)}</td>
      <td class="tar bold">${fn(sR)}</td>`,'rj');
  }

  if(tabel2.length){
    ar(tb,`<td colspan="7" class="tal bold rd">II.&nbsp; SEMUA KEGIATAN</td>`);
    let g=0,last=null,sP=0,sL=0,sI=0,sS=0,sR=0;
    tabel2.forEach(r=>{
      if(r.subkomponen!==last){g++;last=r.subkomponen;}
      const l=r.realisasi_lalu||0,t1m=tabel1.find(t=>t.kode_akun_lengkap===r.kode_akun_lengkap);
      const ii=t1m?(t1m.spp_ini||0):0,sd=l+ii,sisa=r.pagu-sd;
      sP+=r.pagu;sL+=l;sI+=ii;sS+=sd;sR+=sisa;
      ar(tb,`<td>${g}</td><td class="tal">${r.kode_akun_lengkap}</td>
        <td class="tar">${fn(r.pagu)}</td>
        <td class="tar">${l?fn(l):''}</td>
        <td class="tar">${ii?fn(ii):''}</td>
        <td class="tar">${sd?fn(sd):''}</td>
        <td class="tar">${fn(sisa)}</td>`);
    });
    ar(tb,`<td colspan="2" class="tal bold">JUMLAH II</td>
      <td class="tar bold">${fn(sP)}</td><td class="tar bold">${sL?fn(sL):'-'}</td>
      <td class="tar bold">${sI?fn(sI):''}</td><td class="tar bold">${sS?fn(sS):''}</td>
      <td class="tar bold">${fn(sR)}</td>`,'rj');
    ar(tb,`<td colspan="7" class="tal" style="font-size:10px;padding:2px 5px">UANG PERSEDIAAN</td>`);
  }
}

function submitSPP(){
  if(!tabel1.length){alert('Tambahkan minimal 1 MAK di Tabel I!');return;}
  document.getElementById('j1').value=JSON.stringify(tabel1);
  document.getElementById('j2').value=JSON.stringify(tabel2.map(r=>{
    const t1m=tabel1.find(t=>t.kode_akun_lengkap===r.kode_akun_lengkap);
    return{...r,spp_ini:t1m?t1m.spp_ini:0};
  }));
  document.getElementById('frmSPP').submit();
}
</script>
</body>
</html>
