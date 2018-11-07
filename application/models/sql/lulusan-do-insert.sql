/**
 * Author:  Fathoni <m.fathoni@mail.com>
 * Created: Apr 26, 2018
 */

SELECT
    m.nim_mhs, A.id_admisi, sp.nm_status_pengguna,
    M.fd_id_reg_pd AS id_reg_pd, 
    sp.fd_id_jns_keluar AS id_jns_keluar, 
	COALESCE(to_char(pw.tgl_sk_yudisium, 'YYYY-MM-DD'), to_char(A.tgl_keluar, 'YYYY-MM-DD')) as tgl_keluar,
    COALESCE(pw.sk_yudisium, A.no_sk) AS sk_yudisium, 
    COALESCE(to_char(pw.tgl_sk_yudisium, 'YYYY-MM-DD'), to_char(A.tgl_sk, 'YYYY-MM-DD')) AS tgl_sk_yudisium,
    pw.ipk,
    COALESCE(pw.no_ijasah, A.no_ijasah) AS no_seri_ijazah,
    1 AS jalur_skripsi,
    substr(pw.judul_ta, 1, 500) AS judul_skripsi,
    to_char(pw.bulan_awal_bimbingan, 'YYYY-MM-DD') AS bln_awal_bimbingan,
    to_char(pw.bulan_akhir_bimbingan, 'YYYY-MM-DD') AS bln_akhir_bimbingan
FROM admisi A
JOIN mahasiswa M ON M.id_mhs = A.id_mhs
JOIN pengguna P ON P.id_pengguna = M.id_pengguna
JOIN status_pengguna sp ON sp.id_status_pengguna = A.status_akd_mhs
LEFT JOIN pengajuan_wisuda pw ON pw.id_pengajuan_wisuda = A.id_pengajuan_wisuda
WHERE P.id_perguruan_tinggi = 1 AND sp.status_keluar = 1 AND A.fd_sync_on IS NULL