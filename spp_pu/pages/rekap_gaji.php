<?php
// pages/rekap_gaji.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db       = getDB();
$surat_id = (int)($_GET['id'] ?? 0);
if (!$surat_id) { header('Location: daftar_surat.php'); exit; }

// Ambil data surat
$stmt = $db->prepare("
    SELECT sp.*,
           s.nama AS satker_nama, s.kode AS satker_kode,
           s.nama_kementerian, s.nama_unit_org,
           s.nomor_dipa, s.tanggal_dipa,
           s.nama_ppk, s.jabatan_ppk, s.tempat,
           s.nama_penguji_spp
    FROM surat_pp sp
    JOIN satker s ON sp.satker_id = s.id
    WHERE sp.id = ?
");
$stmt->execute([$surat_id]);
$spp = $stmt->fetch();
if (!$spp) die('Data tidak ditemukan.');

// Ambil daftar gaji dari tabel daftar_gaji
$stmtG = $db->prepare("
    SELECT * FROM daftar_gaji
    WHERE surat_id = ?
    ORDER BY no_urut, id
");
$stmtG->execute([$surat_id]);
$daftar = $stmtG->fetchAll();

// Jika daftar_gaji kosong, tampilkan form input
$show_form = empty($daftar) || isset($_GET['edit']);

// Handle POST: simpan daftar gaji
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'simpan_gaji') {
    // Hapus data lama dulu
    $db->prepare("DELETE FROM daftar_gaji WHERE surat_id = ?")->execute([$surat_id]);

    $nama_arr    = $_POST['nama']      ?? [];
    $posisi_arr  = $_POST['posisi']    ?? [];
    $kode_arr    = $_POST['kode_sub']  ?? [];
    $npwp_arr    = $_POST['npwp']      ?? [];
    $nama_rek    = $_POST['nama_rek']  ?? [];
    $bank_arr    = $_POST['bank']      ?? [];
    $norek_arr   = $_POST['no_rek']    ?? [];
    $jml_bln_arr = $_POST['jml_bln']   ?? [];
    $gaji_arr    = $_POST['gaji']      ?? [];
    $pph_arr     = $_POST['pph']       ?? [];

    $ins = $db->prepare("
        INSERT INTO daftar_gaji
        (surat_id, no_urut, nama, posisi, kode_sub_komponen, npwp,
         nama_dalam_rekening, bank, no_rekening, jml_bulan_tagih,
         gaji_pokok, pph, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())
    ");
    foreach ($nama_arr as $i => $nm) {
        if (!trim($nm)) continue;
        $gaji = (int)str_replace(['.', ','], '', $gaji_arr[$i] ?? '0');
        $pph  = (int)str_replace(['.', ','], '', $pph_arr[$i]  ?? '0');
        $ins->execute([
            $surat_id, $i+1, $nm,
            $posisi_arr[$i]  ?? '',
            $kode_arr[$i]    ?? '',
            $npwp_arr[$i]    ?? '',
            $nama_rek[$i]    ?? $nm,
            $bank_arr[$i]    ?? '',
            $norek_arr[$i]   ?? '',
            (int)($jml_bln_arr[$i] ?? 1),
            $gaji, $pph
        ]);
    }
    header('Location: rekap_gaji.php?id=' . $surat_id);
    exit;
}

// Re-fetch setelah simpan
$stmtG->execute([$surat_id]);
$daftar = $stmtG->fetchAll();
$show_form = empty($daftar) || isset($_GET['edit']);

$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
function tglIdn($tgl){ global $bln_names; if(!$tgl)return'-'; [$y,$m,$d]=explode('-',$tgl); return(int)$d.' '.$bln_names[(int)$m].' '.$y; }
function rupiah($n){ return number_format((float)$n,0,',','.'); }

$tgl_surat   = tglIdn($spp['tanggal_surat']);
$bulan_gaji  = $spp['bulan_gaji'] ?? $bln_names[(int)date('n')];
$tahun_gaji  = date('Y', strtotime($spp['tanggal_surat']));
$nama_ppk    = $spp['nama_ppk'] ?? 'Citra Mayasari';
$kode_anggaran = implode('.', array_filter([
    $spp['kode_kegiatan'], $spp['kode_output'],
    $spp['sub_komponen'],  $spp['kode_akun']
]));
$pagu_akun = 0; // bisa diambil dari kegiatan_akun jika perlu

// Ambil pagu dari kegiatan_akun
if ($spp['kode_akun']) {
    $pStmt = $db->prepare("SELECT pagu FROM kegiatan_akun WHERE kode_akun_lengkap LIKE ? LIMIT 1");
    $pStmt->execute(['%' . $spp['kode_akun'] . '%']);
    $pagu_akun = (float)($pStmt->fetchColumn() ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekapitulasi Gaji — <?= htmlspecialchars($spp['nomor_surat']) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;font-size:11.5px;background:#e0e5ec}
#toolbar{position:sticky;top:0;z-index:200;background:#1a3c6e;color:#fff;
  display:flex;justify-content:space-between;align-items:center;
  padding:8px 20px;box-shadow:0 2px 8px rgba(0,0,0,.4)}
.ttl{font-size:13px;font-weight:bold}
.tbtn{border:none;border-radius:4px;padding:6px 16px;font-size:12px;font-weight:bold;cursor:pointer}
.tbtn:hover{opacity:.82}
.tbtn-print{background:#2980b9;color:#fff}
.tbtn-back{background:#7f8c8d;color:#fff}
.tbtn-edit{background:#e67e22;color:#fff}
.tbtn-save{background:#27ae60;color:#fff}
#wrap{max-width:1050px;margin:18px auto 50px;background:#fff;box-shadow:0 2px 14px rgba(0,0,0,.2)}
.surat{padding:28px 35px;line-height:1.5}
.judul-box{text-align:center;margin-bottom:14px}
.judul{font-size:13px;font-weight:bold;text-transform:uppercase}
.sub-judul{font-size:12px;font-weight:bold}
.info-box{font-size:12px;text-align:center;margin-bottom:10px}
/* TABEL */
.tbl{width:100%;border-collapse:collapse;font-size:10.5px;margin:10px 0}
.tbl th,.tbl td{border:1px solid #000;padding:3px 5px;vertical-align:middle}
.tbl thead th{background:#f0f0f0;text-align:center;font-weight:bold;font-size:10px}
.tbl .tar{text-align:right}.tbl .tac{text-align:center}.tbl .tal{text-align:left}
.tbl .bold{font-weight:bold}
.tbl .total-row{font-weight:bold;background:#f8f8f8}
/* INPUT FORM */
.f{display:block;width:100%;border:1px solid #ccc;padding:2px 4px;font-size:10.5px;font-family:Arial,sans-serif}
.f:focus{outline:2px solid #1a3c6e;border-color:#1a3c6e}
/* PANEL AKUN INFO */
.akun-info{background:#e8f4e8;border:1px solid #43a047;border-radius:4px;
  padding:7px 14px;font-size:11px;margin-bottom:10px;display:flex;gap:30px;flex-wrap:wrap}
/* TTD */
.ttd-wrap{display:flex;justify-content:flex-end;margin-top:18px;font-size:12px}
.ttd-r{text-align:center;width:260px}
.ttd-nama{font-weight:bold;margin-top:52px;border-top:1px solid #000;padding-top:3px;display:inline-block;min-width:200px}
/* ADD ROW */
.add-row-btn{background:#1a3c6e;color:#fff;border:none;padding:5px 14px;
  font-size:11px;border-radius:4px;cursor:pointer;margin-top:8px}
.del-btn{background:#e74c3c;color:#fff;border:none;padding:2px 7px;
  font-size:10px;border-radius:3px;cursor:pointer}
@media print{
  #toolbar,#form-section,.add-row-btn,.del-btn,.akun-info{display:none!important}
  #wrap{box-shadow:none;margin:0;max-width:100%}
  .surat{padding:15px 20px}
  body{background:#fff}
  #rekap-section{display:block!important}
}
</style>
</head>
<body>
<div id="toolbar">
  <div class="ttl">📊 Rekapitulasi Gaji <small><?= htmlspecialchars($spp['nomor_surat']) ?></small></div>
  <div style="display:flex;gap:8px">
    <button class="tbtn tbtn-back" onclick="history.back()">← Kembali</button>
    <?php if (!$show_form && !empty($daftar)): ?>
    <button class="tbtn tbtn-edit" onclick="location.href='?id=<?= $surat_id ?>&edit=1'">✏️ Edit</button>
    <?php endif; ?>
    <button class="tbtn tbtn-print" onclick="window.print()">🖨 Cetak</button>
  </div>
</div>

<div id="wrap"><div class="surat">

  <!-- Info akun -->
  <div class="akun-info">
    <span><b>SPP:</b> <?= htmlspecialchars($spp['nomor_surat']) ?></span>
    <span><b>Kode Akun:</b> <?= htmlspecialchars($kode_anggaran) ?></span>
    <?php if ($pagu_akun): ?>
    <span><b>Pagu:</b> Rp. <?= rupiah($pagu_akun) ?></span>
    <?php endif; ?>
    <span><b>Bulan:</b> <?= htmlspecialchars($bulan_gaji) ?> <?= $tahun_gaji ?></span>
  </div>

  <!-- FORM INPUT DAFTAR GAJI -->
  <?php if ($show_form): ?>
  <div id="form-section">
    <div style="font-size:12px;font-weight:bold;margin-bottom:8px;color:#1a3c6e">
      📝 Input Daftar Gaji Tenaga Pendukung
    </div>
    <form method="POST" id="frmGaji">
      <input type="hidden" name="action" value="simpan_gaji">
      <table class="tbl" id="tbl_input">
        <thead>
          <tr>
            <th style="width:30px">No</th>
            <th style="width:140px">Nama Pegawai</th>
            <th style="width:160px">Posisi/Jabatan</th>
            <th style="width:100px">Kode Sub Komp.</th>
            <th style="width:130px">NPWP</th>
            <th style="width:130px">Nama dalam Rekening</th>
            <th style="width:80px">Bank</th>
            <th style="width:110px">No. Rekening</th>
            <th style="width:40px">Bln</th>
            <th style="width:100px">Gaji (Rp)</th>
            <th style="width:80px">PPH (Rp)</th>
            <th style="width:35px">Aksi</th>
          </tr>
        </thead>
        <tbody id="tbody_input">
          <?php
          $rows = !empty($daftar) ? $daftar : [null];
          foreach ($rows as $i => $row): ?>
          <tr id="row_<?= $i ?>">
            <td class="tac"><?= $i + 1 ?></td>
            <td><input name="nama[]" class="f" value="<?= htmlspecialchars($row['nama'] ?? '') ?>" required></td>
            <td><input name="posisi[]" class="f" value="<?= htmlspecialchars($row['posisi'] ?? '') ?>"></td>
            <td><input name="kode_sub[]" class="f" value="<?= htmlspecialchars($row['kode_sub_komponen'] ?? $kode_anggaran) ?>"></td>
            <td><input name="npwp[]" class="f" value="<?= htmlspecialchars($row['npwp'] ?? '') ?>" placeholder="00.000.000.0-000.000"></td>
            <td><input name="nama_rek[]" class="f" value="<?= htmlspecialchars($row['nama_dalam_rekening'] ?? '') ?>"></td>
            <td><input name="bank[]" class="f" value="<?= htmlspecialchars($row['bank'] ?? '') ?>"></td>
            <td><input name="no_rek[]" class="f" value="<?= htmlspecialchars($row['no_rekening'] ?? '') ?>"></td>
            <td><input name="jml_bln[]" class="f" type="number" value="<?= $row['jml_bulan_tagih'] ?? 1 ?>" min="1" max="12" style="text-align:center"></td>
            <td><input name="gaji[]" class="f" value="<?= $row ? rupiah($row['gaji_pokok']) : '' ?>" style="text-align:right" oninput="fmtRp(this)"></td>
            <td><input name="pph[]" class="f" value="<?= $row ? rupiah($row['pph']) : '0' ?>" style="text-align:right" oninput="fmtRp(this)"></td>
            <td class="tac"><button type="button" class="del-btn" onclick="delRow(this)">✕</button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <button type="button" class="add-row-btn" onclick="addRow()">＋ Tambah Pegawai</button>
      <div style="margin-top:12px;display:flex;gap:10px">
        <button type="submit" class="tbtn tbtn-save" style="padding:7px 20px">💾 Simpan Daftar Gaji</button>
        <?php if (!empty($daftar)): ?>
        <button type="button" class="tbtn tbtn-back" onclick="location.href='?id=<?= $surat_id ?>'">Batal</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- TAMPILAN REKAP -->
  <?php if (!empty($daftar)): ?>
  <div id="rekap-section">
    <div class="judul-box">
      <div class="judul">Rekapitulasi</div>
      <div class="sub-judul">Daftar Gaji Tenaga Pendukung di Lingkungan</div>
      <div class="sub-judul"><?= htmlspecialchars(strtoupper($spp['satker_nama'])) ?></div>
      <div style="margin-top:4px;font-size:11px">Pembayaran Melalui Bank BNI</div>
    </div>

    <div class="info-box">
      <strong>PEMBAYARAN GAJI BULAN : <?= strtoupper($bulan_gaji) ?> <?= $tahun_gaji ?></strong>
    </div>

    <table class="tbl">
      <thead>
        <tr>
          <th rowspan="2" style="width:28px">No.</th>
          <th rowspan="2">Nama Pegawai</th>
          <th rowspan="2">Posisi</th>
          <th rowspan="2" style="width:120px">Kode Sub Komponen</th>
          <th rowspan="2" style="width:130px">NPWP</th>
          <th rowspan="2" style="width:120px">Nama dalam Rekening</th>
          <th rowspan="2" style="width:70px">Nama Bank</th>
          <th rowspan="2" style="width:100px">No. Rekening</th>
          <th rowspan="2" style="width:38px">Jml Bln Tagih</th>
          <th rowspan="2" style="width:95px">Gaji</th>
          <th rowspan="2" style="width:95px">Total</th>
          <th rowspan="2" style="width:80px">PPH</th>
          <th rowspan="2" style="width:95px">Penerimaan Bersih</th>
        </tr>
        <tr></tr>
      </thead>
      <tbody>
        <?php
        $total_gaji  = 0;
        $total_pph   = 0;
        $total_bersih= 0;
        foreach ($daftar as $i => $row):
          $gaji   = (float)$row['gaji_pokok'];
          $pph    = (float)$row['pph'];
          $bersih = $gaji - $pph;
          $total_gaji  += $gaji;
          $total_pph   += $pph;
          $total_bersih += $bersih;
        ?>
        <tr>
          <td class="tac"><?= $i+1 ?></td>
          <td><?= htmlspecialchars($row['nama']) ?></td>
          <td style="font-size:10px"><?= htmlspecialchars($row['posisi'] ?? '') ?></td>
          <td class="tac" style="font-size:10px"><?= htmlspecialchars($row['kode_sub_komponen'] ?? $kode_anggaran) ?></td>
          <td class="tac" style="font-size:10px"><?= htmlspecialchars($row['npwp'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['nama_dalam_rekening'] ?? $row['nama']) ?></td>
          <td class="tac"><?= htmlspecialchars($row['bank'] ?? '') ?></td>
          <td class="tac"><?= htmlspecialchars($row['no_rekening'] ?? '') ?></td>
          <td class="tac"><?= $row['jml_bulan_tagih'] ?? 1 ?></td>
          <td class="tar"><?= rupiah($gaji) ?></td>
          <td class="tar"><?= rupiah($gaji) ?></td>
          <td class="tar"><?= $pph ? rupiah($pph) : '-' ?></td>
          <td class="tar"><?= rupiah($bersih) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="total-row">
          <td colspan="9" class="tar bold">TOTAL</td>
          <td class="tar bold"><?= rupiah($total_gaji) ?></td>
          <td class="tar bold"><?= rupiah($total_gaji) ?></td>
          <td class="tar bold"><?= $total_pph ? rupiah($total_pph) : '-' ?></td>
          <td class="tar bold"><?= rupiah($total_bersih) ?></td>
        </tr>
      </tfoot>
    </table>

    <!-- Info Pagu -->
    <?php if ($pagu_akun): ?>
    <div style="font-size:11px;margin-top:5px;text-align:right">
      <?= htmlspecialchars($kode_anggaran) ?> &nbsp; Pagu <?= rupiah($pagu_akun) ?>
    </div>
    <?php endif; ?>

    <!-- Sub komponen info dari PDF contoh -->
    <?php
    $keg_label = ($spp['kode_kegiatan'] ?? '') . ' ' . ($spp['uraian'] ? '' : '');
    ?>
    <div style="font-size:11px;margin-top:4px;color:#555">
      <?= htmlspecialchars($spp['kode_kegiatan'] ?? '') ?>
      <?php
      // Ambil nama kegiatan
      $nkStmt = $db->prepare("SELECT nama_output FROM kegiatan WHERE kode_kegiatan=? AND satker_id=? LIMIT 1");
      $nkStmt->execute([$spp['kode_kegiatan'], $spp['satker_id']]);
      $nk = $nkStmt->fetchColumn();
      if ($nk) echo htmlspecialchars($nk);
      ?>
    </div>

    <!-- TTD -->
    <div class="ttd-wrap">
      <div class="ttd-r">
        <div>Pejabat Pembuat Komitmen</div>
        <div><?= htmlspecialchars($spp['satker_nama'] ?? '') ?></div>
        <span class="ttd-nama"><?= htmlspecialchars($nama_ppk) ?></span>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div></div>

<script>
function fmtRp(el){let v=el.value.replace(/[^0-9]/g,'');el.value=v?parseInt(v).toLocaleString('id-ID'):'';}
let rowCount=<?= max(count($daftar),1) ?>;
const kodeAnggaran=<?= json_encode($kode_anggaran) ?>;

function addRow(){
  rowCount++;
  const tb=document.getElementById('tbody_input');
  const tr=document.createElement('tr');
  tr.id='row_'+rowCount;
  tr.innerHTML=`
    <td style="text-align:center">${tb.rows.length+1}</td>
    <td><input name="nama[]" class="f" required></td>
    <td><input name="posisi[]" class="f"></td>
    <td><input name="kode_sub[]" class="f" value="${kodeAnggaran}"></td>
    <td><input name="npwp[]" class="f" placeholder="00.000.000.0-000.000"></td>
    <td><input name="nama_rek[]" class="f"></td>
    <td><input name="bank[]" class="f"></td>
    <td><input name="no_rek[]" class="f"></td>
    <td><input name="jml_bln[]" class="f" type="number" value="1" min="1" max="12" style="text-align:center"></td>
    <td><input name="gaji[]" class="f" style="text-align:right" oninput="fmtRp(this)"></td>
    <td><input name="pph[]" class="f" value="0" style="text-align:right" oninput="fmtRp(this)"></td>
    <td style="text-align:center"><button type="button" class="del-btn" onclick="delRow(this)">✕</button></td>`;
  tb.appendChild(tr);
}
function delRow(btn){
  const tr=btn.closest('tr');
  if(document.getElementById('tbody_input').rows.length>1) tr.remove();
  else alert('Minimal 1 baris harus ada!');
}
</script>
</body>
</html>