<?php
require_once '../config/database.php';
requireLogin();
header('Content-Type: application/json');

$bulan_dari = $_GET['bulan_dari'] ?? '';
$bulan_sampai = $_GET['bulan_sampai'] ?? '';
$tahun_dari = $_GET['tahun_dari'] ?? '';
$tahun_sampai = $_GET['tahun_sampai'] ?? '';
$guru_id = $_GET['guru_id'] ?? '';

// Support backward compatibility
$bulan = $_GET['bulan'] ?? '';
$tahun = $_GET['tahun'] ?? '';
if ($bulan) $bulan_dari = $bulan_sampai = $bulan;
if ($tahun && !$tahun_dari) { $tahun_dari = $tahun_sampai = $tahun; }

$sql = "SELECT g.*, gr.nama, gr.no_urut FROM gaji g JOIN guru gr ON g.guru_id=gr.id WHERE 1=1";
$params = [];

if ($bulan_dari && $bulan_sampai) {
    $urutanBulan = ['Januari'=>1,'Februari'=>2,'Maret'=>3,'April'=>4,'Mei'=>5,'Juni'=>6,
                    'Juli'=>7,'Agustus'=>8,'September'=>9,'Oktober'=>10,'November'=>11,'Desember'=>12];
    $dari = $urutanBulan[$bulan_dari] ?? 1;
    $sampai = $urutanBulan[$bulan_sampai] ?? 12;
    $listBulan = [];
    foreach ($urutanBulan as $nama => $num) {
        if ($num >= $dari && $num <= $sampai) $listBulan[] = $nama;
    }
    if ($listBulan) {
        $placeholder = implode(',', array_fill(0, count($listBulan), '?'));
        $sql .= " AND g.bulan IN ($placeholder)";
        $params = array_merge($params, $listBulan);
    }
}

if ($tahun_dari && $tahun_sampai) {
    $sql .= " AND g.tahun BETWEEN ? AND ?";
    $params[] = (int)$tahun_dari;
    $params[] = (int)$tahun_sampai;
} elseif ($tahun_dari) {
    $sql .= " AND g.tahun = ?";
    $params[] = (int)$tahun_dari;
}

if ($guru_id) {
    $sql .= " AND g.guru_id=?";
    $params[] = (int)$guru_id;
}

$sql .= " ORDER BY g.tahun DESC, FIELD(g.bulan,'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember') DESC, gr.no_urut ASC, g.baris_ke ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();
$total = array_sum(array_column($data, 'jumlah_akhir'));

echo json_encode(['data' => $data, 'total' => $total]);
?>