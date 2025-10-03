-- =====================================================================
--            SCRIPT UNTUK MEMBUAT TABEL PENGAJUAN PENGELUARAN
-- =====================================================================

CREATE TABLE `pengajuan_pengeluaran` (
  `id_pengajuan` INT(11) NOT NULL AUTO_INCREMENT,
  `id_bruder` INT(11) NOT NULL,
  `id_perkiraan` INT(11) NOT NULL,
  `id_cabang` INT(11) NOT NULL,
  `tanggal_pengajuan` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `keterangan` TEXT DEFAULT NULL,
  `nominal` DECIMAL(15,2) NOT NULL,
  `foto_bukti` VARCHAR(255) DEFAULT NULL COMMENT 'Nama file foto bukti',
  `status` ENUM('pending','disetujui','ditolak') NOT NULL DEFAULT 'pending',
  `tanggal_aksi` DATETIME DEFAULT NULL COMMENT 'Waktu bendahara approve/reject',
  `catatan_bendahara` TEXT DEFAULT NULL COMMENT 'Alasan jika ditolak',
  `diperiksa_oleh` INT(11) DEFAULT NULL COMMENT 'ID user bendahara/admin yg aksi',
  PRIMARY KEY (`id_pengajuan`),
  KEY `idx_bruder` (`id_bruder`),
  KEY `idx_cabang` (`id_cabang`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
