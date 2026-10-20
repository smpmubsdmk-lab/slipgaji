<?php
require_once '../config/database.php';
requireLogin();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ============ INFO DATABASE ============
if ($action === 'info') {
    $total_pegawai = $pdo->query("SELECT COUNT(*) FROM guru WHERE aktif=1")->fetchColumn();
    $total_gaji_rows = $pdo->query("SELECT COUNT(*) FROM gaji")->fetchColumn();
    $total_nominal = $pdo->query("SELECT COALESCE(SUM(jumlah_akhir),0) FROM gaji")->fetchColumn();
    $periode = $pdo->query("SELECT GROUP_CONCAT(DISTINCT CONCAT(bulan,' ',tahun) ORDER BY tahun DESC SEPARATOR ', ') FROM gaji")->fetchColumn();
    echo json_encode([
        'total_pegawai' => $total_pegawai,
        'total_gaji_rows' => $total_gaji_rows,
        'total_nominal' => $total_nominal,
        'periode' => $periode ?: 'Belum ada data'
    ]);
    exit;
}

// ============ REFRESH / HITUNG ULANG ============
if ($action === 'refresh') {
    try {
        $stmt = $pdo->query("SELECT id, jam, per_jam, transport, jabatan1_rp, jabatan2_rp,
                             pot_kehadiran, pot_bpd, pot_bpjs, pot_sosial FROM gaji");
        $rows = $stmt->fetchAll();
        $updated = 0;
        $update = $pdo->prepare("UPDATE gaji SET jumlah=?, total=?, jumlah_akhir=? WHERE id=?");
        foreach ($rows as $r) {
            $jumlah = $r['jam'] * $r['per_jam'];
            $total = $jumlah + $r['transport'] + $r['jabatan1_rp'] + $r['jabatan2_rp'];
            $totalPot = $r['pot_kehadiran'] + $r['pot_bpd'] + $r['pot_bpjs'] + $r['pot_sosial'];
            $akhir = $total - $totalPot;
            $update->execute([$jumlah, $total, $akhir, $r['id']]);
            $updated++;
        }
        logAktivitas($pdo, 'REFRESH_DATA', "Refresh $updated baris");
        echo json_encode(['status'=>'ok', 'msg'=>"Refresh selesai! $updated baris dihitung ulang."]);
    } catch (PDOException $e) {
        echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
    }
    exit;
}

// ============ CEK KONSISTENSI ============
if ($action === 'cek') {
    try {
        $stmt = $pdo->query("SELECT g.id, g.jam, g.per_jam, g.transport, g.jabatan1_rp, g.jabatan2_rp,
                             g.pot_kehadiran, g.pot_bpd, g.pot_bpjs, g.pot_sosial,
                             g.jumlah, g.total, g.jumlah_akhir, gr.nama AS nama_guru
                             FROM gaji g JOIN guru gr ON g.guru_id=gr.id");
        $masalah = [];
        foreach ($stmt as $r) {
            $expectJumlah = $r['jam'] * $r['per_jam'];
            $expectTotal = $expectJumlah + $r['transport'] + $r['jabatan1_rp'] + $r['jabatan2_rp'];
            $expectPot = $r['pot_kehadiran'] + $r['pot_bpd'] + $r['pot_bpjs'] + $r['pot_sosial'];
            $expectAkhir = $expectTotal - $expectPot;

            if (abs($expectJumlah - $r['jumlah']) > 0.01 ||
                abs($expectTotal - $r['total']) > 0.01 ||
                abs($expectAkhir - $r['jumlah_akhir']) > 0.01) {
                $masalah[] = "ID {$r['id']} ({$r['nama_guru']}): di DB={$r['jumlah_akhir']}, seharusnya={$expectAkhir}";
            }
        }
        echo json_encode([
            'status'=>'ok',
            'masalah'=>count($masalah),
            'detail'=>$masalah
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
    }
    exit;
}

// ============ HAPUS PER BULAN ============
if ($action === 'hapus_bulan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    try {
        $stmt = $pdo->prepare("DELETE FROM gaji WHERE bulan=? AND tahun=?");
        $stmt->execute([$d['bulan'], $d['tahun']]);
        $deleted = $stmt->rowCount();
        logAktivitas($pdo, 'HAPUS_BULAN', "Hapus {$d['bulan']} {$d['tahun']}: $deleted baris");
        echo json_encode(['status'=>'ok', 'deleted'=>$deleted]);
    } catch (PDOException $e) {
        echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
    }
    exit;
}

// ============ HAPUS PER PEGAWAI ============
if ($action === 'hapus_guru' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    try {
        $stmt = $pdo->prepare("DELETE FROM gaji WHERE guru_id=?");
        $stmt->execute([$d['guru_id']]);
        $deleted = $stmt->rowCount();
        logAktivitas($pdo, 'HAPUS_GURU', "Hapus guru_id {$d['guru_id']}: $deleted baris");
        echo json_encode(['status'=>'ok', 'deleted'=>$deleted]);
    } catch (PDOException $e) {
        echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
    }
    exit;
}

// ============ HAPUS PER TAHUN ============
if ($action === 'hapus_tahun' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    try {
        $stmt = $pdo->prepare("DELETE FROM gaji WHERE tahun=?");
        $stmt->execute([$d['tahun']]);
        $deleted = $stmt->rowCount();
        logAktivitas($pdo, 'HAPUS_TAHUN', "Hapus tahun {$d['tahun']}: $deleted baris");
        echo json_encode(['status'=>'ok', 'deleted'=>$deleted]);
    } catch (PDOException $e) {
        echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
    }
    exit;
}

// ============ HAPUS SEMUA ============
if ($action === 'hapus_semua' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM gaji")->fetchColumn();
        $pdo->exec("TRUNCATE TABLE gaji");
        logAktivitas($pdo, 'HAPUS_SEMUA', "Hapus semua gaji: $count baris");
        echo json_encode(['status'=>'ok', 'deleted'=>$count]);
    } catch (PDOException $e) {
        echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
    }
    exit;
}

echo json_encode(['status'=>'error', 'msg'=>'Action tidak dikenali']);
?>