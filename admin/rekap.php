<?php require_once '../config/database.php'; requireLogin(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rekap Gaji</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="navbar">
  <h2>📊 Rekap Gaji Pegawai</h2>
  <div class="user-info">
    <span>👤 <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
    <button class="btn btn-warn btn-sm" onclick="loadCurrentTab()">🔄 Refresh</button>
    <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
  </div>
</div>
<div class="container">
  <div class="menu-bar">
    <a href="index.php">🏠 Dashboard</a>
    <a href="input.php">📝 Pengisian Gaji</a>
    <a href="import.php">📥 Import Excel</a>
    <a href="rekap.php" class="active">📊 Rekap Gaji</a>
    <a href="kelola.php">🗂️ Kelola Data</a>
    <a href="pegawai.php">👥 Kelola Pegawai</a>
    <a href="kode.php">🔑 Kode Guru</a>
    <?php if (isSuperAdmin()): ?><a href="users.php">👥 Manajemen Admin</a><?php endif; ?>
  </div>

  <div class="card">
    <div style="display:flex; gap:8px; flex-wrap:wrap; border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:15px;">
      <button class="btn btn-sm tab-btn active" data-tab="bulanan" onclick="switchTab('bulanan')">📅 Rekap Bulanan</button>
      <button class="btn btn-sm tab-btn" data-tab="tahunan" onclick="switchTab('tahunan')">📆 Rekap Tahunan</button>
      <button class="btn btn-sm tab-btn" data-tab="pegawai" onclick="switchTab('pegawai')">👤 Rekap per Pegawai</button>
    </div>

    <!-- TAB BULANAN -->
    <div id="tab-bulanan">
      <h4>Filter Rekap Bulanan</h4>
      <div class="row">
        <div>
          <label>Dari Bulan</label>
          <select id="fBulanDari">
            <option value="">-- Semua --</option>
            <?php foreach (['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $b): ?>
              <option value="<?= $b ?>"><?= $b ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Sampai Bulan</label>
          <select id="fBulanSampai">
            <option value="">-- Semua --</option>
            <?php foreach (['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $b): ?>
              <option value="<?= $b ?>"><?= $b ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Dari Tahun</label>
          <input type="number" id="fTahunDari" placeholder="2025" value="<?= date('Y') ?>">
        </div>
        <div>
          <label>Sampai Tahun</label>
          <input type="number" id="fTahunSampai" placeholder="2026" value="<?= date('Y') ?>">
        </div>
      </div>
      <div class="row">
        <div>
          <label>Pegawai</label>
          <select id="fGuru"><option value="">-- Semua --</option></select>
        </div>
      </div>
      <button class="btn-primary" onclick="loadRekap()">🔍 Filter</button>
      <button class="btn-warn" onclick="resetFilter()">↺ Reset</button>
      <button class="btn-success" onclick="exportExcel()">📥 Export Excel</button>
      <button class="btn" onclick="window.print()">🖨️ Print</button>
    </div>

    <!-- TAB TAHUNAN -->
    <div id="tab-tahunan" class="hidden">
      <h4>Filter Rekap Tahunan</h4>
      <div class="row">
        <div>
          <label>Tahun</label>
          <input type="number" id="fTahunOnly" value="<?= date('Y') ?>">
        </div>
        <div>
          <label>Pegawai</label>
          <select id="fGuruTahunan"><option value="">-- Semua --</option></select>
        </div>
      </div>
      <button class="btn-primary" onclick="loadRekapTahunan()">🔍 Filter</button>
      <button class="btn-success" onclick="exportExcel()">📥 Export Excel</button>
      <button class="btn" onclick="window.print()">🖨️ Print</button>
    </div>

    <!-- TAB PEGAWAI -->
    <div id="tab-pegawai" class="hidden">
      <h4>Rekap Per Pegawai (Semua Bulan)</h4>
      <p style="color:#666; font-size:13px; margin-bottom:10px;">
        Pilih pegawai → total keseluruhan otomatis bertambah setiap ada gaji bulan baru.
      </p>
      <div class="row">
        <div>
          <label>Pilih Pegawai</label>
          <select id="fGuruDetail"><option value="">-- Pilih --</option></select>
        </div>
        <div>
          <label>Tahun</label>
          <input type="number" id="fTahunDetail" value="<?= date('Y') ?>">
        </div>
      </div>
      <button class="btn-primary" onclick="loadRekapDetail()">🔍 Tampilkan</button>
      <button class="btn-success" onclick="exportExcel()">📥 Export Excel</button>
      <button class="btn" onclick="window.print()">🖨️ Print</button>
    </div>
  </div>

  <!-- RINGKASAN TOTAL -->
  <div class="card" id="ringkasanCard">
    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:15px; align-items:center;">
      <div>
        <p style="color:#666; font-size:13px; margin:0;" id="labelPeriode">Periode: Semua</p>
        <p style="font-size:22px; color:#27ae60; font-weight:bold; margin:5px 0;">
          Total: <span id="totalRekap">Rp 0</span>
        </p>
        <p style="color:#666; font-size:12px; margin:0;" id="infoJumlah"></p>
      </div>
      <div id="boxPegawai" style="display:none; background:#e8f4ff; padding:12px 20px; border-radius:8px;">
        <p style="color:#1e3c72; font-size:13px; margin:0; font-weight:600;">👤 <span id="namaPegawaiTerpilih"></span></p>
        <p style="color:#27ae60; font-size:18px; font-weight:bold; margin:5px 0 0;">
          Total: <span id="totalPegawai">Rp 0</span>
        </p>
        <p style="color:#666; font-size:11px; margin:3px 0 0;">
          <span id="periodePegawai"></span> • <span id="jumlahBulanPegawai"></span> bulan
        </p>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table id="tabelRekap">
        <thead>
          <tr>
            <th>No</th><th>Nama</th><th>Keterangan</th><th>Bulan</th><th>Tahun</th>
            <th>Jam</th><th>Per Jam</th><th>Jumlah</th>
            <th>Transport</th><th>Jab1</th><th>Jab2</th><th>Total</th>
            <th>Pot.Hadir</th><th>BPD</th><th>BPJS</th><th>Sosial</th>
            <th>Jumlah Akhir</th><th class="no-print">Aksi</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<style>
.tab-btn { background: #eef3fb; color: #1e3c72; }
.tab-btn.active { background: #1e3c72; color: #fff; }
</style>

<script>
let currentTab = 'bulanan';

function switchTab(tab) {
  currentTab = tab;
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelector(`.tab-btn[data-tab="${tab}"]`).classList.add('active');
  ['bulanan','tahunan','pegawai'].forEach(t => {
    document.getElementById('tab-' + t).classList.toggle('hidden', t !== tab);
  });
  document.getElementById('boxPegawai').style.display = 'none';
  loadCurrentTab();
}

function loadCurrentTab() {
  if (currentTab === 'bulanan') loadRekap();
  else if (currentTab === 'tahunan') loadRekapTahunan();
  else {
    const guru = document.getElementById('fGuruDetail').value;
    if (guru) loadRekapDetail();
    else renderTable({data:[], total:0});
  }
}

async function loadGuruList() {
  const r = await fetch('../api/guru.php?action=list').then(x=>x.json());
  ['fGuru','fGuruTahunan','fGuruDetail'].forEach(id => {
    const sel = document.getElementById(id);
    if (!sel) return;
    sel.innerHTML = '<option value="">-- Semua --</option>';
    r.forEach(g => sel.innerHTML += `<option value="${g.id}">${g.no_urut}. ${g.nama}</option>`);
  });
}

function rp(n) { return 'Rp ' + (+n).toLocaleString('id-ID'); }

async function loadRekap() {
  const bulanDari = document.getElementById('fBulanDari').value;
  const bulanSampai = document.getElementById('fBulanSampai').value;
  const tahunDari = document.getElementById('fTahunDari').value;
  const tahunSampai = document.getElementById('fTahunSampai').value;
  const guru = document.getElementById('fGuru').value;

  const url = `../api/rekap.php?bulan_dari=${bulanDari}&bulan_sampai=${bulanSampai}&tahun_dari=${tahunDari}&tahun_sampai=${tahunSampai}&guru_id=${guru}`;
  const r = await fetch(url).then(x=>x.json());
  
  let label = 'Periode: ';
  if (bulanDari && bulanSampai) label += `${bulanDari} - ${bulanSampai} `;
  if (tahunDari && tahunSampai) label += `${tahunDari} - ${tahunSampai}`;
  if (!bulanDari && !tahunDari) label += 'Semua';
  document.getElementById('labelPeriode').textContent = label;
  document.getElementById('boxPegawai').style.display = 'none';
  
  renderTable(r);
}

async function loadRekapTahunan() {
  const tahun = document.getElementById('fTahunOnly').value;
  const guru = document.getElementById('fGuruTahunan').value;
  const url = `../api/rekap.php?tahun_dari=${tahun}&tahun_sampai=${tahun}&guru_id=${guru}`;
  const r = await fetch(url).then(x=>x.json());
  document.getElementById('labelPeriode').textContent = `Periode: Tahun ${tahun}`;
  document.getElementById('boxPegawai').style.display = 'none';
  renderTable(r);
}

async function loadRekapDetail() {
  const guruId = document.getElementById('fGuruDetail').value;
  const tahun = document.getElementById('fTahunDetail').value;
  if (!guruId) { alert('Pilih pegawai dulu!'); return; }
  
  const url = `../api/rekap.php?tahun_dari=${tahun}&tahun_sampai=${tahun}&guru_id=${guruId}`;
  const r = await fetch(url).then(x=>x.json());
  
  // Hitung total khusus pegawai (semua baris)
  const totalPegawai = r.data.reduce((sum, d) => sum + parseFloat(d.jumlah_akhir), 0);
  const bulanUnik = new Set(r.data.map(d => d.bulan + ' ' + d.tahun));
  const namaGuru = r.data.length > 0 ? r.data[0].nama : '-';
  
  document.getElementById('labelPeriode').textContent = `Periode: Tahun ${tahun}`;
  document.getElementById('boxPegawai').style.display = 'block';
  document.getElementById('namaPegawaiTerpilih').textContent = namaGuru;
  document.getElementById('totalPegawai').textContent = rp(totalPegawai);
  document.getElementById('jumlahBulanPegawai').textContent = bulanUnik.size;
  document.getElementById('periodePegawai').textContent = [...bulanUnik].join(' • ');
  
  renderTable(r);
}

function renderTable(r) {
  const tbody = document.querySelector('#tabelRekap tbody');
  tbody.innerHTML = '';
  r.data.forEach((d, i) => {
    tbody.innerHTML += `
      <tr>
        <td>${i+1}</td>
        <td>${d.nama}</td>
        <td>${d.keterangan || '-'}</td>
        <td>${d.bulan}</td>
        <td>${d.tahun}</td>
        <td>${d.jam}</td>
        <td>${rp(d.per_jam)}</td>
        <td>${rp(d.jumlah)}</td>
        <td>${rp(d.transport)}</td>
        <td>${rp(d.jabatan1_rp)}</td>
        <td>${rp(d.jabatan2_rp)}</td>
        <td>${rp(d.total)}</td>
        <td>${rp(d.pot_kehadiran)}</td>
        <td>${rp(d.pot_bpd)}</td>
        <td>${rp(d.pot_bpjs)}</td>
        <td>${rp(d.pot_sosial)}</td>
        <td><b>${rp(d.jumlah_akhir)}</b></td>
        <td class="no-print">
          <button class="btn-danger btn-sm" onclick="hapus(${d.id})">🗑️</button>
        </td>
      </tr>`;
  });
  document.getElementById('totalRekap').textContent = rp(r.total);
  document.getElementById('infoJumlah').textContent = `${r.data.length} baris data`;
}

function resetFilter() {
  document.getElementById('fBulanDari').value = '';
  document.getElementById('fBulanSampai').value = '';
  document.getElementById('fTahunDari').value = '<?= date('Y') ?>';
  document.getElementById('fTahunSampai').value = '<?= date('Y') ?>';
  document.getElementById('fGuru').value = '';
  loadRekap();
}

async function hapus(id) {
  if (!confirm('Yakin hapus data ini?')) return;
  await fetch('../api/gaji.php?action=delete&id=' + id);
  toast('✅ Data dihapus');
  loadCurrentTab();
}

function exportExcel() {
  const table = document.getElementById('tabelRekap').outerHTML;
  const judul = currentTab === 'bulanan' ? 'Rekap_Bulanan' : currentTab === 'tahunan' ? 'Rekap_Tahunan' : 'Rekap_Per_Pegawai';
  const total = document.getElementById('totalRekap').textContent;
  
  let infoTambahan = '';
  if (currentTab === 'pegawai' && document.getElementById('boxPegawai').style.display !== 'none') {
    infoTambahan = `<p><b>Pegawai:</b> ${document.getElementById('namaPegawaiTerpilih').textContent}</p>
                    <p><b>Total:</b> ${document.getElementById('totalPegawai').textContent}</p>
                    <p><b>Jumlah Bulan:</b> ${document.getElementById('jumlahBulanPegawai').textContent}</p>`;
  }
  
  const html = `<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"></head>
    <body>
      <h2>${judul.replace(/_/g,' ')} - SMP MU Boarding School</h2>
      ${infoTambahan}
      <p><b>Total Keseluruhan:</b> ${total}</p>
      ${table}
    </body></html>`;
  const blob = new Blob([html], {type:'application/vnd.ms-excel'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = judul + '_' + new Date().toISOString().slice(0,10) + '.xls';
  a.click(); toast('✅ Excel diunduh');
}

function toast(msg, err=false) {
  const t = document.createElement('div');
  t.className = 'toast' + (err?' error':''); t.textContent = msg;
  document.body.appendChild(t); setTimeout(()=>t.remove(), 3000);
}

loadGuruList().then(() => {
  const urlParams = new URLSearchParams(window.location.search);
  const guruParam = urlParams.get('guru_id');
  if (guruParam) {
    document.getElementById('fGuruDetail').value = guruParam;
    switchTab('pegawai');
    loadRekapDetail();
  } else {
    loadRekap();
  }
});
</script>
</body>
</html>