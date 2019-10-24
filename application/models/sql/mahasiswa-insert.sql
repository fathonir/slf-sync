/**
 * Author:  Fathoni
 * Created: Nov 1, 2016
 */

SELECT 
	m.id_mhs, p.id_pengguna,

	/* Informasi Mahasiswa */
	P.nm_pengguna AS nm_pd,
	decode(P.kelamin_pengguna, 1, 'L', 2, 'P', NULL, 'L') AS jk, /* default Laki-Laki */
	NULL AS nisn, 
	NVL(m.nik_mhs, CASE WHEN LENGTH(cmb.nik_c_mhs) <= 16 THEN cmb.nik_c_mhs END) AS nik,
	NVL((SELECT nm_kota FROM kota WHERE kota.id_kota = m.LAHIR_KOTA_MHS), 'Belum Terekam') as tmpt_lahir,
	NVL(to_char(tgl_lahir_pengguna, 'YYYY-MM-DD'), '1900-01-01') as tgl_lahir,
	NVL((select id_feeder from agama where agama.id_agama = p.id_agama), 1) as id_agama,  /* default Islam */
	0 AS id_kk,
	'@id_sp' as id_sp,

	/* Info tempat tinggal */
	SUBSTR(COALESCE(alamat_asal_mhs, alamat_mhs), 1, 80) as jln,
	cmb.alamat_rt AS rt, 
	cmb.alamat_rw AS rw, 
	substr(cmb.alamat_dusun, 1, 60) AS nm_dsn, 
	NVL(SUBSTR(COALESCE(alamat_asal_mhs, alamat_mhs), 1, 60), 'Belum Terekam') as ds_kel,
	'000000' as id_wil,
	NULL AS kode_pos,
	NULL AS id_jns_tinggal,
	NULL AS id_alat_transport,
	NULL AS telepon_rumah,
	substr(mobile_mhs, 1, 20) AS telepon_seluler,
	COALESCE(P.email_alternate, cmb.email, p.email_pengguna) AS email,

	/* Other Info */
	0 as a_terima_kps,
	null as no_kps,
	'A' as stat_pd,

	/* Informasi Ayah */
	nm_ayah_mhs as nm_ayah,
	null as tgl_lahir_ayah,
	null as id_jenjang_pendidikan_ayah,
	null as id_pekerjaan_ayah,
	null as id_penghasilan_ayah,
	NVL(null, 0) as id_kebutuhan_khusus_ayah,

	/* Informasi Ibu */
	coalesce(nm_ibu_mhs, 'Belum Terekam') as nm_ibu_kandung,
	null as tgl_lahir_ibu,
	null as id_jenjang_pendidikan_ibu,
	null as id_pekerjaan_ibu,
	null as id_penghasilan_ibu,
	NVL(null, 0) as id_kebutuhan_khusus_ibu,

	/* Informasi Wali */
	null as nm_wali,
	null as tgl_lahir_wali,
	null as id_jenjang_pendidikan_wali,
	null as id_pekerjaan_wali,
	null as id_penghasilan_wali,

	'ID' AS kewarganegaraan

FROM mahasiswa m
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
LEFT JOIN calon_mahasiswa_baru cmb ON cmb.id_c_mhs = M.id_c_mhs
LEFT JOIN calon_mahasiswa_sekolah cms ON cms.id_c_mhs = cmb.id_c_mhs
WHERE 
	pt.npsn = '@npsn' AND
	ps.kode_program_studi = '@kode_prodi' AND
	m.thn_angkatan_mhs = '@angkatan' AND
	m.fd_id_reg_pd IS NULL
ORDER BY m.nim_mhs ASC