/**
 * Author:  Fathoni
 * Created: Feb 13, 2017
 */

SELECT
    kls.id_kelas_mk, kls.fd_id_kls as id_kls,
    ps.fd_id_sms AS id_sms,
    '@id_smt' AS id_smt,
    mk.fd_id_mk as id_mk,
    nk.nama_feeder AS nm_kls,
    nvl(kmk.kredit_semester, 0) AS sks_mk,
    nvl(kmk.kredit_tatap_muka, 0) AS sks_tm,
    nvl(kmk.kredit_praktikum, 0) AS sks_prak,
    nvl(kmk.kredit_prak_lapangan, 0) AS sks_prak_lap,
    nvl(kmk.kredit_simulasi, 0) AS sks_sim,
    0 AS a_selenggara_pditt, 0 AS kuota_pditt, 0 AS a_pengguna_pditt,
    to_char(tgl_mulai, 'YYYY-MM-DD') as tgl_mulai_koas,
    to_char(tgl_akhir, 'YYYY-MM-DD') as tgl_selesai_koas,
    mk.nm_mata_kuliah
FROM kelas_mk kls
JOIN nama_kelas nk ON nk.id_nama_kelas = kls.no_kelas_mk
JOIN kurikulum_mk kmk ON kmk.id_kurikulum_mk = kls.id_kurikulum_mk
JOIN mata_kuliah mk ON mk.id_mata_kuliah = kmk.id_mata_kuliah
JOIN kurikulum k ON k.id_kurikulum = kmk.id_kurikulum
JOIN program_studi ps ON ps.id_program_studi = k.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE 
    pt.npsn = '@npsn' AND
    ps.kode_program_studi = '@kode_prodi' AND
    kls.id_semester = '@smt' AND
    kls.fd_id_kls IS NOT NULL AND kls.fd_sync_on < kls.updated_on