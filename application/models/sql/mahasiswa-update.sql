
/**
 * Author:  umaha
 * Created: Nov 1, 2016
 */

SELECT 
	m.id_mhs, p.fd_id_pd as id_pd,

	/* Informasi Mahasiswa : nama, tmpt & lahir, ibu kandung di exclude dr update */
	decode(p.kelamin_pengguna, 1, 'L', 2, 'P', NULL, 'L') as jenis_kelamin, /* default Laki-Laki */
	m.nisn as nisn,
	NVL(m.nik_mhs, CASE WHEN LENGTH(cmb.nik_c_mhs) <= 16 THEN cmb.nik_c_mhs END) AS nik,
	NVL((select id_feeder from agama where agama.id_agama = p.id_agama), 1) as id_agama,  /* default Islam */

	/* Info tempat tinggal */
	SUBSTR(m.alamat_mhs, 1, 80) as jalan,
	m.alamat_rt_mhs as rt,
	m.alamat_rw_mhs as rw,
	m.alamat_dusun_mhs as dusun,
	SUBSTR(m.alamat_kelurahan_mhs, 1, 60) as kelurahan,
	'000000' as id_wilayah,
	m.alamat_kodepos as kode_pos,
	SUBSTR(m.mobile_mhs, 1, 20) as handphone,
	COALESCE(p.email_alternate, p.email_pengguna) as email,

	/* Kartu Perlindungan Sosial */
	0 as penerima_kps,

	/* Informasi Ayah */
	nm_ayah_mhs as nama_ayah,

    /* Kewarganegaraan */
	'ID' as kewarganegaraan
FROM mahasiswa m
JOIN pengguna p ON p.id_pengguna = m.id_pengguna
JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
LEFT JOIN calon_mahasiswa_baru cmb ON cmb.id_c_mhs = M.id_c_mhs
WHERE 
	pt.npsn = '@npsn' AND
	ps.kode_program_studi = '@kode_prodi' AND
	m.thn_angkatan_mhs = '@angkatan' AND
	m.updated_on > m.fd_sync_on
ORDER BY 1 ASC