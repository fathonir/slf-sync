/* Jumlah Semua Data */
SELECT count(*) AS jumlah FROM mahasiswa_status ms
JOIN mahasiswa m ON m.id_mhs = ms.id_mhs
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND ms.id_semester = '@id_semester'
UNION ALL
/* Jumlah Sudah Link */
SELECT count(*) AS jumlah FROM mahasiswa_status ms
JOIN mahasiswa m ON m.id_mhs = ms.id_mhs
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND ms.id_semester = '@id_semester' AND ms.fd_id_smt IS NOT NULL AND ms.fd_id_reg_pd IS NOT NULL
UNION ALL
/* Jumlah Bakal Update */
SELECT count(*) AS jumlah FROM mahasiswa_status ms
LEFT JOIN status_pengguna sp ON sp.id_status_pengguna = ms.id_status_pengguna
JOIN mahasiswa m ON m.id_mhs = ms.id_mhs
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND ms.id_semester = '@id_semester' AND 
    sp.fd_id_stat_mhs IS NOT NULL AND /* Pembatasan Status Aktivitas Kuliah */
    ms.fd_id_smt IS NOT NULL AND ms.fd_id_reg_pd IS NOT NULL AND ms.fd_sync_on < ms.updated_on
UNION ALL
/* Jumlah Bakal Insert */
SELECT count(*) AS jumlah FROM mahasiswa_status ms
LEFT JOIN status_pengguna sp ON sp.id_status_pengguna = ms.id_status_pengguna
JOIN mahasiswa m ON m.id_mhs = ms.id_mhs
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
WHERE pt.npsn = '@npsn' AND ms.id_semester = '@id_semester' AND
    sp.fd_id_stat_mhs IS NOT NULL AND /* Pembatasan Status Aktivitas Kuliah */
    ms.fd_id_smt IS NULL AND ms.fd_id_reg_pd IS NULL