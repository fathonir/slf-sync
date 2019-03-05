/**
 * Author:  Fathoni <m.fathoni@mail.com>
 * Created: Feb 8, 2019
 */

select count(*) as jumlah
from pengambilan_mk pmk
join mahasiswa m on m.id_mhs = pmk.id_mhs
join pengguna p on p.id_pengguna = m.id_pengguna
join semester s on s.id_semester = pmk.id_semester
where 
    p.id_perguruan_tinggi = 1 
	and	m.thn_angkatan_mhs < 2014
	and m.fd_id_reg_pd is not null 
	and s.thn_akademik_semester < 2014
	and pmk.nilai_huruf is not null
union all
/* Jumlah sudah link */
select count(*) as jumlah
from pengambilan_mk pmk
join mahasiswa m on m.id_mhs = pmk.id_mhs
join pengguna p on p.id_pengguna = m.id_pengguna
join semester s on s.id_semester = pmk.id_semester
where 
    p.id_perguruan_tinggi = 1
	and	m.thn_angkatan_mhs < 2014
	and m.fd_id_reg_pd is not null
	and s.thn_akademik_semester < 2014
	and pmk.nilai_huruf is not null
	and pmk.fd_id_ekuivalensi is not null
union all
/* Jumlah bakal update */
select count(*) as jumlah
from pengambilan_mk pmk
join mahasiswa m on m.id_mhs = pmk.id_mhs
join pengguna p on p.id_pengguna = m.id_pengguna
join semester s on s.id_semester = pmk.id_semester
where 
    p.id_perguruan_tinggi = 1
	and	m.thn_angkatan_mhs < 2014
	and m.fd_id_reg_pd is not null 
    and s.thn_akademik_semester < 2014
	and pmk.nilai_huruf is not null
	and pmk.fd_id_ekuivalensi is not null and pmk.updated_on > pmk.fd_sync_on
union all
/* Jumlah bakal insert */
select count(*) as jumlah
from pengambilan_mk pmk
join mahasiswa m on m.id_mhs = pmk.id_mhs
join pengguna p on p.id_pengguna = m.id_pengguna
join semester s on s.id_semester = pmk.id_semester
where 
    p.id_perguruan_tinggi = 1
	and	m.thn_angkatan_mhs < 2014
	and m.fd_id_reg_pd is not null 
    and s.thn_akademik_semester < 2014
	and pmk.nilai_huruf is not null
	and pmk.fd_id_ekuivalensi is null