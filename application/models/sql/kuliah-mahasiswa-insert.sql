SELECT 
    ms.id_mhs_status, m.nim_mhs,
    '@id_smt' as id_smt, m.fd_id_reg_pd as id_reg_pd, sp.fd_id_stat_mhs as id_stat_mhs, 
    ips, sks_semester as sks_smt, ipk, sks_total, 
    case when substr(s.fd_id_smt, -1) = '3' then 240000 /* Semester Pendek Fix Rp 240.0000 */
    else
        CASE sp.fd_id_stat_mhs WHEN 'C' THEN 0 ELSE tm.total_besar_biaya END
    end
    as biaya_smt
FROM mahasiswa_status ms
JOIN mahasiswa m ON m.id_mhs = ms.id_mhs
LEFT JOIN status_pengguna sp ON sp.id_status_pengguna = ms.id_status_pengguna
JOIN semester s ON s.id_semester = ms.id_semester
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = s.id_perguruan_tinggi
LEFT JOIN tagihan_mhs tm ON tm.id_mhs = m.id_mhs AND tm.id_semester = s.id_semester
WHERE 
    pt.npsn = '@npsn' AND
    s.thn_akademik_semester||decode(nm_semester,'Ganjil',1,'Genap',2,'Pendek',3) = '@id_smt' AND
    sp.fd_id_stat_mhs IS NOT NULL AND /* Pembatasan Status Aktivitas Kuliah */
    ms.fd_id_smt IS NULL AND ms.fd_id_reg_pd IS NULL
ORDER BY m.nim_mhs