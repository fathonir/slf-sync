/**
 * Author:  Fathoni
 * Created: Feb 13, 2017
 */

SELECT id_program_studi, nm_jenjang, nm_program_studi, kode_program_studi
FROM program_studi ps
JOIN jenjang j on j.id_jenjang = ps.id_jenjang
JOIN fakultas f on f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' 
ORDER BY nm_jenjang, nm_program_studi