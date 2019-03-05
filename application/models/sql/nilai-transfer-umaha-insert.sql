/**
 * Author:  Fathoni <m.fathoni@mail.com>
 * Created: Feb 19, 2019
 */

select
	m.nim_mhs||' '||p.nm_pengguna as mhs,
	pmk.id_pengambilan_mk,
	m.fd_id_reg_pd as id_reg_pd,
	mk.fd_id_mk as id_mk,
	substr(mk.kd_mata_kuliah, 1, 20) as kode_mk_asal,
	substr(mk.nm_mata_kuliah, 1, 200) as nm_mk_asal,
	mk.kredit_semester as sks_asal,
	mk.kredit_semester as sks_diakui,
	pmk.nilai_huruf as nilai_huruf_asal,
	pmk.nilai_huruf as nilai_huruf_diakui,
	nvl(sn.nilai_standar_nilai, 0) as nilai_angka_diakui
from pengambilan_mk pmk
join mahasiswa m on m.id_mhs = pmk.id_mhs
join pengguna p on p.id_pengguna = m.id_pengguna
join semester s on s.id_semester = pmk.id_semester
join kelas_mk kls on kls.id_kelas_mk = pmk.id_kelas_mk
join mata_kuliah mk on mk.id_mata_kuliah = kls.id_mata_kuliah
left join standar_nilai sn on sn.nm_standar_nilai = pmk.nilai_huruf
where 
    p.id_perguruan_tinggi = 1 
	and	m.thn_angkatan_mhs < 2014
    and m.fd_id_reg_pd is not null 
    and s.thn_akademik_semester < 2014 
    and pmk.fd_id_ekuivalensi is null
	and pmk.nilai_huruf is not null
	and rownum <= 1000