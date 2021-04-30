/**
 * Author:  Fathoni <m.fathoni@mail.com>
 * Created: Mar 4, 2019
 */

select
    pmk.fd_id_ekuivalensi as id_ekuivalensi,
    m.nim_mhs||' '||p.nm_pengguna as mhs,
    pmk.id_pengambilan_mk_konversi,
    m.fd_id_reg_pd as id_reg_pd,
    mk.fd_id_mk as id_mk,
    pmk.kode_mk_asal,
    pmk.nama_mk_asal as nm_mk_asal,
    pmk.sks_asal,
    pmk.sks_diakui,
    pmk.nilai_huruf_asal,
    pmk.nilai_huruf_diakui,
    nvl(sn.nilai_standar_nilai, 0) as nilai_angka_diakui,
    pt_asal.fd_id_sp as id_sp
from pengambilan_mk_konversi pmk
join mahasiswa m on m.id_mhs = pmk.id_mhs
join pengguna p on p.id_pengguna = m.id_pengguna
join perguruan_tinggi pt on pt.id_perguruan_tinggi = p.id_perguruan_tinggi
join mata_kuliah mk on mk.id_mata_kuliah = pmk.id_mata_kuliah
left join standar_nilai sn on sn.nm_standar_nilai = pmk.nilai_huruf_diakui
join admisi a on a.id_mhs = m.id_mhs and a.id_jalur is not null
join perguruan_tinggi pt_asal on pt_asal.npsn = a.kode_pt_asal
where
    pt.npsn = '@npsn'
    and m.fd_id_reg_pd is not null
    and pmk.fd_id_ekuivalensi is not null and pmk.updated_on > pmk.fd_sync_on