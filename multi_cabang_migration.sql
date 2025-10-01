-- =====================================================================
--            MIGRATION: MULTI-CABANG SUPPORT UNTUK FIC BRUDERAN
-- =====================================================================
-- Script ini menambahkan support untuk multiple cabang
-- dengan isolasi data yang sempurna antar cabang
-- =====================================================================

-- 1. Buat tabel cabang baru
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
);

-- 2. Insert 5 cabang awal
INSERT INTO cabang (kode_cabang, nama_cabang, alamat_cabang, kontak_person, telepon, email) VALUES
('FIC01', 'Cabang Jakarta Pusat', 'Jl. Thamrin No. 1, Jakarta Pusat', 'Admin Jakarta', '021-1234567', 'jakarta@ficbruderan.org'),
('FIC02', 'Cabang Bandung', 'Jl. Asia Afrika No. 123, Bandung', 'Admin Bandung', '022-7654321', 'bandung@ficbruderan.org'),
('FIC03', 'Cabang Surabaya', 'Jl. Pemuda No. 456, Surabaya', 'Admin Surabaya', '031-9876543', 'surabaya@ficbruderan.org'),
('FIC04', 'Cabang Medan', 'Jl. Gatot Subroto No. 789, Medan', 'Admin Medan', '061-4567891', 'medan@ficbruderan.org'),
('FIC05', 'Cabang Makassar', 'Jl. Sudirman No. 321, Makassar', 'Admin Makassar', '0411-2345678', 'makassar@ficbruderan.org');

-- 3. Update tabel bruder - tambah field id_cabang
ALTER TABLE bruder ADD COLUMN id_cabang INT NOT NULL DEFAULT 1;

-- Update data bruder existing dengan cabang pertama (FIC01)
UPDATE bruder SET id_cabang = 1 WHERE id_bruder IN (1,2,3);

-- 4. Update tabel komunitas - tambah field id_cabang
ALTER TABLE komunitas ADD COLUMN id_cabang INT NOT NULL DEFAULT 1;

-- Update data komunitas existing
UPDATE komunitas SET id_cabang = 1 WHERE id_komunitas IN (1,2);

-- 5. Update tabel transaksi - tambah field id_cabang
ALTER TABLE transaksi ADD COLUMN id_cabang INT NOT NULL DEFAULT 1;

-- Update data transaksi existing dengan cabang pertama
UPDATE transaksi SET id_cabang = 1 WHERE id_transaksi IN (1,2,3,4,5,6,7,8);

-- 6. Update tabel kode_perkiraan - tambah field id_cabang
ALTER TABLE kode_perkiraan ADD COLUMN id_cabang INT NOT NULL DEFAULT 1;

-- Update data kode_perkiraan existing
UPDATE kode_perkiraan SET id_cabang = 1 WHERE id_perkiraan IN (1,2,3,4);

-- 7. Update tabel rencana_anggaran - tambah field id_cabang
ALTER TABLE rencana_anggaran ADD COLUMN id_cabang INT NOT NULL DEFAULT 1;

-- 8. Update tabel login - tambah field id_cabang dan level
ALTER TABLE login ADD COLUMN id_cabang INT NOT NULL DEFAULT 1;
ALTER TABLE login ADD COLUMN level ENUM('admin_cabang','bendahara','sekretariat') DEFAULT 'bendahara';

-- Update login existing dengan cabang pertama
UPDATE login SET id_cabang = 1, level = 'admin_cabang' WHERE id = 1;

-- 9. Buat stored procedure untuk setup cabang baru
DELIMITER $$
CREATE PROCEDURE setup_cabang_baru(
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

    COMMIT;

    SELECT new_cabang_id AS cabang_id, 'Cabang berhasil dibuat dengan kode perkiraan' AS message;
END$$
DELIMITER ;

-- 10. Update stored procedure catat_transaksi_keuangan untuk include id_cabang
DELIMITER $$
CREATE PROCEDURE catat_transaksi_keuangan_multi_cabang(
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

-- 11. Buat view untuk laporan per cabang
CREATE VIEW laporan_keuangan_per_cabang AS
SELECT
    c.nama_cabang,
    c.kode_cabang,
    COUNT(t.id_transaksi) AS jumlah_transaksi,
    SUM(t.nominal_penerimaan) AS total_penerimaan,
    SUM(t.nominal_pengeluaran) AS total_pengeluaran,
    (SUM(t.nominal_penerimaan) - SUM(t.nominal_pengeluaran)) AS saldo_akhir
FROM cabang c
LEFT JOIN transaksi t ON c.id_cabang = t.id_cabang
WHERE c.status = 'aktif'
GROUP BY c.id_cabang, c.nama_cabang, c.kode_cabang;

-- 12. Index untuk performance
CREATE INDEX idx_bruder_cabang ON bruder(id_cabang);
CREATE INDEX idx_komunitas_cabang ON komunitas(id_cabang);
CREATE INDEX idx_transaksi_cabang ON transaksi(id_cabang);
CREATE INDEX idx_transaksi_cabang_tanggal ON transaksi(id_cabang, tanggal_transaksi);
CREATE INDEX idx_kode_perkiraan_cabang ON kode_perkiraan(id_cabang);
CREATE INDEX idx_login_cabang ON login(id_cabang);

-- 13. Trigger untuk auto-update updated_at di tabel cabang
DELIMITER $$
CREATE TRIGGER trg_cabang_updated_at
BEFORE UPDATE ON cabang
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END$$
DELIMITER ;

SELECT 'Multi-cabang migration completed successfully!' AS status;
