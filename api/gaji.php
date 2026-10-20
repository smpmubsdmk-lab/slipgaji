<?php
require_once '../config/database.php';
requireLogin();
header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

if ($action === 'save_multi' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    $guru_id = (int)$d['guru_id'];
    $bulan = $d['bulan'];
    $tahun = (int)$d['tahun'];

    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM gaji WHERE guru_id=? AND bulan=? AND tahun=?")
            ->execute([$guru_id, $bulan, $tahun]);

        $barisKe = 0;
        foreach ($d['baris'] as $b) {
            $barisKe++;
            $jam = (int)$b['jam'];
            $perJam = (float)$b['per_jam'];
            $transport = (float)$b['transport'];
            $jab1 = (float)$b['jabatan1_rp'];
            $jab2 = (float)$b['jabatan2_rp'];
            $potH = (float)$b['pot_kehadiran'];
            $potB = (float)$b['pot_bpd'];
            $potBpjs = (float)$b['pot_bpjs'];
            $potS = (float)$b['pot_sosial'];

            $jumlah = $jam * $perJam;
            $total = $jumlah + $transport + $jab1 + $jab2;
            $totalPot = $potH + $potB + $potBpjs + $potS;
            $akhir = $total - $totalPot;

            $sql = "INSERT INTO gaji (guru_id, bulan, tahun, baris_ke, keterangan, jam, per_jam, jumlah,
                    transport, jabatan1_rp, jabatan2_rp, total, pot_kehadiran, pot_bpd, pot_bpjs, pot_sosial,
                    jumlah_akhir, tanggal_input, created_by)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),?)";
            $pdo->prepare($sql)->execute([$guru_id, $bulan, $tahun, $barisKe, $b['keterangan'] ?: null,
                $jam, $perJam, $jumlah, $transport, $jab1, $jab2, $total,
                $potH, $potB, $potBpjs, $potS, $akhir, $_SESSION['admin_id']]);
        }
        $pdo->commit();
        logAktivitas($pdo, 'SIMPAN_GAJI', "Guru ID $guru_id - $bulan $tahun ($barisKe baris)");
        echo json_encode(['status'=>'ok']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
    }
    exit;
}
if ($action === 'delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM gaji WHERE id=?")->execute([$_GET['id']]);
    logAktivitas($pdo, 'HAPUS_GAJI', "Hapus gaji ID {$_GET['id']}");
    echo json_encode(['status'=>'ok']);
    exit;
}
?>