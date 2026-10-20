<?php
require_once '../config/database.php';
if (!isset($_SESSION['guru_id'])) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM guru WHERE id=?");
$stmt->execute([$_SESSION['guru_id']]);
$guru = $stmt->fetch();

$stmt = $pdo->prepare("SELECT bulan, tahun FROM gaji WHERE guru_id=? ORDER BY tahun DESC, id DESC LIMIT 1");
$stmt->execute([$guru['id']]);
$periode = $stmt->fetch();
if (!$periode) die('Belum ada slip gaji.');

$stmt = $pdo->prepare("SELECT * FROM gaji WHERE guru_id=? AND bulan=? AND tahun=? ORDER BY baris_ke ASC");
$stmt->execute([$guru['id'], $periode['bulan'], $periode['tahun']]);
$baris_gaji = $stmt->fetchAll();

$grand_total = 0;
foreach ($baris_gaji as $b) $grand_total += $b['jumlah_akhir'];

$stmt = $pdo->prepare("SELECT * FROM jabatan_guru WHERE guru_id=? AND tahun=?");
$stmt->execute([$guru['id'], $periode['tahun']]);
$jabatan_thn = $stmt->fetch();

if ($jabatan_thn) {
    $jab = $jabatan_thn;
} else {
    $jab = $guru;
}
$jabatan = implode(' / ', array_filter([$jab['jabatan1'], $jab['jabatan2'], $jab['jabatan3'], $jab['jabatan4'] ?? null]));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Slip Gaji - <?= htmlspecialchars($guru['nama']) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
<div class="navbar no-print">
  <div style="display:flex; align-items:center; gap:10px; flex:1 1 auto; min-width:0;">
    <img src="../assets/img/logo-smpmu.jpeg" alt="Logo SMP MU" 
         style="width:38px; height:38px; object-fit:contain; border-radius:50%; 
                background:#fff; padding:2px; flex-shrink:0;">
    <h2 style="margin:0;">Slip Gaji Pegawai</h2>
  </div>
  <div class="user-info">
    <span>👤 <?= htmlspecialchars($guru['nama']) ?></span>
    <a href="logout.php" class="btn btn-danger btn-sm">Keluar</a>
  </div>
</div>
<div class="container">
  <div class="card no-print">
    <div class="info-box">💡 Download slip sebagai PDF atau PNG untuk arsip.</div>
    <div style="text-align:center;">
      <button class="btn-success" onclick="downloadPNG()">📷 Download PNG</button>
      <button class="btn-success" onclick="downloadPDF()">📄 Download PDF</button>
      <button class="btn-warn" onclick="window.print()">🖨️ Print</button>
    </div>
  </div>
  <div class="card" style="background:transparent; box-shadow:none; padding:0;">
    <div id="slipPrintable" class="slip">
      <!-- HEADER DENGAN LOGO -->
      <div class="slip-header" style="display:flex; align-items:center; gap:15px; text-align:left; padding-bottom:15px;">
        <img src="../assets/img/logo-smpmu.jpeg" alt="Logo SMP MU" 
             style="width:70px; height:70px; object-fit:contain; flex-shrink:0;" 
             crossorigin="anonymous">
        <div style="flex:1; text-align:center;">
          <h2 style="margin:0;">SMP MU BOARDING SCHOOL</h2>
          <p style="margin:5px 0 0;">SLIP GAJI PEGAWAI</p>
          <p style="font-size:13px; color:#666; margin:3px 0 0;">Periode: <?= $periode['bulan'] ?> <?= $periode['tahun'] ?></p>
        </div>
        <!-- Placeholder agar teks tetap center -->
        <div style="width:70px; flex-shrink:0;"></div>
      </div>

      <div class="slip-row"><span>Nama</span><b><?= htmlspecialchars($guru['nama']) ?></b></div>
      <div class="slip-row"><span>Jabatan</span><span><?= htmlspecialchars($jabatan ?: '-') ?></span></div>

      <?php foreach ($baris_gaji as $idx => $b): ?>
        <div class="slip-block">
          <h5>
            <?= count($baris_gaji) > 1 ? 'Bagian '.($idx+1) : 'Rincian Honor' ?>
            <?= $b['keterangan'] ? ' — '.htmlspecialchars($b['keterangan']) : '' ?>
          </h5>
          <div class="slip-row"><span>Jam (<?= $b['jam'] ?> × <?= formatRupiah($b['per_jam']) ?>)</span><span><?= formatRupiah($b['jumlah']) ?></span></div>
          <div class="slip-row"><span>Transport</span><span><?= formatRupiah($b['transport']) ?></span></div>
          <div class="slip-row"><span>Tunjangan Jabatan 1</span><span><?= formatRupiah($b['jabatan1_rp']) ?></span></div>
          <div class="slip-row"><span>Tunjangan Jabatan 2</span><span><?= formatRupiah($b['jabatan2_rp']) ?></span></div>
          <div class="slip-row"><b>Total</b><b><?= formatRupiah($b['total']) ?></b></div>
          <div class="slip-row"><span>Pot. Kehadiran</span><span>- <?= formatRupiah($b['pot_kehadiran']) ?></span></div>
          <div class="slip-row"><span>Pot. BPD</span><span>- <?= formatRupiah($b['pot_bpd']) ?></span></div>
          <div class="slip-row"><span>Pot. BPJS</span><span>- <?= formatRupiah($b['pot_bpjs']) ?></span></div>
          <div class="slip-row"><span>Pot. Sosial</span><span>- <?= formatRupiah($b['pot_sosial']) ?></span></div>
          <div class="slip-row"><b>Subtotal Bagian <?= $idx+1 ?></b><b style="color:#27ae60;"><?= formatRupiah($b['jumlah_akhir']) ?></b></div>
        </div>
      <?php endforeach; ?>

      <div class="slip-row total"><span>TOTAL DITERIMA</span><span><?= formatRupiah($grand_total) ?></span></div>
      <p style="text-align:center; margin-top:25px; font-size:12px; color:#666;">
        Slip ini dicetak otomatis oleh sistem dan sah tanpa tanda tangan.
      </p>
    </div>
  </div>
</div>
<script>
async function downloadPNG() {
  const canvas = await html2canvas(document.getElementById('slipPrintable'), { 
    scale: 2, backgroundColor: '#fff', useCORS: true 
  });
  const link = document.createElement('a');
  link.download = 'SlipGaji_<?= preg_replace('/[^A-Za-z0-9]/','_', $guru['nama']) ?>_<?= $periode['bulan'] ?>_<?= $periode['tahun'] ?>.png';
  link.href = canvas.toDataURL('image/png'); link.click();
}
async function downloadPDF() {
  const { jsPDF } = window.jspdf;
  const canvas = await html2canvas(document.getElementById('slipPrintable'), { 
    scale: 2, backgroundColor: '#fff', useCORS: true 
  });
  const pdf = new jsPDF('p', 'mm', 'a4');
  const w = 190, h = canvas.height * w / canvas.width;
  pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 10, 10, w, h);
  pdf.save('SlipGaji_<?= preg_replace('/[^A-Za-z0-9]/','_', $guru['nama']) ?>_<?= $periode['bulan'] ?>_<?= $periode['tahun'] ?>.pdf');
}
</script>
</body>
</html>