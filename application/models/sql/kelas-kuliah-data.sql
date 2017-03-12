/**
 * Author:  Fathoni
 * Created: Feb 13, 2017
 */

/* Jumlah Semua Data */
SELECT count(*) AS jumlah FROM kelas_mk kls
JOIN program_studi ps ON ps.id_program_studi = kls.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND ps.kode_program_studi = '@kode_prodi' AND kls.id_semester = '@smt'
UNION ALL
/* Jumlah sudah link */
SELECT count(*) AS jumlah FROM kelas_mk kls
JOIN program_studi ps ON ps.id_program_studi = kls.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND ps.kode_program_studi = '@kode_prodi' AND kls.id_semester = '@smt' AND kls.fd_id_kls IS NOT NULL
UNION ALL
/* Jumlah bakal update */
SELECT count(*) AS jumlah FROM kelas_mk kls
JOIN program_studi ps ON ps.id_program_studi = kls.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND ps.kode_program_studi = '@kode_prodi' AND kls.id_semester = '@smt' AND kls.fd_id_kls IS NOT NULL AND kls.fd_sync_on < kls.updated_on
UNION ALL
/* Jumlah bakal insert */
SELECT count(*) AS jumlah FROM kelas_mk kls
JOIN program_studi ps ON ps.id_program_studi = kls.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND ps.kode_program_studi = '@kode_prodi' AND kls.id_semester = '@smt' AND kls.fd_id_kls IS NULL