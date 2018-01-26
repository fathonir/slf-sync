/**
 * Author:  Fathoni
 * Created: Feb 14, 2017
 */

/* Jumlah Semua Data */
SELECT count(*) AS jumlah FROM pengampu_mk pjmk
JOIN kelas_mk kls ON kls.id_kelas_mk = pjmk.id_kelas_mk
-- JOIN kurikulum_mk kmk ON kmk.id_kurikulum_mk = kls.id_kurikulum_mk
-- JOIN kurikulum k ON k.id_kurikulum = kmk.id_kurikulum
JOIN program_studi ps ON ps.id_program_studi = kls.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND pjmk.pjmk_pengampu_mk = '1'
UNION ALL
/* Jumlah sudah link */
SELECT count(*) AS jumlah FROM pengampu_mk pjmk
JOIN kelas_mk kls ON kls.id_kelas_mk = pjmk.id_kelas_mk
JOIN kurikulum_mk kmk ON kmk.id_kurikulum_mk = kls.id_kurikulum_mk
JOIN kurikulum k ON k.id_kurikulum = kmk.id_kurikulum
JOIN program_studi ps ON ps.id_program_studi = k.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND pjmk.pjmk_pengampu_mk = '1' AND 
    pjmk.fd_id_ajar IS NOT NULL
UNION ALL
/* Jumlah bakal update */
SELECT count(*) AS jumlah FROM pengampu_mk pjmk
JOIN kelas_mk kls ON kls.id_kelas_mk = pjmk.id_kelas_mk
JOIN kurikulum_mk kmk ON kmk.id_kurikulum_mk = kls.id_kurikulum_mk
JOIN kurikulum k ON k.id_kurikulum = kmk.id_kurikulum
JOIN program_studi ps ON ps.id_program_studi = k.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND pjmk.pjmk_pengampu_mk = '1' AND 
    pjmk.fd_id_ajar IS NOT NULL AND pjmk.fd_sync_on < pjmk.updated_on
UNION ALL
/* Jumlah bakal insert */
SELECT count(*) AS jumlah FROM pengampu_mk pjmk
JOIN kelas_mk kls ON kls.id_kelas_mk = pjmk.id_kelas_mk
JOIN kurikulum_mk kmk ON kmk.id_kurikulum_mk = kls.id_kurikulum_mk
JOIN kurikulum k ON k.id_kurikulum = kmk.id_kurikulum
JOIN program_studi ps ON ps.id_program_studi = k.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND pjmk.pjmk_pengampu_mk = '1' AND 
    pjmk.fd_id_ajar IS NULL