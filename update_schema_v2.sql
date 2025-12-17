ALTER TABLE `kamar`
ADD `checklist_data` JSON NULL DEFAULT NULL COMMENT 'Simpan data checklist dalam format JSON' AFTER `status_ketersediaan`;
