/**
 * Author:  Fathoni
 * Created: Feb 14, 2017
 */

/* Jumlah semua data */
SELECT count(*) AS jumlah FROM pengambilan_mk pmk
WHERE pmk.status_apv_pengambilan_mk = 1 AND pmk.id_kelas_mk = '@id_kelas_mk'
UNION ALL
/* Jumlah sudah link */
SELECT count(*) AS jumlah FROM pengambilan_mk pmk
JOIN mahasiswa m ON m.id_mhs = pmk.id_mhs
JOIN kelas_mk kls ON kls.id_kelas_mk = pmk.id_kelas_mk
WHERE
    pmk.status_apv_pengambilan_mk = 1 AND pmk.id_kelas_mk = '@id_kelas_mk' AND
    pmk.fd_id_kls IS NOT NULL AND pmk.fd_id_reg_pd IS NOT NULL
UNION ALL
/* Jumlah bakal Update */
SELECT count(*) AS jumlah FROM pengambilan_mk pmk
JOIN mahasiswa m ON m.id_mhs = pmk.id_mhs
JOIN kelas_mk kls ON kls.id_kelas_mk = pmk.id_kelas_mk
WHERE
    pmk.status_apv_pengambilan_mk = 1 AND pmk.id_kelas_mk = '@id_kelas_mk' AND
    pmk.fd_id_kls IS NOT NULL AND pmk.fd_id_reg_pd IS NOT NULL AND
    pmk.fd_sync_on < pmk.updated_on
UNION ALL
/* Jumlah bakal Insert */
SELECT count(*) AS jumlah FROM pengambilan_mk pmk
JOIN mahasiswa m ON m.id_mhs = pmk.id_mhs
JOIN kelas_mk kls ON kls.id_kelas_mk = pmk.id_kelas_mk
WHERE
    pmk.status_apv_pengambilan_mk = 1 AND pmk.id_kelas_mk = '@id_kelas_mk' AND
    pmk.fd_id_kls IS NULL AND pmk.fd_id_reg_pd IS NULL