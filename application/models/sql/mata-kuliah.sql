/**
 * Author:  Fathoni
 * Created: Feb 13, 2017
 */

/* Jumlah Semua Data */
SELECT count(*) AS jumlah FROM mata_kuliah mk
JOIN program_studi ps ON ps.id_program_studi = mk.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn'
UNION ALL
/* Jumlah sudah link */
SELECT count(*) AS jumlah FROM mata_kuliah mk
JOIN program_studi ps ON ps.id_program_studi = mk.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND mk.fd_id_mk IS NOT NULL
UNION ALL
/* Jumlah bakal update */
SELECT count(*) AS jumlah FROM mata_kuliah mk
JOIN program_studi ps ON ps.id_program_studi = mk.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND mk.fd_id_mk IS NOT NULL AND mk.fd_sync_on < mk.updated_on
UNION ALL
/* Jumlah bakal insert */
SELECT count(*) AS jumlah FROM mata_kuliah mk
JOIN program_studi ps ON ps.id_program_studi = mk.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND mk.fd_id_mk IS NULL