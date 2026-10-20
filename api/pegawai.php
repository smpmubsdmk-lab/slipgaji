<?php
require_once '../config/database.php';
requireLogin();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ============ LIST SEMUA PEGAWAI ============
if ($action === 'list_all') {
    $sql = "SELECT g.*, k.nama AS kategori FROM guru g 
            JOIN kategori k ON g.kategori_id = k.id 
            ORDER BY g.aktif DESC, g.no_urut ASC";
    echo json_encode($pdo->query($sql)->fetchAll());
    exit;
}

// ============ GET JABATAN PER TAHUN ============
if ($action === 'get_jabatan') {
    $guru_id = (int)($_GET['guru_id'] ?? 0);
    $tahun = (int)($_GET['tahun'] ?? date('Y'));
    $stmt = $pdo->prepare("SELECT * FROM jabatan_guru WHERE guru_id=? AND tahun=?");
    $stmt->execute([$guru_id, $tahun]);
    $j = $stmt->fetch();
    if (!$j) {
        // Fallback ke tabel guru
        $stmt = $pdo->prepare("SELECT jabatan1, jabatan2, jabatan3, jabatan4 FROM guru WHERE id=?");
        $stmt->execute([$guru_id]);
        $j = $stmt->fetch() ?: [];
    }
    echo json_encode($j ?: ['jabatan1'=>'','jabatan2'=>'','jabatan3'=>'','jabatan4'=>'']);
    exit;
}

// ============ TAMBAH PEGAWAI BARU ============
if ($action === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    try {
        // No urut otomatis kalau kosong
        if (empty($d['no_urut'])) {
            $max = $pdo->query("SELECT COALESCE(MAX(no_urut),0)+1 FROM guru")->fetchColumn();
            $d['no_urut'] = $max;
        }

        // Generate kode unik: MU + no_urut 3 digit + 3 huruf random
        $prefix = 'MU' . str_pad($d['no_urut'], 3, '0', STR_PAD_LEFT);
        do {
            $suffix = strtoupper(substr(md5(uniqid(rand(), true)), 0, 3));
            $kode = $prefix . '-' . $suffix;
            $cek = $pdo->prepare("SELECT COUNT(*) FROM guru WHERE kode_unik=?");
            $cek->execute([$kode]);
        } while ($cek->fetchColumn() > 0);

        // Insert ke tabel guru
        $sql = "INSERT INTO guru (no_urut, nama, jabatan1, jabatan2, jabatan3, jabatan4, kategori_id, kode_unik, aktif)
                VALUES (?,?,?,?,?,?,?,?,1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $d['no_urut'],
            $d['nama'],
            $d['jabatan1'] ?: null,
            $d['jabatan2'] ?: null,
            $d['jabatan3'] ?: null,
            $d['jabatan4'] ?: null,
            $d['kategori_id'] ?: 1,
            $kode
        ]);
        $guru_id = $pdo->lastInsertId();

        // Simpan jabatan tahun ini ke jabatan_guru
        $tahun = date('Y');
        $sql = "INSERT INTO jabatan_guru (guru_id, tahun, jabatan1, jabatan2, jabatan3, jabatan4)
                VALUES (?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE
                jabatan1=VALUES(jabatan1), jabatan2=VALUES(jabatan2),
                jabatan3=VALUES(jabatan3), jabatan4=VALUES(jabatan4)";
        $pdo->prepare($sql)->execute([
            $guru_id, $tahun,
            $d['jabatan1'] ?: null,
            $d['jabatan2'] ?: null,
            $d['jabatan3'] ?: null,
            $d['jabatan4'] ?: null
        ]);

        logAktivitas($pdo, 'TAMBAH_PEGAWAI', "Tambah {$d['nama']} (kode: $kode)");
        echo json_encode(['status'=>'ok', 'kode'=>$kode, 'id'=>$guru_id]);
    } catch (PDOException $e) {
        echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
    }
    exit;
}

// ============ EDIT PEGAWAI ============
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    try {
        $pdo->beginTransaction();

        // Update tabel guru (nama, no_urut, kategori)
        $sql = "UPDATE guru SET nama=?, no_urut=?, kategori_id=? WHERE id=?";
        $pdo->prepare($sql)->execute([
            $d['nama'],
            $d['no_urut'] ?: null,
            $d['kategori_id'] ?: 1,
            $d['id']
        ]);

        // Update jabatan tahun ini di jabatan_guru
        $sql = "INSERT INTO jabatan_guru (guru_id, tahun, jabatan1, jabatan2, jabatan3, jabatan4)
                VALUES (?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE
                jabatan1=VALUES(jabatan1), jabatan2=VALUES(jabatan2),
                jabatan3=VALUES(jabatan3), jabatan4=VALUES(jabatan4)";
        $pdo->prepare($sql)->execute([
            $d['id'],
            $d['tahun'] ?: date('Y'),
            $d['jabatan1'] ?: null,
            $d['jabatan2'] ?: null,
            $d['jabatan3'] ?: null,
            $d['jabatan4'] ?: null
        ]);

        $pdo->commit();
        logAktivitas($pdo, 'EDIT_PEGAWAI', "Edit pegawai ID {$d['id']} ({$d['nama']})");
        echo json_encode(['status'=>'ok']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
    }
    exit;
}

// ============ NON-AKTIFKAN ============
if ($action === 'nonaktifkan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    try {
        $pdo->prepare("UPDATE guru SET aktif=0 WHERE id=?")->execute([$d['id']]);
        logAktivitas($pdo, 'NONAKTIF_PEGAWAI', "Nonaktifkan pegawai ID {$d['id']}");
        echo json_encode(['status'=>'ok']);
    } catch (PDOException $e) {
        echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
    }
    exit;
}

// ============ AKTIFKAN KEMBALI ============
if ($action === 'aktifkan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    try {
        $pdo->prepare("UPDATE guru SET aktif=1 WHERE id=?")->execute([$d['id']]);
        logAktivitas($pdo, 'AKTIF_PEGAWAI', "Aktifkan pegawai ID {$d['id']}");
        echo json_encode(['status'=>'ok']);
    } catch (PDOException $e) {
        echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
    }
    exit;
}

// ============ HAPUS PERMANEN ============
if ($action === 'hapus_permanen' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    try {
        // ON DELETE CASCADE di foreign key otomatis hapus gaji & jabatan_guru
        $pdo->prepare("DELETE FROM guru WHERE id=?")->execute([$d['id']]);
        logAktivitas($pdo, 'HAPUS_PEGAWAI', "Hapus permanen pegawai ID {$d['id']}");
        echo json_encode(['status'=>'ok']);
    } catch (PDOException $e) {
        echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
    }
    exit;
}

echo json_encode(['status'=>'error', 'msg'=>'Action tidak dikenali']);
?>