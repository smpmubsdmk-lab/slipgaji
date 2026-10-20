<?php
require_once '../config/database.php';
requireLogin();

date_default_timezone_set('Asia/Jakarta');

$periodeTerbaru = $pdo->query("
    SELECT bulan, tahun
    FROM gaji
    ORDER BY tahun DESC,
    FIELD(bulan,'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember') DESC,
    id DESC
    LIMIT 1
")->fetch();

$bulanTerbaru = $periodeTerbaru['bulan'] ?? 'Belum ada data';
$tahunTerbaru = $periodeTerbaru['tahun'] ?? date('Y');

$totalGajiBulanIni = 0;
$jumlahSlipBulanIni = 0;
if ($periodeTerbaru) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_akhir),0) FROM gaji WHERE bulan=? AND tahun=?");
    $stmt->execute([$periodeTerbaru['bulan'], $periodeTerbaru['tahun']]);
    $totalGajiBulanIni = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT guru_id) FROM gaji WHERE bulan=? AND tahun=?");
    $stmt->execute([$periodeTerbaru['bulan'], $periodeTerbaru['tahun']]);
    $jumlahSlipBulanIni = $stmt->fetchColumn();
}

$totalGuru = $pdo->query("SELECT COUNT(*) FROM guru WHERE aktif=1")->fetchColumn();

$tahunIni = date('Y');
$statBulanan = $pdo->query("
    SELECT bulan, COALESCE(SUM(jumlah_akhir),0) AS total, COUNT(DISTINCT guru_id) AS jumlah_pegawai
    FROM gaji
    WHERE tahun = $tahunIni
    GROUP BY bulan
    ORDER BY FIELD(bulan, 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember')
")->fetchAll();

$statTahunan = $pdo->query("
    SELECT tahun, COALESCE(SUM(jumlah_akhir),0) AS total, COUNT(DISTINCT CONCAT(guru_id,'-',bulan)) AS jumlah_slip
    FROM gaji
    GROUP BY tahun
    ORDER BY tahun DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - SMP MU Boarding School</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="navbar">
  <div style="display:flex; align-items:center; gap:10px; flex:1 1 auto; min-width:0;">
    <img src="../assets/img/logo-smpmu.jpeg" alt="Logo SMP MU" 
         style="width:38px; height:38px; object-fit:contain; border-radius:50%; 
                background:#fff; padding:2px; flex-shrink:0;">
    <h2 style="margin:0;">Slip Gaji - SMP MU Boarding School</h2>
  </div>
  <div class="user-info">
    <span>👤 <?= htmlspecialchars($_SESSION['nama_lengkap']) ?> (<?= $_SESSION['role'] ?>)</span>
    <button class="btn btn-warn btn-sm" onclick="location.reload()">🔄 Refresh</button>
    <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
  </div>
</div>
<div class="container">
  <div class="menu-bar">
    <a href="index.php" class="active">🏠 Dashboard</a>
    <a href="input.php">📝 Pengisian Gaji</a>
    <a href="import.php">📥 Import Excel</a>
    <a href="rekap.php">📊 Rekap Gaji</a>
    <a href="kelola.php">🗂️ Kelola Data</a>
    <a href="pegawai.php">👥 Kelola Pegawai</a>
    <a href="kode.php">🔑 Kode Guru</a>
    <?php if (isSuperAdmin()): ?><a href="users.php">👥 Manajemen Admin</a><?php endif; ?>
  </div>

  <div class="card" style="text-align:center;">
    <img src="../assets/img/logo-smpmu.jpeg" alt="Logo SMP MU" 
         style="width:100px; height:100px; object-fit:contain; margin:0 auto 15px; display:block;">
    <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>! 👋</h2>
    <p>Anda login sebagai <b><?= $_SESSION['role'] ?></b></p>
    <p style="font-size:18px; margin-top:10px; color:#1e3c72; font-weight:bold;">
      🕐 <span id="jamWIB">--:--:--</span> WIB
    </p>
    <p style="color:#666; font-size:13px;" id="tanggalWIB"></p>
  </div>

  <div class="row">
    <div class="card" style="text-align:center; background:linear-gradient(135deg,#1e3c72,#2a5298); color:#fff;">
      <h3 style="color:#fff;">👥 Total Pegawai</h3>
      <p style="font-size:36px; font-weight:bold;"><?= $totalGuru ?></p>
      <p style="font-size:12px; opacity:0.85;">pegawai aktif</p>
    </div>

    <div class="card" style="text-align:center; background:linear-gradient(135deg,#27ae60,#2ecc71); color:#fff;">
      <h3 style="color:#fff;">💰 Total Gaji Bulan Ini</h3>
      <p style="font-size:22px; font-weight:bold;"><?= formatRupiah($totalGajiBulanIni) ?></p>
      <p style="font-size:12px; opacity:0.85;"><?= $bulanTerbaru ?> <?= $tahunTerbaru ?></p>
    </div>

    <div class="card" style="text-align:center; background:linear-gradient(135deg,#f39c12,#e67e22); color:#fff;">
      <h3 style="color:#fff;">📄 Slip Bulan Ini</h3>
      <p style="font-size:36px; font-weight:bold;"><?= $jumlahSlipBulanIni ?></p>
      <p style="font-size:12px; opacity:0.85;"><?= $bulanTerbaru ?> <?= $tahunTerbaru ?></p>
    </div>
  </div>

  <div class="card">
    <h3>📅 Statistik Gaji Bulanan Tahun <?= $tahunIni ?></h3>
    <p style="color:#666; font-size:13px; margin-bottom:10px;">Total bertambah otomatis setiap ada pengisian gaji bulan baru</p>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:50px;">No</th>
            <th>Bulan</th>
            <th>Jumlah Pegawai</th>
            <th style="text-align:right;">Total Gaji</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($statBulanan)): ?>
            <tr><td colspan="4" style="text-align:center; color:#999; padding:20px;">Belum ada data untuk tahun <?= $tahunIni ?></td></tr>
          <?php else: ?>
            <?php 
            $no = 1;
            $grandTotal = 0;
            foreach ($statBulanan as $s): 
              $grandTotal += $s['total'];
            ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><b><?= htmlspecialchars($s['bulan']) ?></b></td>
                <td><?= $s['jumlah_pegawai'] ?> pegawai</td>
                <td style="text-align:right;"><b style="color:#27ae60;"><?= formatRupiah($s['total']) ?></b></td>
              </tr>
            <?php endforeach; ?>
            <tr style="background:#e8f4ff; font-weight:bold;">
              <td colspan="3" style="text-align:right;">TOTAL TAHUN <?= $tahunIni ?>:</td>
              <td style="text-align:right; color:#27ae60; font-size:15px;"><?= formatRupiah($grandTotal) ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h3>📆 Statistik Gaji Tahunan</h3>
    <p style="color:#666; font-size:13px; margin-bottom:10px;">Total bertambah otomatis setiap ada pengisian gaji tahun baru</p>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:50px;">No</th>
            <th>Tahun</th>
            <th>Jumlah Slip</th>
            <th style="text-align:right;">Total Gaji</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($statTahunan)): ?>
            <tr><td colspan="4" style="text-align:center; color:#999; padding:20px;">Belum ada data</td></tr>
          <?php else: ?>
            <?php $no = 1; foreach ($statTahunan as $s): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><b><?= $s['tahun'] ?></b></td>
                <td><?= $s['jumlah_slip'] ?> slip</td>
                <td style="text-align:right;"><b style="color:#27ae60;"><?= formatRupiah($s['total']) ?></b></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function updateJamWIB() {
  const now = new Date();
  const opsi = { weekday:'long', day:'numeric', month:'long', year:'numeric' };
  const tgl = now.toLocaleDateString('id-ID', opsi);
  document.getElementById('tanggalWIB').textContent = tgl;

  const opsiJam = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'Asia/Jakarta' };
  const jam = now.toLocaleTimeString('id-ID', opsiJam).replace(/\./g, ':');
  document.getElementById('jamWIB').textContent = jam;
}
updateJamWIB();
setInterval(updateJamWIB, 1000);
</script>
</body>
</html>