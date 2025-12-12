CREATE TABLE tbl_users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- WAJIB di-hash!
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'front_office', 'housekeeping') NOT NULL DEFAULT 'front_office',
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tbl_properti (
    id_properti INT AUTO_INCREMENT PRIMARY KEY,
    nama_properti VARCHAR(100) NOT NULL,
    alamat TEXT
) ENGINE=InnoDB;

CREATE TABLE tbl_kamar (
    id_kamar INT AUTO_INCREMENT PRIMARY KEY,
    id_properti INT NOT NULL,
    nama_kamar VARCHAR(50) NOT NULL, -- Misal: "101", "102", "Twin 1", "Double 2"
    tipe_kamar VARCHAR(50),          -- Misal: "Single", "Double", "Twin", "Suite"
    harga_default DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status ENUM('Tersedia', 'Rusak') NOT NULL DEFAULT 'Tersedia',
    
    FOREIGN KEY (id_properti) REFERENCES tbl_properti(id_properti) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_tamu (
    id_tamu INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(150) NOT NULL,
    no_hp VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100),
    catatan_membership TEXT, -- Untuk fitur membership
    didaftarkan_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tbl_reservasi (
    id_reservasi INT AUTO_INCREMENT PRIMARY KEY,
    id_kamar INT NOT NULL,
    id_tamu INT NOT NULL,
    
    tgl_checkin DATE NOT NULL,
    tgl_checkout DATE NOT NULL,
    
    harga_total DECIMAL(10, 2) NOT NULL,
    jumlah_tamu INT NOT NULL DEFAULT 1,
    
    platform_booking VARCHAR(50) NOT NULL DEFAULT 'OTS', -- Misal: OTS, Agoda, Booking.com
    
    status_booking ENUM('Booking', 'Checked-in', 'Checked-out', 'Canceled') NOT NULL DEFAULT 'Booking',
    status_pembayaran ENUM('Belum Bayar', 'DP', 'Lunas') NOT NULL DEFAULT 'Belum Bayar',
    
    catatan_operator TEXT, -- Catatan dari Front Office
    dibuat_oleh_user INT, -- Opsional, ID user yg input
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_kamar) REFERENCES tbl_kamar(id_kamar),
    FOREIGN KEY (id_tamu) REFERENCES tbl_tamu(id_tamu),
    FOREIGN KEY (dibuat_oleh_user) REFERENCES tbl_users(id_user)
) ENGINE=InnoDB;