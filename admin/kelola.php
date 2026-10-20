<?php require_once '../config/database.php'; requireLogin(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Data - SMP MU Boarding School</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="navbar">
  <h2>🗂️ Kelola Data Gaji</h2>
  <div class="user-info">
    <span>👤 <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
    <button class="btn btn-warn btn-sm" onclick="location.reload()">🔄 Refresh</button>
    <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
  </div>
</div>
<div class="container">
  <div class="menu-bar">
    <a href="index.php">🏠 Dashboard</a>
    <a href="input.php">📝 Pengisian Gaji</a>
    <a href="import.php">📥 Import Excel</a>
    <a href="rekap.php">📊 Rekap Gaji</a>
    <a href="kelola.php" class="active">🗂️ Kelola Data</a>
    <a href="pegawai.php">👥 Kelola Pegawai</a>
    <a href="kode.php">🔑 Kode Guru</a>
    <?php if (isSuperAdmin()): ?><a href="users.php">👥 Manajemen Admin</a><?php endif; ?>
  </div>

  <div class="card" style="background:#fff3cd; border-left:4px solid #f39c12;">
    <h3>⚠️ PERHATIAN</h3>
    <p>Menu ini untuk <b>memperbaiki kekeliruan data</b>. Setiap aksi di sini <b>permanen</b> dan tidak bisa di-undo.</p>
    <p style="margin-top:8px;">Disarankan <b>backup database</b> sebelum hapus data besar-besaran.</p>
  </div>

  <!-- INFO DATABASE -->
  <div class="card">
    <h3>📊 Info Database</h3>
    <div id="infoDb" style="font-size:14px; line-height:2;">
      <div>Loading...</div>
    </div>
  </div>

  <!-- REFRESH DATA -->
  <div class="card">
    <h3>🔄 Refresh & Sinkronisasi Data</h3>
    <p style="color:#666; font-size:13px; margin-bottom:12px;">Refresh menghitung ulang semua total gaji di database. Gunakan jika ada data yang tampak tidak konsisten.</p>
    <button class="btn btn-warn" onclick="refreshData()">🔄 Refresh Semua Total</button>
    <button class="btn btn-success" onclick="cekKonsistensi()">✓ Cek Konsistensi Data</button>
  </div>

  <!-- HAPUS PER BULAN -->
  <div class="card">
    <h3>🗑️ Hapus Data Per Bulan</h3>
    <p style="color:#666; font-size:13px; margin-bottom:12px;">Hapus semua data gaji pada bulan & tahun tertentu. Cocok kalau salah import dan mau ulang.</p>
    <div class="row">
      <div>
        <label>Bulan</label>
        <select id="delBulan">
          <?php foreach (['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $b): ?>
            <option><?= $b ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Tahun</label>
        <input type="number" id="delTahun" value="<?= date('Y') ?>">
      </div>
    </div>
    <button class="btn-danger" onclick="hapusPerBulan()">🗑️ Hapus Data Bulan Ini</button>
  </div>

  <!-- HAPUS PER PEGAWAI -->
  <div class="card">
    <h3>🗑️ Hapus Data Per Pegawai</h3>
    <p style="color:#666; font-size:13px; margin-bottom:12px;">Hapus semua riwayat gaji satu pegawai. Cocok kalau pegawai resign atau data salah.</p>
    <div class="row">
      <div>
        <label>Pegawai</label>
        <select id="delGuru"><option value="">-- Pilih Pegawai --</option></select>
      </div>
    </div>
    <button class="btn-danger" onclick="hapusPerGuru()">🗑️ Hapus Semua Gaji Pegawai Ini</button>
  </div>

  <!-- HAPUS PER TAHUN -->
  <div class="card">
    <h3>🗑️ Hapus Data Per Tahun</h3>
    <p style="color:#666; font-size:13px; margin-bottom:12px;">Hapus semua data gaji satu tahun penuh. Gunakan untuk reset tahun ajaran baru.</p>
    <div class="row">
      <div>
        <label>Tahun</label>
        <input type="number" id="delTahunOnly" value="<?= date('Y') ?>">
      </div>
    </div>
    <button class="btn-danger" onclick="hapusPerTahun()">🗑️ Hapus Data Tahun Ini</button>
  </div>

  <!-- HAPUS SEMUA -->
  <div class="card" style="border:2px solid #c0392b;">
    <h3 style="color:#c0392b;">⚠️ Danger Zone — Hapus Semua Data</h3>
    <p style="color:#666; font-size:13px; margin-bottom:12px;">Hapus <b>SELURUH</b> data gaji (semua pegawai, semua bulan, semua tahun). Data pegawai & kode tidak dihapus.</p>
    <button class="btn-danger" onclick="hapusSemua()">🚨 HAPUS SEMUA DATA GAJI</button>
  </div>
</div>

<script>
function rp(n) { return 'Rp ' + (+n).toLocaleString('id-ID'); }

async function loadInfo() {
  const r = await fetch('../api/kelola.php?action=info').then(x=>x.json());
  document.getElementById('infoDb').innerHTML = `
    <div>👥 Total Pegawai: <b>${r.total_pegawai}</b></div>
    <div>📄 Total Baris Gaji: <b>${r.total_gaji_rows}</b></div>
    <div>💰 Total Nominal: <b style="color:#27ae60;">${rp(r.total_nominal)}</b></div>
    <div>📅 Periode Tersedia: <b>${r.periode}</b></div>
  `;
}

async function loadGuru() {
  const r = await fetch('../api/guru.php?action=list').then(x=>x.json());
  const sel = document.getElementById('delGuru');
  r.forEach(g => sel.innerHTML += `<option value="${g.id}">${g.no_urut}. ${g.nama}</option>`);
}

async function refreshData() {
  if (!confirm('Refresh & hitung ulang semua total gaji?')) return;
  const r = await fetch('../api/kelola.php?action=refresh').then(x=>x.json());
  if (r.status === 'ok') { toast('✅ ' + r.msg); loadInfo(); }
  else toast('❌ ' + r.msg, true);
}

async function cekKonsistensi() {
  const r = await fetch('../api/kelola.php?action=cek').then(x=>x.json());
  if (r.status === 'ok') {
    if (r.masalah === 0) toast('✅ Semua data konsisten!');
    else alert(`⚠️ Ada ${r.masalah} baris data yang perlu diperbaiki:\n\n${r.detail.join('\n')}`);
  } else toast('❌ ' + r.msg, true);
}

async function hapusPerBulan() {
  const bulan = document.getElementById('delBulan').value;
  const tahun = document.getElementById('delTahun').value;
  if (!confirm(`Hapus SEMUA data gaji bulan ${bulan} ${tahun}?`)) return;
  if (!confirm(`Yakin? Data tidak bisa dikembalikan!`)) return;
  const r = await fetch('../api/kelola.php?action=hapus_bulan', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({bulan, tahun})
  }).then(x=>x.json());
  if (r.status === 'ok') { toast(`✅ ${r.deleted} baris dihapus`); loadInfo(); }
  else toast('❌ ' + r.msg, true);
}

async function hapusPerGuru() {
  const guru_id = document.getElementById('delGuru').value;
  if (!guru_id) { alert('Pilih pegawai dulu!'); return; }
  const nama = document.getElementById('delGuru').selectedOptions[0].text;
  if (!confirm(`Hapus SEMUA gaji untuk:\n${nama}?`)) return;
  if (!confirm(`Yakin? Semua riwayat gaji pegawai ini akan hilang!`)) return;
  const r = await fetch('../api/kelola.php?action=hapus_guru', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({guru_id})
  }).then(x=>x.json());
  if (r.status === 'ok') { toast(`✅ ${r.deleted} baris dihapus`); loadInfo(); }
  else toast('❌ ' + r.msg, true);
}

async function hapusPerTahun() {
  const tahun = document.getElementById('delTahunOnly').value;
  if (!confirm(`Hapus SEMUA data gaji tahun ${tahun}?`)) return;
  if (!confirm(`Yakin? Seluruh tahun akan dihapus!`)) return;
  const r = await fetch('../api/kelola.php?action=hapus_tahun', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({tahun})
  }).then(x=>x.json());
  if (r.status === 'ok') { toast(`✅ ${r.deleted} baris dihapus`); loadInfo(); }
  else toast('❌ ' + r.msg, true);
}

async function hapusSemua() {
  if (!confirm('⚠️ PERINGATAN!\n\nHapus SELURUH data gaji?\n\nIni tidak bisa dibatalkan!')) return;
  if (!confirm('Anda YAKIN? Ketik OK untuk konfirmasi terakhir.')) return;
  const konfirm = prompt('Ketik "HAPUS SEMUA" (huruf besar) untuk konfirmasi:');
  if (konfirm !== 'HAPUS SEMUA') { toast('❌ Dibatalkan', true); return; }
  const r = await fetch('../api/kelola.php?action=hapus_semua', {method:'POST'}).then(x=>x.json());
  if (r.status === 'ok') { toast(`✅ ${r.deleted} baris dihapus`); loadInfo(); }
  else toast('❌ ' + r.msg, true);
}

function toast(msg, err=false) {
  const t = document.createElement('div');
  t.className = 'toast' + (err?' error':''); t.textContent = msg;
  document.body.appendChild(t); setTimeout(()=>t.remove(), 3000);
}

loadInfo();
loadGuru();
</script>
</body>
</html>