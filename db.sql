-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 17, 2025 at 07:37 PM
-- Server version: 10.11.10-MariaDB-log
-- PHP Version: 8.3.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `adiputra`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_kamar`
--

CREATE TABLE `tbl_kamar` (
  `id_kamar` int(11) NOT NULL,
  `id_properti` int(11) NOT NULL,
  `nama_kamar` varchar(50) NOT NULL,
  `tipe_kamar` varchar(50) DEFAULT NULL,
  `harga_default` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Tersedia','Kotor','Maintenance','Tidak Tersedia') NOT NULL DEFAULT 'Tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `tbl_kamar`
--

INSERT INTO `tbl_kamar` (`id_kamar`, `id_properti`, `nama_kamar`, `tipe_kamar`, `harga_default`, `status`) VALUES
(2, 3, '102', 'Family', 250000.00, 'Tersedia'),
(3, 3, '101', 'Family', 250000.00, 'Tersedia'),
(4, 3, '103', 'Family', 250000.00, 'Tersedia'),
(5, 3, '104', 'Family', 250000.00, 'Tersedia'),
(6, 3, '105', 'Family', 250000.00, 'Tersedia'),
(7, 4, '201', 'Family', 250000.00, 'Tersedia'),
(8, 4, '202', 'Family', 250000.00, 'Tersedia'),
(9, 4, '205', 'Double', 230000.00, 'Tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_properti`
--

CREATE TABLE `tbl_properti` (
  `id_properti` int(11) NOT NULL,
  `nama_properti` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `tbl_properti`
--

INSERT INTO `tbl_properti` (`id_properti`, `nama_properti`, `alamat`) VALUES
(3, 'Adiputra GH 1', 'Jl.Tata Bumi, No.111'),
(4, 'Adiputra GH 2', 'Jl.Tata Bumi, No.111');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_reservasi`
--

CREATE TABLE `tbl_reservasi` (
  `id_reservasi` int(11) NOT NULL,
  `id_kamar` int(11) NOT NULL,
  `id_tamu` int(11) NOT NULL,
  `tgl_checkin` datetime NOT NULL,
  `tgl_checkout` datetime NOT NULL,
  `harga_total` decimal(10,2) NOT NULL,
  `jumlah_tamu` int(11) NOT NULL DEFAULT 1,
  `platform_booking` varchar(50) NOT NULL DEFAULT 'OTS',
  `status_booking` enum('Booking','Checked-in','Checked-out','Canceled') NOT NULL DEFAULT 'Booking',
  `status_pembayaran` enum('Belum Bayar','DP','Lunas') NOT NULL DEFAULT 'Belum Bayar',
  `jumlah_dp` decimal(10,2) DEFAULT NULL,
  `catatan_operator` text DEFAULT NULL,
  `path_identitas` varchar(255) DEFAULT NULL,
  `dibuat_oleh_user` int(11) DEFAULT NULL,
  `jenis_booking` varchar(50) DEFAULT NULL,
  `dibuat_pada` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `tbl_reservasi`
--

INSERT INTO `tbl_reservasi` (`id_reservasi`, `id_kamar`, `id_tamu`, `tgl_checkin`, `tgl_checkout`, `harga_total`, `jumlah_tamu`, `platform_booking`, `status_booking`, `status_pembayaran`, `jumlah_dp`, `catatan_operator`, `path_identitas`, `dibuat_oleh_user`, `jenis_booking`, `dibuat_pada`) VALUES
(19, 3, 7, '2025-12-16 14:00:00', '2025-12-17 12:00:00', 25000000.00, 1, 'Traveloka', 'Checked-out', 'DP', 20000000.00, '0', 'uploads/identitas/identitas_19_1765861022.png', 1, 'Transit 3 Jam', '2025-12-16 04:56:19'),
(20, 4, 8, '2025-12-16 14:00:00', '2025-12-20 12:00:00', 25000000.00, 1, 'OTS', 'Checked-out', 'Lunas', NULL, '0', 'uploads/identitas/identitas_20_1765861121.png', 1, 'Harian', '2025-12-16 04:58:05'),
(21, 5, 9, '2025-12-16 14:00:00', '2025-12-17 12:00:00', 250000.00, 1, 'OTS', 'Checked-out', 'Lunas', NULL, '0', 'uploads/identitas/identitas_21_1765861808.png', 1, 'Harian', '2025-12-16 05:09:26'),
(22, 2, 10, '2025-12-16 14:00:00', '2025-12-17 12:00:00', 250000.00, 1, 'OTS', 'Canceled', 'Lunas', NULL, '0', NULL, 1, 'Harian', '2025-12-16 06:02:09'),
(23, 6, 11, '2025-12-16 14:00:00', '2025-12-20 12:00:00', 250000.00, 1, 'OTS', 'Canceled', 'Lunas', NULL, '0', NULL, 1, 'Harian', '2025-12-16 06:02:56');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_reservasi_identitas`
--

CREATE TABLE `tbl_reservasi_identitas` (
  `id` int(11) NOT NULL,
  `id_reservasi` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_tamu`
--

CREATE TABLE `tbl_tamu` (
  `id_tamu` int(11) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `catatan_membership` text DEFAULT NULL,
  `didaftarkan_pada` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `tbl_tamu`
--

INSERT INTO `tbl_tamu` (`id_tamu`, `nama_lengkap`, `no_hp`, `email`, `catatan_membership`, `didaftarkan_pada`) VALUES
(7, 'Cek Gaya', '07119823734563', 'cek@mail.com', NULL, '2025-12-16 04:56:19'),
(8, 'Aleesha', '082371869118', 'rizkibinmangtrisno@gmail.com', NULL, '2025-12-16 04:58:05'),
(9, 'Aleshoo', '0821887935657721', 'rizkibinmangtrisno@gmail.com', NULL, '2025-12-16 05:09:26'),
(10, 'RISKI NURHADI', '0877683587372', 'rizkibinmangtrisno@gmail.com', NULL, '2025-12-16 06:02:09'),
(11, 'RISKI NURHADI', '082371869121', 'rizkibinmangtrisno@gmail.com', NULL, '2025-12-16 06:02:56');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','front_office','housekeeping') NOT NULL DEFAULT 'front_office',
  `dibuat_pada` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`id_user`, `username`, `password`, `nama_lengkap`, `role`, `dibuat_pada`) VALUES
(1, 'riskinurhadi20', '$2y$10$6jz99Af5Y/V3N4yumNMNRORQ50l.Ia6zxTllbD5Mp2bXXzMrRcO.K', 'Riski Nurhadi', 'admin', '2025-11-03 16:19:38'),
(2, 'riskinurhadi10', '$2y$10$E9A5qGHib0hcwBEuUHOfs.GOW1SPU437310b/VNCWS1.oZpjUonxS', 'Riski Nurhadi', 'front_office', '2025-11-04 05:44:31'),
(3, 'housekeeping', '$2y$10$t1eSJWl/YMwflzRNJMP8N.rGBTbZiDtxYveKdWAjkGb7NOHr0luPK', 'housekeeping', 'housekeeping', '2025-12-13 03:55:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_kamar`
--
ALTER TABLE `tbl_kamar`
  ADD PRIMARY KEY (`id_kamar`),
  ADD KEY `id_properti` (`id_properti`);

--
-- Indexes for table `tbl_properti`
--
ALTER TABLE `tbl_properti`
  ADD PRIMARY KEY (`id_properti`);

--
-- Indexes for table `tbl_reservasi`
--
ALTER TABLE `tbl_reservasi`
  ADD PRIMARY KEY (`id_reservasi`),
  ADD KEY `id_kamar` (`id_kamar`),
  ADD KEY `id_tamu` (`id_tamu`),
  ADD KEY `dibuat_oleh_user` (`dibuat_oleh_user`);

--
-- Indexes for table `tbl_reservasi_identitas`
--
ALTER TABLE `tbl_reservasi_identitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_reservasi` (`id_reservasi`);

--
-- Indexes for table `tbl_tamu`
--
ALTER TABLE `tbl_tamu`
  ADD PRIMARY KEY (`id_tamu`),
  ADD UNIQUE KEY `no_hp` (`no_hp`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_kamar`
--
ALTER TABLE `tbl_kamar`
  MODIFY `id_kamar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tbl_properti`
--
ALTER TABLE `tbl_properti`
  MODIFY `id_properti` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_reservasi`
--
ALTER TABLE `tbl_reservasi`
  MODIFY `id_reservasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `tbl_reservasi_identitas`
--
ALTER TABLE `tbl_reservasi_identitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_tamu`
--
ALTER TABLE `tbl_tamu`
  MODIFY `id_tamu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_kamar`
--
ALTER TABLE `tbl_kamar`
  ADD CONSTRAINT `tbl_kamar_ibfk_1` FOREIGN KEY (`id_properti`) REFERENCES `tbl_properti` (`id_properti`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_reservasi`
--
ALTER TABLE `tbl_reservasi`
  ADD CONSTRAINT `tbl_reservasi_ibfk_1` FOREIGN KEY (`id_kamar`) REFERENCES `tbl_kamar` (`id_kamar`),
  ADD CONSTRAINT `tbl_reservasi_ibfk_2` FOREIGN KEY (`id_tamu`) REFERENCES `tbl_tamu` (`id_tamu`),
  ADD CONSTRAINT `tbl_reservasi_ibfk_3` FOREIGN KEY (`dibuat_oleh_user`) REFERENCES `tbl_users` (`id_user`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
