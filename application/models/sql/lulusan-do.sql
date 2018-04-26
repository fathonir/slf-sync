/**
 * Author:  Fathoni <m.fathoni@mail.com>
 * Created: Apr 5, 2018
 */

/* Jumlah Semua Data */
SELECT COUNT(*) AS jumlah
FROM admisi A
JOIN mahasiswa M ON M.id_mhs = A.id_mhs
JOIN pengguna P ON P.id_pengguna = M.id_pengguna
JOIN status_pengguna sp ON sp.id_status_pengguna = A.status_akd_mhs
WHERE P.id_perguruan_tinggi = 1 AND sp.status_keluar = 1

UNION ALL

/* Jumlah Sudah Link */
SELECT COUNT(*) AS jumlah
FROM admisi A
JOIN mahasiswa M ON M.id_mhs = A.id_mhs
JOIN pengguna P ON P.id_pengguna = M.id_pengguna
JOIN status_pengguna sp ON sp.id_status_pengguna = A.status_akd_mhs
WHERE P.id_perguruan_tinggi = 1 AND sp.status_keluar = 1 AND a.fd_sync_on IS NOT NULL

UNION ALL

/* Jumlah Bakal Update */
SELECT COUNT(*) AS jumlah
FROM admisi A
JOIN mahasiswa M ON M.id_mhs = A.id_mhs
JOIN pengguna P ON P.id_pengguna = M.id_pengguna
JOIN status_pengguna sp ON sp.id_status_pengguna = A.status_akd_mhs
WHERE P.id_perguruan_tinggi = 1 AND sp.status_keluar = 1 AND a.fd_sync_on IS NOT NULL AND
	(a.updated_on > a.fd_sync_on)

UNION ALL

/* Jumlah Bakal Insert */
SELECT COUNT(*) AS jumlah
FROM admisi A
JOIN mahasiswa M ON M.id_mhs = A.id_mhs
JOIN pengguna P ON P.id_pengguna = M.id_pengguna
JOIN status_pengguna sp ON sp.id_status_pengguna = A.status_akd_mhs
WHERE P.id_perguruan_tinggi = 1 AND sp.status_keluar = 1 AND a.fd_sync_on IS NULL