ALTER TABLE tbl_reservasi
CHANGE COLUMN tgl_checkin tgl_checkin DATETIME NOT NULL,
CHANGE COLUMN tgl_checkout tgl_checkout DATETIME NOT NULL,
ADD COLUMN jenis_booking VARCHAR(50) AFTER dibuat_oleh_user;
