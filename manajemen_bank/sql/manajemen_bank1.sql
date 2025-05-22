-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2025 at 03:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `manajemen_bank1`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ajukan_pinjaman` (IN `p_id_akun` INT, IN `p_jumlah` INT, IN `p_jangka` INT)   BEGIN
    DECLARE v_jumlah_aktif INT;

    -- Cek apakah pengguna punya pinjaman aktif
    SELECT COUNT(*) INTO v_jumlah_aktif
    FROM pengajuan_pinjaman p
    JOIN jadwal_pembayaran_pinjaman j ON p.id_pinjaman = j.id_pinjaman
    WHERE p.id_akun = p_id_akun 
      AND p.status_pinjaman = 'Disetujui'
      AND j.status_pembayaran = 'Belum Lunas';

    -- Jika tidak ada pinjaman aktif, izinkan pengajuan baru
    IF v_jumlah_aktif = 0 THEN
        INSERT INTO pengajuan_pinjaman (id_akun, jumlah_pinjaman, status_pinjaman, jangka)
        VALUES (p_id_akun, p_jumlah, 'Menunggu', p_jangka);
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tidak bisa mengajukan pinjaman, masih ada pinjaman aktif yang belum lunas.';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_bayar_pinjaman` (IN `p_id_akun` INT, IN `p_id_jadwal` INT, IN `p_jumlah` DECIMAL(15,2))   BEGIN
    -- Update status pembayaran
    UPDATE jadwal_pembayaran_pinjaman
    SET status_pembayaran = 'Lunas'
    WHERE id_jadwal = p_id_jadwal AND jumlah_bayar = p_jumlah;

    -- Kurangi saldo akun nasabah
    UPDATE akun_nasabah
    SET saldo = saldo - p_jumlah
    WHERE id_akun = p_id_akun;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_setor` (IN `p_id_akun` INT, IN `p_jumlah` INT)   BEGIN
    INSERT INTO transaksi (id_akun, jenis_transaksi, jumlah, waktu_transaksi, tanggal_transaksi)
    VALUES (p_id_akun, 'Setor', p_jumlah, NOW(), NOW());
    UPDATE akun_nasabah SET saldo = saldo + p_jumlah WHERE id_akun = p_id_akun;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_setujui_pinjaman` (IN `p_id_pinjaman` INT)   BEGIN
    -- Set status pinjaman menjadi 'Disetujui'
    UPDATE pengajuan_pinjaman
    SET status_pinjaman = 'Disetujui'
    WHERE id_pinjaman = p_id_pinjaman;

    -- Hapus jadwal pembayaran sebelumnya (jika ada)
    DELETE FROM jadwal_pembayaran_pinjaman
    WHERE id_pinjaman = p_id_pinjaman;

    -- Ambil data pinjaman
    SELECT jumlah_pinjaman, jangka INTO @jumlah_pinjaman, @jangka
    FROM pengajuan_pinjaman
    WHERE id_pinjaman = p_id_pinjaman;

    -- Hitung jumlah cicilan per bulan
    SET @jumlah_cicilan = @jumlah_pinjaman / @jangka;

    -- Buat jadwal pembayaran
    SET @tanggal_mulai = CURDATE();
    SET @id_jadwal = 0;

    WHILE @id_jadwal < @jangka DO
        SET @id_jadwal = @id_jadwal + 1;
        INSERT INTO jadwal_pembayaran_pinjaman (
            id_pinjaman, 
            jumlah_bayar, 
            tanggal_jatuh_tempo, 
            status_pembayaran
        ) VALUES (
            p_id_pinjaman, 
            @jumlah_cicilan, 
            DATE_ADD(@tanggal_mulai, INTERVAL @id_jadwal MONTH), 
            'Belum Lunas'
        );
    END WHILE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tarik` (IN `p_id_akun` INT, IN `p_jumlah` INT)   BEGIN
    INSERT INTO transaksi (id_akun, jenis_transaksi, jumlah, waktu_transaksi, tanggal_transaksi)
    VALUES (p_id_akun, 'Tarik', p_jumlah, NOW(), NOW());
    UPDATE akun_nasabah SET saldo = saldo - p_jumlah WHERE id_akun = p_id_akun;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_transfer` (IN `p_id_pengirim` INT, IN `p_id_penerima` INT, IN `p_jumlah` INT)   BEGIN
    DECLARE v_id_transaksi INT;

    INSERT INTO transaksi (id_akun, jenis_transaksi, jumlah, waktu_transaksi, tanggal_transaksi)
    VALUES (p_id_pengirim, 'Transfer', p_jumlah, NOW(), NOW());

    SET v_id_transaksi = LAST_INSERT_ID();

    INSERT INTO riwayat_transfer (id_transaksi, akun_tujuan, status_transfer, tanggal)
    VALUES (v_id_transaksi, p_id_penerima, 'Berhasil', NOW());

    UPDATE akun_nasabah SET saldo = saldo - p_jumlah WHERE id_akun = p_id_pengirim;
    UPDATE akun_nasabah SET saldo = saldo + p_jumlah WHERE id_akun = p_id_penerima;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `akun_nasabah`
--

CREATE TABLE `akun_nasabah` (
  `id_akun` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `saldo` decimal(15,2) DEFAULT NULL,
  `status_akun` enum('Aktif','NonAktif') NOT NULL DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `akun_nasabah`
--

INSERT INTO `akun_nasabah` (`id_akun`, `nama`, `password`, `nik`, `alamat`, `no_hp`, `jenis_kelamin`, `saldo`, `status_akun`) VALUES
(14, 'Mufid Afkarrafi Wafairuzzaki', '123', '987654321', 'tbn', '081559555597', 'Laki-laki', 70000.00, 'Aktif'),
(18, 'Zaki', '123', '891234567', 'tbn', '081559555597', 'Laki-laki', 7000000.02, 'Aktif'),
(19, 'rizki', '123', '0987654321', 'malang', '081559555595', 'Laki-laki', 0.00, 'Aktif');

--
-- Triggers `akun_nasabah`
--
DELIMITER $$
CREATE TRIGGER `after_update_info` AFTER UPDATE ON `akun_nasabah` FOR EACH ROW BEGIN
    IF OLD.nama != NEW.nama OR OLD.alamat != NEW.alamat OR OLD.no_hp != NEW.no_hp THEN
        INSERT INTO transaksi (id_akun, jenis_transaksi, jumlah, waktu_transaksi, tanggal_transaksi)
        VALUES (NEW.id_akun, 'Update Info', 0, NOW(), NOW());
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_delete_akun` BEFORE DELETE ON `akun_nasabah` FOR EACH ROW BEGIN
    IF (SELECT COUNT(*) FROM pengajuan_pinjaman WHERE id_akun = OLD.id_akun AND status_pinjaman = 'Disetujui') > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tidak bisa hapus akun yang masih punya pinjaman aktif';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_register_min_saldo` BEFORE INSERT ON `akun_nasabah` FOR EACH ROW BEGIN
    IF NEW.saldo < 50000 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Minimal saldo 50000 untuk registrasi';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_pembayaran_pinjaman`
--

CREATE TABLE `jadwal_pembayaran_pinjaman` (
  `id_jadwal` int(11) NOT NULL,
  `id_pinjaman` int(11) DEFAULT NULL,
  `jumlah_bayar` decimal(15,2) DEFAULT NULL,
  `tanggal_jatuh_tempo` date DEFAULT NULL,
  `status_pembayaran` enum('Belum Lunas','Lunas') DEFAULT 'Belum Lunas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_pembayaran_pinjaman`
--

INSERT INTO `jadwal_pembayaran_pinjaman` (`id_jadwal`, `id_pinjaman`, `jumlah_bayar`, `tanggal_jatuh_tempo`, `status_pembayaran`) VALUES
(179, 44, 500000.00, '2025-06-22', 'Lunas'),
(180, 44, 500000.00, '2025-07-22', 'Lunas'),
(181, 45, 833333.33, '2025-06-22', 'Lunas'),
(182, 45, 833333.33, '2025-07-22', 'Lunas'),
(183, 45, 833333.33, '2025-08-22', 'Lunas'),
(184, 45, 833333.33, '2025-09-22', 'Lunas'),
(185, 45, 833333.33, '2025-10-22', 'Lunas'),
(186, 45, 833333.33, '2025-11-22', 'Lunas'),
(187, 47, 500000.00, '2025-06-22', 'Lunas'),
(188, 47, 500000.00, '2025-07-22', 'Lunas'),
(189, 48, 500000.00, '2025-06-22', 'Lunas'),
(190, 48, 500000.00, '2025-07-22', 'Lunas');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_pinjaman`
--

CREATE TABLE `pengajuan_pinjaman` (
  `id_pinjaman` int(11) NOT NULL,
  `id_akun` int(11) DEFAULT NULL,
  `jumlah_pinjaman` int(11) DEFAULT NULL,
  `status_pinjaman` enum('Menunggu','Disetujui','Ditolak') DEFAULT NULL,
  `jangka` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengajuan_pinjaman`
--

INSERT INTO `pengajuan_pinjaman` (`id_pinjaman`, `id_akun`, `jumlah_pinjaman`, `status_pinjaman`, `jangka`) VALUES
(44, 14, 1000000, 'Disetujui', 2),
(45, 18, 5000000, 'Disetujui', 6),
(46, 14, 1000000, 'Ditolak', 2),
(47, 14, 1000000, 'Disetujui', 2),
(48, 19, 1000000, 'Disetujui', 2),
(49, 19, 2000000, 'Ditolak', 3);

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_transfer`
--

CREATE TABLE `riwayat_transfer` (
  `id_transfer` int(11) NOT NULL,
  `id_transaksi` int(11) DEFAULT NULL,
  `akun_tujuan` int(11) DEFAULT NULL,
  `status_transfer` enum('Berhasil','Gagal') DEFAULT NULL,
  `tanggal` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riwayat_transfer`
--

INSERT INTO `riwayat_transfer` (`id_transfer`, `id_transaksi`, `akun_tujuan`, `status_transfer`, `tanggal`) VALUES
(6, 101, 14, 'Berhasil', '2025-05-22 16:16:53'),
(7, 108, 14, 'Berhasil', '2025-05-22 20:10:46');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `id_akun` int(11) DEFAULT NULL,
  `jenis_transaksi` enum('Setor','Tarik','Transfer') DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `waktu_transaksi` datetime NOT NULL DEFAULT current_timestamp(),
  `tanggal_transaksi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `id_akun`, `jenis_transaksi`, `jumlah`, `waktu_transaksi`, `tanggal_transaksi`) VALUES
(91, 14, 'Setor', 500000, '2025-05-22 15:02:54', '2025-05-22 15:02:54'),
(92, 14, 'Tarik', 20000, '2025-05-22 15:41:53', '2025-05-22 15:41:53'),
(93, 14, 'Setor', 500000, '2025-05-22 16:06:42', '2025-05-22 16:06:42'),
(94, 18, '', 0, '2025-05-22 16:12:05', '2025-05-22 16:12:05'),
(95, 18, '', 0, '2025-05-22 16:12:20', '2025-05-22 16:12:20'),
(96, 18, '', 0, '2025-05-22 16:12:24', '2025-05-22 16:12:24'),
(97, 18, 'Setor', 1000000, '2025-05-22 16:14:28', '2025-05-22 16:14:28'),
(98, 18, 'Tarik', 1000050, '2025-05-22 16:15:17', '2025-05-22 16:15:17'),
(99, 18, 'Tarik', 49950, '2025-05-22 16:15:30', '2025-05-22 16:15:30'),
(100, 18, 'Setor', 20000, '2025-05-22 16:16:45', '2025-05-22 16:16:45'),
(101, 18, 'Transfer', 20000, '2025-05-22 16:16:53', '2025-05-22 16:16:53'),
(102, 18, 'Setor', 1000000, '2025-05-22 16:21:30', '2025-05-22 16:21:30'),
(103, 18, 'Setor', 1000000, '2025-05-22 16:44:13', '2025-05-22 16:44:13'),
(104, 18, 'Setor', 10000000, '2025-05-22 18:52:26', '2025-05-22 18:52:26'),
(105, 14, 'Setor', 1000000, '2025-05-22 20:08:33', '2025-05-22 20:08:33'),
(106, 19, 'Setor', 20000, '2025-05-22 20:10:25', '2025-05-22 20:10:25'),
(107, 19, 'Tarik', 50000, '2025-05-22 20:10:35', '2025-05-22 20:10:35'),
(108, 19, 'Transfer', 20000, '2025-05-22 20:10:46', '2025-05-22 20:10:46'),
(109, 19, 'Setor', 1000000, '2025-05-22 20:16:42', '2025-05-22 20:16:42'),
(110, 19, '', 0, '2025-05-22 20:17:44', '2025-05-22 20:17:44'),
(111, 19, '', 0, '2025-05-22 20:17:57', '2025-05-22 20:17:57');

--
-- Triggers `transaksi`
--
DELIMITER $$
CREATE TRIGGER `before_setor_min` BEFORE INSERT ON `transaksi` FOR EACH ROW BEGIN
    IF NEW.jenis_transaksi = 'Setor' AND NEW.jumlah < 20000 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Minimal setor 20000';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_tarik_min` BEFORE INSERT ON `transaksi` FOR EACH ROW BEGIN
    IF NEW.jenis_transaksi = 'Tarik' AND NEW.jumlah < 20000 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Minimal tarik 20000';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_transfer_check` BEFORE INSERT ON `transaksi` FOR EACH ROW BEGIN
    IF NEW.jenis_transaksi = 'Transfer' THEN
        IF NEW.jumlah < 20000 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Minimal transfer 20000';
        END IF;
        IF (SELECT status_akun FROM akun_nasabah WHERE id_akun = NEW.id_akun) = 'NonAktif' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Akun nonaktif tidak bisa transfer';
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_jadwal_pembayaran_pengguna`
-- (See below for the actual view)
--
CREATE TABLE `view_jadwal_pembayaran_pengguna` (
`id_jadwal` int(11)
,`id_pinjaman` int(11)
,`id_akun` int(11)
,`nama` varchar(100)
,`jumlah_bayar` decimal(15,2)
,`tanggal_jatuh_tempo` date
,`status_pembayaran` enum('Belum Lunas','Lunas')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_pinjaman_aktif`
-- (See below for the actual view)
--
CREATE TABLE `view_pinjaman_aktif` (
`id_pinjaman` int(11)
,`nama` varchar(100)
,`jumlah_pinjaman` int(11)
,`jangka` int(11)
,`status_pinjaman` enum('Menunggu','Disetujui','Ditolak')
,`id_akun` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_rekap_pinjaman`
-- (See below for the actual view)
--
CREATE TABLE `view_rekap_pinjaman` (
`id_akun` int(11)
,`nama` varchar(100)
,`total_pengajuan` bigint(21)
,`total_pinjaman` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_riwayat_setor`
-- (See below for the actual view)
--
CREATE TABLE `view_riwayat_setor` (
`id_transaksi` int(11)
,`id_akun` int(11)
,`jenis_transaksi` enum('Setor','Tarik','Transfer')
,`jumlah` int(11)
,`waktu_transaksi` datetime
,`tanggal_transaksi` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_riwayat_tarik`
-- (See below for the actual view)
--
CREATE TABLE `view_riwayat_tarik` (
`id_transaksi` int(11)
,`id_akun` int(11)
,`jenis_transaksi` enum('Setor','Tarik','Transfer')
,`jumlah` int(11)
,`waktu_transaksi` datetime
,`tanggal_transaksi` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_riwayat_transfer_lengkap`
-- (See below for the actual view)
--
CREATE TABLE `view_riwayat_transfer_lengkap` (
`id_transfer` int(11)
,`id_akun` int(11)
,`pengirim` varchar(100)
,`penerima` varchar(100)
,`jumlah_transfer` int(11)
,`status_transfer` enum('Berhasil','Gagal')
,`tanggal` datetime
);

-- --------------------------------------------------------

--
-- Structure for view `view_jadwal_pembayaran_pengguna`
--
DROP TABLE IF EXISTS `view_jadwal_pembayaran_pengguna`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_jadwal_pembayaran_pengguna`  AS SELECT `jp`.`id_jadwal` AS `id_jadwal`, `pp`.`id_pinjaman` AS `id_pinjaman`, `a`.`id_akun` AS `id_akun`, `a`.`nama` AS `nama`, `jp`.`jumlah_bayar` AS `jumlah_bayar`, `jp`.`tanggal_jatuh_tempo` AS `tanggal_jatuh_tempo`, `jp`.`status_pembayaran` AS `status_pembayaran` FROM ((`jadwal_pembayaran_pinjaman` `jp` join `pengajuan_pinjaman` `pp` on(`jp`.`id_pinjaman` = `pp`.`id_pinjaman`)) join `akun_nasabah` `a` on(`pp`.`id_akun` = `a`.`id_akun`)) ;

-- --------------------------------------------------------

--
-- Structure for view `view_pinjaman_aktif`
--
DROP TABLE IF EXISTS `view_pinjaman_aktif`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_pinjaman_aktif`  AS SELECT `p`.`id_pinjaman` AS `id_pinjaman`, `a`.`nama` AS `nama`, `p`.`jumlah_pinjaman` AS `jumlah_pinjaman`, `p`.`jangka` AS `jangka`, `p`.`status_pinjaman` AS `status_pinjaman`, `p`.`id_akun` AS `id_akun` FROM (`pengajuan_pinjaman` `p` join `akun_nasabah` `a` on(`p`.`id_akun` = `a`.`id_akun`)) WHERE `p`.`status_pinjaman` = 'Disetujui' AND exists(select 1 from `jadwal_pembayaran_pinjaman` `j` where `j`.`id_pinjaman` = `p`.`id_pinjaman` AND `j`.`status_pembayaran` = 'Belum Lunas' limit 1) ;

-- --------------------------------------------------------

--
-- Structure for view `view_rekap_pinjaman`
--
DROP TABLE IF EXISTS `view_rekap_pinjaman`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_rekap_pinjaman`  AS SELECT `a`.`id_akun` AS `id_akun`, `a`.`nama` AS `nama`, count(`p`.`id_pinjaman`) AS `total_pengajuan`, sum(`p`.`jumlah_pinjaman`) AS `total_pinjaman` FROM (`akun_nasabah` `a` left join `pengajuan_pinjaman` `p` on(`a`.`id_akun` = `p`.`id_akun`)) GROUP BY `a`.`id_akun`, `a`.`nama` ;

-- --------------------------------------------------------

--
-- Structure for view `view_riwayat_setor`
--
DROP TABLE IF EXISTS `view_riwayat_setor`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_riwayat_setor`  AS SELECT `transaksi`.`id_transaksi` AS `id_transaksi`, `transaksi`.`id_akun` AS `id_akun`, `transaksi`.`jenis_transaksi` AS `jenis_transaksi`, `transaksi`.`jumlah` AS `jumlah`, `transaksi`.`waktu_transaksi` AS `waktu_transaksi`, `transaksi`.`tanggal_transaksi` AS `tanggal_transaksi` FROM `transaksi` WHERE `transaksi`.`jenis_transaksi` = 'Setor' ;

-- --------------------------------------------------------

--
-- Structure for view `view_riwayat_tarik`
--
DROP TABLE IF EXISTS `view_riwayat_tarik`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_riwayat_tarik`  AS SELECT `transaksi`.`id_transaksi` AS `id_transaksi`, `transaksi`.`id_akun` AS `id_akun`, `transaksi`.`jenis_transaksi` AS `jenis_transaksi`, `transaksi`.`jumlah` AS `jumlah`, `transaksi`.`waktu_transaksi` AS `waktu_transaksi`, `transaksi`.`tanggal_transaksi` AS `tanggal_transaksi` FROM `transaksi` WHERE `transaksi`.`jenis_transaksi` = 'Tarik' ;

-- --------------------------------------------------------

--
-- Structure for view `view_riwayat_transfer_lengkap`
--
DROP TABLE IF EXISTS `view_riwayat_transfer_lengkap`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_riwayat_transfer_lengkap`  AS SELECT `rt`.`id_transfer` AS `id_transfer`, `t`.`id_akun` AS `id_akun`, `a1`.`nama` AS `pengirim`, `a2`.`nama` AS `penerima`, `t`.`jumlah` AS `jumlah_transfer`, `rt`.`status_transfer` AS `status_transfer`, `rt`.`tanggal` AS `tanggal` FROM (((`riwayat_transfer` `rt` join `transaksi` `t` on(`rt`.`id_transaksi` = `t`.`id_transaksi`)) join `akun_nasabah` `a1` on(`t`.`id_akun` = `a1`.`id_akun`)) join `akun_nasabah` `a2` on(`rt`.`akun_tujuan` = `a2`.`id_akun`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `akun_nasabah`
--
ALTER TABLE `akun_nasabah`
  ADD PRIMARY KEY (`id_akun`);

--
-- Indexes for table `jadwal_pembayaran_pinjaman`
--
ALTER TABLE `jadwal_pembayaran_pinjaman`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `id_pinjaman` (`id_pinjaman`);

--
-- Indexes for table `pengajuan_pinjaman`
--
ALTER TABLE `pengajuan_pinjaman`
  ADD PRIMARY KEY (`id_pinjaman`),
  ADD KEY `pengajuan_pinjaman_ibfk_1` (`id_akun`);

--
-- Indexes for table `riwayat_transfer`
--
ALTER TABLE `riwayat_transfer`
  ADD PRIMARY KEY (`id_transfer`),
  ADD KEY `riwayat_transfer_ibfk_1` (`id_transaksi`),
  ADD KEY `fk_transfer_akun_tujuan` (`akun_tujuan`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_akun` (`id_akun`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `akun_nasabah`
--
ALTER TABLE `akun_nasabah`
  MODIFY `id_akun` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `jadwal_pembayaran_pinjaman`
--
ALTER TABLE `jadwal_pembayaran_pinjaman`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT for table `pengajuan_pinjaman`
--
ALTER TABLE `pengajuan_pinjaman`
  MODIFY `id_pinjaman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `riwayat_transfer`
--
ALTER TABLE `riwayat_transfer`
  MODIFY `id_transfer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `jadwal_pembayaran_pinjaman`
--
ALTER TABLE `jadwal_pembayaran_pinjaman`
  ADD CONSTRAINT `jadwal_pembayaran_pinjaman_ibfk_1` FOREIGN KEY (`id_pinjaman`) REFERENCES `pengajuan_pinjaman` (`id_pinjaman`);

--
-- Constraints for table `pengajuan_pinjaman`
--
ALTER TABLE `pengajuan_pinjaman`
  ADD CONSTRAINT `pengajuan_pinjaman_ibfk_1` FOREIGN KEY (`id_akun`) REFERENCES `akun_nasabah` (`id_akun`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `riwayat_transfer`
--
ALTER TABLE `riwayat_transfer`
  ADD CONSTRAINT `fk_transfer_akun_tujuan` FOREIGN KEY (`akun_tujuan`) REFERENCES `akun_nasabah` (`id_akun`),
  ADD CONSTRAINT `riwayat_transfer_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `fk_transaksi_akun` FOREIGN KEY (`id_akun`) REFERENCES `akun_nasabah` (`id_akun`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
