<?php
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'database';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// ============ SESUAIKAN DENGAN NAMA FOLDER ANDA ============
define('BASE_URL', 'http://localhost/slip_gaji_mu/');

// ============ HELPER ============
function isLogin() { return isset($_SESSION['admin_id']); }
function isSuperAdmin() { return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'; }
function requireLogin() { if (!isLogin()) { header('Location: ' . BASE_URL . 'login.php'); exit; } }
function requireSuperAdmin() { requireLogin(); if (!isSuperAdmin()) die('Akses ditolak.'); }

function logAktivitas($pdo, $aksi, $detail) {
    $stmt = $pdo->prepare("INSERT INTO log_aktivitas (admin_id, aksi, detail) VALUES (?,?,?)");
    $stmt->execute([$_SESSION['admin_id'] ?? null, $aksi, $detail]);
}

function formatRupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

// ============ HASH PASSWORD (SHA-256) ============
// Ganti fungsi password_verify (bcrypt) dengan SHA-256 agar tidak ada masalah
function hashPassword($password) {
    return hash('sha256', $password);
}
function verifyPassword($password, $hash) {
    return hash('sha256', $password) === $hash;
}
?>