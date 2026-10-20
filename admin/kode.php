<?php require_once '../config/database.php'; requireLogin(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kode Guru</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="navbar">
  <h2>🔑 Kode Akses Guru</h2>
  <div class="user-info">
    <span>👤 <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
    <button class="btn btn-warn btn-sm" onclick="load()">🔄 Refresh</button>
    <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
  </div>
</div>
<div class="container">
  <div class="menu-bar">
    <a href="index.php">🏠 Dashboard</a>
    <a href="input.php">📝 Pengisian Gaji</a>
    <a href="import.php">📥 Import Excel</a>
    <a href="rekap.php">📊 Rekap Gaji</a>
    <a href="kelola.php">🗂️ Kelola Data</a>
    <a href="pegawai.php">👥 Kelola Pegawai</a>
    <a href="kode.php" class="active">🔑 Kode Guru</a>
    <?php if (isSuperAdmin()): ?><a href="users.php">👥 Manajemen Admin</a><?php endif; ?>
  </div>

  <div class="card">
    <h3>Daftar Kode Unik Pegawai</h3>
    <p style="color:#666; font-size:13px;">Kode berlaku selamanya. Jabatan ditampilkan sesuai tahun <?= date('Y') ?>.</p>
    <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
      <button class="btn-warn" onclick="window.print()">🖨️ Cetak Semua</button>
      <button class="btn" onclick="load()">🔄 Refresh</button>
    </div>
    <div class="table-wrap" style="margin-top:15px;">
      <table>
        <thead>
          <tr><th>No</th><th>Nama</th><th>Jabatan <?= date('Y') ?></th><th>Kategori</th><th>Kode Unik</th><th class="no-print">Aksi</th></tr>
        </thead>
        <tbody id="tblKode"></tbody>
      </table>
    </div>
  </div>
</div>
<script>
const TAHUN_INI = <?= date('Y') ?>;

async function load() {
  const r = await fetch('../api/guru.php?action=list').then(x=>x.json());
  const tb = document.getElementById('tblKode');
  tb.innerHTML = '';
  
  for (let i = 0; i < r.length; i++) {
    const g = r[i];
    // Ambil jabatan tahun ini
    const j = await fetch(`../api/pegawai.php?action=get_jabatan&guru_id=${g.id}&tahun=${TAHUN_INI}`).then(x=>x.json());
    const jab = [j.jabatan1, j.jabatan2, j.jabatan3, j.jabatan4].filter(x=>x).join(' / ') || '-';
    
    tb.innerHTML += `
      <tr>
        <td>${g.no_urut || i+1}</td>
        <td>${g.nama}</td>
        <td>${jab}</td>
        <td><span class="badge">${g.kategori || 'GURU'}</span></td>
        <td><span class="kode-box">${g.kode_unik}</span></td>
        <td class="no-print"><button class="btn-warn btn-sm" onclick="resetKode(${g.id})">🔄 Ganti</button></td>
      </tr>`;
  }
}
async function resetKode(id) {
  if (!confirm('Ganti kode ini?')) return;
  const r = await fetch('../api/guru.php?action=reset_kode&id=' + id).then(x=>x.json());
  toast('✅ Kode baru: ' + r.kode); load();
}
function toast(msg, err=false) {
  const t = document.createElement('div');
  t.className = 'toast' + (err?' error':''); t.textContent = msg;
  document.body.appendChild(t); setTimeout(()=>t.remove(), 3000);
}
load();
</script>
</body>
</html>