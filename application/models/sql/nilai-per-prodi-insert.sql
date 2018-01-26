/**
 * Author:  Fathoni
 * Created: Jun 1, 2017
 */

SELECT
    pmk.id_pengambilan_mk,
    m.nim_mhs||' '||p.nm_pengguna AS mhs,
    kls.fd_id_kls AS id_kls,
    m.fd_id_reg_pd AS id_reg_pd,
    pmk.nilai_angka,
    pmk.nilai_huruf,
    nvl(sn.nilai_standar_nilai, sn2.nilai_standar_nilai) as nilai_indeks,
    mk.nm_mata_kuliah||' ('||nk.nama_feeder||')' as nama_kelas
FROM pengambilan_mk pmk
JOIN mahasiswa m ON m.id_mhs = pmk.id_mhs
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN kelas_mk kls ON kls.id_kelas_mk = pmk.id_kelas_mk
JOIN mata_kuliah mk ON mk.id_mata_kuliah = kls.id_mata_kuliah
JOIN nama_kelas nk ON nk.id_nama_kelas = kls.no_kelas_mk
JOIN program_studi ps ON ps.id_program_studi = kls.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
LEFT JOIN peraturan_nilai pn ON 
    pn.id_perguruan_tinggi = pt.id_perguruan_tinggi AND
    pn.nilai_min_peraturan_nilai <= round(pmk.nilai_angka, 2) AND round(pmk.nilai_angka, 2) <= pn.nilai_max_peraturan_nilai
LEFT JOIN standar_nilai sn ON sn.id_standar_nilai = pn.id_standar_nilai
LEFT JOIN standar_nilai sn2 ON sn2.nm_standar_nilai = pmk.nilai_huruf
WHERE
    pmk.status_apv_pengambilan_mk = 1 AND
    pt.npsn = '@npsn' AND
    ps.kode_program_studi = '@kode_prodi' AND
    pmk.id_semester = '@smt' AND
    pmk.fd_id_kls IS NULL AND pmk.fd_id_reg_pd IS NULL