<?php
require_once '../config/database.php';
if (isset($_SESSION['guru_id'])) { header('Location: slip.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = strtoupper(trim($_POST['kode'] ?? ''));
    $stmt = $pdo->prepare("SELECT * FROM guru WHERE kode_unik = ? AND aktif=1");
    $stmt->execute([$kode]);
    $g = $stmt->fetch();

    if (!$g) {
        $error = 'Kode tidak valid!';
    } else {
        $cek = $pdo->prepare("SELECT COUNT(*) FROM gaji WHERE guru_id=?");
        $cek->execute([$g['id']]);
        if ($cek->fetchColumn() == 0) {
            $error = 'Belum ada slip gaji untuk Anda. Hubungi admin.';
        } else {
            $_SESSION['guru_id'] = $g['id'];
            $_SESSION['guru_nama'] = $g['nama'];
            header('Location: slip.php'); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Guru - SMP MU Boarding School</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-bg">
<div class="login-container">
  <div class="login-card" style="text-align:center;">
    <img src="../assets/img/logo-smpmu.jpeg" alt="Logo SMP MU" 
         style="width:90px; height:90px; object-fit:contain; border-radius:50%; 
                background:#fff; padding:5px; box-shadow:0 4px 12px rgba(0,0,0,0.15); 
                margin:0 auto 15px; display:block;">
    <h1 style="margin-bottom:5px;">🎓 Slip Gaji Guru</h1>
    <p style="color:#666; margin-bottom:20px;">SMP MU Boarding School</p>
    <hr style="margin:0 0 20px 0;">
    <h3 style="text-align:left;">Login Pegawai</h3>
    <?php if ($error): ?><p class="error" style="text-align:left;">❌ <?= $error ?></p><?php endif; ?>
    <form method="POST" style="text-align:left;">
      <input type="text" name="kode" placeholder="Masukkan Kode Unik" required autofocus
             style="text-transform:uppercase; text-align:center; font-size:18px; letter-spacing:2px;">
      <button type="submit" class="btn-success" style="width:100%;">Masuk</button>
    </form>
    <div class="info-box" style="margin-top:20px; font-size:12px; text-align:left;">
      💡 Kode ini berlaku selamanya. Masukkan setiap kali ingin melihat slip gaji.
    </div>
  </div>
</div>
</body>
</html>