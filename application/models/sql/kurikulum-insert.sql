/**
 * Author:  Fathoni
 * Created: Feb 13, 2017
 */

SELECT
    k.id_kurikulum,
    nm_kurikulum AS nm_kurikulum_sp,
    k.smt_normal AS jml_sem_normal,
    k.sks_lulus AS jml_sks_lulus,
    k.sks_wajib AS jml_sks_wajib,
    k.sks_pilihan AS jml_sks_pilihan,
    ps.fd_id_sms AS id_sms,
    j.id_jenjang_pendidikan_feeder AS id_jenj_didik,
    s.thn_akademik_semester||decode(s.nm_semester, 'Ganjil','1', 'Genap','2') AS id_smt
FROM kurikulum k
JOIN semester s ON s.id_semester = k.id_semester_mulai
JOIN program_studi ps ON ps.id_program_studi = k.id_program_studi
JOIN jenjang j ON j.id_jenjang = ps.id_jenjang
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE 
  pt.npsn = '@npsn' AND ps.kode_program_studi = '@kode_prodi' AND
  k.fd_id_kurikulum_sp IS NULL