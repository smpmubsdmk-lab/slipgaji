-- ============================================================
-- MIGRASI: Tabel jabatan_guru
-- Untuk menyimpan riwayat jabatan per pegawai per tahun
-- Jalankan SETELAH database.sql utama ter-import
-- ============================================================

USE slip_gaji_mu;

-- Tabel jabatan per tahun
CREATE TABLE IF NOT EXISTS jabatan_guru (
  id INT AUTO_INCREMENT PRIMARY KEY,
  guru_id INT NOT NULL,
  tahun INT NOT NULL,
  jabatan1 VARCHAR(80) DEFAULT NULL,
  jabatan2 VARCHAR(80) DEFAULT NULL,
  jabatan3 VARCHAR(80) DEFAULT NULL,
  jabatan4 VARCHAR(80) DEFAULT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
  UNIQUE KEY unique_jabatan (guru_id, tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Isi data awal: copy dari tabel guru untuk tahun 2026
INSERT INTO jabatan_guru (guru_id, tahun, jabatan1, jabatan2, jabatan3, jabatan4)
SELECT id, 2026, jabatan1, jabatan2, jabatan3, jabatan4
FROM guru
ON DUPLICATE KEY UPDATE
  jabatan1 = VALUES(jabatan1),
  jabatan2 = VALUES(jabatan2),
  jabatan3 = VALUES(jabatan3),
  jabatan4 = VALUES(jabatan4);

SELECT 'Migrasi selesai!' AS status,
       (SELECT COUNT(*) FROM jabatan_guru) AS total_jabatan_tersimpan;