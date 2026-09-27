<?php
// pages/nominatif_perjadin.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db       = getDB();
$surat_id = (int)($_GET['id'] ?? 0);
if (!$surat_id) { header('Location: daftar_surat.php'); exit; }

$stmt = $db->prepare("
    SELECT sp.*,
           s.nama AS satker_nama, s.kode AS satker_kode,
           s.nama_kementerian, s.nama_unit_org,
           s.nomor_dipa, s.tanggal_dipa,
           s.nama_ppk, s.tempat,
           s.nama_penguji_spp
    FROM surat_pp sp
    JOIN satker s ON sp.satker_id = s.id
    WHERE sp.id = ?
");
$stmt->execute([$surat_id]);
$spp = $stmt->fetch();
if (!$spp) die('Data tidak ditemukan.');

// Ambil daftar perjadin
$stmtP = $db->prepare("SELECT * FROM daftar_perjadin WHERE surat_id = ? ORDER BY no_urut, id");
$stmtP->execute([$surat_id]);
$daftar = $stmtP->fetchAll();
$show_form = empty($daftar) || isset($_GET['edit']);

// Handle POST simpan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'simpan_perjadin') {
    $db->prepare("DELETE FROM daftar_perjadin WHERE surat_id = ?")->execute([$surat_id]);
    $ins = $db->prepare("
        INSERT INTO daftar_perjadin
        (surat_id, no_urut, nama, nip, golongan, jabatan, tujuan,
         tgl_berangkat, tgl_pulang,
         jml_hari_uang_harian, tarif_uang_harian,
         jml_hari_transport, tarif_transport,
         jml_hari_penginapan, tarif_penginapan, total_biaya)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $nama_arr  = $_POST['nama']     ?? [];
    foreach ($nama_arr as $i => $nm) {
        if (!trim($nm)) continue;
        $jh = (int)($_POST['jml_harian'][$i] ?? 0);
        $th = (int)str_replace(['.', ','], '', $_POST['tarif_harian'][$i] ?? '0');
        $jt = (int)($_POST['jml_transport'][$i] ?? 0);
        $tt = (int)str_replace(['.', ','], '', $_POST['tarif_transport'][$i] ?? '0');
        $jp = (int)($_POST['jml_penginapan'][$i] ?? 0);
        $tp = (int)str_replace(['.', ','], '', $_POST['tarif_penginapan'][$i] ?? '0');
        $total = ($jh * $th) + ($jt * $tt) + ($jp * $tp);
        $ins->execute([
            $surat_id, $i+1, $nm,
            $_POST['nip'][$i]       ?? '',
            $_POST['gol'][$i]       ?? '',
            $_POST['jabatan'][$i]   ?? '',
            $_POST['tujuan'][$i]    ?? '',
            $_POST['tgl_berangkat'][$i] ?: null,
            $_POST['tgl_pulang'][$i]    ?: null,
            $jh, $th, $jt, $tt, $jp, $tp, $total
        ]);
    }
    header('Location: nominatif_perjadin.php?id=' . $surat_id); exit;
}

// Re-fetch
$stmtP->execute([$surat_id]);
$daftar = $stmtP->fetchAll();
$show_form = empty($daftar) || isset($_GET['edit']);

$bln_names = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
function tglIdn($tgl){ global $bln_names; if(!$tgl)return'-'; [$y,$m,$d]=explode('-',$tgl); return(int)$d.' '.$bln_names[(int)$m].' '.$y; }
function rupiah($n){ return number_format((float)$n,0,',','.'); }

$tgl_surat   = tglIdn($spp['tanggal_surat']);
$nama_ppk    = $spp['nama_ppk'] ?? 'Citra Mayasari';
$nama_bendahara = 'Saflir Kurin';
$nama_pembuat   = 'Ghina Febriani Khairunnisa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Nominatif Perjadin — <?= htmlspecialchars($spp['nomor_surat']) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;font-size:11px;background:#e0e5ec}
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
#wrap{max-width:1100px;margin:18px auto 50px;background:#fff;box-shadow:0 2px 14px rgba(0,0,0,.2)}
.surat{padding:25px 30px;line-height:1.5}

/* JUDUL */
.judul-box{text-align:center;margin-bottom:6px}
.judul{font-size:13px;font-weight:bold;text-transform:uppercase}
.sub-judul{font-size:12px;font-weight:bold;text-transform:uppercase}
.info-bank{font-size:11px;margin:2px 0}

/* TABEL */
.tbl{width:100%;border-collapse:collapse;font-size:10px;margin:10px 0}
.tbl th,.tbl td{border:1px solid #000;padding:3px 4px;vertical-align:middle}
.tbl thead th{background:#f0f0f0;text-align:center;font-weight:bold;font-size:9.5px}
.tbl .tac{text-align:center}.tbl .tar{text-align:right}.tbl .tal{text-align:left}
.tbl .bold{font-weight:bold}
.tbl .total-row{font-weight:bold;background:#f5f5f5}

/* FORM INPUT */
.f{display:block;width:100%;border:1px solid #ccc;padding:2px 3px;font-size:10px;font-family:Arial,sans-serif}
.f:focus{outline:2px solid #1a3c6e}

/* TTD */
.ttd-wrap{display:flex;justify-content:space-between;margin-top:20px;font-size:11px}
.ttd-box{text-align:center}
.ttd-nama{font-weight:bold;margin-top:50px;border-top:1px solid #000;padding-top:2px;
  display:inline-block;min-width:160px}

.add-btn{background:#1a3c6e;color:#fff;border:none;padding:5px 14px;
  font-size:11px;border-radius:4px;cursor:pointer;margin-top:8px}
.del-btn{background:#e74c3c;color:#fff;border:none;padding:2px 6px;
  font-size:9px;border-radius:3px;cursor:pointer}

@media print{
  #toolbar,#form-section,.add-btn,.del-btn{display:none!important}
  #wrap{box-shadow:none;margin:0;max-width:100%}
  .surat{padding:12px 15px}
  body{background:#fff}
  #rekap-section{display:block!important}
}
</style>
</head>
<body>
<div id="toolbar">
  <div class="ttl">✈️ Nominatif Perjadin <small><?= htmlspecialchars($spp['nomor_surat']) ?></small></div>
  <div style="display:flex;gap:8px">
    <button class="tbtn tbtn-back" onclick="history.back()">← Kembali</button>
    <?php if (!$show_form && !empty($daftar)): ?>
    <button class="tbtn tbtn-edit" onclick="location.href='?id=<?= $surat_id ?>&edit=1'">✏️ Edit</button>
    <?php endif; ?>
    <button class="tbtn tbtn-print" onclick="window.print()">🖨 Cetak</button>
  </div>
</div>

<div id="wrap"><div class="surat">

<?php if ($show_form): ?>
<!-- ══ FORM INPUT ══ -->
<div id="form-section">
  <div style="font-size:12px;font-weight:bold;margin-bottom:8px;color:#1a3c6e">
    📝 Input Daftar Nominatif Perjalanan Dinas
  </div>
  <form method="POST" id="frmPerjadin">
  <input type="hidden" name="action" value="simpan_perjadin">
  <div style="overflow-x:auto">
  <table class="tbl" id="tbl_input">
    <thead>
      <tr>
        <th rowspan="2" style="width:25px">No</th>
        <th rowspan="2" style="width:130px">Nama</th>
        <th rowspan="2" style="width:110px">NIP</th>
        <th rowspan="2" style="width:45px">Gol</th>
        <th rowspan="2" style="width:120px">Tujuan</th>
        <th colspan="2">Tanggal</th>
        <th colspan="3">Uang Harian</th>
        <th colspan="3">Transportasi (PP)</th>
        <th colspan="3">Penginapan</th>
        <th rowspan="2" style="width:30px">Aksi</th>
      </tr>
      <tr>
        <th style="width:95px">Berangkat</th>
        <th style="width:95px">Pulang</th>
        <th style="width:35px">Hari</th>
        <th style="width:80px">Tarif</th>
        <th style="width:75px">Jumlah</th>
        <th style="width:35px">Hari</th>
        <th style="width:80px">Tarif</th>
        <th style="width:75px">Jumlah</th>
        <th style="width:35px">Hari</th>
        <th style="width:80px">Tarif</th>
        <th style="width:75px">Jumlah</th>
      </tr>
    </thead>
    <tbody id="tbody_input">
      <?php
      $rows = !empty($daftar) ? $daftar : [null];
      foreach ($rows as $i => $row):
      $jh = $row['jml_hari_uang_harian'] ?? 0;
      $th = $row['tarif_uang_harian'] ?? 0;
      $jt = $row['jml_hari_transport'] ?? 0;
      $tt = $row['tarif_transport'] ?? 0;
      $jp = $row['jml_hari_penginapan'] ?? 0;
      $tp = $row['tarif_penginapan'] ?? 0;
      ?>
      <tr>
        <td class="tac"><?= $i+1 ?></td>
        <td><input name="nama[]" class="f" value="<?= htmlspecialchars($row['nama'] ?? '') ?>" required></td>
        <td><input name="nip[]" class="f" value="<?= htmlspecialchars($row['nip'] ?? '') ?>"></td>
        <td><input name="gol[]" class="f" value="<?= htmlspecialchars($row['golongan'] ?? '') ?>" style="width:42px"></td>
        <td><input name="tujuan[]" class="f" value="<?= htmlspecialchars($row['tujuan'] ?? '') ?>"></td>
        <td><input name="tgl_berangkat[]" class="f" type="date" value="<?= $row['tgl_berangkat'] ?? '' ?>"></td>
        <td><input name="tgl_pulang[]" class="f" type="date" value="<?= $row['tgl_pulang'] ?? '' ?>"></td>
        <!-- Uang harian -->
        <td><input name="jml_harian[]" class="f" type="number" value="<?= $jh ?>" min="0" oninput="calcRow(this)" style="text-align:center"></td>
        <td><input name="tarif_harian[]" class="f" value="<?= $th ? rupiah($th) : '' ?>" oninput="fmtRp(this);calcRow(this)" style="text-align:right"></td>
        <td class="tar jml-harian"><?= $jh && $th ? rupiah($jh * $th) : '' ?></td>
        <!-- Transport -->
        <td><input name="jml_transport[]" class="f" type="number" value="<?= $jt ?>" min="0" oninput="calcRow(this)" style="text-align:center"></td>
        <td><input name="tarif_transport[]" class="f" value="<?= $tt ? rupiah($tt) : '' ?>" oninput="fmtRp(this);calcRow(this)" style="text-align:right"></td>
        <td class="tar jml-transport"><?= $jt && $tt ? rupiah($jt * $tt) : '' ?></td>
        <!-- Penginapan -->
        <td><input name="jml_penginapan[]" class="f" type="number" value="<?= $jp ?>" min="0" oninput="calcRow(this)" style="text-align:center"></td>
        <td><input name="tarif_penginapan[]" class="f" value="<?= $tp ? rupiah($tp) : '' ?>" oninput="fmtRp(this);calcRow(this)" style="text-align:right"></td>
        <td class="tar jml-penginapan"><?= $jp && $tp ? rupiah($jp * $tp) : '' ?></td>
        <td class="tac"><button type="button" class="del-btn" onclick="delRow(this)">✕</button></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <button type="button" class="add-btn" onclick="addRow()">＋ Tambah Pegawai</button>
  <div style="margin-top:12px;display:flex;gap:10px">
    <button type="submit" class="tbtn tbtn-save" style="padding:7px 20px">💾 Simpan</button>
    <?php if (!empty($daftar)): ?>
    <button type="button" class="tbtn tbtn-back" onclick="location.href='?id=<?= $surat_id ?>'">Batal</button>
    <?php endif; ?>
  </div>
  </form>
</div>
<?php endif; ?>

<!-- ══ TAMPILAN CETAK ══ -->
<?php if (!empty($daftar)): ?>
<div id="rekap-section">
  <div class="judul-box">
    <div class="judul">Daftar Nominatif Perjalanan Dinas Biasa</div>
    <div class="sub-judul"><?= htmlspecialchars($spp['satker_nama']) ?></div>
  </div>

  <!-- Info bank rekening satker -->
  <div class="info-bank" style="margin:4px 0 8px 0; font-size:11px">
    Bank Mandiri KCP Kementerian Pekerjaan Umum &nbsp;|&nbsp;
    No. Rekening : 8100126912781000 (BPG 139 DIREKTORAT SSPPS)
  </div>

  <div style="font-size:11px;margin-bottom:6px">
    Jakarta, <?= $tgl_surat ?>
  </div>

  <div style="overflow-x:auto">
  <table class="tbl">
    <thead>
      <tr>
        <th rowspan="3" style="width:25px">No.</th>
        <th rowspan="3">NAMA</th>
        <th rowspan="3" style="width:110px">NIP</th>
        <th rowspan="3" style="width:40px">GOL</th>
        <th rowspan="3">TUJUAN</th>
        <th colspan="2">TANGGAL</th>
        <th colspan="3">Uang Harian</th>
        <th colspan="3">Transportasi (PP)</th>
        <th colspan="3">Penginapan</th>
        <th rowspan="3" style="width:80px">TOTAL BIAYA</th>
      </tr>
      <tr>
        <th rowspan="2" style="width:80px">Berangkat</th>
        <th rowspan="2" style="width:80px">Pulang</th>
        <th style="width:32px">Hari</th>
        <th style="width:65px">Rp.</th>
        <th style="width:70px">Jumlah</th>
        <th style="width:32px">Hari</th>
        <th style="width:65px">Rp.</th>
        <th style="width:70px">Jumlah</th>
        <th style="width:32px">Hari</th>
        <th style="width:65px">Rp.</th>
        <th style="width:70px">Jumlah</th>
      </tr>
      <tr>
        <td class="tac" style="font-size:9px">1</td><td class="tac" style="font-size:9px">2</td><td class="tac" style="font-size:9px">3</td>
        <td class="tac" style="font-size:9px">4</td><td class="tac" style="font-size:9px">5</td><td class="tac" style="font-size:9px">6</td>
        <td class="tac" style="font-size:9px">7</td><td class="tac" style="font-size:9px">8</td><td class="tac" style="font-size:9px">9</td>
      </tr>
    </thead>
    <tbody>
      <?php
      $total_harian = $total_transport = $total_penginapan = $grand_total = 0;
      foreach ($daftar as $i => $row):
        $jh = (int)$row['jml_hari_uang_harian'];
        $th_ = (float)$row['tarif_uang_harian'];
        $jt = (int)$row['jml_hari_transport'];
        $tt_ = (float)$row['tarif_transport'];
        $jp = (int)$row['jml_hari_penginapan'];
        $tp_ = (float)$row['tarif_penginapan'];
        $jml_h = $jh * $th_;
        $jml_t = $jt * $tt_;
        $jml_p = $jp * $tp_;
        $total = (float)$row['total_biaya'];
        $total_harian    += $jml_h;
        $total_transport += $jml_t;
        $total_penginapan+= $jml_p;
        $grand_total     += $total;
      ?>
      <tr>
        <td class="tac"><?= $i+1 ?></td>
        <td><?= htmlspecialchars($row['nama']) ?></td>
        <td class="tac" style="font-size:9.5px"><?= htmlspecialchars($row['nip'] ?? '') ?></td>
        <td class="tac"><?= htmlspecialchars($row['golongan'] ?? '') ?></td>
        <td style="font-size:9.5px"><?= htmlspecialchars($row['tujuan'] ?? '') ?></td>
        <td class="tac" style="font-size:9.5px"><?= tglIdn($row['tgl_berangkat']) ?></td>
        <td class="tac" style="font-size:9.5px"><?= tglIdn($row['tgl_pulang']) ?></td>
        <!-- Uang harian -->
        <td class="tac"><?= $jh ?: '' ?></td>
        <td class="tar"><?= $th_ ? rupiah($th_) : '' ?></td>
        <td class="tar"><?= $jml_h ? rupiah($jml_h) : '' ?></td>
        <!-- Transport -->
        <td class="tac"><?= $jt ?: '' ?></td>
        <td class="tar"><?= $tt_ ? rupiah($tt_) : '' ?></td>
        <td class="tar"><?= $jml_t ? rupiah($jml_t) : '' ?></td>
        <!-- Penginapan -->
        <td class="tac"><?= $jp ?: '' ?></td>
        <td class="tar"><?= $tp_ ? rupiah($tp_) : '' ?></td>
        <td class="tar"><?= $jml_p ? rupiah($jml_p) : '' ?></td>
        <td class="tar bold"><?= rupiah($total) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr class="total-row">
        <td colspan="7" class="tar bold">JUMLAH PERJALANAN DINAS</td>
        <td colspan="3" class="tar bold"><?= rupiah($total_harian) ?></td>
        <td colspan="3" class="tar bold"><?= rupiah($total_transport) ?></td>
        <td colspan="3" class="tar bold"><?= rupiah($total_penginapan) ?></td>
        <td class="tar bold"><?= rupiah($grand_total) ?></td>
      </tr>
    </tfoot>
  </table>
  </div>

  <div style="font-size:11px;font-style:italic;margin-top:4px">
    Terbilang : <?= ucfirst(trim(terbilang($grand_total))) ?> rupiah
  </div>

  <!-- TTD -->
  <div class="ttd-wrap">
    <div class="ttd-box">
      <div>Bendahara Pengeluaran</div><br>
      <span class="ttd-nama"><?= htmlspecialchars($nama_bendahara) ?></span>
    </div>
    <div class="ttd-box">
      <div>Mengetahui,</div>
      <div>Pejabat Pembuat Komitmen</div>
      <div><?= htmlspecialchars($spp['satker_nama']) ?></div>
      <span class="ttd-nama"><?= htmlspecialchars($nama_ppk) ?></span>
    </div>
    <div class="ttd-box">
      <div>Pembuat Daftar,</div><br>
      <span class="ttd-nama"><?= htmlspecialchars($nama_pembuat) ?></span>
    </div>
  </div>
</div>
<?php endif; ?>

</div></div>

<script>
function fmtRp(el){let v=el.value.replace(/[^0-9]/g,'');el.value=v?parseInt(v).toLocaleString('id-ID'):'';}
function parseRp(s){return parseInt((s||'0').replace(/[^0-9]/g,''))||0;}

function calcRow(el){
  const tr=el.closest('tr');
  const jh=parseInt(tr.querySelector('input[name="jml_harian[]"]').value)||0;
  const th=parseRp(tr.querySelector('input[name="tarif_harian[]"]').value);
  const jt=parseInt(tr.querySelector('input[name="jml_transport[]"]').value)||0;
  const tt=parseRp(tr.querySelector('input[name="tarif_transport[]"]').value);
  const jp=parseInt(tr.querySelector('input[name="jml_penginapan[]"]').value)||0;
  const tp=parseRp(tr.querySelector('input[name="tarif_penginapan[]"]').value);
  tr.querySelector('.jml-harian').textContent=(jh&&th)?(jh*th).toLocaleString('id-ID'):'';
  tr.querySelector('.jml-transport').textContent=(jt&&tt)?(jt*tt).toLocaleString('id-ID'):'';
  tr.querySelector('.jml-penginapan').textContent=(jp&&tp)?(jp*tp).toLocaleString('id-ID'):'';
}

let rowCount=<?= max(count($daftar),1) ?>;
function addRow(){
  rowCount++;
  const tb=document.getElementById('tbody_input');
  const n=tb.rows.length+1;
  const tr=document.createElement('tr');
  tr.innerHTML=`
    <td class="tac">${n}</td>
    <td><input name="nama[]" class="f" required></td>
    <td><input name="nip[]" class="f"></td>
    <td><input name="gol[]" class="f" style="width:42px"></td>
    <td><input name="tujuan[]" class="f"></td>
    <td><input name="tgl_berangkat[]" class="f" type="date"></td>
    <td><input name="tgl_pulang[]" class="f" type="date"></td>
    <td><input name="jml_harian[]" class="f" type="number" value="0" min="0" oninput="calcRow(this)" style="text-align:center"></td>
    <td><input name="tarif_harian[]" class="f" oninput="fmtRp(this);calcRow(this)" style="text-align:right"></td>
    <td class="tar jml-harian"></td>
    <td><input name="jml_transport[]" class="f" type="number" value="0" min="0" oninput="calcRow(this)" style="text-align:center"></td>
    <td><input name="tarif_transport[]" class="f" oninput="fmtRp(this);calcRow(this)" style="text-align:right"></td>
    <td class="tar jml-transport"></td>
    <td><input name="jml_penginapan[]" class="f" type="number" value="0" min="0" oninput="calcRow(this)" style="text-align:center"></td>
    <td><input name="tarif_penginapan[]" class="f" oninput="fmtRp(this);calcRow(this)" style="text-align:right"></td>
    <td class="tar jml-penginapan"></td>
    <td class="tac"><button type="button" class="del-btn" onclick="delRow(this)">✕</button></td>`;
  tb.appendChild(tr);
}
function delRow(btn){
  const tb=document.getElementById('tbody_input');
  if(tb.rows.length>1) btn.closest('tr').remove();
}
</script>
</body>
</html>