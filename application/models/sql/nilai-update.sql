/**
 * Author:  Fathoni
 * Created: Feb 14, 2017
 */

SELECT
    pmk.id_pengambilan_mk,
    m.nim_mhs||' '||p.nm_pengguna AS mhs,
    kls.fd_id_kls AS id_kls,
    m.fd_id_reg_pd AS id_reg_pd,
    pmk.nilai_angka,
    pmk.nilai_huruf
FROM pengambilan_mk pmk
JOIN mahasiswa m ON m.id_mhs = pmk.id_mhs
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN kelas_mk kls ON kls.id_kelas_mk = pmk.id_kelas_mk
WHERE pmk.status_apv_pengambilan_mk = 1 AND pmk.id_kelas_mk = '@id_kelas_mk' AND
    pmk.fd_id_kls IS NULL AND pmk.fd_id_reg_pd IS NULL AND
    pmk.fd_sync_on < pmk.updated_on