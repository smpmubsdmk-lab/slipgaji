<?php
require_once 'config/database.php';
if (isLogin()) { header('Location: ' . BASE_URL . 'admin/index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && verifyPassword($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['username'] = $admin['username'];
        $_SESSION['nama_lengkap'] = $admin['nama_lengkap'];
        $_SESSION['role'] = $admin['role'];
        logAktivitas($pdo, 'LOGIN', 'Login berhasil');
        header('Location: ' . BASE_URL . 'admin/index.php'); exit;
    }
    $error = 'Username atau password salah!';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin - SMP MU Boarding School</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-bg">
<div class="login-container">
  <div class="login-card" style="text-align:center;">
    <img src="assets/img/logo-smpmu.jpeg" alt="Logo SMP MU" 
         style="width:90px; height:90px; object-fit:contain; border-radius:50%; 
                background:#fff; padding:5px; box-shadow:0 4px 12px rgba(0,0,0,0.15); 
                margin:0 auto 15px; display:block;">
    <h1 style="margin-bottom:5px;">🎓 Slip Gaji Guru</h1>
    <p style="color:#666; margin-bottom:20px;">SMP MU Boarding School</p>
    <hr style="margin:0 0 20px 0;">
    <h3 style="text-align:left;">Login Admin</h3>
    <?php if ($error): ?><p class="error" style="text-align:left;">❌ <?= $error ?></p><?php endif; ?>
    <form method="POST" style="text-align:left;">
      <input type="text" name="username" placeholder="Username" required autofocus>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" style="width:100%;">Masuk</button>
    </form>

    <!-- TOMBOL LOGIN GURU (hanya di halaman admin) -->
    <div style="margin-top:20px; padding-top:15px; border-top:1px dashed #ddd;">
      <p style="font-size:13px; color:#666; margin:0 0 8px;">Bukan admin?</p>
      <a href="guru/" class="btn btn-success" 
         style="width:100%; text-decoration:none; display:block; box-sizing:border-box;">
        👨‍🏫 Login sebagai Guru
      </a>
    </div>

    <p style="font-size:11px; color:#999; margin-top:15px;">Default: superadmin / admin123</p>
  </div>
</div>
</body>
</html>