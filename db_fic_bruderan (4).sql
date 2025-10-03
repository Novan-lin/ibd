-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 03 Okt 2025 pada 15.12
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_fic_bruderan`
--

DELIMITER $$
--
-- Prosedur
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `catat_transaksi_keuangan` (IN `p_id_bruder` INT, IN `p_id_perkiraan` INT, IN `p_tanggal_transaksi` DATE, IN `p_keterangan` TEXT, IN `p_sumber_dana` ENUM('Kas Harian','Bank'), IN `p_tipe_transaksi` ENUM('Penerimaan','Pengeluaran'), IN `p_nominal` DECIMAL(15,2))   BEGIN
    DECLARE penerimaan DECIMAL(15,2) DEFAULT 0.00;
    DECLARE pengeluaran DECIMAL(15,2) DEFAULT 0.00;

    IF p_tipe_transaksi = 'Penerimaan' THEN
        SET penerimaan = p_nominal;
    ELSEIF p_tipe_transaksi = 'Pengeluaran' THEN
        SET pengeluaran = p_nominal;
    END IF;

    INSERT INTO `transaksi` (
        `id_bruder`, `id_perkiraan`, `tanggal_transaksi`, `keterangan`, 
        `sumber_dana`, `nominal_penerimaan`, `nominal_pengeluaran`
    ) VALUES (
        p_id_bruder, p_id_perkiraan, p_tanggal_transaksi, p_keterangan,
        p_sumber_dana, penerimaan, pengeluaran
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `catat_transaksi_keuangan_multi_cabang` (IN `p_id_bruder` INT, IN `p_id_perkiraan` INT, IN `p_tanggal_transaksi` DATE, IN `p_keterangan` TEXT, IN `p_sumber_dana` ENUM('Kas Harian','Bank'), IN `p_tipe_transaksi` ENUM('Penerimaan','Pengeluaran'), IN `p_nominal` DECIMAL(15,2), IN `p_id_cabang` INT)   BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_histori_transaksi_bruder` (IN `p_id_bruder` INT)   BEGIN
    SELECT
        t.tanggal_transaksi,
        kp.kode_perkiraan,
        kp.nama_akun,
        t.keterangan,
        t.nominal_penerimaan,
        t.nominal_pengeluaran
    FROM
        `transaksi` AS t
    JOIN
        `kode_perkiraan` AS kp ON t.id_perkiraan = kp.id_perkiraan
    WHERE
        t.id_bruder = p_id_bruder
    ORDER BY
        t.tanggal_transaksi DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `laporan_keuangan_bulanan` (IN `p_bulan` INT, IN `p_tahun` INT)   BEGIN
    SELECT
        IFNULL(SUM(`nominal_penerimaan`), 0) AS `total_penerimaan`,
        IFNULL(SUM(`nominal_pengeluaran`), 0) AS `total_pengeluaran`
    FROM `transaksi`
    WHERE
        MONTH(`tanggal_transaksi`) = p_bulan AND YEAR(`tanggal_transaksi`) = p_tahun;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `setup_cabang_baru` (IN `p_kode_cabang` VARCHAR(10), IN `p_nama_cabang` VARCHAR(100), IN `p_alamat_cabang` TEXT, IN `p_kontak_person` VARCHAR(100), IN `p_telepon` VARCHAR(20), IN `p_email` VARCHAR(100))   BEGIN
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `tambah_bruder_baru` (IN `p_nama_bruder` VARCHAR(100), IN `p_username` VARCHAR(50), IN `p_password` VARCHAR(255), IN `p_id_komunitas` INT, IN `p_ttl_bruder` DATE, IN `p_alamat_bruder` TEXT)   BEGIN
    DECLARE username_count INT;
    SELECT COUNT(*) INTO username_count FROM `bruder` WHERE `username` = p_username;

    IF username_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Gagal menambahkan bruder. Username sudah terdaftar.';
    ELSE
        -- Mengganti email dengan username
        INSERT INTO `bruder` (
            `nama_bruder`, `username`, `password`, `status`,
            `id_komunitas`, `ttl_bruder`, `alamat_bruder`
        ) VALUES (
            p_nama_bruder, p_username, SHA2(p_password, 256), 'bruder',
            p_id_komunitas, p_ttl_bruder, p_alamat_bruder
        );
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `update_data_bruder` (IN `p_id_bruder` INT, IN `p_nama_bruder_baru` VARCHAR(100), IN `p_alamat_baru` TEXT, IN `p_ttl_baru` DATE)   BEGIN
    UPDATE `bruder`
    SET
        `nama_bruder` = p_nama_bruder_baru,
        `alamat_bruder` = p_alamat_baru,
        `ttl_bruder` = p_ttl_baru
    WHERE
        `id_bruder` = p_id_bruder;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `bruder`
--

CREATE TABLE `bruder` (
  `id_bruder` int(11) NOT NULL,
  `nama_bruder` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Simpan dalam bentuk hash',
  `status` enum('admin','bruder') NOT NULL DEFAULT 'bruder',
  `id_komunitas` int(11) DEFAULT NULL,
  `ttl_bruder` date DEFAULT NULL,
  `alamat_bruder` text DEFAULT NULL,
  `gambar_bruder` varchar(255) DEFAULT NULL,
  `thn_msk_pst` year(4) DEFAULT NULL,
  `riwayat_tugas` text DEFAULT NULL,
  `id_cabang` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bruder`
--

INSERT INTO `bruder` (`id_bruder`, `nama_bruder`, `email`, `password`, `status`, `id_komunitas`, `ttl_bruder`, `alamat_bruder`, `gambar_bruder`, `thn_msk_pst`, `riwayat_tugas`, `id_cabang`) VALUES
(1, 'Bruder Yohanes', 'yohanes@bruderan.org', '$2y$10$...', 'admin', 1, '1980-05-15', 'Asrama Komunitas St. Fransiskus', NULL, '2000', NULL, 1),
(2, 'Bruder Petrus', 'petrus@bruderan.org', '$2y$10$...', 'bruder', 1, '1992-11-20', 'Asrama Komunitas St. Fransiskus', NULL, '2012', NULL, 1),
(3, 'Bruder Antonius', 'antonius@bruderan.org', '$2y$10$...', 'bruder', 1, '1988-01-30', 'Asrama Komunitas St. Yusuf', NULL, '2008', NULL, 1),
(4, 'Bruder Paulus', 'paulus.jakarta@ficbruderan.org', 'password123', 'bruder', 1, '1985-03-20', 'Asrama Komunitas St. Fransiskus, Jakarta', NULL, NULL, NULL, 1),
(5, 'Bruder Markus', 'markus.jakarta@ficbruderan.org', 'password123', 'bruder', 1, '1982-07-15', 'Asrama Komunitas St. Fransiskus, Jakarta', NULL, NULL, NULL, 1),
(6, 'Bruder Petrus', 'petrus.bandung@ficbruderan.org', 'password123', 'bruder', 2, '1988-11-10', 'Asrama Komunitas St. Petrus, Bandung', NULL, NULL, NULL, 2),
(7, 'Bruder Andreas', 'andreas.bandung@ficbruderan.org', 'password123', 'bruder', 2, '1990-05-25', 'Asrama Komunitas St. Petrus, Bandung', NULL, NULL, NULL, 2),
(8, 'Bruder Lukas', 'lukas.bandung@ficbruderan.org', 'password123', 'bruder', 2, '1987-09-12', 'Asrama Komunitas St. Petrus, Bandung', NULL, NULL, NULL, 2),
(9, 'Bruder Antonius', 'antonius.surabaya@ficbruderan.org', 'password123', 'bruder', 3, '1986-01-30', 'Asrama Komunitas St. Paulus, Surabaya', NULL, NULL, NULL, 3),
(10, 'Bruder Stefanus', 'stefanus.surabaya@ficbruderan.org', 'password123', 'bruder', 3, '1989-04-18', 'Asrama Komunitas St. Paulus, Surabaya', NULL, NULL, NULL, 3),
(11, 'Bruder Filipus', 'filipus.surabaya@ficbruderan.org', 'password123', 'bruder', 3, '1984-12-08', 'Asrama Komunitas St. Paulus, Surabaya', NULL, NULL, NULL, 3),
(12, 'Bruder Yakobus', 'yakobus.medan@ficbruderan.org', 'password123', 'bruder', 4, '1983-06-22', 'Asrama Komunitas St. Andreas, Medan', NULL, NULL, NULL, 4),
(13, 'Bruder Yudas', 'yudas.medan@ficbruderan.org', 'password123', 'bruder', 4, '1987-08-14', 'Asrama Komunitas St. Andreas, Medan', NULL, NULL, NULL, 4),
(14, 'Bruder Matias', 'matias.medan@ficbruderan.org', 'password123', 'bruder', 4, '1985-02-28', 'Asrama Komunitas St. Andreas, Medan', NULL, NULL, NULL, 4),
(15, 'Bruder Simon', 'simon.makassar@ficbruderan.org', 'password123', 'bruder', 5, '1984-10-05', 'Asrama Komunitas St. Lukas, Makassar', NULL, NULL, NULL, 5),
(16, 'Bruder Bartolomeus', 'bartolomeus.makassar@ficbruderan.org', 'password123', 'bruder', 5, '1986-12-20', 'Asrama Komunitas St. Lukas, Makassar', NULL, NULL, NULL, 5),
(17, 'Bruder Tadeus', 'tadeus.makassar@ficbruderan.org', 'password123', 'bruder', 5, '1988-03-15', 'Asrama Komunitas St. Lukas, Makassar', NULL, NULL, NULL, 5);

--
-- Trigger `bruder`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_bruder` AFTER UPDATE ON `bruder` FOR EACH ROW BEGIN
    IF NEW.status <> OLD.status THEN
        INSERT INTO log_perubahan_bruder (id_bruder, info_perubahan)
        VALUES (NEW.id_bruder, CONCAT('Status diubah dari ', OLD.status, ' menjadi ', NEW.status));
    END IF;
    IF NEW.id_komunitas <> OLD.id_komunitas THEN
        INSERT INTO log_perubahan_bruder (id_bruder, info_perubahan)
        VALUES (NEW.id_bruder, CONCAT('Pindah tugas dari komunitas ID ', OLD.id_komunitas, ' ke ID ', NEW.id_komunitas));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `bruder_backup`
--

CREATE TABLE `bruder_backup` (
  `id_bruder` int(11) NOT NULL DEFAULT 0,
  `nama_bruder` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Simpan dalam bentuk hash',
  `status` enum('admin','bruder') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'bruder',
  `id_komunitas` int(11) DEFAULT NULL,
  `ttl_bruder` date DEFAULT NULL,
  `alamat_bruder` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gambar_bruder` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `thn_msk_pst` year(4) DEFAULT NULL,
  `riwayat_tugas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_cabang` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `bruder_backup`
--

INSERT INTO `bruder_backup` (`id_bruder`, `nama_bruder`, `email`, `password`, `status`, `id_komunitas`, `ttl_bruder`, `alamat_bruder`, `gambar_bruder`, `thn_msk_pst`, `riwayat_tugas`, `id_cabang`) VALUES
(1, 'Bruder Yohanes', 'yohanes@bruderan.org', '$2y$10$...', 'admin', 1, '1980-05-15', 'Asrama Komunitas St. Fransiskus', NULL, '2000', NULL, 1),
(2, 'Bruder Petrus', 'petrus@bruderan.org', '$2y$10$...', 'bruder', 2, '1992-11-20', 'Asrama Komunitas St. Fransiskus', NULL, '2012', NULL, 1),
(3, 'Bruder Antonius', 'antonius@bruderan.org', '$2y$10$...', 'bruder', 2, '1988-01-30', 'Asrama Komunitas St. Yusuf', NULL, '2008', NULL, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `cabang`
--

CREATE TABLE `cabang` (
  `id_cabang` int(11) NOT NULL,
  `kode_cabang` varchar(10) NOT NULL COMMENT 'Kode unik: FIC01, FIC02, dll',
  `nama_cabang` varchar(100) NOT NULL,
  `alamat_cabang` text DEFAULT NULL,
  `kontak_person` varchar(100) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `cabang`
--

INSERT INTO `cabang` (`id_cabang`, `kode_cabang`, `nama_cabang`, `alamat_cabang`, `kontak_person`, `telepon`, `email`, `status`, `created_at`, `updated_at`) VALUES
(1, 'FIC01', 'Cabang Jakarta Pusat', 'Jl. Thamrin No. 1, Jakarta Pusat', 'Admin Jakarta', '021-1234567', 'jakarta@ficbruderan.org', 'aktif', '2025-09-29 08:46:20', '2025-09-29 08:46:20'),
(2, 'FIC02', 'Cabang Bandung', 'Jl. Asia Afrika No. 123, Bandung', 'Admin Bandung', '022-7654321', 'bandung@ficbruderan.org', 'aktif', '2025-09-29 08:46:20', '2025-09-29 08:46:20'),
(3, 'FIC03', 'Cabang Surabaya', 'Jl. Pemuda No. 456, Surabaya', 'Admin Surabaya', '031-9876543', 'surabaya@ficbruderan.org', 'aktif', '2025-09-29 08:46:20', '2025-09-29 08:46:20'),
(4, 'FIC04', 'Cabang Medan', 'Jl. Gatot Subroto No. 789, Medan', 'Admin Medan', '061-4567891', 'medan@ficbruderan.org', 'aktif', '2025-09-29 08:46:20', '2025-09-29 08:46:20'),
(5, 'FIC05', 'Cabang Makassar', 'Jl. Sudirman No. 321, Makassar', 'Admin Makassar', '0411-2345678', 'makassar@ficbruderan.org', 'aktif', '2025-09-29 08:46:20', '2025-09-29 08:46:20');

--
-- Trigger `cabang`
--
DELIMITER $$
CREATE TRIGGER `trg_cabang_updated_at` BEFORE UPDATE ON `cabang` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cabang_backup`
--

CREATE TABLE `cabang_backup` (
  `id_cabang` int(11) NOT NULL DEFAULT 0,
  `kode_cabang` varchar(10) NOT NULL COMMENT 'Kode unik: FIC01, FIC02, dll',
  `nama_cabang` varchar(100) NOT NULL,
  `alamat_cabang` text DEFAULT NULL,
  `kontak_person` varchar(100) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `cabang_backup`
--

INSERT INTO `cabang_backup` (`id_cabang`, `kode_cabang`, `nama_cabang`, `alamat_cabang`, `kontak_person`, `telepon`, `email`, `status`, `created_at`, `updated_at`) VALUES
(1, 'FIC01', 'Cabang Jakarta Pusat', 'Jl. Thamrin No. 1, Jakarta Pusat', 'Admin Jakarta', '021-1234567', 'jakarta@ficbruderan.org', 'aktif', '2025-09-29 08:46:20', '2025-09-29 08:46:20'),
(2, 'FIC02', 'Cabang Bandung', 'Jl. Asia Afrika No. 123, Bandung', 'Admin Bandung', '022-7654321', 'bandung@ficbruderan.org', 'aktif', '2025-09-29 08:46:20', '2025-09-29 08:46:20'),
(3, 'FIC03', 'Cabang Surabaya', 'Jl. Pemuda No. 456, Surabaya', 'Admin Surabaya', '031-9876543', 'surabaya@ficbruderan.org', 'aktif', '2025-09-29 08:46:20', '2025-09-29 08:46:20'),
(4, 'FIC04', 'Cabang Medan', 'Jl. Gatot Subroto No. 789, Medan', 'Admin Medan', '061-4567891', 'medan@ficbruderan.org', 'aktif', '2025-09-29 08:46:20', '2025-09-29 08:46:20'),
(5, 'FIC05', 'Cabang Makassar', 'Jl. Sudirman No. 321, Makassar', 'Admin Makassar', '0411-2345678', 'makassar@ficbruderan.org', 'aktif', '2025-09-29 08:46:20', '2025-09-29 08:46:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kode_perkiraan`
--

CREATE TABLE `kode_perkiraan` (
  `id_perkiraan` int(11) NOT NULL,
  `kode_perkiraan` varchar(20) NOT NULL,
  `nama_akun` varchar(100) NOT NULL,
  `pos` varchar(50) DEFAULT NULL,
  `tipe_akun` enum('Penerimaan','Pengeluaran') NOT NULL,
  `id_cabang` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kode_perkiraan`
--

INSERT INTO `kode_perkiraan` (`id_perkiraan`, `kode_perkiraan`, `nama_akun`, `pos`, `tipe_akun`, `id_cabang`) VALUES
(1, '110100', 'Kas', 'A', 'Penerimaan', 1),
(2, '110300', 'Bank', 'B', 'Penerimaan', 1),
(3, '410101', 'Gaji/Pendapatan Bruder', 'C', 'Penerimaan', 1),
(4, '410102', 'Pensiun Bruder', 'D', 'Penerimaan', 1),
(5, '430101', 'Hasil Kebun Dan Piaraan', 'E', 'Penerimaan', 1),
(6, '420101', 'Bunga Tabungan', 'F', 'Penerimaan', 1),
(7, '410202', 'Sumbangan', 'G', 'Penerimaan', 1),
(8, '430103', 'Penerimaan Lainnya', 'H', 'Penerimaan', 1),
(9, '610100', 'Penerimaan dari DP', 'I', 'Penerimaan', 1),
(10, '510101', 'Makanan', '1', 'Pengeluaran', 1),
(11, '510201', 'Pakaian Dan Perlengkapan Pribadi', '2', 'Pengeluaran', 1),
(12, '510301', 'Pemeriksaan Dan Pengobatan', '3', 'Pengeluaran', 1),
(13, '510303', 'Hiburan / Rekreasi', '4', 'Pengeluaran', 1),
(14, '510501', 'Transport Harian', '5', 'Pengeluaran', 1),
(15, '520401', 'Sewa Pribadi', '6', 'Pengeluaran', 1),
(16, '510102', 'Bahan Bakar Dapur', '7', 'Pengeluaran', 1),
(17, '510103', 'Perlengkapan Cuci dan Kebersihan', '8', 'Pengeluaran', 1),
(18, '510104', 'Perabot Rumah Tangga', '9', 'Pengeluaran', 1),
(19, '510105', 'Iuran Hidup Bermasyarakat Dan Menggereja', '10', 'Pengeluaran', 1),
(20, '510401', 'Listrik', '11', 'Pengeluaran', 1),
(21, '510402', 'Air', '12', 'Pengeluaran', 1),
(22, '510403', 'Telepon Dan Internet', '13', 'Pengeluaran', 1),
(23, '520201', 'Keperluan Ibadah', '14', 'Pengeluaran', 1),
(24, '530302', 'Sumbangan', '15', 'Pengeluaran', 1),
(25, '540101', 'Insentif ART', '16', 'Pengeluaran', 1),
(26, '540201', 'Pemeliharaan Rumah', '17', 'Pengeluaran', 1),
(27, '540202', 'Pemeliharaan Kebun Dan Piaraan', '18', 'Pengeluaran', 1),
(28, '540203', 'Pemeliharaan Kendaraan', '19', 'Pengeluaran', 1),
(29, '540204', 'Pemeliharaan Mesin Dan Peralatan', '20', 'Pengeluaran', 1),
(30, '550101', 'Administrasi Komunitas', '21', 'Pengeluaran', 1),
(31, '550102', 'Legal dan Perijinan', '22', 'Pengeluaran', 1),
(32, '550106', 'Buku, Majalah, Koran', '23', 'Pengeluaran', 1),
(33, '550107', 'Administrasi Bank', '24', 'Pengeluaran', 1),
(34, '550201', 'Pajak Bunga Bank', '25', 'Pengeluaran', 1),
(35, '550202', 'Pajak Kendaraan dan PBB', '26', 'Pengeluaran', 1),
(36, '110202', 'Kas Kecil DP', '27', 'Pengeluaran', 1),
(37, '110201', 'Kas Kecil Komunitas', '28', 'Pengeluaran', 1),
(38, '520501', 'Penunjang Kesehatan Lansia', '29', 'Pengeluaran', 1),
(39, '520502', 'Pemeliharaan Rohani Lansia', '30', 'Pengeluaran', 1),
(40, '520503', 'Kegiatan Bruder Lansia', '31', 'Pengeluaran', 1),
(41, '130400', 'Mesin dan Peralatan', '32', 'Pengeluaran', 1),
(42, '510100', 'Perabot Rumah Tangga', '33', 'Pengeluaran', 1),
(43, '510502', 'Transport Pertemuan', '34', 'Pengeluaran', 1),
(44, '520300', 'Perayaan Syukur', '35', 'Pengeluaran', 1),
(45, '520400', 'Kegiatan Lainnya', '36', 'Pengeluaran', 1),
(46, '540200', 'Pemeliharaan Rumah', '37', 'Pengeluaran', 1),
(47, '550100', 'Budget Khusus Lainnya', '38', 'Pengeluaran', 1),
(48, '510300', 'Pemeriksaan dan Pengobatan', '39', 'Pengeluaran', 1),
(49, '550300', 'Pertemuan DP', '40', 'Pengeluaran', 1),
(50, '530100', 'Kegiatan Acc. DP', '41', 'Pengeluaran', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `komunitas`
--

CREATE TABLE `komunitas` (
  `id_komunitas` int(11) NOT NULL,
  `nama_komunitas` varchar(150) NOT NULL,
  `alamat_komunitas` text DEFAULT NULL,
  `id_cabang` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `komunitas`
--

INSERT INTO `komunitas` (`id_komunitas`, `nama_komunitas`, `alamat_komunitas`, `id_cabang`) VALUES
(1, 'Komunitas St. Fransiskus Asisi', 'Jl. Merdeka No. 10, Jakarta', 1),
(2, 'Komunitas St. Yusuf', 'Jl. Pahlawan No. 5, Bandung', 1),
(3, 'Komunitas St. Fransiskus', 'Jl. Thamrin No. 1, Jakarta Pusat', 1),
(4, 'Komunitas St. Yusuf', 'Jl. Sudirman No. 50, Jakarta Pusat', 1),
(5, 'Komunitas St. Petrus', 'Jl. Asia Afrika No. 123, Bandung', 2),
(6, 'Komunitas St. Paulus', 'Jl. Diponegoro No. 456, Surabaya', 3),
(7, 'Komunitas St. Andreas', 'Jl. SM. Raja No. 789, Medan', 4),
(8, 'Komunitas St. Lukas', 'Jl. AP. Pettarani No. 321, Makassar', 5),
(9, 'Komunitas St. Fransiskus', 'Jl. Thamrin No. 1, Jakarta Pusat', 1),
(10, 'Komunitas St. Yusuf', 'Jl. Sudirman No. 50, Jakarta Pusat', 1),
(11, 'Komunitas St. Petrus', 'Jl. Asia Afrika No. 123, Bandung', 2),
(12, 'Komunitas St. Paulus', 'Jl. Diponegoro No. 456, Surabaya', 3),
(13, 'Komunitas St. Andreas', 'Jl. SM. Raja No. 789, Medan', 4),
(14, 'Komunitas St. Lukas', 'Jl. AP. Pettarani No. 321, Makassar', 5);

-- --------------------------------------------------------

--
-- Struktur dari tabel `komunitas_backup`
--

CREATE TABLE `komunitas_backup` (
  `id_komunitas` int(11) NOT NULL DEFAULT 0,
  `nama_komunitas` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `alamat_komunitas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_cabang` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `komunitas_backup`
--

INSERT INTO `komunitas_backup` (`id_komunitas`, `nama_komunitas`, `alamat_komunitas`, `id_cabang`) VALUES
(1, 'Komunitas St. Fransiskus Asisi', 'Jl. Merdeka No. 10, Jakarta', 1),
(2, 'Komunitas St. Yusuf', 'Jl. Pahlawan No. 5, Bandung', 1);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `laporan_keuangan_per_cabang`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `laporan_keuangan_per_cabang` (
`nama_cabang` varchar(100)
,`kode_cabang` varchar(10)
,`jumlah_transaksi` bigint(21)
,`total_penerimaan` decimal(37,2)
,`total_pengeluaran` decimal(37,2)
,`saldo_akhir` decimal(38,2)
);

-- --------------------------------------------------------

--
-- Struktur dari tabel `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(25) NOT NULL,
  `id_cabang` int(11) NOT NULL DEFAULT 1,
  `level` enum('admin_cabang','bendahara','sekretariat') DEFAULT 'bendahara'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `login`
--

INSERT INTO `login` (`id`, `username`, `password`, `id_cabang`, `level`) VALUES
(1, 'bruderjakartapusat', 'bruderjakartapusat', 1, ''),
(2, 'bendaharajakartapusat', 'bendaharajakartapusat', 1, 'bendahara'),
(3, 'adminjakartapusat', 'adminjakartapusat', 1, 'admin_cabang'),
(4, 'bruderbandung', 'bruderbandung', 2, ''),
(5, 'bendaharabandung', 'bendaharabandung', 2, 'bendahara'),
(6, 'adminbandung', 'adminbandung', 2, 'admin_cabang'),
(7, 'brudersurabaya', 'brudersurabaya', 3, ''),
(8, 'bendaharasurabaya', 'bendaharasurabaya', 3, 'bendahara'),
(9, 'adminsurabaya', 'adminsurabaya', 3, 'admin_cabang'),
(10, 'brudermedan', 'brudermedan', 4, ''),
(11, 'bendaharamedan', 'bendaharamedan', 4, 'bendahara'),
(12, 'adminmedan', 'adminmedan', 4, 'admin_cabang'),
(13, 'brudermakassar', 'brudermakassar', 5, ''),
(14, 'bendaharamakassar', 'bendaharamakassar', 5, 'bendahara'),
(15, 'adminmakassar', 'adminmakassar', 5, 'admin_cabang');

-- --------------------------------------------------------

--
-- Struktur dari tabel `login_backup`
--

CREATE TABLE `login_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `username` varchar(25) NOT NULL,
  `password` varchar(25) NOT NULL,
  `id_cabang` int(11) NOT NULL DEFAULT 1,
  `level` enum('admin_cabang','bendahara','sekretariat') DEFAULT 'bendahara'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `login_backup`
--

INSERT INTO `login_backup` (`id`, `username`, `password`, `id_cabang`, `level`) VALUES
(1, 'admin', 'admin', 1, 'admin_cabang');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_perubahan_bruder`
--

CREATE TABLE `log_perubahan_bruder` (
  `id_log` int(11) NOT NULL,
  `id_bruder` int(11) DEFAULT NULL,
  `info_perubahan` text NOT NULL,
  `waktu_perubahan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `log_perubahan_bruder`
--

INSERT INTO `log_perubahan_bruder` (`id_log`, `id_bruder`, `info_perubahan`, `waktu_perubahan`) VALUES
(3, 2, 'Pindah tugas dari komunitas ID 1 ke ID 2', '2025-09-14 16:27:50'),
(4, 2, 'Pindah tugas dari komunitas ID 2 ke ID 1', '2025-09-29 09:22:13'),
(5, 3, 'Pindah tugas dari komunitas ID 2 ke ID 1', '2025-09-29 09:22:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengajuan_pengeluaran`
--

CREATE TABLE `pengajuan_pengeluaran` (
  `id_pengajuan` int(11) NOT NULL,
  `id_bruder` int(11) NOT NULL,
  `id_perkiraan` int(11) NOT NULL,
  `id_cabang` int(11) NOT NULL,
  `tanggal_pengajuan` datetime NOT NULL DEFAULT current_timestamp(),
  `keterangan` text DEFAULT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `foto_bukti` varchar(255) DEFAULT NULL COMMENT 'Nama file foto bukti',
  `status` enum('pending','disetujui','ditolak') NOT NULL DEFAULT 'pending',
  `tanggal_aksi` datetime DEFAULT NULL COMMENT 'Waktu bendahara approve/reject',
  `catatan_bendahara` text DEFAULT NULL COMMENT 'Alasan jika ditolak',
  `diperiksa_oleh` int(11) DEFAULT NULL COMMENT 'ID user bendahara/admin yg aksi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengajuan_pengeluaran`
--

INSERT INTO `pengajuan_pengeluaran` (`id_pengajuan`, `id_bruder`, `id_perkiraan`, `id_cabang`, `tanggal_pengajuan`, `keterangan`, `nominal`, `foto_bukti`, `status`, `tanggal_aksi`, `catatan_bendahara`, `diperiksa_oleh`) VALUES
(1, 6, 37, 2, '2025-10-02 21:40:26', 'biaya', 500000.00, NULL, 'disetujui', '2025-10-02 21:54:26', NULL, NULL),
(2, 6, 20, 2, '2025-10-02 21:46:25', 'listrik', 100000.00, NULL, 'disetujui', '2025-10-02 21:56:59', NULL, NULL),
(3, 6, 36, 2, '2025-10-02 21:46:50', 'listrik', 200000.00, 'bukti_68de905a26e950.81192897.jpg', 'disetujui', '2025-10-02 21:51:10', NULL, NULL),
(4, 6, 36, 2, '2025-10-02 21:57:31', 'biaya', 500000.00, 'bukti_68de92dbcda8d6.54440341.jpg', 'disetujui', '2025-10-02 21:57:43', NULL, NULL),
(5, 6, 37, 2, '2025-10-02 21:57:59', 'biaya', 500000.00, NULL, 'ditolak', '2025-10-02 21:58:20', 'tolak', NULL),
(6, 6, 37, 2, '2025-10-03 00:04:57', 'biaya', 500000.00, 'bukti_68deb0b9a57508.16663469.jpg', 'disetujui', '2025-10-03 00:05:13', NULL, NULL),
(7, 6, 45, 2, '2025-10-03 10:37:01', 'listrik', 500000.00, 'bukti_68df44dde0b802.00029655.jpg', 'disetujui', '2025-10-03 10:37:53', NULL, NULL),
(8, 6, 10, 2, '2025-10-03 18:29:33', 'biaya', 500000.00, 'bukti_68dfb39d75d037.56079985.jpg', 'disetujui', '2025-10-03 18:48:10', NULL, NULL),
(9, 6, 37, 2, '2025-10-03 18:38:25', 'listrik', 500000.00, NULL, 'disetujui', '2025-10-03 18:38:47', NULL, NULL),
(10, 6, 21, 2, '2025-10-03 18:49:24', 'air', 51000.00, NULL, 'disetujui', '2025-10-03 18:49:53', NULL, NULL),
(11, 6, 10, 2, '2025-10-03 18:49:39', 'makanan', 52000.00, NULL, 'disetujui', '2025-10-03 18:52:22', NULL, NULL),
(12, 6, 36, 2, '2025-10-03 19:00:12', 'listrik', 52000.00, NULL, 'disetujui', '2025-10-03 19:00:24', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `perjalanan_bruder`
--

CREATE TABLE `perjalanan_bruder` (
  `id_perjalanan` int(11) NOT NULL,
  `id_bruder` int(11) NOT NULL,
  `tanggal_berangkat` date NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `jumlah_hari` int(11) DEFAULT NULL COMMENT 'Akan diisi otomatis oleh trigger',
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `perjalanan_bruder`
--

INSERT INTO `perjalanan_bruder` (`id_perjalanan`, `id_bruder`, `tanggal_berangkat`, `tanggal_kembali`, `jumlah_hari`, `keterangan`) VALUES
(1, 3, '2025-02-10', '2025-02-15', 6, 'Mengikuti retret tahunan di Lembang'),
(2, 3, '2025-02-10', '2025-02-15', 6, 'Mengikuti retret tahunan di Lembang'),
(3, 6, '2025-10-01', '2025-10-02', 2, 'yapping'),
(4, 6, '2025-10-02', '2025-10-03', 2, 'adada');

--
-- Trigger `perjalanan_bruder`
--
DELIMITER $$
CREATE TRIGGER `trg_hitung_jumlah_hari_insert` BEFORE INSERT ON `perjalanan_bruder` FOR EACH ROW BEGIN
    -- Menghitung selisih hari (inklusif)
    SET NEW.jumlah_hari = DATEDIFF(NEW.tanggal_kembali, NEW.tanggal_berangkat) + 1;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_hitung_jumlah_hari_update` BEFORE UPDATE ON `perjalanan_bruder` FOR EACH ROW BEGIN
    -- Menghitung ulang jika tanggal berubah
    SET NEW.jumlah_hari = DATEDIFF(NEW.tanggal_kembali, NEW.tanggal_berangkat) + 1;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rencana_anggaran`
--

CREATE TABLE `rencana_anggaran` (
  `id_rab` int(11) NOT NULL,
  `id_bruder` int(11) NOT NULL,
  `id_perkiraan` int(11) NOT NULL,
  `bulan` int(2) NOT NULL COMMENT 'Contoh: 1 untuk Januari, 9 untuk September',
  `tahun` int(4) NOT NULL,
  `jumlah_anggaran` decimal(15,2) NOT NULL,
  `id_cabang` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `id_bruder` int(11) NOT NULL,
  `id_perkiraan` int(11) NOT NULL,
  `tanggal_transaksi` date NOT NULL,
  `keterangan` text DEFAULT NULL,
  `sumber_dana` enum('Kas Harian','Bank') NOT NULL,
  `nominal_penerimaan` decimal(15,2) DEFAULT 0.00,
  `nominal_pengeluaran` decimal(15,2) DEFAULT 0.00,
  `reff` varchar(50) DEFAULT NULL,
  `id_cabang` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `id_bruder`, `id_perkiraan`, `tanggal_transaksi`, `keterangan`, `sumber_dana`, `nominal_penerimaan`, `nominal_pengeluaran`, `reff`, `id_cabang`) VALUES
(1, 1, 1, '2025-01-01', 'Saldo awal kas harian tahun 2025', 'Kas Harian', 5000000.00, 0.00, NULL, 1),
(2, 2, 3, '2025-01-05', 'Pembayaran listrik bulan Januari', 'Kas Harian', 0.00, 750000.00, NULL, 1),
(3, 1, 3, '2025-09-22', 'biaya', 'Kas Harian', 0.00, 100000.00, NULL, 1),
(4, 1, 3, '2025-09-22', 'biaya', 'Kas Harian', 0.00, 100000.00, NULL, 1),
(5, 1, 3, '2025-09-22', 'biaya', 'Kas Harian', 5000000.00, 0.00, NULL, 1),
(6, 1, 3, '2025-09-22', 'biaya', 'Kas Harian', 5000000.00, 0.00, NULL, 1),
(7, 1, 1, '2025-09-22', 'biaya', 'Kas Harian', 0.00, 1000000.00, NULL, 1),
(8, 1, 3, '2025-09-25', 'makan', 'Kas Harian', 0.00, 5000.00, NULL, 1),
(9, 1, 2, '2025-01-15', 'Donasi misa minggu', 'Kas Harian', 500000.00, 0.00, NULL, 1),
(10, 1, 3, '2025-01-20', 'Bayar listrik januari', 'Kas Harian', 0.00, 300000.00, NULL, 1),
(11, 4, 2, '2025-01-16', 'Donasi kegiatan sosial', 'Kas Harian', 750000.00, 0.00, NULL, 1),
(12, 4, 4, '2025-01-25', 'Bayar transport kegiatan', 'Kas Harian', 0.00, 150000.00, NULL, 1),
(13, 5, 2, '2025-01-18', 'Donasi umat paroki', 'Kas Harian', 600000.00, 0.00, NULL, 1),
(14, 5, 3, '2025-01-28', 'Bayar maintenance gedung', 'Kas Harian', 0.00, 200000.00, NULL, 1),
(17, 7, 2, '2025-01-12', 'Donasi kegiatan kepemudaan', 'Kas Harian', 350000.00, 0.00, NULL, 2),
(18, 7, 4, '2025-01-24', 'Bayar bahan kegiatan', 'Kas Harian', 0.00, 100000.00, NULL, 2),
(19, 8, 2, '2025-01-14', 'Donasi misa harian', 'Kas Harian', 450000.00, 0.00, NULL, 2),
(20, 8, 3, '2025-01-26', 'Bayar kebersihan asrama', 'Kas Harian', 0.00, 80000.00, NULL, 2),
(25, 7, 20, '2025-10-01', 'listrik', 'Kas Harian', 0.00, 200000.00, NULL, 2),
(28, 1, 2, '2025-01-15', 'Donasi misa minggu', 'Kas Harian', 500000.00, 0.00, NULL, 1),
(29, 1, 3, '2025-01-20', 'Bayar listrik januari', 'Kas Harian', 0.00, 300000.00, NULL, 1),
(30, 4, 2, '2025-01-16', 'Donasi kegiatan sosial', 'Kas Harian', 750000.00, 0.00, NULL, 1),
(31, 4, 4, '2025-01-25', 'Bayar transport kegiatan', 'Kas Harian', 0.00, 150000.00, NULL, 1),
(32, 5, 2, '2025-01-18', 'Donasi umat paroki', 'Kas Harian', 600000.00, 0.00, NULL, 1),
(33, 5, 3, '2025-01-28', 'Bayar maintenance gedung', 'Kas Harian', 0.00, 200000.00, NULL, 1),
(36, 7, 2, '2025-01-12', 'Donasi kegiatan kepemudaan', 'Kas Harian', 350000.00, 0.00, NULL, 2),
(37, 7, 4, '2025-01-24', 'Bayar bahan kegiatan', 'Kas Harian', 0.00, 100000.00, NULL, 2),
(38, 8, 2, '2025-01-14', 'Donasi misa harian', 'Kas Harian', 450000.00, 0.00, NULL, 2),
(39, 8, 3, '2025-01-26', 'Bayar kebersihan asrama', 'Kas Harian', 0.00, 80000.00, NULL, 2),
(44, 6, 36, '2025-10-02', 'listrik', 'Kas Harian', 0.00, 200000.00, NULL, 2),
(45, 6, 37, '2025-10-02', 'biaya', 'Kas Harian', 0.00, 500000.00, NULL, 2),
(46, 6, 20, '2025-10-02', 'listrik', 'Kas Harian', 0.00, 100000.00, NULL, 2),
(47, 6, 36, '2025-10-02', 'biaya', 'Kas Harian', 0.00, 500000.00, NULL, 2),
(48, 6, 37, '2025-10-03', 'biaya', 'Kas Harian', 0.00, 500000.00, NULL, 2),
(49, 6, 45, '2025-10-03', 'listrik', 'Kas Harian', 0.00, 500000.00, NULL, 2),
(50, 6, 1, '2025-10-03', 'gaji', 'Kas Harian', 500000.00, 0.00, NULL, 2),
(51, 6, 36, '2025-10-03', 'listrik', 'Kas Harian', 50.00, 0.00, NULL, 2);

--
-- Trigger `transaksi`
--
DELIMITER $$
CREATE TRIGGER `trg_validasi_transaksi` BEFORE INSERT ON `transaksi` FOR EACH ROW BEGIN
    IF NEW.nominal_penerimaan > 0 AND NEW.nominal_pengeluaran > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaksi tidak boleh memiliki nominal penerimaan dan pengeluaran sekaligus.';
    END IF;
    IF NEW.nominal_penerimaan > 0 THEN
        SET NEW.nominal_pengeluaran = 0;
    END IF;
    IF NEW.nominal_pengeluaran > 0 THEN
        SET NEW.nominal_penerimaan = 0;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `view_bruder_per_cabang`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `view_bruder_per_cabang` (
`kode_cabang` varchar(10)
,`nama_cabang` varchar(100)
,`jumlah_bruder` bigint(21)
,`daftar_bruder` mediumtext
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `view_cabang_status`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `view_cabang_status` (
`id_cabang` int(11)
,`kode_cabang` varchar(10)
,`nama_cabang` varchar(100)
,`status` enum('aktif','nonaktif')
,`jumlah_bruder` bigint(21)
,`jumlah_komunitas` bigint(21)
,`jumlah_transaksi` bigint(21)
,`jumlah_login` bigint(21)
);

-- --------------------------------------------------------

--
-- Struktur untuk view `laporan_keuangan_per_cabang`
--
DROP TABLE IF EXISTS `laporan_keuangan_per_cabang`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `laporan_keuangan_per_cabang`  AS SELECT `c`.`nama_cabang` AS `nama_cabang`, `c`.`kode_cabang` AS `kode_cabang`, count(`t`.`id_transaksi`) AS `jumlah_transaksi`, sum(`t`.`nominal_penerimaan`) AS `total_penerimaan`, sum(`t`.`nominal_pengeluaran`) AS `total_pengeluaran`, sum(`t`.`nominal_penerimaan`) - sum(`t`.`nominal_pengeluaran`) AS `saldo_akhir` FROM (`cabang` `c` left join `transaksi` `t` on(`c`.`id_cabang` = `t`.`id_cabang`)) WHERE `c`.`status` = 'aktif' GROUP BY `c`.`id_cabang`, `c`.`nama_cabang`, `c`.`kode_cabang` ;

-- --------------------------------------------------------

--
-- Struktur untuk view `view_bruder_per_cabang`
--
DROP TABLE IF EXISTS `view_bruder_per_cabang`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_bruder_per_cabang`  AS SELECT `c`.`kode_cabang` AS `kode_cabang`, `c`.`nama_cabang` AS `nama_cabang`, count(`b`.`id_bruder`) AS `jumlah_bruder`, group_concat(`b`.`nama_bruder` order by `b`.`nama_bruder` ASC separator ', ') AS `daftar_bruder` FROM (`cabang` `c` left join `bruder` `b` on(`c`.`id_cabang` = `b`.`id_cabang`)) GROUP BY `c`.`id_cabang`, `c`.`kode_cabang`, `c`.`nama_cabang` ORDER BY `c`.`id_cabang` ASC ;

-- --------------------------------------------------------

--
-- Struktur untuk view `view_cabang_status`
--
DROP TABLE IF EXISTS `view_cabang_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_cabang_status`  AS SELECT `c`.`id_cabang` AS `id_cabang`, `c`.`kode_cabang` AS `kode_cabang`, `c`.`nama_cabang` AS `nama_cabang`, `c`.`status` AS `status`, count(distinct `b`.`id_bruder`) AS `jumlah_bruder`, count(distinct `k`.`id_komunitas`) AS `jumlah_komunitas`, count(distinct `t`.`id_transaksi`) AS `jumlah_transaksi`, count(distinct `l`.`id`) AS `jumlah_login` FROM ((((`cabang` `c` left join `bruder` `b` on(`c`.`id_cabang` = `b`.`id_cabang`)) left join `komunitas` `k` on(`c`.`id_cabang` = `k`.`id_cabang`)) left join `transaksi` `t` on(`c`.`id_cabang` = `t`.`id_cabang`)) left join `login` `l` on(`c`.`id_cabang` = `l`.`id_cabang`)) GROUP BY `c`.`id_cabang`, `c`.`kode_cabang`, `c`.`nama_cabang`, `c`.`status` ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `bruder`
--
ALTER TABLE `bruder`
  ADD PRIMARY KEY (`id_bruder`),
  ADD UNIQUE KEY `email_unik` (`email`),
  ADD KEY `fk_bruder_komunitas` (`id_komunitas`),
  ADD KEY `idx_bruder_cabang` (`id_cabang`);

--
-- Indeks untuk tabel `cabang`
--
ALTER TABLE `cabang`
  ADD PRIMARY KEY (`id_cabang`),
  ADD UNIQUE KEY `kode_cabang` (`kode_cabang`);

--
-- Indeks untuk tabel `kode_perkiraan`
--
ALTER TABLE `kode_perkiraan`
  ADD PRIMARY KEY (`id_perkiraan`),
  ADD UNIQUE KEY `kode_perkiraan_unik` (`kode_perkiraan`),
  ADD KEY `idx_kode_perkiraan_cabang` (`id_cabang`);

--
-- Indeks untuk tabel `komunitas`
--
ALTER TABLE `komunitas`
  ADD PRIMARY KEY (`id_komunitas`),
  ADD KEY `idx_komunitas_cabang` (`id_cabang`);

--
-- Indeks untuk tabel `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_cabang` (`id_cabang`),
  ADD KEY `idx_login_level` (`level`);

--
-- Indeks untuk tabel `log_perubahan_bruder`
--
ALTER TABLE `log_perubahan_bruder`
  ADD PRIMARY KEY (`id_log`);

--
-- Indeks untuk tabel `pengajuan_pengeluaran`
--
ALTER TABLE `pengajuan_pengeluaran`
  ADD PRIMARY KEY (`id_pengajuan`),
  ADD KEY `idx_bruder` (`id_bruder`),
  ADD KEY `idx_cabang` (`id_cabang`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `perjalanan_bruder`
--
ALTER TABLE `perjalanan_bruder`
  ADD PRIMARY KEY (`id_perjalanan`),
  ADD KEY `fk_perjalanan_bruder` (`id_bruder`);

--
-- Indeks untuk tabel `rencana_anggaran`
--
ALTER TABLE `rencana_anggaran`
  ADD PRIMARY KEY (`id_rab`),
  ADD UNIQUE KEY `anggaran_unik` (`id_bruder`,`id_perkiraan`,`bulan`,`tahun`),
  ADD KEY `fk_rab_perkiraan` (`id_perkiraan`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `fk_transaksi_bruder` (`id_bruder`),
  ADD KEY `fk_transaksi_perkiraan` (`id_perkiraan`),
  ADD KEY `idx_transaksi_cabang` (`id_cabang`),
  ADD KEY `idx_transaksi_cabang_tanggal` (`id_cabang`,`tanggal_transaksi`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `bruder`
--
ALTER TABLE `bruder`
  MODIFY `id_bruder` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT untuk tabel `cabang`
--
ALTER TABLE `cabang`
  MODIFY `id_cabang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `kode_perkiraan`
--
ALTER TABLE `kode_perkiraan`
  MODIFY `id_perkiraan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT untuk tabel `komunitas`
--
ALTER TABLE `komunitas`
  MODIFY `id_komunitas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `log_perubahan_bruder`
--
ALTER TABLE `log_perubahan_bruder`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pengajuan_pengeluaran`
--
ALTER TABLE `pengajuan_pengeluaran`
  MODIFY `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `perjalanan_bruder`
--
ALTER TABLE `perjalanan_bruder`
  MODIFY `id_perjalanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `rencana_anggaran`
--
ALTER TABLE `rencana_anggaran`
  MODIFY `id_rab` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `bruder`
--
ALTER TABLE `bruder`
  ADD CONSTRAINT `fk_bruder_komunitas` FOREIGN KEY (`id_komunitas`) REFERENCES `komunitas` (`id_komunitas`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `perjalanan_bruder`
--
ALTER TABLE `perjalanan_bruder`
  ADD CONSTRAINT `fk_perjalanan_bruder` FOREIGN KEY (`id_bruder`) REFERENCES `bruder` (`id_bruder`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rencana_anggaran`
--
ALTER TABLE `rencana_anggaran`
  ADD CONSTRAINT `fk_rab_bruder` FOREIGN KEY (`id_bruder`) REFERENCES `bruder` (`id_bruder`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rab_perkiraan` FOREIGN KEY (`id_perkiraan`) REFERENCES `kode_perkiraan` (`id_perkiraan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `fk_transaksi_bruder` FOREIGN KEY (`id_bruder`) REFERENCES `bruder` (`id_bruder`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaksi_perkiraan` FOREIGN KEY (`id_perkiraan`) REFERENCES `kode_perkiraan` (`id_perkiraan`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
