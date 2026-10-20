<?php require_once '../config/database.php'; requireLogin(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Pegawai - SMP MU Boarding School</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="navbar">
  <h2>👥 Kelola Pegawai</h2>
  <div class="user-info">
    <span>👤 <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
    <button class="btn btn-warn btn-sm" onclick="loadPegawai()">🔄 Refresh</button>
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
    <a href="pegawai.php" class="active">👥 Kelola Pegawai</a>
    <a href="kode.php">🔑 Kode Guru</a>
    <?php if (isSuperAdmin()): ?><a href="users.php">👥 Manajemen Admin</a><?php endif; ?>
  </div>

  <!-- FORM TAMBAH PEGAWAI -->
  <div class="card">
    <h3>➕ Tambah Pegawai Baru</h3>
    <form id="formTambah" onsubmit="return tambahPegawai(event)">
      <div class="row">
        <div>
          <label>Nama Lengkap *</label>
          <input type="text" id="inNama" required placeholder="cth: AHMAD FAUZI, S.Pd">
        </div>
        <div>
          <label>No Urut</label>
          <input type="number" id="inNoUrut" placeholder="otomatis">
        </div>
        <div>
          <label>Kategori *</label>
          <select id="inKategori" required>
            <option value="1">GURU</option>
            <option value="2">KARYAWAN</option>
          </select>
        </div>
      </div>
      <div class="row">
        <div><label>Jabatan 1</label><input type="text" id="inJab1" placeholder="cth: Kepsek"></div>
        <div><label>Jabatan 2</label><input type="text" id="inJab2" placeholder="cth: Walas"></div>
        <div><label>Jabatan 3</label><input type="text" id="inJab3" placeholder="cth: Guru"></div>
        <div><label>Jabatan 4</label><input type="text" id="inJab4" placeholder="cth: Bend BOS"></div>
      </div>
      <div class="info-box">
        ℹ️ Jabatan yang diisi di sini akan menjadi jabatan awal untuk tahun <b><?= date('Y') ?></b>.<br>
        Kode unik login guru akan dibuat otomatis.
      </div>
      <button type="submit" class="btn-success">💾 Simpan Pegawai</button>
    </form>
  </div>

  <!-- DAFTAR PEGAWAI -->
  <div class="card">
    <h3>📋 Daftar Pegawai (Aktif)</h3>
    <p style="color:#666; font-size:13px;">Total: <b id="countAktif">0</b> pegawai aktif</p>
    <div class="table-wrap" style="margin-top:12px;">
      <table>
        <thead>
          <tr>
            <th>No</th><th>Nama</th><th>Kategori</th><th>Kode Unik</th>
            <th class="no-print">Aksi</th>
          </tr>
        </thead>
        <tbody id="tblAktif"></tbody>
      </table>
    </div>
  </div>

  <!-- DAFTAR PEGAWAI NON-AKTIF -->
  <div class="card">
    <h3>🚫 Pegawai Non-Aktif (Arsip)</h3>
    <p style="color:#666; font-size:13px;">Pegawai yang sudah tidak aktif — data gaji & jabatan lama tetap tersimpan.</p>
    <div class="table-wrap" style="margin-top:12px;">
      <table>
        <thead>
          <tr>
            <th>No</th><th>Nama</th><th>Kategori</th><th>Kode Unik</th>
            <th class="no-print">Aksi</th>
          </tr>
        </thead>
        <tbody id="tblNonaktif"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL EDIT -->
<div id="modalEdit" class="hidden" style="position:fixed; inset:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; padding:15px;">
  <div style="background:#fff; border-radius:10px; padding:20px; max-width:600px; width:100%; max-height:90vh; overflow-y:auto;">
    <h3 id="modalTitle">Edit Pegawai</h3>
    <input type="hidden" id="editId">
    <div class="row">
      <div><label>Nama Lengkap</label><input type="text" id="edNama"></div>
      <div><label>No Urut</label><input type="number" id="edNoUrut"></div>
    </div>
    <div class="row">
      <div><label>Kategori</label>
        <select id="edKategori">
          <option value="1">GURU</option>
          <option value="2">KARYAWAN</option>
        </select>
      </div>
      <div><label>Kode Unik</label><input type="text" id="edKode" readonly></div>
    </div>
    <h4>Jabatan Tahun <span id="edTahun"><?= date('Y') ?></span></h4>
    <div class="row">
      <div><label>Jabatan 1</label><input type="text" id="edJab1"></div>
      <div><label>Jabatan 2</label><input type="text" id="edJab2"></div>
    </div>
    <div class="row">
      <div><label>Jabatan 3</label><input type="text" id="edJab3"></div>
      <div><label>Jabatan 4</label><input type="text" id="edJab4"></div>
    </div>
    <div style="margin-top:15px; display:flex; gap:8px; flex-wrap:wrap;">
      <button class="btn-success" onclick="simpanEdit()">💾 Simpan</button>
      <button class="btn-warn" onclick="tutupModal()">✖ Batal</button>
      <button class="btn-danger" onclick="nonaktifkan()" style="margin-left:auto;">🚫 Non-aktifkan</button>
    </div>
  </div>
</div>

<script>
let dataPegawai = [];

function rp(n) { return 'Rp ' + (+n).toLocaleString('id-ID'); }

async function loadPegawai() {
  const r = await fetch('../api/pegawai.php?action=list_all').then(x=>x.json());
  dataPegawai = r;

  const aktif = r.filter(g => g.aktif == 1);
  const nonaktif = r.filter(g => g.aktif != 1);

  document.getElementById('countAktif').textContent = aktif.length;

  // Tabel Aktif
  const tblA = document.getElementById('tblAktif');
  tblA.innerHTML = '';
  if (aktif.length === 0) {
    tblA.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#999;">Belum ada pegawai aktif</td></tr>';
  } else {
    aktif.forEach((g, i) => {
      tblA.innerHTML += `
        <tr>
          <td>${g.no_urut || i+1}</td>
          <td><b>${g.nama}</b></td>
          <td><span class="badge">${g.kategori}</span></td>
          <td><span class="kode-box">${g.kode_unik}</span></td>
          <td class="no-print">
            <button class="btn-warn btn-sm" onclick="bukaEdit(${g.id})">✏️ Edit</button>
          </td>
        </tr>`;
    });
  }

  // Tabel Non-aktif
  const tblN = document.getElementById('tblNonaktif');
  tblN.innerHTML = '';
  if (nonaktif.length === 0) {
    tblN.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#999;">Belum ada pegawai non-aktif</td></tr>';
  } else {
    nonaktif.forEach((g, i) => {
      tblN.innerHTML += `
        <tr>
          <td>${i+1}</td>
          <td>${g.nama}</td>
          <td><span class="badge">${g.kategori}</span></td>
          <td><span class="kode-box">${g.kode_unik}</span></td>
          <td class="no-print">
            <button class="btn-success btn-sm" onclick="aktifkanKembali(${g.id})">✅ Aktifkan</button>
            <button class="btn-danger btn-sm" onclick="hapusPermanen(${g.id}, '${g.nama}')">🗑️ Hapus</button>
          </td>
        </tr>`;
    });
  }
}

async function tambahPegawai(e) {
  e.preventDefault();
  const data = {
    nama: document.getElementById('inNama').value.trim(),
    no_urut: document.getElementById('inNoUrut').value || null,
    kategori_id: document.getElementById('inKategori').value,
    jabatan1: document.getElementById('inJab1').value.trim(),
    jabatan2: document.getElementById('inJab2').value.trim(),
    jabatan3: document.getElementById('inJab3').value.trim(),
    jabatan4: document.getElementById('inJab4').value.trim()
  };
  const r = await fetch('../api/pegawai.php?action=tambah', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify(data)
  }).then(x=>x.json());

  if (r.status === 'ok') {
    toast('✅ Pegawai baru ditambahkan! Kode: ' + r.kode);
    document.getElementById('formTambah').reset();
    loadPegawai();
  } else {
    toast('❌ ' + r.msg, true);
  }
  return false;
}

function bukaEdit(id) {
  const g = dataPegawai.find(x => x.id == id);
  if (!g) return;
  document.getElementById('editId').value = g.id;
  document.getElementById('edNama').value = g.nama;
  document.getElementById('edNoUrut').value = g.no_urut || '';
  document.getElementById('edKategori').value = g.kategori_id;
  document.getElementById('edKode').value = g.kode_unik;
  document.getElementById('edTahun').textContent = new Date().getFullYear();

  // Ambil jabatan tahun ini
  fetch('../api/pegawai.php?action=get_jabatan&guru_id=' + id + '&tahun=' + new Date().getFullYear())
    .then(x=>x.json()).then(j => {
      document.getElementById('edJab1').value = j.jabatan1 || '';
      document.getElementById('edJab2').value = j.jabatan2 || '';
      document.getElementById('edJab3').value = j.jabatan3 || '';
      document.getElementById('edJab4').value = j.jabatan4 || '';
    });

  document.getElementById('modalEdit').classList.remove('hidden');
}

function tutupModal() {
  document.getElementById('modalEdit').classList.add('hidden');
}

async function simpanEdit() {
  const id = document.getElementById('editId').value;
  const data = {
    id: id,
    nama: document.getElementById('edNama').value.trim(),
    no_urut: document.getElementById('edNoUrut').value || null,
    kategori_id: document.getElementById('edKategori').value,
    jabatan1: document.getElementById('edJab1').value.trim(),
    jabatan2: document.getElementById('edJab2').value.trim(),
    jabatan3: document.getElementById('edJab3').value.trim(),
    jabatan4: document.getElementById('edJab4').value.trim(),
    tahun: new Date().getFullYear()
  };
  const r = await fetch('../api/pegawai.php?action=edit', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify(data)
  }).then(x=>x.json());

  if (r.status === 'ok') {
    toast('✅ Data pegawai diupdate!');
    tutupModal();
    loadPegawai();
  } else {
    toast('❌ ' + r.msg, true);
  }
}

async function nonaktifkan() {
  const id = document.getElementById('editId').value;
  if (!confirm('Non-aktifkan pegawai ini?\n\nData gaji & jabatan lama TETAP tersimpan, tapi pegawai tidak muncul di daftar aktif.')) return;
  const r = await fetch('../api/pegawai.php?action=nonaktifkan', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({id})
  }).then(x=>x.json());
  if (r.status === 'ok') {
    toast('✅ Pegawai dinon-aktifkan');
    tutupModal();
    loadPegawai();
  } else toast('❌ ' + r.msg, true);
}

async function aktifkanKembali(id) {
  if (!confirm('Aktifkan kembali pegawai ini?')) return;
  const r = await fetch('../api/pegawai.php?action=aktifkan', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({id})
  }).then(x=>x.json());
  if (r.status === 'ok') { toast('✅ Pegawai diaktifkan kembali'); loadPegawai(); }
  else toast('❌ ' + r.msg, true);
}

async function hapusPermanen(id, nama) {
  if (!confirm(`HAPUS PERMANEN pegawai "${nama}"?\n\n⚠️ Semua data gaji & jabatan pegawai ini akan HILANG PERMANEN!\n\nDisarankan "Non-aktifkan" saja kalau hanya ingin sembunyikan.`)) return;
  if (!confirm('Anda YAKIN? Ini tidak bisa dibatalkan!')) return;
  const r = await fetch('../api/pegawai.php?action=hapus_permanen', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({id})
  }).then(x=>x.json());
  if (r.status === 'ok') { toast('✅ Pegawai dihapus permanen'); loadPegawai(); }
  else toast('❌ ' + r.msg, true);
}

function toast(msg, err=false) {
  const t = document.createElement('div');
  t.className = 'toast' + (err?' error':''); t.textContent = msg;
  document.body.appendChild(t); setTimeout(()=>t.remove(), 3000);
}

loadPegawai();
</script>
</body>
</html>