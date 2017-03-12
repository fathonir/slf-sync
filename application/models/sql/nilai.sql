/**
 * Author:  Fathoni
 * Created: Feb 14, 2017
 */

/* Jumlah semua data */
SELECT count(*) AS jumlah
FROM pengambilan_mk pmk
JOIN mahasiswa m ON m.id_mhs = pmk.id_mhs
JOIN kelas_mk kls ON kls.id_kelas_mk = pmk.id_kelas_mk
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pmk.status_apv_pengambilan_mk = 1 AND
    pt.npsn = '@npsn'
UNION ALL
/* Jumlah sudah link */
SELECT count(*) AS jumlah
FROM pengambilan_mk pmk
JOIN mahasiswa m ON m.id_mhs = pmk.id_mhs
JOIN kelas_mk kls ON kls.id_kelas_mk = pmk.id_kelas_mk
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pmk.status_apv_pengambilan_mk = 1 AND
    pt.npsn = '@npsn' AND pmk.fd_id_kls IS NOT NULL AND pmk.fd_id_reg_pd IS NOT NULL
UNION ALL
/* Jumlah bakal update */
SELECT count(*) AS jumlah
FROM pengambilan_mk pmk
JOIN mahasiswa m ON m.id_mhs = pmk.id_mhs
JOIN kelas_mk kls ON kls.id_kelas_mk = pmk.id_kelas_mk
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pmk.status_apv_pengambilan_mk = 1 AND
    pt.npsn = '@npsn' AND pmk.fd_id_kls IS NOT NULL AND pmk.fd_id_reg_pd IS NOT NULL AND pmk.fd_sync_on < pmk.updated_on
UNION ALL
/* Jumlah bakal insert */
SELECT count(*) AS jumlah
FROM pengambilan_mk pmk
JOIN mahasiswa m ON m.id_mhs = pmk.id_mhs
JOIN kelas_mk kls ON kls.id_kelas_mk = pmk.id_kelas_mk
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pmk.status_apv_pengambilan_mk = 1 AND
    pt.npsn = '@npsn' AND pmk.fd_id_kls IS NULL AND pmk.fd_id_reg_pd IS NULL