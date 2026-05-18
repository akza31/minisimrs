-- Database Mini SIMRS
-- Import melalui phpMyAdmin atau mysql CLI.
-- Login awal aplikasi: admin / admin123

CREATE DATABASE IF NOT EXISTS db_mini_simrs
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_mini_simrs;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS penjualan_obat;
DROP TABLE IF EXISTS kasir;
DROP TABLE IF EXISTS billing;
DROP TABLE IF EXISTS layanan;
DROP TABLE IF EXISTS sep_checks;
DROP TABLE IF EXISTS pendaftaran;
DROP TABLE IF EXISTS obat;
DROP TABLE IF EXISTS tarif;
DROP TABLE IF EXISTS tempat_tidur;
DROP TABLE IF EXISTS bangsal;
DROP TABLE IF EXISTS poli;
DROP TABLE IF EXISTS dokter;
DROP TABLE IF EXISTS pasien;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(120) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'petugas', 'kasir') NOT NULL DEFAULT 'petugas',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE pasien (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  no_rm VARCHAR(30) NOT NULL UNIQUE,
  nama VARCHAR(140) NOT NULL,
  nik VARCHAR(32) NULL,
  gender ENUM('Laki-laki', 'Perempuan') NOT NULL,
  tgl_lahir DATE NULL,
  alamat TEXT NULL,
  no_bpjs VARCHAR(40) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE dokter (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(140) NOT NULL,
  spesialis VARCHAR(100) NULL,
  sip VARCHAR(80) NULL,
  telepon VARCHAR(30) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE poli (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(120) NOT NULL,
  lantai VARCHAR(30) NULL,
  status ENUM('Aktif', 'Nonaktif') NOT NULL DEFAULT 'Aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE bangsal (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(120) NOT NULL,
  kelas VARCHAR(80) NULL,
  penanggung_jawab VARCHAR(120) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tempat_tidur (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bangsal_id INT UNSIGNED NULL,
  bangsal VARCHAR(120) NOT NULL,
  nomor VARCHAR(40) NOT NULL,
  kelas VARCHAR(80) NULL,
  status ENUM('Tersedia', 'Terisi', 'Perbaikan') NOT NULL DEFAULT 'Tersedia',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bed_bangsal FOREIGN KEY (bangsal_id) REFERENCES bangsal(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE tarif (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(160) NOT NULL,
  kategori VARCHAR(100) NULL,
  harga DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE obat (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(160) NOT NULL,
  satuan VARCHAR(40) NULL,
  stok INT NOT NULL DEFAULT 0,
  harga DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE pendaftaran (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  jenis ENUM('Pasien Baru', 'Poli', 'Rawat Inap', 'UGD') NOT NULL,
  pasien_id INT UNSIGNED NOT NULL,
  poli_id INT UNSIGNED NULL,
  dokter_id INT UNSIGNED NULL,
  bangsal_id INT UNSIGNED NULL,
  keluhan TEXT NULL,
  status ENUM('Terdaftar', 'Dalam Layanan', 'Selesai') NOT NULL DEFAULT 'Terdaftar',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_daftar_pasien FOREIGN KEY (pasien_id) REFERENCES pasien(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_daftar_poli FOREIGN KEY (poli_id) REFERENCES poli(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_daftar_dokter FOREIGN KEY (dokter_id) REFERENCES dokter(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_daftar_bangsal FOREIGN KEY (bangsal_id) REFERENCES bangsal(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE sep_checks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tanggal DATETIME NOT NULL,
  pasien_id INT UNSIGNED NULL,
  no_kartu VARCHAR(60) NOT NULL,
  no_rujukan VARCHAR(80) NOT NULL,
  tujuan VARCHAR(120) NULL,
  hasil VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sep_pasien FOREIGN KEY (pasien_id) REFERENCES pasien(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE layanan (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  unit ENUM('Poli', 'UGD', 'Rawat Inap', 'Laborat', 'Radiologi') NOT NULL,
  pendaftaran_id INT UNSIGNED NOT NULL,
  dokter_id INT UNSIGNED NULL,
  diagnosa VARCHAR(180) NULL,
  tindakan TEXT NULL,
  catatan TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_layanan_daftar FOREIGN KEY (pendaftaran_id) REFERENCES pendaftaran(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_layanan_dokter FOREIGN KEY (dokter_id) REFERENCES dokter(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE billing (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  pendaftaran_id INT UNSIGNED NOT NULL,
  tarif_id INT UNSIGNED NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  harga DECIMAL(14,2) NOT NULL DEFAULT 0,
  total DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('Belum Lunas', 'Lunas') NOT NULL DEFAULT 'Belum Lunas',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_billing_daftar FOREIGN KEY (pendaftaran_id) REFERENCES pendaftaran(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_billing_tarif FOREIGN KEY (tarif_id) REFERENCES tarif(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE kasir (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  jenis ENUM('Rawat Jalan', 'UGD', 'Rawat Inap') NOT NULL,
  pasien_id INT UNSIGNED NOT NULL,
  total DECIMAL(14,2) NOT NULL DEFAULT 0,
  bayar DECIMAL(14,2) NOT NULL DEFAULT 0,
  kembalian DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('Lunas', 'Kurang Bayar') NOT NULL DEFAULT 'Kurang Bayar',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_kasir_pasien FOREIGN KEY (pasien_id) REFERENCES pasien(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE penjualan_obat (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  pasien_id INT UNSIGNED NOT NULL,
  obat_id INT UNSIGNED NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  harga DECIMAL(14,2) NOT NULL DEFAULT 0,
  total DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_jual_obat_pasien FOREIGN KEY (pasien_id) REFERENCES pasien(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_jual_obat_obat FOREIGN KEY (obat_id) REFERENCES obat(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO users (id, nama, username, password, role) VALUES
(1, 'Administrator', 'admin', '$2y$10$JtXp7WSwLcj.GMaNWq5jcuZM8OCju6bqs.zSo39V96k5YxZEWiCCy', 'admin');

INSERT INTO pasien (id, no_rm, nama, nik, gender, tgl_lahir, alamat, no_bpjs) VALUES
(1, 'RM-000001', 'Siti Aminah', '3276010101800001', 'Perempuan', '1980-01-01', 'Jl. Melati No. 12', '0001234567890'),
(2, 'RM-000002', 'Budi Santoso', '3276020202900002', 'Laki-laki', '1990-02-02', 'Jl. Kenanga No. 7', '0009876543210');

INSERT INTO dokter (id, nama, spesialis, sip, telepon) VALUES
(1, 'dr. Raka Pratama', 'Umum', 'SIP-UM-001', '081234567890'),
(2, 'dr. Maya Lestari, Sp.PD', 'Penyakit Dalam', 'SIP-PD-002', '081298765432');

INSERT INTO poli (id, nama, lantai, status) VALUES
(1, 'Poli Umum', '1', 'Aktif'),
(2, 'Poli Penyakit Dalam', '2', 'Aktif');

INSERT INTO bangsal (id, nama, kelas, penanggung_jawab) VALUES
(1, 'Mawar', 'Kelas 1', 'Ns. Lina'),
(2, 'Anggrek', 'Kelas 2', 'Ns. Putri');

INSERT INTO tempat_tidur (id, bangsal_id, bangsal, nomor, kelas, status) VALUES
(1, 1, 'Mawar', 'MW-01', 'Kelas 1', 'Tersedia'),
(2, 1, 'Mawar', 'MW-02', 'Kelas 1', 'Terisi'),
(3, 2, 'Anggrek', 'AG-01', 'Kelas 2', 'Tersedia');

INSERT INTO tarif (id, nama, kategori, harga) VALUES
(1, 'Konsultasi Dokter Umum', 'Rawat Jalan', 75000),
(2, 'Pemeriksaan Darah Lengkap', 'Laborat', 125000),
(3, 'Foto Thorax', 'Radiologi', 180000);

INSERT INTO obat (id, nama, satuan, stok, harga) VALUES
(1, 'Paracetamol 500 mg', 'Tablet', 240, 1200),
(2, 'Amoxicillin 500 mg', 'Kapsul', 120, 2500);

INSERT INTO pendaftaran (id, tanggal, jenis, pasien_id, poli_id, dokter_id, bangsal_id, keluhan, status) VALUES
(1, CURDATE(), 'Poli', 1, 1, 1, NULL, 'Kontrol rutin', 'Terdaftar');

INSERT INTO layanan (id, tanggal, unit, pendaftaran_id, dokter_id, diagnosa, tindakan, catatan) VALUES
(1, CURDATE(), 'Poli', 1, 1, 'Observasi', 'Konsultasi dokter umum', 'Data contoh layanan');

INSERT INTO billing (id, tanggal, pendaftaran_id, tarif_id, qty, harga, total, status) VALUES
(1, CURDATE(), 1, 1, 1, 75000, 75000, 'Belum Lunas');

INSERT INTO kasir (id, tanggal, jenis, pasien_id, total, bayar, kembalian, status) VALUES
(1, CURDATE(), 'Rawat Jalan', 1, 75000, 75000, 0, 'Lunas');

INSERT INTO penjualan_obat (id, tanggal, pasien_id, obat_id, qty, harga, total) VALUES
(1, CURDATE(), 1, 1, 10, 1200, 12000);

INSERT INTO sep_checks (id, tanggal, pasien_id, no_kartu, no_rujukan, tujuan, hasil) VALUES
(1, NOW(), 1, '0001234567890', 'RJK-20260518-001', 'Poli Umum', 'Layak dibuat SEP');

