-- Skema untuk fitur checklist kamar

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Tabel untuk mencatat setiap proses checklist yang dijalankan
--
CREATE TABLE `tbl_checklist_runs` (
  `id_run` int(11) NOT NULL,
  `id_kamar` int(11) NOT NULL,
  `id_user` int(11) NULL DEFAULT NULL,
  `run_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `final_status_kamar` enum('Tersedia','Kotor','Maintenance') NOT NULL,
  `catatan_umum` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Tabel untuk menyimpan detail item dari setiap proses checklist
--
CREATE TABLE `tbl_checklist_run_items` (
  `id_run_item` int(11) NOT NULL,
  `id_run` int(11) NOT NULL,
  `item_key` varchar(50) NOT NULL COMMENT 'e.g., ac, tv, sprei',
  `item_status` enum('Baik','Perbaikan') NOT NULL,
  `catatan_item` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Indexes for table `tbl_checklist_runs`
--
ALTER TABLE `tbl_checklist_runs`
  ADD PRIMARY KEY (`id_run`),
  ADD KEY `id_kamar` (`id_kamar`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tbl_checklist_run_items`
--
ALTER TABLE `tbl_checklist_run_items`
  ADD PRIMARY KEY (`id_run_item`),
  ADD KEY `id_run` (`id_run`);

--
-- AUTO_INCREMENT for table `tbl_checklist_runs`
--
ALTER TABLE `tbl_checklist_runs`
  MODIFY `id_run` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_checklist_run_items`
--
ALTER TABLE `tbl_checklist_run_items`
  MODIFY `id_run_item` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_checklist_runs`
--
ALTER TABLE `tbl_checklist_runs`
  ADD CONSTRAINT `tbl_checklist_runs_ibfk_1` FOREIGN KEY (`id_kamar`) REFERENCES `tbl_kamar` (`id_kamar`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_checklist_runs_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `tbl_users` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `tbl_checklist_run_items`
--
ALTER TABLE `tbl_checklist_run_items`
  ADD CONSTRAINT `tbl_checklist_run_items_ibfk_1` FOREIGN KEY (`id_run`) REFERENCES `tbl_checklist_runs` (`id_run`) ON DELETE CASCADE;

COMMIT;
