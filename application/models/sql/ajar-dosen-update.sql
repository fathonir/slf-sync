/**
 * Author:  Fathoni
 * Created: Feb 14, 2017
 */

SELECT 
    pjmk.id_pengampu_mk, pjmk.fd_id_ajar as id_ajar,
    1 AS id_jns_eval,
    d.fd_id_reg_ptk AS id_reg_ptk,
    kls.fd_id_kls AS id_kls,
    nvl(kmk.kredit_semester, 0) AS sks_subst_tot,
    nvl(kmk.kredit_tatap_muka, 0) AS sks_tm_subst,
    nvl(kmk.kredit_praktikum, 0) AS sks_prak_subst,
    nvl(kmk.kredit_prak_lapangan, 0) AS sks_prak_lap_subst,
    nvl(kmk.kredit_simulasi, 0) AS sks_sim_subst,
    nvl(kls.jumlah_pertemuan_kelas_mk, 0) AS jml_tm_renc,
    nvl(kls.jumlah_pertemuan_kelas_mk, 0) AS jml_tm_real,
    '('||d.nidn_dosen||') '||p.nm_pengguna AS nm_dosen,
    mk.nm_mata_kuliah||' ('||nk.nama_feeder||')' AS nm_kelas
FROM pengampu_mk pjmk
JOIN dosen d ON d.id_dosen = pjmk.id_dosen
JOIN pengguna p ON p.id_pengguna = d.id_pengguna
JOIN kelas_mk kls ON kls.id_kelas_mk = pjmk.id_kelas_mk
JOIN nama_kelas nk ON nk.id_nama_kelas = kls.no_kelas_mk
JOIN kurikulum_mk kmk ON kmk.id_kurikulum_mk = kls.id_kurikulum_mk
JOIN mata_kuliah mk ON mk.id_mata_kuliah = kmk.id_mata_kuliah
JOIN program_studi ps ON ps.id_program_studi = kls.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE 
    pt.npsn = '@npsn' AND
    ps.kode_program_studi = '@kode_prodi' AND
    kls.id_semester = '@smt' AND
    pjmk.pjmk_pengampu_mk = '1' AND
    kls.fd_id_kls IS NOT NULL AND d.fd_id_reg_ptk IS NOT NULL AND
    pjmk.fd_id_ajar IS NOT NULL AND pjmk.fd_sync_on < pjmk.updated_on
ORDER BY nm_dosen