<?php
require_once '../config/database.php';
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    try {
        $pdo->prepare("INSERT INTO admin (username, password, nama_lengkap, role) VALUES (?,?,?,?)")
            ->execute([
                trim($_POST['username']),
                hashPassword($_POST['password']),
                trim($_POST['nama']),
                $_POST['role']
            ]);
        $msg = 'Admin baru ditambahkan!';
    } catch (PDOException $e) { $err = 'Gagal: ' . $e->getMessage(); }
}
if (isset($_GET['del'])) {
    $pdo->prepare("DELETE FROM admin WHERE id=? AND username != 'superadmin'")->execute([$_GET['del']]);
    header('Location: users.php'); exit;
}
$admins = $pdo->query("SELECT * FROM admin ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="navbar">
  <h2>👥 Manajemen Admin</h2>
  <div class="user-info">
    <span>👤 <?= htmlspecialchars($_SESSION['nama_lengkap']) ?> (SuperAdmin)</span>
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
    <a href="kelola.php">🗂️ Kelola Data</a>
    <a href="kode.php">🔑 Kode Guru</a>
    <a href="users.php" class="active">👥 Manajemen Admin</a>
  </div>
  <?php if (!empty($msg)): ?><div class="info-box" style="background:#d4edda;">✅ <?= $msg ?></div><?php endif; ?>
  <?php if (!empty($err)): ?><div class="info-box" style="background:#f8d7da;">❌ <?= $err ?></div><?php endif; ?>
  <div class="card">
    <h3>Tambah Admin</h3>
    <form method="POST">
      <div class="row">
        <div><label>Username</label><input name="username" required></div>
        <div><label>Nama Lengkap</label><input name="nama" required></div>
        <div><label>Password</label><input type="password" name="password" required></div>
        <div><label>Role</label>
          <select name="role"><option value="admin">Admin</option><option value="superadmin">Super Admin</option></select>
        </div>
      </div>
      <button type="submit" name="add" class="btn-success">➕ Tambah</button>
    </form>
  </div>
  <div class="card">
    <h3>Daftar Admin</h3>
    <div class="table-wrap">
      <table>
        <tr><th>No</th><th>Username</th><th>Nama</th><th>Role</th><th>Aksi</th></tr>
        <?php foreach ($admins as $i => $a): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($a['username']) ?></td>
            <td><?= htmlspecialchars($a['nama_lengkap']) ?></td>
            <td><span class="badge <?= $a['role']=='superadmin'?'':'badge-success' ?>"><?= $a['role'] ?></span></td>
            <td>
              <?php if ($a['username'] !== 'superadmin' && $a['id'] != $_SESSION['admin_id']): ?>
                <a href="?del=<?= $a['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')">🗑️</a>
              <?php else: ?>—<?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
</div>
</body>
</html>