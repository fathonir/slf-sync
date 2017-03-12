/**
 * Author:  Fathoni
 * Created: Feb 13, 2017
 */

/* Jumlah Semua Data */
SELECT count(*) as jumlah FROM mahasiswa m
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN perguruan_tinggi pt on pt.id_perguruan_tinggi = p.id_perguruan_tinggi
WHERE npsn = '@npsn' AND ps.kode_program_studi = '@kode_prodi' AND m.thn_angkatan_mhs = '@angkatan'
UNION ALL
/* Jumlah Sudah Link*/
SELECT count(*) as jumlah FROM mahasiswa m
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
WHERE npsn = '@npsn' AND ps.kode_program_studi = '@kode_prodi' AND m.thn_angkatan_mhs = '@angkatan' AND m.fd_id_reg_pd IS NOT NULL
UNION ALL
/* Jumlah Bakal Update */
SELECT count(*) AS jumlah FROM mahasiswa m
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
WHERE npsn = '@npsn' AND ps.kode_program_studi = '@kode_prodi' AND m.thn_angkatan_mhs = '@angkatan' AND m.fd_id_reg_pd IS NOT NULL AND m.fd_sync_on < m.updated_on
UNION ALL
/* Jumlah Bakal Insert */
SELECT count(*) as jumlah FROM mahasiswa m
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
WHERE npsn = '@npsn' AND ps.kode_program_studi = '@kode_prodi' AND m.thn_angkatan_mhs = '@angkatan' AND m.fd_id_reg_pd IS NULL