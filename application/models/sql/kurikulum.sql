/**
 * Author:  Fathoni
 * Created: Feb 13, 2017
 */

/* Jumlah Semua Data */
SELECT count(*) AS jumlah FROM kurikulum k
JOIN program_studi ps ON ps.id_program_studi = k.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn'
UNION ALL
/* Jumlah sudah link */
SELECT count(*) AS jumlah FROM kurikulum k
JOIN program_studi ps ON ps.id_program_studi = k.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND k.fd_id_kurikulum_sp IS NOT NULL
UNION ALL
/* Jumlah bakal update */
SELECT count(*) AS jumlah FROM kurikulum k
JOIN program_studi ps ON ps.id_program_studi = k.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND k.fd_id_kurikulum_sp IS NOT NULL AND k.fd_sync_on < k.updated_on
UNION ALL
/* Jumlah bakal insert */
SELECT count(*) AS jumlah FROM kurikulum k
JOIN program_studi ps ON ps.id_program_studi = k.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND k.fd_id_kurikulum_sp IS NULL