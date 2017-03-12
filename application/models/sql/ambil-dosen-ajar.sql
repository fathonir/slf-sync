/**
 * Author:  Fathoni
 * Created: Feb 14, 2017
 */

SELECT
    d.fd_id_reg_ptk AS id_reg_ptk,
    d.nidn_dosen, p.nm_pengguna,
    1 as perlu_sync
FROM dosen d
JOIN pengguna p ON p.id_pengguna = d.id_pengguna
JOIN program_studi ps ON ps.id_program_studi = d.id_program_studi
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND ps.kode_program_studi = '@kode_prodi' AND d.fd_id_reg_ptk IS NOT NULL
ORDER BY p.nm_pengguna