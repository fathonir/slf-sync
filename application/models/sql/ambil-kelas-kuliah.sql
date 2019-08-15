/**
 * Author:  Fathoni
 * Created: Feb 14, 2017
 */

SELECT kls.id_kelas_mk, mk.kd_mata_kuliah||' '||mk.nm_mata_kuliah||' ('||nk.nama_kelas||') ' as nm_kelas,
    (SELECT count(*) FROM pengambilan_mk pmk WHERE pmk.id_kelas_mk = kls.id_kelas_mk AND status_apv_pengambilan_mk = 1) as peserta,
    (SELECT count(*) FROM pengambilan_mk pmk WHERE pmk.id_kelas_mk = kls.id_kelas_mk AND status_apv_pengambilan_mk = 1 AND nilai_huruf is not null) as ada_nilai,
    (SELECT count(*) FROM pengambilan_mk pmk WHERE  pmk.id_kelas_mk = kls.id_kelas_mk AND status_apv_pengambilan_mk = 1 AND 
    (/* Insert Baru */(fd_id_kls IS NULL AND fd_id_reg_pd IS NULL) OR /* Update*/ (fd_id_kls IS NOT NULL AND fd_id_reg_pd IS NOT NULL AND fd_sync_on < updated_on))) AS perlu_sync
FROM kelas_mk kls
JOIN mata_kuliah mk ON mk.id_mata_kuliah = kls.id_mata_kuliah
JOIN nama_kelas nk ON nk.id_nama_kelas = kls.no_kelas_mk
JOIN program_studi ps ON ps.id_program_studi = kls.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND ps.kode_program_studi = '@kode_prodi' AND kls.id_semester = '@smt' AND kls.fd_id_kls IS NOT NULL
ORDER BY mk.nm_mata_kuliah, nk.nama_feeder