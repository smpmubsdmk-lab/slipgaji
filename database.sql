-- ============================================================
-- Aplikasi Slip Gaji - SMP MU Boarding School
-- DATABASE FINAL - Siap Import
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";



-- ============================================================
-- Tabel admin
-- Password default: admin123
-- Hash SHA-256 dari "admin123":
-- 240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9
-- ============================================================
CREATE TABLE admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  nama_lengkap VARCHAR(100) NOT NULL,
  role ENUM('superadmin','admin') NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO admin (username, password, nama_lengkap, role) VALUES
('superadmin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'Super Admin', 'superadmin');

-- ============================================================
-- Tabel kategori
-- ============================================================
CREATE TABLE kategori (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO kategori (nama) VALUES ('GURU'), ('KARYAWAN');

-- ============================================================
-- Tabel guru (25 pegawai)
-- ============================================================
CREATE TABLE guru (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_urut INT DEFAULT NULL,
  nama VARCHAR(120) NOT NULL,
  jabatan1 VARCHAR(80) DEFAULT NULL,
  jabatan2 VARCHAR(80) DEFAULT NULL,
  jabatan3 VARCHAR(80) DEFAULT NULL,
  jabatan4 VARCHAR(80) DEFAULT NULL,
  kategori_id INT DEFAULT 1,
  kode_unik VARCHAR(30) NOT NULL UNIQUE,
  aktif TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (kategori_id) REFERENCES kategori(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO guru (no_urut, nama, jabatan1, jabatan2, jabatan3, jabatan4, kategori_id, kode_unik) VALUES
(1,'NIRLIANAWATI, S.Pd','Kepsek',NULL,'Guru',NULL,1,'MU001-NRL'),
(2,'HANNAH, S.Psi','Wakil Kepsek',NULL,'Guru',NULL,1,'MU002-HNN'),
(3,'UMI LATIFAH, S.Si','Wakakur','Walas','Guru',NULL,1,'MU003-UML'),
(4,'NAILIL MUNA, S.E','Wakasis','Walas','Guru',NULL,1,'MU004-NLM'),
(5,'M. GUNAWAN','BK 1','TU',NULL,NULL,1,'MU005-MGN'),
(6,'SHALSABILA CHOIRUNNISA','BK 2',NULL,NULL,NULL,1,'MU006-SHC'),
(7,'ABDUL MUJIB','Sarpras',NULL,'Satpam SMP + JM',NULL,1,'MU007-ABM'),
(8,'MUHAMMAD KHOERONI, S.Pd.I','Wakahumas',NULL,NULL,NULL,1,'MU008-MHK'),
(9,'UMI LATIFAH, S.Ag','Bend Sekolah','Walas','Guru','Bend BOS',1,'MU009-UML'),
(10,'FATMAWATUR ROHMAH, S.Pd','Bend BOS','Walas','Guru',NULL,1,'MU010-FTR'),
(11,'KHOLISOH, S.Pd.',NULL,'Walas','Guru',NULL,1,'MU011-KHL'),
(12,'LAILATUL QODRIYAH, S.HI',NULL,'Walas','Guru',NULL,1,'MU012-LLQ'),
(13,'KUMAYATI, S.Pd',NULL,'Walas','Guru',NULL,1,'MU013-KMY'),
(14,'NUR FARIDA, SPd',NULL,'Walas','Guru',NULL,1,'MU014-NRF'),
(15,'SITI ZAHROTUL CHOIRIYAH, S. Pd',NULL,'Walas','Guru',NULL,1,'MU015-SZC'),
(16,'KHIKMATUL KHAFIDHOH',NULL,'Walas','Guru',NULL,1,'MU016-KHK'),
(17,'LIANA ARIE SUSANTIE, S.Pd',NULL,'Walas','Guru',NULL,1,'MU017-LAS'),
(18,'JAWAHIRUL AINUR FARIZQI',NULL,'Walas','Guru',NULL,1,'MU018-JAF'),
(19,'THOLAFUDIN, S.Pd.I',NULL,NULL,'Guru',NULL,1,'MU019-TLF'),
(20,'INNAROTUL ULYA, S.Pd',NULL,NULL,'Guru',NULL,1,'MU020-INR'),
(21,'SARWATI, S. Pd',NULL,NULL,'Guru',NULL,1,'MU021-SRW'),
(22,'SOLICHIN, S.Pd',NULL,NULL,'Guru',NULL,1,'MU022-SLC'),
(23,'MUSLIHUL ANWAR','Ka.TU','Operator','Guru',NULL,2,'MU023-MSA'),
(24,'NASYWA BELINDA ARDININGRUM','Koperasi',NULL,NULL,NULL,2,'MU024-NBA'),
(25,'AISYAH','Perpus',NULL,'Guru',NULL,2,'MU025-AYS');

-- ============================================================
-- Tabel gaji
-- ============================================================
CREATE TABLE gaji (
  id INT AUTO_INCREMENT PRIMARY KEY,
  guru_id INT NOT NULL,
  bulan VARCHAR(20) NOT NULL,
  tahun INT NOT NULL,
  baris_ke INT NOT NULL DEFAULT 1,
  keterangan VARCHAR(100) DEFAULT NULL,
  jam INT DEFAULT 0,
  per_jam DECIMAL(12,2) DEFAULT 0.00,
  jumlah DECIMAL(14,2) DEFAULT 0.00,
  transport DECIMAL(14,2) DEFAULT 0.00,
  jabatan1_rp DECIMAL(14,2) DEFAULT 0.00,
  jabatan2_rp DECIMAL(14,2) DEFAULT 0.00,
  total DECIMAL(14,2) DEFAULT 0.00,
  pot_kehadiran DECIMAL(14,2) DEFAULT 0.00,
  pot_bpd DECIMAL(14,2) DEFAULT 0.00,
  pot_bpjs DECIMAL(14,2) DEFAULT 0.00,
  pot_sosial DECIMAL(14,2) DEFAULT 0.00,
  jumlah_akhir DECIMAL(14,2) DEFAULT 0.00,
  tanggal_input DATE DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES admin(id),
  UNIQUE KEY unique_gaji (guru_id, bulan, tahun, baris_ke)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Tabel log
-- ============================================================
CREATE TABLE log_aktivitas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT DEFAULT NULL,
  aksi VARCHAR(100) DEFAULT NULL,
  detail TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SELESAI
-- ============================================================