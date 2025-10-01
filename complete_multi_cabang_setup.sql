-- =====================================================================
--            COMPLETE MULTI-CABANG SETUP - FIC BRUDERAN
-- =====================================================================
-- Single file untuk setup semua cabang sekaligus
-- Jalankan sekali jalan di phpMyAdmin - tidak perlu file terpisah
-- =====================================================================

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;

-- =============================================
-- 1. BACKUP EXISTING DATA (Safety First)
-- =============================================

CREATE TABLE IF NOT EXISTS login_backup AS SELECT * FROM login;
CREATE TABLE IF NOT EXISTS cabang_backup AS SELECT * FROM cabang;

-- =============================================
-- 2. CREATE CABANG STRUCTURE
-- =============================================

CREATE TABLE IF NOT EXISTS cabang (
    id_cabang INT PRIMARY KEY AUTO_INCREMENT,
    kode_cabang VARCHAR(10) UNIQUE NOT NULL COMMENT 'Kode unik: FIC01, FIC02, dll',
    nama_cabang VARCHAR(100) NOT NULL,
    alamat_cabang TEXT,
    kontak_person VARCHAR(100),
    telepon VARCHAR(20),
    email VARCHAR(100),
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================
-- 3. INSERT CABANG DATA (dengan INSERT IGNORE untuk handle duplicate)
-- =============================================

INSERT IGNORE INTO cabang (kode_cabang, nama_cabang, alamat_cabang, kontak_person, telepon, email) VALUES
('FIC01', 'Cabang Jakarta Pusat', 'Jl. Thamrin No. 1, Jakarta Pusat', 'Admin Jakarta', '021-1234567', 'jakarta@ficbruderan.org'),
('FIC02', 'Cabang Bandung', 'Jl. Asia Afrika No. 123, Bandung', 'Admin Bandung', '022-7654321', 'bandung@ficbruderan.org'),
('FIC03', 'Cabang Surabaya', 'Jl. Pemuda No. 456, Surabaya', 'Admin Surabaya', '031-9876543', 'surabaya@ficbruderan.org'),
('FIC04', 'Cabang Medan', 'Jl. Gatot Subroto No. 789, Medan', 'Admin Medan', '061-4567891', 'medan@ficbruderan.org'),
('FIC05', 'Cabang Makassar', 'Jl. Sudirman No. 321, Makassar', 'Admin Makassar', '0411-2345678', 'makassar@ficbruderan.org');

-- =============================================
-- 4. UPDATE EXISTING TABLES (Safe - hanya add column jika belum ada)
-- =============================================

ALTER TABLE bruder ADD COLUMN IF NOT EXISTS id_cabang INT NOT NULL DEFAULT 1;
ALTER TABLE komunitas ADD COLUMN IF NOT EXISTS id_cabang INT NOT NULL DEFAULT 1;
ALTER TABLE transaksi ADD COLUMN IF NOT EXISTS id_cabang INT NOT NULL DEFAULT 1;
ALTER TABLE kode_perkiraan ADD COLUMN IF NOT EXISTS id_cabang INT NOT NULL DEFAULT 1;
ALTER TABLE rencana_anggaran ADD COLUMN IF NOT EXISTS id_cabang INT NOT NULL DEFAULT 1;
ALTER TABLE login ADD COLUMN IF NOT EXISTS id_cabang INT NOT NULL DEFAULT 1;
ALTER TABLE login ADD COLUMN IF NOT EXISTS level ENUM('bruder','bendahara','admin_cabang') DEFAULT 'bruder';

-- =============================================
-- 5. UPDATE EXISTING DATA DENGAN CABANG PERTAMA
-- =============================================

UPDATE bruder SET id_cabang = 1 WHERE id_cabang IS NULL OR id_cabang = 0;
UPDATE komunitas SET id_cabang = 1 WHERE id_cabang IS NULL OR id_cabang = 0;
UPDATE transaksi SET id_cabang = 1 WHERE id_cabang IS NULL OR id_cabang = 0;
UPDATE kode_perkiraan SET id_cabang = 1 WHERE id_cabang IS NULL OR id_cabang = 0;
UPDATE rencana_anggaran SET id_cabang = 1 WHERE id_cabang IS NULL OR id_cabang = 0;
UPDATE login SET id_cabang = 1 WHERE id_cabang IS NULL OR id_cabang = 0;

-- =============================================
-- 6. CLEAR DAN RECREATE LOGIN DENGAN PATTERN BARU
-- =============================================

TRUNCATE TABLE login;

INSERT INTO login (username, password, id_cabang, level) VALUES
-- =============================================
-- CABANG FIC01 - JAKARTA PUSAT
-- =============================================
('bruderjakartapusat', 'bruderjakartapusat', 1, 'bruder'),
('bendaharajakartapusat', 'bendaharajakartapusat', 1, 'bendahara'),
('adminjakartapusat', 'adminjakartapusat', 1, 'admin_cabang'),

-- =============================================
-- CABANG FIC02 - BANDUNG
-- =============================================
('bruderbandung', 'bruderbandung', 2, 'bruder'),
('bendaharabandung', 'bendaharabandung', 2, 'bendahara'),
('adminbandung', 'adminbandung', 2, 'admin_cabang'),

-- =============================================
-- CABANG FIC03 - SURABAYA
-- =============================================
('brudersurabaya', 'brudersurabaya', 3, 'bruder'),
('bendaharasurabaya', 'bendaharasurabaya', 3, 'bendahara'),
('adminsurabaya', 'adminsurabaya', 3, 'admin_cabang'),

-- =============================================
-- CABANG FIC04 - MEDAN
-- =============================================
('brudermedan', 'brudermedan', 4, 'bruder'),
('bendaharamedan', 'bendaharamedan', 4, 'bendahara'),
('adminmedan', 'adminmedan', 4, 'admin_cabang'),

-- =============================================
-- CABANG FIC05 - MAKASSAR
-- =============================================
('brudermakassar', 'brudermakassar', 5, 'bruder'),
('bendaharamakassar', 'bendaharamakassar', 5, 'bendahara'),
('adminmakassar', 'adminmakassar', 5, 'admin_cabang');

-- =============================================
-- 7. SETUP BRUDER CABANG ASSIGNMENT (Fixed)
-- =============================================

-- Note: Tabel bruder tidak memiliki kolom username
-- Username ada di tabel login terpisah
-- Keduanya terhubung via id_cabang untuk multi-cabang support

-- =============================================
-- 8. CREATE INDEXES UNTUK PERFORMANCE
-- =============================================

CREATE INDEX IF NOT EXISTS idx_bruder_cabang ON bruder(id_cabang);
CREATE INDEX IF NOT EXISTS idx_komunitas_cabang ON komunitas(id_cabang);
CREATE INDEX IF NOT EXISTS idx_transaksi_cabang ON transaksi(id_cabang);
CREATE INDEX IF NOT EXISTS idx_transaksi_cabang_tanggal ON transaksi(id_cabang, tanggal_transaksi);
CREATE INDEX IF NOT EXISTS idx_kode_perkiraan_cabang ON kode_perkiraan(id_cabang);
CREATE INDEX IF NOT EXISTS idx_login_cabang ON login(id_cabang);
CREATE INDEX IF NOT EXISTS idx_login_level ON login(level);

-- =============================================
-- 9. CREATE STORED PROCEDURES
-- =============================================

DELIMITER $$

-- Stored procedure untuk setup cabang baru
CREATE PROCEDURE IF NOT EXISTS setup_cabang_baru(
    IN p_kode_cabang VARCHAR(10),
    IN p_nama_cabang VARCHAR(100),
    IN p_alamat_cabang TEXT,
    IN p_kontak_person VARCHAR(100),
    IN p_telepon VARCHAR(20),
    IN p_email VARCHAR(100)
)
BEGIN
    DECLARE new_cabang_id INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Insert cabang baru
    INSERT INTO cabang (kode_cabang, nama_cabang, alamat_cabang, kontak_person, telepon, email)
    VALUES (p_kode_cabang, p_nama_cabang, p_alamat_cabang, p_kontak_person, p_telepon, p_email);

    SET new_cabang_id = LAST_INSERT_ID();

    -- Copy kode perkiraan dari cabang pertama
    INSERT INTO kode_perkiraan (kode_perkiraan, nama_akun, pos, tipe_akun, id_cabang)
    SELECT kode_perkiraan, nama_akun, pos, tipe_akun, new_cabang_id
    FROM kode_perkiraan
    WHERE id_cabang = 1;

    -- Create login credentials untuk cabang baru
    INSERT INTO login (username, password, id_cabang, level)
    VALUES
    (CONCAT('bruder', LOWER(REPLACE(p_nama_cabang, ' ', ''))), CONCAT('bruder', LOWER(REPLACE(p_nama_cabang, ' ', ''))), new_cabang_id, 'bruder'),
    (CONCAT('bendahara', LOWER(REPLACE(p_nama_cabang, ' ', ''))), CONCAT('bendahara', LOWER(REPLACE(p_nama_cabang, ' ', ''))), new_cabang_id, 'bendahara'),
    (CONCAT('admin', LOWER(REPLACE(p_nama_cabang, ' ', ''))), CONCAT('admin', LOWER(REPLACE(p_nama_cabang, ' ', ''))), new_cabang_id, 'admin_cabang');

    COMMIT;

    SELECT new_cabang_id AS cabang_id, 'Cabang berhasil dibuat dengan login credentials' AS message;
END$$

-- Stored procedure untuk multi-cabang transaction
CREATE PROCEDURE IF NOT EXISTS catat_transaksi_keuangan_multi_cabang(
    IN p_id_bruder INT,
    IN p_id_perkiraan INT,
    IN p_tanggal_transaksi DATE,
    IN p_keterangan TEXT,
    IN p_sumber_dana ENUM('Kas Harian','Bank'),
    IN p_tipe_transaksi ENUM('Penerimaan','Pengeluaran'),
    IN p_nominal DECIMAL(15,2),
    IN p_id_cabang INT
)
BEGIN
    DECLARE penerimaan DECIMAL(15,2) DEFAULT 0.00;
    DECLARE pengeluaran DECIMAL(15,2) DEFAULT 0.00;
    DECLARE bruder_cabang_id INT;

    -- Cek apakah bruder ada di cabang yang sama
    SELECT id_cabang INTO bruder_cabang_id
    FROM bruder WHERE id_bruder = p_id_bruder;

    IF bruder_cabang_id != p_id_cabang THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Bruder tidak terdaftar di cabang ini!';
    END IF;

    IF p_tipe_transaksi = 'Penerimaan' THEN
        SET penerimaan = p_nominal;
    ELSEIF p_tipe_transaksi = 'Pengeluaran' THEN
        SET pengeluaran = p_nominal;
    END IF;

    INSERT INTO transaksi (
        id_bruder, id_perkiraan, tanggal_transaksi, keterangan,
        sumber_dana, nominal_penerimaan, nominal_pengeluaran, id_cabang
    ) VALUES (
        p_id_bruder, p_id_perkiraan, p_tanggal_transaksi, p_keterangan,
        p_sumber_dana, penerimaan, pengeluaran, p_id_cabang
    );
END$$

DELIMITER ;

-- =============================================
-- 10. CREATE VIEW UNTUK VERIFICATION
-- =============================================

CREATE OR REPLACE VIEW view_cabang_status AS
SELECT
    c.id_cabang,
    c.kode_cabang,
    c.nama_cabang,
    c.status,
    COUNT(DISTINCT b.id_bruder) AS jumlah_bruder,
    COUNT(DISTINCT k.id_komunitas) AS jumlah_komunitas,
    COUNT(DISTINCT t.id_transaksi) AS jumlah_transaksi,
    COUNT(DISTINCT l.id) AS jumlah_login
FROM cabang c
LEFT JOIN bruder b ON c.id_cabang = b.id_cabang
LEFT JOIN komunitas k ON c.id_cabang = k.id_cabang
LEFT JOIN transaksi t ON c.id_cabang = t.id_cabang
LEFT JOIN login l ON c.id_cabang = l.id_cabang
GROUP BY c.id_cabang, c.kode_cabang, c.nama_cabang, c.status;

-- =============================================
-- 11. FINAL VERIFICATION QUERIES
-- =============================================

COMMIT;

-- Show setup results
SELECT '🎉 COMPLETE MULTI-CABANG SETUP FINISHED!' AS status;

-- Show all cabang
SELECT '📋 DAFTAR CABANG:' AS info;
SELECT id_cabang, kode_cabang, nama_cabang, status FROM cabang ORDER BY id_cabang;

-- Show login credentials per cabang
SELECT '🔐 LOGIN CREDENTIALS PER CABANG:' AS info;
SELECT
    c.nama_cabang AS 'Cabang',
    GROUP_CONCAT(
        CONCAT(UPPER(l.level), ': ', l.username, '/', l.password)
        ORDER BY l.level SEPARATOR ' | '
    ) AS 'Login Credentials'
FROM cabang c
JOIN login l ON c.id_cabang = l.id_cabang
GROUP BY c.id_cabang, c.nama_cabang
ORDER BY c.id_cabang;

-- Show summary statistics
SELECT '📊 SUMMARY:' AS info;
SELECT
    (SELECT COUNT(*) FROM cabang WHERE status = 'aktif') AS 'Cabang Aktif',
    (SELECT COUNT(*) FROM login WHERE level = 'bruder') AS 'Bruder Login',
    (SELECT COUNT(*) FROM login WHERE level = 'bendahara') AS 'Bendahara Login',
    (SELECT COUNT(*) FROM login WHERE level = 'admin_cabang') AS 'Admin Login',
    (SELECT COUNT(*) FROM bruder) AS 'Total Bruder',
    (SELECT COUNT(*) FROM transaksi) AS 'Total Transaksi';

-- Show instructions
SELECT '🚀 CARA PAKAI:' AS info;
SELECT '1. Login dengan credentials di atas' AS instruction;
SELECT '2. Setiap cabang memiliki data terpisah' AS instruction;
SELECT '3. User hanya bisa akses data cabang sendiri' AS instruction;
SELECT '4. Untuk tambah cabang: CALL setup_cabang_baru()' AS instruction;

SELECT '✅ Multi-cabang setup completed successfully!' AS final_status;
