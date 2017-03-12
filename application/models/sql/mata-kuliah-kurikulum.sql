/**
 * Author:  Fathoni
 * Created: Feb 13, 2017
 */

/* Jumlah sudah Link */
SELECT count(*) AS JUMLAH FROM kurikulum_mk kmk
JOIN kurikulum k ON k.id_kurikulum = kmk.id_kurikulum
JOIN program_studi ps ON ps.id_program_studi = k.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn'
UNION ALL
/* Jumlah sudah Link */
SELECT count(*) FROM kurikulum_mk kmk
JOIN kurikulum k ON k.id_kurikulum = kmk.id_kurikulum
JOIN program_studi ps ON ps.id_program_studi = k.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND kmk.fd_id_kurikulum_sp IS NOT NULL AND kmk.fd_id_mk IS NOT NULL
UNION ALL
/* Jumlah bakal update */
SELECT count(*) FROM kurikulum_mk kmk
JOIN kurikulum k ON k.id_kurikulum = kmk.id_kurikulum
JOIN program_studi ps ON ps.id_program_studi = k.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND kmk.fd_id_kurikulum_sp IS NOT NULL AND kmk.fd_id_mk IS NOT NULL AND kmk.fd_sync_on < kmk.updated_on
UNION ALL
/* Jumlah bakal Insert */
SELECT count(*) FROM kurikulum_mk kmk
JOIN kurikulum k ON k.id_kurikulum = kmk.id_kurikulum
JOIN program_studi ps ON ps.id_program_studi = k.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND kmk.fd_id_kurikulum_sp IS NULL AND kmk.fd_id_mk IS NULL