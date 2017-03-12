/* Jumlah Semua Data */
SELECT count(*) as jumlah FROM mahasiswa m
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN perguruan_tinggi pt on pt.id_perguruan_tinggi = p.id_perguruan_tinggi
WHERE npsn = '@npsn'
UNION ALL
/* Jumlah Sudah Link*/
SELECT count(*) as jumlah FROM mahasiswa m
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
WHERE npsn = '@npsn' AND m.fd_id_reg_pd IS NOT NULL
UNION ALL
/* Jumlah Bakal Update */
SELECT count(*) AS jumlah FROM mahasiswa m
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
WHERE npsn = '@npsn' AND m.fd_id_reg_pd IS NOT NULL AND m.fd_sync_on < m.updated_on
UNION ALL
/* Jumlah Bakal Insert */
SELECT count(*) as jumlah FROM mahasiswa m
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
WHERE npsn = '@npsn' AND m.fd_id_reg_pd IS NULL