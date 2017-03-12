/**
 * Author:  Fathoni
 * Created: Feb 13, 2017
 */

SELECT DISTINCT thn_angkatan_mhs FROM mahasiswa m
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' 
ORDER BY 1 DESC