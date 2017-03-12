/**
 * Author:  Fathoni
 * Created: Feb 14, 2017
 */

SELECT id_semester FROM semester s
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = s.id_perguruan_tinggi
WHERE 
    pt.npsn = '@npsn' AND 
    s.thn_akademik_semester||decode(upper(nm_semester), 'GANJIL','1','GENAP','2','PENDEK','3') = '@id_smt'