<?php
require_once '../config/database.php';
requireLogin();
$gurus = $pdo->query("SELECT g.*, k.nama AS kategori FROM guru g JOIN kategori k ON g.kategori_id=k.id WHERE g.aktif=1 ORDER BY g.no_urut ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengisian Gaji</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="navbar">
  <h2>📝 Pengisian Gaji Pegawai</h2>
  <div class="user-info">
    <span>👤 <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
    <button class="btn btn-warn btn-sm" onclick="location.reload()">🔄 Refresh</button>
    <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
  </div>
</div>
<div class="container">
  <div class="menu-bar">
    <a href="index.php">🏠 Dashboard</a>
    <a href="input.php" class="active">📝 Pengisian Gaji</a>
    <a href="import.php">📥 Import Excel</a>
    <a href="rekap.php">📊 Rekap Gaji</a>
    <a href="kelola.php">🗂️ Kelola Data</a>
    <a href="kode.php">🔑 Kode Guru</a>
    <?php if (isSuperAdmin()): ?><a href="users.php">👥 Manajemen Admin</a><?php endif; ?>
  </div>

  <div class="card">
    <h3>Pilih Pegawai (<?= count($gurus) ?> orang)</h3>
    <div class="guru-grid" id="guruGrid">
      <?php foreach ($gurus as $g):
        $jab = implode(' / ', array_filter([$g['jabatan1'], $g['jabatan2'], $g['jabatan3'], $g['jabatan4']]));
      ?>
        <div class="guru-item" data-id="<?= $g['id'] ?>" data-nama="<?= htmlspecialchars($g['nama']) ?>">
          <div class="nama"><?= $g['no_urut'] ?>. <?= htmlspecialchars($g['nama']) ?></div>
          <div class="jabatan">
            <?= htmlspecialchars($jab) ?>
            <br><span class="badge"><?= $g['kategori'] ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card hidden" id="formCard">
    <h3 id="formTitle">Form Gaji</h3>
    <input type="hidden" id="guruId">
    <div class="row">
      <div><label>Nama</label><input type="text" id="inpNama" readonly></div>
      <div><label>Bulan</label>
        <select id="inpBulan">
          <?php foreach (['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $b): ?>
            <option><?= $b ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Tahun</label><input type="number" id="inpTahun" value="<?= date('Y') ?>"></div>
    </div>

    <div id="barisContainer"></div>

    <button class="btn-warn btn-sm" onclick="tambahBaris()" style="margin-top:10px;">➕ Tambah Baris Gaji (untuk jabatan ganda)</button>

    <div class="info-box" style="margin-top:15px;">
      <b style="font-size:17px; color:#27ae60;">TOTAL SEMUA BARIS: <span id="lblGrandTotal">Rp 0</span></b>
    </div>
    <button class="btn-success" onclick="simpan()">💾 Simpan Semua</button>
    <button class="btn-warn" onclick="resetForm()">🔄 Reset</button>
  </div>
</div>

<script>
let selectedId = null;

document.querySelectorAll('.guru-item').forEach(el => {
  el.onclick = () => {
    document.querySelectorAll('.guru-item').forEach(x => x.classList.remove('selected'));
    el.classList.add('selected');
    selectedId = el.dataset.id;
    document.getElementById('guruId').value = selectedId;
    document.getElementById('inpNama').value = el.dataset.nama;
    document.getElementById('formTitle').textContent = 'Form Gaji: ' + el.dataset.nama;
    document.getElementById('formCard').classList.remove('hidden');
    resetForm();
    document.getElementById('formCard').scrollIntoView({behavior:'smooth'});
  };
});

function rp(n) { return 'Rp ' + (+n).toLocaleString('id-ID'); }

function buatBarisGaji(keterangan='', jam=50, perJam=25000, transport=172000, jab1=500000, jab2=250000, potH=12000, potB=0, potBpjs=10800, potS=30000) {
  const div = document.createElement('div');
  div.className = 'gaji-baris';
  div.innerHTML = `
    <button type="button" class="btn-danger btn-sm hapus-baris" onclick="this.parentElement.remove(); hitungSemua();">✖ Hapus</button>
    <div class="row">
      <div><label>Keterangan Jabatan (opsional)</label><input type="text" class="keterangan" value="${keterangan}" placeholder="cth: Bend BOS / Operator"></div>
    </div>
    <h4>💰 Jam & Honor</h4>
    <div class="row row-3">
      <div><label>Jam (F)</label><input type="number" class="jam" value="${jam}" oninput="hitungSemua()"></div>
      <div><label>Per Jam (G)</label><input type="number" class="perJam" value="${perJam}" oninput="hitungSemua()"></div>
      <div><label>Jumlah H=F×G</label><input type="text" class="jumlah" readonly></div>
    </div>
    <h4>🎁 Tunjangan</h4>
    <div class="row row-3">
      <div><label>Transport (I)</label><input type="number" class="transport" value="${transport}" oninput="hitungSemua()"></div>
      <div><label>Jabatan 1 (J)</label><input type="number" class="jab1" value="${jab1}" oninput="hitungSemua()"></div>
      <div><label>Jabatan 2 (K)</label><input type="number" class="jab2" value="${jab2}" oninput="hitungSemua()"></div>
    </div>
    <h4>📉 Potongan</h4>
    <div class="row row-3">
      <div><label>Kehadiran (M)</label><input type="number" class="potH" value="${potH}" oninput="hitungSemua()"></div>
      <div><label>BPD (N)</label><input type="number" class="potB" value="${potB}" oninput="hitungSemua()"></div>
      <div><label>BPJS (O)</label><input type="number" class="potBpjs" value="${potBpjs}" oninput="hitungSemua()"></div>
      <div><label>Sosial (P)</label><input type="number" class="potS" value="${potS}" oninput="hitungSemua()"></div>
    </div>
    <div class="info-box">
      <b>Total (L):</b> <span class="lblTotal">Rp 0</span> |
      <b>Potongan:</b> <span class="lblPot" style="color:#c0392b;">Rp 0</span> |
      <b style="color:#27ae60;">Jumlah Akhir (Q): <span class="lblAkhir">Rp 0</span></b>
    </div>
  `;
  document.getElementById('barisContainer').appendChild(div);
  hitungSemua();
}

function tambahBaris() {
  buatBarisGaji('', 50, 25000, 0, 0, 0, 0, 0, 0, 0);
}

function hitungSemua() {
  let grand = 0;
  document.querySelectorAll('.gaji-baris').forEach(b => {
    const jam = +b.querySelector('.jam').value || 0;
    const perJam = +b.querySelector('.perJam').value || 0;
    const transport = +b.querySelector('.transport').value || 0;
    const jab1 = +b.querySelector('.jab1').value || 0;
    const jab2 = +b.querySelector('.jab2').value || 0;
    const pot = (+b.querySelector('.potH').value||0) + (+b.querySelector('.potB').value||0)
              + (+b.querySelector('.potBpjs').value||0) + (+b.querySelector('.potS').value||0);

    const jumlah = jam * perJam;
    b.querySelector('.jumlah').value = rp(jumlah);
    const total = jumlah + transport + jab1 + jab2;
    const akhir = total - pot;

    b.querySelector('.lblTotal').textContent = rp(total);
    b.querySelector('.lblPot').textContent = rp(pot);
    b.querySelector('.lblAkhir').textContent = rp(akhir);
    grand += akhir;
  });
  document.getElementById('lblGrandTotal').textContent = rp(grand);
}

function resetForm() {
  document.getElementById('barisContainer').innerHTML = '';
  buatBarisGaji('', 50, 25000, 172000, 500000, 250000, 12000, 0, 10800, 30000);
  hitungSemua();
}

async function simpan() {
  const guru_id = +document.getElementById('guruId').value;
  const bulan = document.getElementById('inpBulan').value;
  const tahun = +document.getElementById('inpTahun').value;

  const baris = [];
  document.querySelectorAll('.gaji-baris').forEach((b, i) => {
    baris.push({
      baris_ke: i+1,
      keterangan: b.querySelector('.keterangan').value,
      jam: +b.querySelector('.jam').value || 0,
      per_jam: +b.querySelector('.perJam').value || 0,
      transport: +b.querySelector('.transport').value || 0,
      jabatan1_rp: +b.querySelector('.jab1').value || 0,
      jabatan2_rp: +b.querySelector('.jab2').value || 0,
      pot_kehadiran: +b.querySelector('.potH').value || 0,
      pot_bpd: +b.querySelector('.potB').value || 0,
      pot_bpjs: +b.querySelector('.potBpjs').value || 0,
      pot_sosial: +b.querySelector('.potS').value || 0
    });
  });

  const res = await fetch('../api/gaji.php?action=save_multi', {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({guru_id, bulan, tahun, baris})
  });
  const r = await res.json();
  if (r.status === 'ok') { toast('✅ Gaji berhasil disimpan!'); resetForm(); }
  else toast('❌ ' + r.msg, true);
}

function toast(msg, err=false) {
  const t = document.createElement('div');
  t.className = 'toast' + (err?' error':''); t.textContent = msg;
  document.body.appendChild(t); setTimeout(() => t.remove(), 3000);
}
</script>
</body>
</html>