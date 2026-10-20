<?php
require_once '../config/database.php';
requireLogin();
header('Content-Type: application/json');
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $sql = "SELECT g.*, k.nama AS kategori FROM guru g JOIN kategori k ON g.kategori_id=k.id WHERE g.aktif=1 ORDER BY g.no_urut";
    echo json_encode($pdo->query($sql)->fetchAll());
    exit;
}
if ($action === 'reset_kode' && isset($_GET['id'])) {
    $newKode = 'MU' . str_pad($_GET['id'], 3, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5(rand()), 0, 3));
    $pdo->prepare("UPDATE guru SET kode_unik=? WHERE id=?")->execute([$newKode, $_GET['id']]);
    logAktivitas($pdo, 'RESET_KODE', "Reset kode ID {$_GET['id']}");
    echo json_encode(['status'=>'ok','kode'=>$newKode]);
    exit;
}
?>