
/**
 * Author:  umaha
 * Created: Nov 1, 2016
 */

SELECT 
	m.id_mhs,
	'@id_sms' as id_sms,
	NULL as id_pd, /* id_pd di dapat setelah insert mahasiswa */
	'@id_sp' as id_sp,

	/* Jenis pendaftaran */
	(SELECT kj.id_jns_daftar FROM admisi A
	JOIN jalur j ON j.id_jalur = A.id_jalur
	JOIN kode_jalur kj ON kj.kode_jalur = j.kode_jalur
	WHERE A.id_jalur IS NOT NULL and a.id_mhs = m.id_mhs) as id_jns_daftar,

	/* Total SKS Diakui */
	(SELECT coalesce(sum(sks_diakui),0) FROM pengambilan_mk_konversi pmk
	WHERE pmk.ID_MHS = m.id_mhs) as sks_diakui,

	m.nim_mhs as nipd,
	m.thn_angkatan_mhs||'-09-01' as tgl_masuk_sp,
	1 as a_pernah_paud,
	1 as a_pernah_tk,

	/* Semester Mulai */
	(SELECT thn_akademik_semester||decode(group_semester, 'Ganjil','1','Genap','2') FROM admisi A
	JOIN semester s ON s.id_semester = a.id_semester
	WHERE A.id_jalur IS NOT NULL and a.id_mhs = m.id_mhs) as mulai_smt,
	m.biaya_masuk as biaya_masuk_kuliah

FROM mahasiswa m
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
WHERE 
	pt.npsn = '@npsn' AND
	ps.kode_program_studi = '@kode_prodi' AND
	m.thn_angkatan_mhs = '@angkatan' AND
	m.fd_id_reg_pd IS NULL
	-- m.id_mhs NOT IN (SELECT id_mhs FROM feeder_mahasiswa_pt)
ORDER BY m.nim_mhs ASC