/**
 * Author:  Fathoni <m.fathoni@mail.com>
 * Created: Apr 26, 2018
 */

SELECT
    m.nim_mhs, A.id_admisi, sp.nm_status_pengguna,
    M.fd_id_reg_pd AS id_registrasi_mahasiswa,
    sp.fd_id_jns_keluar AS id_jenis_keluar, 
	COALESCE(to_char(pw.tgl_sk_yudisium, 'YYYY-MM-DD'), to_char(A.tgl_keluar, 'YYYY-MM-DD')) as tanggal_keluar,
    COALESCE(pw.sk_yudisium, A.no_sk) AS nomor_sk_yudisium, 
    COALESCE(to_char(pw.tgl_sk_yudisium, 'YYYY-MM-DD'), to_char(A.tgl_sk, 'YYYY-MM-DD')) AS tanggal_sk_yudisium,
    pw.ipk,
    COALESCE(pw.no_ijasah, A.no_ijasah) AS nomor_ijazah,
    1 AS jalur_skripsi,
    substr(pw.judul_ta, 1, 500) AS judul_skripsi,
    to_char(pw.bulan_awal_bimbingan, 'YYYY-MM-DD') AS bulan_awal_bimbingan,
    to_char(pw.bulan_akhir_bimbingan, 'YYYY-MM-DD') AS bulan_akhir_bimbingan,
    S.FD_ID_SMT AS id_periode_keluar
FROM admisi A
JOIN mahasiswa M ON M.id_mhs = A.id_mhs
JOIN pengguna P ON P.id_pengguna = M.id_pengguna
JOIN status_pengguna sp ON sp.id_status_pengguna = A.status_akd_mhs
JOIN semester S ON S.id_semester = A.id_semester
LEFT JOIN pengajuan_wisuda pw ON pw.id_pengajuan_wisuda = A.id_pengajuan_wisuda
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND sp.status_keluar = 1 AND A.fd_sync_on IS NULL