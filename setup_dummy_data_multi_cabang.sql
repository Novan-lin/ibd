-- =====================================================================
--            SETUP DUMMY DATA MULTI-CABANG - FIC BRUDERAN
-- =====================================================================
-- Script ini menambahkan data dummy yang realistis untuk testing:
-- 3 bruder per cabang × 5 cabang = 15 bruder total
-- 1 komunitas per cabang (plus 2 yang sudah ada)
-- Data siap pakai untuk test multi-cabang functionality
-- =====================================================================

-- =============================================
-- 1. BACKUP DATA EXISTING (Safety First)
-- =============================================

CREATE TABLE IF NOT EXISTS bruder_backup AS SELECT * FROM bruder;
CREATE TABLE IF NOT EXISTS komunitas_backup AS SELECT * FROM komunitas;

-- =============================================
-- 2. TAMBAH KOMUNITAS PER CABANG
-- =============================================

INSERT IGNORE INTO komunitas (nama_komunitas, alamat_komunitas, id_cabang) VALUES
-- Cabang FIC01 Jakarta Pusat (sudah ada: St. Fransiskus & St. Yusuf)
('Komunitas St. Fransiskus', 'Jl. Thamrin No. 1, Jakarta Pusat', 1),
('Komunitas St. Yusuf', 'Jl. Sudirman No. 50, Jakarta Pusat', 1),

-- Cabang FIC02 Bandung
('Komunitas St. Petrus', 'Jl. Asia Afrika No. 123, Bandung', 2),

-- Cabang FIC03 Surabaya
('Komunitas St. Paulus', 'Jl. Diponegoro No. 456, Surabaya', 3),

-- Cabang FIC04 Medan
('Komunitas St. Andreas', 'Jl. SM. Raja No. 789, Medan', 4),

-- Cabang FIC05 Makassar
('Komunitas St. Lukas', 'Jl. AP. Pettarani No. 321, Makassar', 5);

-- =============================================
-- 3. UPDATE BRUDER EXISTING DENGAN CABANG YANG BENAR
-- =============================================

-- Pastikan bruder existing ter-assign ke cabang yang tepat
UPDATE bruder SET
    id_cabang = 1,
    id_komunitas = 1
WHERE id_bruder IN (1,2,3);

-- =============================================
-- 4. TAMBAH BRUDER BARU PER CABANG (2 untuk Jakarta, 3 untuk yang lain)
-- =============================================

-- Cabang FIC01 Jakarta Pusat: Tambah 2 bruder lagi (total 3)
INSERT IGNORE INTO bruder (nama_bruder, email, password, status, id_komunitas, id_cabang, ttl_bruder, alamat_bruder) VALUES
('Bruder Paulus', 'paulus.jakarta@ficbruderan.org', 'password123', 'bruder', 1, 1, '1985-03-20', 'Asrama Komunitas St. Fransiskus, Jakarta'),
('Bruder Markus', 'markus.jakarta@ficbruderan.org', 'password123', 'bruder', 1, 1, '1982-07-15', 'Asrama Komunitas St. Fransiskus, Jakarta');

-- Cabang FIC02 Bandung: Tambah 3 bruder
INSERT IGNORE INTO bruder (nama_bruder, email, password, status, id_komunitas, id_cabang, ttl_bruder, alamat_bruder) VALUES
('Bruder Petrus', 'petrus.bandung@ficbruderan.org', 'password123', 'bruder', 2, 2, '1988-11-10', 'Asrama Komunitas St. Petrus, Bandung'),
('Bruder Andreas', 'andreas.bandung@ficbruderan.org', 'password123', 'bruder', 2, 2, '1990-05-25', 'Asrama Komunitas St. Petrus, Bandung'),
('Bruder Lukas', 'lukas.bandung@ficbruderan.org', 'password123', 'bruder', 2, 2, '1987-09-12', 'Asrama Komunitas St. Petrus, Bandung');

-- Cabang FIC03 Surabaya: Tambah 3 bruder
INSERT IGNORE INTO bruder (nama_bruder, email, password, status, id_komunitas, id_cabang, ttl_bruder, alamat_bruder) VALUES
('Bruder Antonius', 'antonius.surabaya@ficbruderan.org', 'password123', 'bruder', 3, 3, '1986-01-30', 'Asrama Komunitas St. Paulus, Surabaya'),
('Bruder Stefanus', 'stefanus.surabaya@ficbruderan.org', 'password123', 'bruder', 3, 3, '1989-04-18', 'Asrama Komunitas St. Paulus, Surabaya'),
('Bruder Filipus', 'filipus.surabaya@ficbruderan.org', 'password123', 'bruder', 3, 3, '1984-12-08', 'Asrama Komunitas St. Paulus, Surabaya');

-- Cabang FIC04 Medan: Tambah 3 bruder
INSERT IGNORE INTO bruder (nama_bruder, email, password, status, id_komunitas, id_cabang, ttl_bruder, alamat_bruder) VALUES
('Bruder Yakobus', 'yakobus.medan@ficbruderan.org', 'password123', 'bruder', 4, 4, '1983-06-22', 'Asrama Komunitas St. Andreas, Medan'),
('Bruder Yudas', 'yudas.medan@ficbruderan.org', 'password123', 'bruder', 4, 4, '1987-08-14', 'Asrama Komunitas St. Andreas, Medan'),
('Bruder Matias', 'matias.medan@ficbruderan.org', 'password123', 'bruder', 4, 4, '1985-02-28', 'Asrama Komunitas St. Andreas, Medan');

-- Cabang FIC05 Makassar: Tambah 3 bruder
INSERT IGNORE INTO bruder (nama_bruder, email, password, status, id_komunitas, id_cabang, ttl_bruder, alamat_bruder) VALUES
('Bruder Simon', 'simon.makassar@ficbruderan.org', 'password123', 'bruder', 5, 5, '1984-10-05', 'Asrama Komunitas St. Lukas, Makassar'),
('Bruder Bartolomeus', 'bartolomeus.makassar@ficbruderan.org', 'password123', 'bruder', 5, 5, '1986-12-20', 'Asrama Komunitas St. Lukas, Makassar'),
('Bruder Tadeus', 'tadeus.makassar@ficbruderan.org', 'password123', 'bruder', 5, 5, '1988-03-15', 'Asrama Komunitas St. Lukas, Makassar');

-- =============================================
-- 5. TAMBAH DATA TRANSAKSI DUMMY PER CABANG
-- =============================================

-- Transaksi dummy untuk Jakarta (3 bruder × 2 transaksi = 6 transaksi)
INSERT IGNORE INTO transaksi (id_bruder, id_perkiraan, tanggal_transaksi, keterangan, sumber_dana, nominal_penerimaan, nominal_pengeluaran, id_cabang) VALUES
-- Bruder Yohanes
(1, 2, '2025-01-15', 'Donasi misa minggu', 'Kas Harian', 500000.00, 0.00, 1),
(1, 3, '2025-01-20', 'Bayar listrik januari', 'Kas Harian', 0.00, 300000.00, 1),

-- Bruder Paulus
(4, 2, '2025-01-16', 'Donasi kegiatan sosial', 'Kas Harian', 750000.00, 0.00, 1),
(4, 4, '2025-01-25', 'Bayar transport kegiatan', 'Kas Harian', 0.00, 150000.00, 1),

-- Bruder Markus
(5, 2, '2025-01-18', 'Donasi umat paroki', 'Kas Harian', 600000.00, 0.00, 1),
(5, 3, '2025-01-28', 'Bayar maintenance gedung', 'Kas Harian', 0.00, 200000.00, 1);

-- Transaksi dummy untuk Bandung (3 transaksi per bruder)
INSERT IGNORE INTO transaksi (id_bruder, id_perkiraan, tanggal_transaksi, keterangan, sumber_dana, nominal_penerimaan, nominal_pengeluaran, id_cabang) VALUES
-- Bruder Petrus
(6, 2, '2025-01-10', 'Donasi retret komunitas', 'Kas Harian', 400000.00, 0.00, 2),
(6, 3, '2025-01-22', 'Bayar PDAM januari', 'Kas Harian', 0.00, 250000.00, 2),

-- Bruder Andreas
(7, 2, '2025-01-12', 'Donasi kegiatan kepemudaan', 'Kas Harian', 350000.00, 0.00, 2),
(7, 4, '2025-01-24', 'Bayar bahan kegiatan', 'Kas Harian', 0.00, 100000.00, 2),

-- Bruder Lukas
(8, 2, '2025-01-14', 'Donasi misa harian', 'Kas Harian', 450000.00, 0.00, 2),
(8, 3, '2025-01-26', 'Bayar kebersihan asrama', 'Kas Harian', 0.00, 80000.00, 2);

-- =============================================
-- 6. CREATE VIEW UNTUK VERIFICATION
-- =============================================

CREATE OR REPLACE VIEW view_bruder_per_cabang AS
SELECT
    c.kode_cabang,
    c.nama_cabang,
    COUNT(b.id_bruder) AS jumlah_bruder,
    GROUP_CONCAT(b.nama_bruder ORDER BY b.nama_bruder SEPARATOR ', ') AS daftar_bruder
FROM cabang c
LEFT JOIN bruder b ON c.id_cabang = b.id_cabang
GROUP BY c.id_cabang, c.kode_cabang, c.nama_cabang
ORDER BY c.id_cabang;

-- =============================================
-- 7. FINAL VERIFICATION QUERIES
-- =============================================

-- Show setup results
SELECT '🎉 DUMMY DATA MULTI-CABANG SETUP COMPLETED!' AS status;

-- Show bruder per cabang
SELECT '👥 BRUDER PER CABANG:' AS info;
SELECT * FROM view_bruder_per_cabang;

-- Show komunitas per cabang
SELECT '🏢 KOMUNITAS PER CABANG:' AS info;
SELECT
    c.nama_cabang AS 'Cabang',
    GROUP_CONCAT(k.nama_komunitas ORDER BY k.nama_komunitas SEPARATOR ', ') AS 'Komunitas'
FROM cabang c
LEFT JOIN komunitas k ON c.id_cabang = k.id_cabang
GROUP BY c.id_cabang, c.nama_cabang
ORDER BY c.id_cabang;

-- Show transaksi per cabang
SELECT '💰 TRANSAKSI PER CABANG:' AS info;
SELECT
    c.nama_cabang AS 'Cabang',
    COUNT(t.id_transaksi) AS 'Jumlah Transaksi',
    SUM(t.nominal_penerimaan) AS 'Total Penerimaan',
    SUM(t.nominal_pengeluaran) AS 'Total Pengeluaran'
FROM cabang c
LEFT JOIN transaksi t ON c.id_cabang = t.id_cabang
GROUP BY c.id_cabang, c.nama_cabang
ORDER BY c.id_cabang;

-- Show summary
SELECT '📊 SUMMARY:' AS info;
SELECT
    (SELECT COUNT(*) FROM bruder) AS 'Total Bruder',
    (SELECT COUNT(*) FROM komunitas) AS 'Total Komunitas',
    (SELECT COUNT(*) FROM transaksi) AS 'Total Transaksi',
    (SELECT COUNT(DISTINCT id_cabang) FROM bruder) AS 'Cabang dengan Bruder',
    (SELECT COUNT(DISTINCT id_cabang) FROM transaksi) AS 'Cabang dengan Transaksi';

-- Show instructions
SELECT '🚀 TESTING INSTRUCTIONS:' AS info;
SELECT '1. Login dengan bruder dari berbagai cabang' AS instruction;
SELECT '2. Input transaksi dan verifikasi terisolasi per cabang' AS instruction;
SELECT '3. Test edit/delete hanya bisa di cabang sendiri' AS instruction;
SELECT '4. Coba login sebagai bendahara dari berbagai cabang' AS instruction;

SELECT '✅ Dummy data multi-cabang setup completed successfully!' AS final_status;
