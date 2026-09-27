<?php
/**
 * KOP SURAT HELPER
 * Menampilkan kop surat sesuai tipe yang dipilih
 * Tipe: 'dirjen' | 'direktorat' | 'satker'
 */

$_KOP_BASE = '/spp_pu/assets/img/kop/';
$_KOP_MAP  = [
  'dirjen'     => 'kop_dirjen.jpg',
  'direktorat' => 'kop_direktorat.jpg',
  'satker'     => 'kop_satker.jpg',
];

function getKopSuratImg(string $tipe = 'satker'): string {
  global $_KOP_BASE, $_KOP_MAP;
  $file = $_KOP_MAP[$tipe] ?? $_KOP_MAP['satker'];
  return $_KOP_BASE . $file;
}

function renderKopSurat(string $tipe = 'satker', bool $printMode = false): void {
  $src = getKopSuratImg($tipe);
  $style = $printMode
    ? 'width:100%;display:block;margin-bottom:0;'
    : 'width:100%;display:block;border-radius:10px 10px 0 0;margin-bottom:0;';
  echo '<div class="kop-surat-wrap" style="line-height:0;margin-bottom:12px;">';
  echo '<img src="' . htmlspecialchars($src) . '" alt="Kop Surat" style="' . $style . '">';
  echo '<hr style="border:none;border-bottom:2px solid #000;margin:0;">';
  echo '</div>';
}
