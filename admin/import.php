<?php
require_once '../config/database.php';
requireLogin();

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
} else {
    die('<div style="padding:20px; font-family:sans-serif;">
        <h2 style="color:#c0392b;">❌ Library belum terinstall</h2>
        <p>Folder <code>vendor/</code> tidak ditemukan. Jalankan di terminal VS Code:</p>
        <pre style="background:#f0f0f0; padding:12px; border-radius:6px;">cd "C:\xampp\htdocs\Slip gaji MU"
composer install --ignore-platform-req=ext-gd</pre>
    </div>');
}

use PhpOffice\PhpSpreadsheet\IOFactory;

$pesan = ''; $error = ''; $log = []; $success = 0; $failed = 0; $jabatan_updated = 0;

function bersihkanAngka($val) {
    if ($val === null || $val === '') return 0;
    if (is_numeric($val)) return (float) $val;
    $s = trim((string)$val);
    $s = preg_replace('/[^0-9,.\-]/', '', $s);
    if ($s === '' || $s === '-') return 0;
    $adaKoma = strpos($s, ',') !== false;
    $adaTitik = strpos($s, '.') !== false;
    if ($adaKoma && $adaTitik) {
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
    } elseif ($adaKoma) {
        $posKoma = strrpos($s, ',');
        $setelahKoma = substr($s, $posKoma + 1);
        if (strlen($setelahKoma) == 3 && strpos($s, ',') === $posKoma) {
            $s = str_replace(',', '', $s);
        } else {
            $s = str_replace(',', '.', $s);
        }
    } elseif ($adaTitik) {
        $posTitik = strrpos($s, '.');
        $setelahTitik = substr($s, $posTitik + 1);
        if (strlen($setelahTitik) == 3 && strpos($s, '.') === $posTitik) {
            $s = str_replace('.', '', $s);
        }
    }
    return (float) $s;
}

function bacaCell($sheet, $col, $row) {
    $cell = $sheet->getCell($col . $row);
    try {
        $val = $cell->getCalculatedValue();
    } catch (\Exception $e) {
        $val = $cell->getValue();
    }
    if ($val === null || $val === '') {
        $val = $sheet->getCell($col . $row)->getFormattedValue();
    }
    return $val;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
    $file = $_FILES['file_excel']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ['xlsx','xls'])) {
        $error = 'Format file harus .xlsx atau .xls';
    } else {
        try {
            $spreadsheet = IOFactory::load($file);
            $sheetNames = $spreadsheet->getSheetNames();
            $sheetTahun = $sheetNames[0];
            $tahun = (int) preg_replace('/[^0-9]/', '', $sheetTahun);
            if ($tahun < 2000) $tahun = date('Y');

            $sheet = $spreadsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow();

            $guruMap = [];
            foreach ($pdo->query("SELECT id, nama FROM guru") as $g) {
                $guruMap[strtolower(trim($g['nama']))] = $g['id'];
            }

            $bulanInput = $_POST['bulan'] ?? 'Januari';
            $skipRows = [1,2,3,4,5, 29, 30, 35, 36, 37, 38, 39, 40];
            $barisLanjutan = [15 => 9, 32 => 23];

            $pdo->beginTransaction();
            $guru_baris = [];
            $jabatan_tersimpan = []; // hindari duplikat update jabatan

            for ($rowNum = 1; $rowNum <= $highestRow; $rowNum++) {
                if (in_array($rowNum, $skipRows)) continue;

                $nama = trim((string) bacaCell($sheet, 'B', $rowNum));

                if (!empty($nama)) {
                    $key = strtolower($nama);
                    if (!isset($guruMap[$key])) {
                        $failed++;
                        $log[] = "Baris $rowNum: Guru '$nama' tidak ada di database";
                        continue;
                    }
                    $guru_id = $guruMap[$key];
                } elseif (isset($barisLanjutan[$rowNum])) {
                    $guru_id = $barisLanjutan[$rowNum];
                } else {
                    continue;
                }

                $keterangan = trim((string) bacaCell($sheet, 'C', $rowNum));
                if ($keterangan === '') $keterangan = trim((string) bacaCell($sheet, 'D', $rowNum));
                if ($keterangan === '') $keterangan = trim((string) bacaCell($sheet, 'E', $rowNum));

                // ============ SIMPAN JABATAN PER TAHUN ============
                // Hanya baris yang ada nama (bukan baris lanjutan) yang diambil jabatannya
                if (!empty($nama) && !isset($jabatan_tersimpan[$guru_id])) {
                    $jab1 = trim((string) bacaCell($sheet, 'C', $rowNum)) ?: null;
                    $jab2 = trim((string) bacaCell($sheet, 'D', $rowNum)) ?: null;
                    $jab3 = trim((string) bacaCell($sheet, 'E', $rowNum)) ?: null;
                    $jab4 = null; // Excel tidak punya kolom Jab4 khusus

                    // Cek apakah baris berikut adalah lanjutan (misal baris 15 untuk guru 9)
                    // Kalau ya, ambil jabatan tambahannya sebagai jab4
                    if (isset($barisLanjutan[$rowNum + 1]) && $barisLanjutan[$rowNum + 1] == $guru_id) {
                        $nextRow = $rowNum + 1;
                        $nextJab = trim((string) bacaCell($sheet, 'C', $nextRow));
                        if ($nextJab) $jab4 = $nextJab;
                    }

                    $sql = "INSERT INTO jabatan_guru (guru_id, tahun, jabatan1, jabatan2, jabatan3, jabatan4)
                            VALUES (?,?,?,?,?,?)
                            ON DUPLICATE KEY UPDATE
                            jabatan1=VALUES(jabatan1), jabatan2=VALUES(jabatan2),
                            jabatan3=VALUES(jabatan3), jabatan4=VALUES(jabatan4)";
                    $pdo->prepare($sql)->execute([$guru_id, $tahun, $jab1, $jab2, $jab3, $jab4]);
                    $jabatan_tersimpan[$guru_id] = true;
                    $jabatan_updated++;
                }

                // ============ SIMPAN GAJI ============
                $jam       = (int)   bersihkanAngka(bacaCell($sheet, 'F', $rowNum));
                $per_jam   = bersihkanAngka(bacaCell($sheet, 'G', $rowNum));
                $transport = bersihkanAngka(bacaCell($sheet, 'I', $rowNum));
                $jabRp1    = bersihkanAngka(bacaCell($sheet, 'J', $rowNum));
                $jabRp2    = bersihkanAngka(bacaCell($sheet, 'K', $rowNum));
                $potH      = bersihkanAngka(bacaCell($sheet, 'M', $rowNum));
                $potB      = bersihkanAngka(bacaCell($sheet, 'N', $rowNum));
                $potBpjs   = bersihkanAngka(bacaCell($sheet, 'O', $rowNum));
                $potS      = bersihkanAngka(bacaCell($sheet, 'P', $rowNum));

                $jumlah   = $jam * $per_jam;
                $total    = $jumlah + $transport + $jabRp1 + $jabRp2;
                $totalPot = $potH + $potB + $potBpjs + $potS;
                $akhir    = $total - $totalPot;

                if ($jam == 0 && $total == 0 && $akhir == 0) continue;

                if (!isset($guru_baris[$guru_id])) $guru_baris[$guru_id] = 0;
                $guru_baris[$guru_id]++;
                $baris_ke = $guru_baris[$guru_id];

                try {
                    $sql = "INSERT INTO gaji 
                        (guru_id, bulan, tahun, baris_ke, keterangan, jam, per_jam, jumlah, transport, jabatan1_rp, jabatan2_rp,
                         total, pot_kehadiran, pot_bpd, pot_bpjs, pot_sosial, jumlah_akhir, tanggal_input, created_by)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),?)
                        ON DUPLICATE KEY UPDATE
                        keterangan=VALUES(keterangan), jam=VALUES(jam), per_jam=VALUES(per_jam), jumlah=VALUES(jumlah),
                        transport=VALUES(transport), jabatan1_rp=VALUES(jabatan1_rp), jabatan2_rp=VALUES(jabatan2_rp),
                        total=VALUES(total), pot_kehadiran=VALUES(pot_kehadiran), pot_bpd=VALUES(pot_bpd),
                        pot_bpjs=VALUES(pot_bpjs), pot_sosial=VALUES(pot_sosial),
                        jumlah_akhir=VALUES(jumlah_akhir), updated_at=CURRENT_TIMESTAMP";
                    $pdo->prepare($sql)->execute([$guru_id, $bulanInput, $tahun, $baris_ke, $keterangan ?: null,
                                    $jam, $per_jam, $jumlah, $transport, $jabRp1, $jabRp2,
                                    $total, $potH, $potB, $potBpjs, $potS, $akhir, $_SESSION['admin_id']]);
                    $success++;
                } catch (PDOException $e) {
                    $failed++;
                    $log[] = "Baris $rowNum: " . $e->getMessage();
                }
            }
            $pdo->commit();

            logAktivitas($pdo, 'IMPORT_EXCEL', "Import $sheetTahun: $success sukses, $failed gagal, jabatan: $jabatan_updated");
            $pesan = "✅ Import selesai! Sheet: <b>$sheetTahun</b> → Tahun: <b>$tahun</b>.<br>
                      📄 Baris gaji: <b>$success</b> berhasil, <b>$failed</b> gagal<br>
                      👔 Jabatan tersimpan: <b>$jabatan_updated</b> pegawai";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Error baca file: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Import Excel</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="navbar">
  <h2>📥 Import Data Gaji dari Excel</h2>
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
    <a href="import.php" class="active">📥 Import Excel</a>
    <a href="rekap.php">📊 Rekap Gaji</a>
    <a href="kelola.php">🗂️ Kelola Data</a>
    <a href="pegawai.php">👥 Kelola Pegawai</a>
    <a href="kode.php">🔑 Kode Guru</a>
    <?php if (isSuperAdmin()): ?><a href="users.php">👥 Manajemen Admin</a><?php endif; ?>
  </div>

  <?php if ($pesan): ?>
    <div class="info-box" style="background:#d4edda; border-left-color:#27ae60;">
      <?= $pesan ?>
      <?php if ($failed > 0 && $log): ?>
        <details style="margin-top:10px;">
          <summary style="cursor:pointer; color:#c0392b;">Lihat detail (<?= $failed ?> gagal)</summary>
          <ul style="margin:10px 0 0 20px; font-size:12px;">
            <?php foreach ($log as $l): ?><li><?= htmlspecialchars($l) ?></li><?php endforeach; ?>
          </ul>
        </details>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <?php if ($error): ?><div class="info-box" style="background:#f8d7da;">❌ <?= $error ?></div><?php endif; ?>

  <div class="card">
    <h3>📤 Upload File Excel</h3>
    <form method="POST" enctype="multipart/form-data">
      <div class="row">
        <div>
          <label>Bulan untuk data ini</label>
          <select name="bulan" required>
            <?php foreach (['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $b): ?>
              <option <?= $b=='Januari' ? 'selected' : '' ?>><?= $b ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="drop-zone" onclick="document.getElementById('fileInput').click()">
        <input type="file" name="file_excel" id="fileInput" accept=".xlsx,.xls" onchange="showName(this)">
        <p style="font-size:40px;">📁</p>
        <p><b>Klik untuk pilih file</b> atau drag & drop</p>
        <p id="fileName" style="color:#1e3c72; font-weight:bold; margin-top:10px;"></p>
      </div>
      <button type="submit" class="btn-success" style="margin-top:15px; width:100%;">🚀 Proses Import</button>
    </form>
  </div>
</div>
<script>
function showName(input) {
  if (input.files.length > 0) document.getElementById('fileName').textContent = '📎 ' + input.files[0].name;
}
const dz = document.querySelector('.drop-zone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
dz.addEventListener('drop', e => {
  e.preventDefault(); dz.classList.remove('dragover');
  document.getElementById('fileInput').files = e.dataTransfer.files;
  showName(document.getElementById('fileInput'));
});
</script>
</body>
</html>