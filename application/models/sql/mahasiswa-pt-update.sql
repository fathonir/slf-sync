
/**
 * Author:  umaha
 * Created: Nov 1, 2016
 */

SELECT 
	m.id_mhs, m.fd_id_reg_pd as id_reg_pd,
	'@id_sms' as id_sms,
	p.fd_id_pd as id_pd,
	'@id_sp' as id_sp,

	/* Jenis pendaftaran */
	(SELECT kj.id_jns_daftar FROM admisi A
	JOIN jalur j ON j.id_jalur = A.id_jalur
	JOIN kode_jalur kj ON kj.kode_jalur = j.kode_jalur
	WHERE A.id_jalur IS NOT NULL and a.id_mhs = m.id_mhs) as id_jns_daftar,

	/* Total SKS Diakui */
	(SELECT coalesce(sum(kredit_semester),0) 
	FROM kurikulum_mk 
	JOIN pengambilan_mk pmk on pmk.id_kurikulum_mk = kurikulum_mk.id_kurikulum_mk
	WHERE pmk.id_mhs = m.id_mhs and pmk.status_transfer = 1) as sks_diakui,

	m.nim_mhs as nipd,
	m.thn_angkatan_mhs||'-09-01' as tgl_masuk_sp,  /* Default tanggal masuk 1 September */
	1 as a_pernah_paud,
	1 as a_pernah_tk,

	/* Semester Mulai */
	(SELECT thn_akademik_semester||decode(group_semester, 'Ganjil','1','Genap','2') FROM admisi A
	JOIN semester s ON s.id_semester = a.id_semester
	WHERE A.id_jalur IS NOT NULL and a.id_mhs = m.id_mhs) as mulai_smt

FROM mahasiswa m
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
WHERE 
	pt.npsn = '@npsn' AND
	ps.kode_program_studi = '@kode_prodi' AND
	m.thn_angkatan_mhs = '@angkatan' AND
	m.updated_on > m.fd_sync_on
ORDER BY m.nim_mhs ASC