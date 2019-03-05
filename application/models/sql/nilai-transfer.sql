/**
 * Author:  Fathoni <m.fathoni@mail.com>
 * Created: Apr 29, 2018
 */

/* jumlah semua */
select count(*) as jumlah from pengambilan_mk pmk
join mahasiswa m on m.id_mhs = pmk.id_mhs
join pengguna p on p.id_pengguna = m.id_pengguna
join perguruan_tinggi pt on pt.id_perguruan_tinggi = p.id_perguruan_tinggi
where pt.npsn = '@npsn' and m.fd_id_reg_pd is not null and pmk.status_transfer = 1

union all

/* jumlah link */
select count(*) from pengambilan_mk pmk
join mahasiswa m on m.id_mhs = pmk.id_mhs
join pengguna p on p.id_pengguna = m.id_pengguna
join perguruan_tinggi pt on pt.id_perguruan_tinggi = p.id_perguruan_tinggi
where pt.npsn = '@npsn' and m.fd_id_reg_pd is not null and pmk.status_transfer = 1 and pmk.fd_id_ekuivalensi is not null

union all

/* jumlah update */
select count(*) from pengambilan_mk pmk
join mahasiswa m on m.id_mhs = pmk.id_mhs
join pengguna p on p.id_pengguna = m.id_pengguna
join perguruan_tinggi pt on pt.id_perguruan_tinggi = p.id_perguruan_tinggi
where pt.npsn = '@npsn' and m.fd_id_reg_pd is not null and pmk.status_transfer = 1 and pmk.fd_id_ekuivalensi is not null and
    pmk.updated_on > pmk.fd_sync_on
    
union all

/* jumlah bakal insert */
select count(*) from pengambilan_mk pmk
join mahasiswa m on m.id_mhs = pmk.id_mhs
join pengguna p on p.id_pengguna = m.id_pengguna
join perguruan_tinggi pt on pt.id_perguruan_tinggi = p.id_perguruan_tinggi
where pt.npsn = '@npsn' and m.fd_id_reg_pd is not null and pmk.status_transfer = 1 and pmk.fd_id_ekuivalensi is null