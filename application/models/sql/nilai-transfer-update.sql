/**
 * Author:  Fathoni <m.fathoni@mail.com>
 * Created: Mar 4, 2019
 */

select
	m.nim_mhs||' '||p.nm_pengguna as mhs,
	pmk.id_pengambilan_mk,
	m.fd_id_reg_pd as id_reg_pd,
	mk.fd_id_mk as id_mk,
	coalesce(pmk.kd_mata_kuliah_asal, substr(mk.kd_mata_kuliah, 1, 20)) as kode_mk_asal,
	coalesce(pmk.nm_mata_kuliah_asal, substr(mk.nm_mata_kuliah, 1, 200)) as nm_mk_asal,
	coalesce(pmk.kredit_asal, mk.kredit_semester) as sks_asal,
	coalesce(pmk.kredit_diakui, mk.kredit_semester) as sks_diakui,
	pmk.nilai_huruf_asal,
	pmk.nilai_huruf as nilai_huruf_diakui,
	nvl(sn.nilai_standar_nilai, 0) as nilai_angka_diakui
from pengambilan_mk pmk
join mahasiswa m on m.id_mhs = pmk.id_mhs
join pengguna p on p.id_pengguna = m.id_pengguna
join perguruan_tinggi pt on pt.id_perguruan_tinggi = p.id_perguruan_tinggi
join kurikulum_mk kur on kur.id_kurikulum_mk = pmk.id_kurikulum_mk
join mata_kuliah mk on mk.id_mata_kuliah = kur.id_mata_kuliah
left join standar_nilai sn on sn.nm_standar_nilai = pmk.nilai_huruf
where 
	pt.npsn = '@npsn' 
	and m.fd_id_reg_pd is not null
	and pmk.status_transfer = 1
	and pmk.fd_id_ekuivalensi is not null and pmk.updated_on > pmk.fd_sync_on