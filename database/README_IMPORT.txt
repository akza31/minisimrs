DATABASE MINI SIMRS

Nama database:
db_mini_simrs

File import:
database/mini_simrs.sql

Cara import lewat phpMyAdmin:
1. Buka http://localhost/phpmyadmin
2. Pilih menu Import
3. Pilih file database/mini_simrs.sql
4. Klik Go / Kirim

Cara import lewat MySQL CLI:
mysql -u root < database/mini_simrs.sql

Login awal:
username: admin
password: admin123

Catatan:
Versi aplikasi yang sudah dibuat saat ini masih memakai penyimpanan JSON lokal di data/store.json agar langsung jalan tanpa setup database. File SQL ini disiapkan sebagai database MySQL lengkap bila nanti aplikasi ingin dipindahkan ke koneksi MySQL.
