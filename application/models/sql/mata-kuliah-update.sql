/**
 * Author:  Fathoni
 * Created: Feb 13, 2017
 */

SELECT
    mk.id_mata_kuliah, mk.fd_id_mk as id_mk,
    ps.fd_id_sms AS id_sms,
    j.id_jenjang_pendidikan_feeder AS id_jenj_didik,
    kd_mata_kuliah AS kode_mk,
    nm_mata_kuliah as nm_mk,
    id_jenis_mk AS jns_mk,
    id_kelompok_mk AS kel_mk,
    kredit_semester AS sks_mk,
    kredit_tatap_muka AS sks_tm,
    kredit_praktikum AS sks_prak,
    kredit_prak_lapangan AS sks_prak_lapangan,
    kredit_simulasi AS sks_sim,
    mk.metode_pelaksanaan AS metode_pelaksanaan_kuliah,
    mk.ada_sap AS a_sap,
    mk.ada_silabus AS a_silabus,
    mk.ada_bahan_ajar AS a_bahan_ajar,
    mk.status_praktikum AS acara_prak,
    mk.ada_diktat AS a_diktat,
    to_char(mk.tgl_mulai_efektif, 'YYYY-MM-DD') AS tgl_mulai_efektif,
    to_char(mk.tgl_akhir_efektif, 'YYYY-MM-DD') as tgl_akhir_efektif
FROM mata_kuliah mk
JOIN program_studi ps ON ps.id_program_studi = mk.id_program_studi
JOIN jenjang j ON j.id_jenjang = ps.id_jenjang
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE 
    pt.npsn = '@npsn' and ps.kode_program_studi = '@kode_prodi' AND
    mk.fd_id_mk IS NOT NULL AND mk.fd_sync_on < mk.updated_on