-- =====================================================================
--            SETUP LOGIN SYSTEM UNTUK MULTI-CABANG FIC BRUDERAN
-- =====================================================================
-- Script ini mengupdate sistem login untuk mendukung:
-- 1. Hanya 2 role: bruder dan bendahara
-- 2. Login credentials per cabang dengan pattern yang mudah
-- 3. Auto-generate username dan password berdasarkan cabang
-- =====================================================================

-- 1. Backup data login existing (untuk safety)
CREATE TABLE IF NOT EXISTS login_backup AS SELECT * FROM login;

-- 2. Update struktur tabel login
ALTER TABLE login
MODIFY COLUMN level ENUM('bruder','bendahara','admin_cabang') DEFAULT 'bruder';

-- 3. Clear data login existing (karena akan di-recreate dengan pattern baru)
TRUNCATE TABLE login;

-- 4. Insert login untuk Bruder per cabang
-- Pattern: bruder{nama_cabang_tanpa_spasi}
INSERT INTO login (username, password, id_cabang, level) VALUES
('bruderjakartapusat', 'bruderjakartapusat', 1, 'bruder'),
('bruderbandung', 'bruderbandung', 2, 'bruder'),
('brudersurabaya', 'brudersurabaya', 3, 'bruder'),
('brudermedan', 'brudermedan', 4, 'bruder'),
('brudermakassar', 'brudermakassar', 5, 'bruder');

-- 5. Insert login untuk Bendahara per cabang
-- Pattern: bendahara{nama_cabang_tanpa_spasi}
INSERT INTO login (username, password, id_cabang, level) VALUES
('bendaharajakartapusat', 'bendaharajakartapusat', 1, 'bendahara'),
('bendaharabandung', 'bendaharabandung', 2, 'bendahara'),
('bendaharasurabaya', 'bendaharasurabaya', 3, 'bendahara'),
('bendaharamedan', 'bendaharamedan', 4, 'bendahara'),
('bendaharamakassar', 'bendaharamakassar', 5, 'bendahara');

-- 6. Insert login untuk Admin Cabang (jika diperlukan)
-- Pattern: admin{nama_cabang_tanpa_spasi}
INSERT INTO login (username, password, id_cabang, level) VALUES
('adminjakartapusat', 'adminjakartapusat', 1, 'admin_cabang'),
('adminbandung', 'adminbandung', 2, 'admin_cabang'),
('adminsurabaya', 'adminsurabaya', 3, 'admin_cabang'),
('adminmedan', 'adminmedan', 4, 'admin_cabang'),
('adminmakassar', 'adminmakassar', 5, 'admin_cabang');

-- 7. Update bruder yang sudah ada dengan login credentials
-- Assign login bruder ke bruder yang sudah ada
UPDATE bruder b
JOIN login l ON l.username = CONCAT('bruder', LOWER(REPLACE(
    (SELECT c.nama_cabang FROM cabang c WHERE c.id_cabang = b.id_cabang), ' ', '')))
SET b.username = l.username
WHERE b.id_cabang = l.id_cabang
AND l.level = 'bruder';

-- 8. Buat view untuk melihat login credentials per cabang
CREATE OR REPLACE VIEW view_login_credentials AS
SELECT
    c.kode_cabang,
    c.nama_cabang,
    l.username,
    l.password,
    l.level,
    CASE
        WHEN l.level = 'bruder' THEN 'Bruder (input transaksi, lihat laporan)'
        WHEN l.level = 'bendahara' THEN 'Bendahara (full akses keuangan)'
        WHEN l.level = 'admin_cabang' THEN 'Admin Cabang (manage semua data cabang)'
    END AS deskripsi_role
FROM login l
JOIN cabang c ON l.id_cabang = c.id_cabang
ORDER BY c.id_cabang, l.level;

-- 9. Buat stored procedure untuk reset password jika diperlukan
DELIMITER $$
CREATE PROCEDURE reset_password_cabang(
    IN p_username VARCHAR(50),
    IN p_new_password VARCHAR(50)
)
BEGIN
    UPDATE login
    SET password = p_new_password,
        updated_at = CURRENT_TIMESTAMP
    WHERE username = p_username;
END$$
DELIMITER ;

-- 10. Show hasil setup
SELECT '=== LOGIN CREDENTIALS SETUP COMPLETED ===' AS status;

-- 11. Tampilkan semua credentials yang sudah dibuat
SELECT
    c.nama_cabang AS 'Cabang',
    GROUP_CONCAT(
        CONCAT(l.level, ': ', l.username, ' / ', l.password)
        ORDER BY l.level SEPARATOR ' | '
    ) AS 'Login Credentials'
FROM cabang c
JOIN login l ON c.id_cabang = l.id_cabang
GROUP BY c.id_cabang, c.nama_cabang
ORDER BY c.id_cabang;

-- 12. Summary
SELECT
    COUNT(CASE WHEN level = 'bruder' THEN 1 END) AS 'Total Bruder Login',
    COUNT(CASE WHEN level = 'bendahara' THEN 1 END) AS 'Total Bendahara Login',
    COUNT(CASE WHEN level = 'admin_cabang' THEN 1 END) AS 'Total Admin Cabang Login',
    COUNT(DISTINCT id_cabang) AS 'Total Cabang'
FROM login;
