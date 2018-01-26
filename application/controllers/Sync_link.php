<?php

/**
 * @property CI_Remotedb $rdb Remote DB Sistem Langitan
 * @property string $token Token webservice
 * @property string $npsn Kode Perguruan Tinggi
 * @property array $satuan_pendidikan Row: satuan_pendidikan
 */
class Sync_link extends MY_Controller
{
	function __construct()
	{
		parent::__construct();
		
		$this->check_credentials();

		// Inisialisasi Token dan Satuan Pendidikan
		$this->token = $this->session->userdata('token');
		$this->satuan_pendidikan = $this->session->userdata(FEEDER_SATUAN_PENDIDIKAN);
		
		// Inisialisasi URL Feeder
		$this->load->library('feeder', array('url' => $this->session->userdata('wsdl')));
		
		// Inisialisasi Library RemoteDB
		$this->load->library('remotedb', NULL, 'rdb');
		$this->rdb->set_url($this->session->userdata('langitan'));
	}
	
	function dosen()
	{		
		$jumlah = array();
		
		// Ambil jumlah dosen di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_DOSEN, null);
		$jumlah['feeder'] = $response['result'];
		
		// Ambil jumlah dosen di langitan
		$data_set = $this->rdb->QueryToArray(
			"SELECT count(*) as jumlah FROM pengguna p
			JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
			WHERE join_table = 2 AND pt.npsn = '{$this->satuan_pendidikan['npsn']}'
			UNION ALL
			SELECT count(*) as jumlah FROM pengguna p
			JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
			WHERE join_table = 2 AND pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND p.fd_id_sdm IS NOT NULL");
		$jumlah['langitan'] = $data_set[0]['JUMLAH'];
		$jumlah['linked'] = $data_set[1]['JUMLAH'];
		$jumlah['insert'] = $jumlah['feeder'] - $jumlah['linked'];
		
		$this->smarty->assign('jumlah', $jumlah);
		
		$this->smarty->assign('url_sync', site_url('sync_link/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync_link/'.$this->uri->segment(2).'.tpl');
	}
	
	function proses_dosen()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync_link/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_FEEDER;
		
		if ($mode == MODE_AMBIL_DATA_FEEDER)
		{
			$response = $this->feeder->GetCountRecordset($this->token, FEEDER_DOSEN);
			
			// simpan ke cache
			$this->session->set_userdata('jumlah_'.FEEDER_DOSEN, $response['result']);
			
			$result['message'] = 'Jumlah data Dosen di Feeder yg akan diproses : ' . $response['result'];
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// ambil dari cache
			$total_data = $this->session->userdata('jumlah_'.FEEDER_DOSEN);
			
			// Jika masih belum di proses semua
			if ($index_proses < $total_data)
			{
				// Ambil per row di feeder
				$response = $this->feeder->GetRecordset($this->token, FEEDER_DOSEN, null, '1', '1', $index_proses);
				$data = $response['result'][0];

				// Cek di langitan berdasarkan id_reg_pd
				$response = $this->rdb->QueryToArray("SELECT COUNT(*) AS jumlah FROM pengguna WHERE fd_id_sdm = '{$data['id_sdm']}'");
				
				// number
				$n = $index_proses + 1;
				
				// Jika ada
				if ($response[0]['JUMLAH'] > 0)
				{
					$result['message'] = "{$n}. {$data['nidn']} {$data['nm_sdm']} ==> Ada";
				}
				else
				{
					$result['message'] = "{$n}. {$data['nidn']} {$data['nm_sdm']} ==> Tidak ada";
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			else
			{
				$result['status'] = SYNC_STATUS_DONE;
				$result['message'] = 'Selesai';
			}
		}
		
		echo json_encode($result);
	}
	
	/**
	 * GET /sync_link/mahasiswa/
	 */
	function mahasiswa()
	{
		$jumlah = array();
		
		// Ambil jumlah mahasiswa di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_MAHASISWA, null);
		$jumlah['feeder'] = $response['result'];
		

		// Ambil jumlah mahasiswa di Sistem Langitan & yg sudah link
		$mhs_set = $this->rdb->QueryToArray(
			"SELECT count(*) as jumlah FROM mahasiswa m
			JOIN pengguna p ON p.id_pengguna = m.id_pengguna
			JOIN perguruan_tinggi pt on pt.id_perguruan_tinggi = p.id_perguruan_tinggi
			WHERE npsn = '{$this->satuan_pendidikan['npsn']}'
			UNION ALL
			SELECT count(*) as jumlah FROM mahasiswa m
			JOIN feeder_mahasiswa fm on fm.id_mhs = m.id_mhs
			JOIN pengguna p ON p.id_pengguna = m.id_pengguna
			JOIN perguruan_tinggi pt on pt.id_perguruan_tinggi = p.id_perguruan_tinggi
			WHERE npsn = '{$this->satuan_pendidikan['npsn']}'");
		$jumlah['langitan'] = $mhs_set[0]['JUMLAH'];
		$jumlah['linked'] = $mhs_set[1]['JUMLAH'];
		
		$this->smarty->assign('jumlah', $jumlah);
		
		$this->smarty->assign('url_sync', site_url('sync_link/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync_link/'.$this->uri->segment(2).'.tpl');
	}
	
	private function proses_mahasiswa()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync_link/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{
			$mahasiswa_set = $this->rdb->QueryToArray(
				"SELECT m.id_mhs, m.nim_mhs, fm.id_pd FROM mahasiswa m
				JOIN pengguna p ON p.id_pengguna = m.id_pengguna
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
				LEFT JOIN feeder_mahasiswa fm ON fm.id_mhs = m.id_mhs
				WHERE 
					pt.npsn = '{$this->satuan_pendidikan['npsn']}'
				ORDER BY 1 ASC");
				
			// simpan ke cache
			$this->session->set_userdata('mahasiswa_set', $mahasiswa_set);
			
			$result['message'] = 'Ambil data langitan selesai. Jumlah data: ' . count($mahasiswa_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil dari cache
			$mahasiswa_set = $this->session->userdata('mahasiswa_set');
			
			// Jika masih dalam rentang index, di proses
			if ($index_proses < count($mahasiswa_set))
			{
				// Ambil row mahasiswa_pt
				$response = $this->feeder->GetRecord($this->token, FEEDER_MAHASISWA, "id_pd = '{$mahasiswa_set[$index_proses]['ID_PD']}'");
				
				// Jika ada
				if (isset($response['result']['id_pd']))
				{
					// Jika sudah ada di Feeder_Mahasiswa -> Update id_pd2 (temp)
					if ($mahasiswa_set[$index_proses]['ID_PD'] != '')
					{
						// Update
						$updated = $this->rdb->Query(
							"UPDATE feeder_mahasiswa set id_pd2 = '{$response['result']['id_pd']}' where id_pd = '{$mahasiswa_set[$index_proses]['ID_PD']}'");
						
						$result["message"] = "Update {$mahasiswa_set[$index_proses]['NIM_MHS']} ==> {$response['result']['id_pd']} : " .
							($updated ? "Berhasil" : "Gagal");
					}
					else // Jika belum, insert
					{
						$inserted = $this->rdb->Query(
							"INSERT INTO feeder_mahasiswa (id_pd, id_mhs, last_sync, last_update) "
							. "VALUES ('{$response['result']['id_pd']}', {$mahasiswa_set[$index_proses]['ID_MHS']}, sysdate, sysdate)");
							
						$result["message"] = "Insert {$mahasiswa_set[$index_proses]['NIM_MHS']} ==> {$response['result']['id_pd']} : " .
							($inserted ? "Berhasil" : "Gagal");
					}
					
				}
				else
				{
					$result['message'] = "{$mahasiswa_set[$index_proses]['NIM_MHS']} tidak ada di Feeder";
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			else
			{
				$result['status'] = SYNC_STATUS_DONE;
				$result['message'] = 'Selesai';
			}
		}
		
		echo json_encode($result);
	}
	
	
	/**
	 * GET /sync_link/mahasiswa_pt/
	 */
	function mahasiswa_pt()
	{
		$jumlah = array();
		
		// Ambil jumlah mahasiswa di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_MAHASISWA_PT, null);
		$jumlah['feeder'] = $response['result'];

		// Ambil jumlah mahasiswa di Sistem Langitan & yg sudah link
		$mhs_set = $this->rdb->QueryToArray(
			"SELECT count(*) as jumlah FROM mahasiswa m
			JOIN pengguna p ON p.id_pengguna = m.id_pengguna
			JOIN perguruan_tinggi pt on pt.id_perguruan_tinggi = p.id_perguruan_tinggi
			WHERE npsn = '{$this->satuan_pendidikan['npsn']}'
			UNION ALL
			SELECT count(*) as jumlah FROM mahasiswa m
			JOIN feeder_mahasiswa_pt fm on fm.id_mhs = m.id_mhs
			JOIN pengguna p ON p.id_pengguna = m.id_pengguna
			JOIN perguruan_tinggi pt on pt.id_perguruan_tinggi = p.id_perguruan_tinggi
			WHERE npsn = '{$this->satuan_pendidikan['npsn']}'");
		$jumlah['langitan'] = $mhs_set[0]['JUMLAH'];
		$jumlah['linked'] = $mhs_set[1]['JUMLAH'];
		$this->smarty->assign('jumlah', $jumlah);
		
		$this->smarty->assign('url_sync', site_url('sync_link/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync_link/'.$this->uri->segment(2).'.tpl');
	}
	
	private function proses_mahasiswa_pt()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync_link/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{
			$mahasiswa_set = $this->rdb->QueryToArray(
				"SELECT m.id_mhs, m.nim_mhs, nm_pengguna FROM mahasiswa m
				JOIN pengguna p ON p.id_pengguna = m.id_pengguna
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = p.id_perguruan_tinggi
				WHERE 
					pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND 
					m.id_mhs NOT IN (SELECT id_mhs FROM feeder_mahasiswa_pt)
				ORDER BY 2 ASC");
				
			// simpan ke cache
			$this->session->set_userdata('mahasiswa_set', $mahasiswa_set);
			
			$result['message'] = 'Ambil data langitan selesai. Jumlah data: ' . count($mahasiswa_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil dari cache
			$mahasiswa_set = $this->session->userdata('mahasiswa_set');
			
			// Jika masih dalam rentang index, di proses
			if ($index_proses < count($mahasiswa_set))
			{
				// Ambil row mahasiswa_pt
				$response = $this->feeder->GetRecord($this->token, FEEDER_MAHASISWA_PT, "nipd = '{$mahasiswa_set[$index_proses]['NIM_MHS']}'");
				
				// Jika ada
				if (isset($response['result']['id_reg_pd']))
				{
					/*
					$inserted = $this->rdb->Query(
						"INSERT INTO feeder_mahasiswa_pt (id_reg_pd, id_mhs, last_sync) "
						. "VALUES ('{$response['result']['id_reg_pd']}', {$mahasiswa_set[$index_proses]['ID_MHS']}, sysdate)");
					
					$result['message'] = "Update {$mahasiswa_set[$index_proses]['NIM_MHS']} ==> {$response['result']['id_reg_pd']} : " . 
						($inserted ? 'Berhasil' : 'Gagal');
					*/
					
					$result['message'] = "{$mahasiswa_set[$index_proses]['NIM_MHS']} ada di Feeder";
				}
				else //jika tidak ada
				{
					$result['message'] = "{$mahasiswa_set[$index_proses]['NIM_MHS']} \"{$mahasiswa_set[$index_proses]['NM_PENGGUNA']}\" tidak ada di Feeder";
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			else
			{
				$result['status'] = SYNC_STATUS_DONE;
				$result['message'] = 'Selesai';
			}
		}
			
		echo json_encode($result);
	}
	
	
	
	/**
	 * GET /sync_link/program_studi/
	 */
	function program_studi()
	{	
		$jumlah = array(
			'feeder'	=> '-',
			'langitan'	=> '-',
			'linked'	=> '-'
		);
		
		// Jumlah di feeder
		$result = $this->feeder->GetCountRecordset($this->token, FEEDER_SMS, "p.id_sp = '{$this->satuan_pendidikan['id_sp']}'");
		$jumlah['feeder'] = $result['result'];
		
		// Jumlah di langitan
		$result = $this->rdb->QueryToArray(
			"SELECT count(*) as jumlah FROM program_studi ps
			JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
			JOIN perguruan_tinggi pt on pt.id_perguruan_tinggi = f.id_perguruan_tinggi
			WHERE npsn = '{$this->satuan_pendidikan['npsn']}'");
		$jumlah['langitan'] = $result[0]['JUMLAH'];
		
		// Jumlah yg sudah link (punya ID_SMS)
		$result = $this->rdb->QueryToArray(
			"SELECT count(*) as jumlah FROM program_studi ps
			JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
			JOIN perguruan_tinggi pt on pt.id_perguruan_tinggi = f.id_perguruan_tinggi
			WHERE npsn = '{$this->satuan_pendidikan['npsn']}' AND fd_id_sms IS NOT NULL");
		$jumlah['linked'] = $result[0]['JUMLAH'];
		
		$this->smarty->assign('jumlah', $jumlah);
		
		$this->smarty->assign('url_sync', site_url('sync_link/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync_link/program_studi.tpl');
	}
	
	private function proses_program_studi()
	{
		$result = array(
			'status'	=> '',
			'time'		=> '',
			'message'	=> '',
			'nextUrl'	=> '',
			'params'	=> ''
		);
		
		$format_time = "%d/%m/%Y %H:%M:%S";
		
		// Ambil program studi PT Feeder
		$response = $this->feeder->GetRecordset($this->token, FEEDER_SMS, "id_sp = '{$this->satuan_pendidikan['id_sp']}'");
		$sms_set = $response['result'];
		
		// Ambil prodi langitan yg belum link
		$program_studi_set = $this->rdb->QueryToArray(
			"SELECT id_program_studi, kode_program_studi FROM program_studi ps
			JOIN fakultas f on f.id_fakultas = ps.id_fakultas
			JOIN perguruan_tinggi pt on pt.id_perguruan_tinggi = f.id_perguruan_tinggi
			WHERE pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND ps.id_program_studi NOT IN (SELECT id_program_studi FROM feeder_sms)");
		
		$jumlah_sync = 0;
		
		// Pastikan ada data
		if (count($sms_set) > 0 && count($program_studi_set) > 0)
		{
			foreach ($sms_set as $sms)
			{
				foreach ($program_studi_set as $prodi)
				{
					if (trim($sms['kode_prodi']) == ($prodi['KODE_PROGRAM_STUDI']))
					{
						$sql = "INSERT INTO feeder_sms (id_sms, id_program_studi) VALUES ('{$sms['id_sms']}','{$prodi['ID_PROGRAM_STUDI']}')";
						$this->rdb->Query($sql);
						$jumlah_sync++;
						break;
					}
				}
			}
			
			$result['message'] = 'Berhasil melakukan link ' . $jumlah_sync . ' program studi';
		}
		else
		{
			$result['message'] = 'Tidak ada data yang di link';
		}
		
		$result['status'] = 'done';
		$result['time'] = strftime($format_time);
		
		echo json_encode($result);
	}
	
	
	/**
	 * GET /sync_link/kuliah_mahasiswa/
	 */
	function kuliah_mahasiswa()
	{
		$jumlah = array();
		
		// Ambil jumlah di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_KULIAH_MAHASISWA, null);
		$jumlah['feeder'] = $response['result'];
		
		// Ambil jumlah mahasiswa di Sistem Langitan & yg sudah link
		$sql_kuliah_mahasiswa = file_get_contents(APPPATH.'models/sql_link/kuliah_mahasiswa.sql');
		$kuliah_mahasiswa_set = $this->rdb->QueryToArray($sql_kuliah_mahasiswa);
		
		//$jumlah['langitan'] = $kuliah_mahasiswa_set[0]['JUMLAH'];
		//$jumlah['linked'] = $kuliah_mahasiswa_set[1]['JUMLAH'];
		
		$jumlah['langitan'] = '0';
		$jumlah['linked'] = '0';
		
		$this->smarty->assign('jumlah', $jumlah);
		
		$this->smarty->assign('url_sync', site_url('sync_link/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync_link/'.$this->uri->segment(2).'.tpl');
	}
	
	private function proses_kuliah_mahasiswa()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync_link/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_FEEDER;
		
		
	}
	
	private function proses_lulus()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync_link/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_FEEDER;
		
		if ($mode == MODE_AMBIL_DATA_FEEDER)
		{
			$response = $this->feeder->GetCountRecordset($this->token, FEEDER_MAHASISWA_PT, "ket_keluar = 'Lulus'");
			
			// simpan ke cache
			$this->session->set_userdata('jumlah_'.FEEDER_MAHASISWA_PT, $response['result']);
			
			$result['message'] = 'Ambil data feeder selesai. Jumlah data yg akan diproses : ' . $response['result'];
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// ambil dari cache
			$total_data = $this->session->userdata('jumlah_'.FEEDER_MAHASISWA_PT);
			
			if ($index_proses < $total_data)
			{
				// Ambil per row di feeder
				$response = $this->feeder->GetRecordset($this->token, FEEDER_MAHASISWA_PT, "ket_keluar = 'Lulus'", '1', '1', $index_proses);
				$mahasiswa_pt = $response['result'][0];
				
				// Ambil data langitan by id_reg_pd
				$response = $this->rdb->QueryToArray(
					"SELECT fm.id_mhs, ps.id_jenjang FROM feeder_mahasiswa_pt fm
					JOIN mahasiswa m ON m.id_mhs = fm.id_mhs
					JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
					WHERE fm.id_reg_pd = '{$mahasiswa_pt['id_reg_pd']}'");
				$id_mhs		= $response[0]['ID_MHS'];
				$id_jenjang	= $response[0]['ID_JENJANG'];
				
				if ($id_mhs)
				{
					// Konversi tahun keluar ke ID_SEMESTER
					// 2014 >> 197, 2015 >> 209
					$tahun_keluar = substr($mahasiswa_pt['tgl_keluar'], 0, 4);
					if ($tahun_keluar == '2014') $id_semester = 197;
					else if ($tahun_keluar == '2015') $id_semester = 209;
					
					// Update Mahasiswa
					$updated_1 = $this->rdb->Query("UPDATE mahasiswa SET status_akademik_mhs = 4 WHERE id_mhs = {$id_mhs}");
					
					// ambil row admisi lulus
					$admisi = $this->rdb->QueryToArray("SELECT COUNT(*) as jumlah FROM admisi WHERE id_mhs = {$id_mhs} AND status_akd_mhs = 4");
					
					// Jika belum punya row admisi
					if ($admisi[0]['JUMLAH'] == 0)
					{
						// Insert Admisi Lulus (id status akd = 4)
						$inserted_1 = $this->rdb->Query(
							"INSERT INTO admisi (
								id_mhs, id_semester, status_akd_mhs, status_apv, 
								tgl_usulan, tgl_apv, 
								no_ijasah,
								keterangan, id_pengguna)
							VALUES (
								{$id_mhs}, {$id_semester}, 4, 1,
								to_date('{$mahasiswa_pt['tgl_keluar']}', 'YYYY-MM-DD'), to_date('{$mahasiswa_pt['tgl_keluar']}', 'YYYY-MM-DD'),
								'{$mahasiswa_pt['no_seri_ijazah']}',
								'Lulusan UMAHA Tahun {$tahun_keluar}', 60)");
					}
					else
					{
						$inserted_1 = TRUE;
					}

					// Konversi ID Periode wisuda
					if ($tahun_keluar == '2014' && $id_jenjang == '1') { $id_periode_wisuda = 67; }
					if ($tahun_keluar == '2014' && $id_jenjang == '5') { $id_periode_wisuda = 68; }
					if ($tahun_keluar == '2015' && $id_jenjang == '1') { $id_periode_wisuda = 65; }
					if ($tahun_keluar == '2015' && $id_jenjang == '5') { $id_periode_wisuda = 69; }
					
					// Cleansing petik 
					$mahasiswa_pt['judul_skripsi'] = str_replace("'", "''", $mahasiswa_pt['judul_skripsi']);
					// Format Tgl SK Yudisium
					if ($mahasiswa_pt['tgl_sk_yudisium'])
						$mahasiswa_pt['tgl_sk_yudisium'] = "to_date('{$mahasiswa_pt['tgl_sk_yudisium']}','YYYY-MM-DD')";
					else
						$mahasiswa_pt['tgl_sk_yudisium'] = 'NULL';
							
					// ambil row pengajuan wisuda
					$pengajuan_wisuda = $this->rdb->QueryToArray("SELECT count(*) as jumlah FROM pengajuan_wisuda WHERE id_mhs = {$id_mhs}");
					
					// Jika belum ada row pengajuan wisuda
					if ($pengajuan_wisuda[0]['JUMLAH'] == 0)
					{
						// Insert Pengajuan Wisuda
						$sql_insert_2 = "INSERT INTO pengajuan_wisuda (
								id_mhs, judul_ta, yudisium, id_periode_wisuda,
								no_ijasah, sk_yudisium, tgl_sk_yudisium, ipk)
							VALUES (
								{$id_mhs}, '{$mahasiswa_pt['judul_skripsi']}', 2, {$id_periode_wisuda},
								'{$mahasiswa_pt['no_seri_ijazah']}', '{$mahasiswa_pt['sk_yudisium']}', {$mahasiswa_pt['tgl_sk_yudisium']}, '{$mahasiswa_pt['ipk']}')";
						$inserted_2 = $this->rdb->Query($sql_insert_2);
					}
					else
					{
						$inserted_2 = TRUE;
					}
					
					$result['message'] = "Proses {$mahasiswa_pt['nipd']}: ".
						"Update Mahasiswa = " . ($updated_1 ? 'Berhasil. ' : 'GAGAL. ') .
						"Insert Admisi = " . ($inserted_1 ? 'Berhasil. ' : 'GAGAL. ') .
						"Insert Wisuda = " . ($inserted_2 ? 'Berhasil. ' : "GAGAL. {$sql_insert_2}");
				}
				else
				{
					$result['message'] = 'Gagal baca id_mhs';
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			else
			{
				$result['status'] = SYNC_STATUS_DONE;
				$result['message'] = 'Selesai';
			}
		}
		
		echo json_encode($result);
	}
	
	private function proses_link_mahasiswa_pt2()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync_link/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_FEEDER;
		
		if ($mode == MODE_AMBIL_DATA_FEEDER)
		{
			$response = $this->feeder->GetCountRecordset($this->token, FEEDER_MAHASISWA_PT);
			
			// simpan ke cache
			$this->session->set_userdata('jumlah_'.FEEDER_MAHASISWA_PT, $response['result']);
			
			$result['message'] = 'Ambil data feeder selesai. Jumlah data yg akan diproses : ' . $response['result'];
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// ambil dari cache
			$total_data = $this->session->userdata('jumlah_'.FEEDER_MAHASISWA_PT);
			
			if ($index_proses < $total_data)
			{
				// Ambil per row di feeder
				$response = $this->feeder->GetRecordset($this->token, FEEDER_MAHASISWA_PT, null, '1', '1', $index_proses);
				$mahasiswa_pt = $response['result'][0];

				// Cek di langitan berdasarkan id_reg_pd
				$response = $this->rdb->QueryToArray(
					"SELECT COUNT(*) AS jumlah FROM feeder_mahasiswa_pt WHERE id_reg_pd = '{$mahasiswa_pt['id_reg_pd']}'");
				
				// Jika ada
				if ($response[0]['JUMLAH'] > 0)
				{
					$result['message'] = "{$mahasiswa_pt['nipd']} ==> Ada";
				}
				else
				{
					$result['message'] = "{$mahasiswa_pt['nipd']} ==> Tidak ada";
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			else
			{
				$result['status'] = SYNC_STATUS_DONE;
				$result['message'] = 'Selesai';
			}
		}
		
		echo json_encode($result);
	}
	
	
	function start($mode)
	{
		if ($mode == 'dosen')
		{
			$this->smarty->assign('jenis_sinkronisasi', 'Import Dosen');
			$this->smarty->assign('url', site_url('sync_link/proses/'.$mode));
		}
		
		if ($mode == 'mahasiswa')
		{
			$this->smarty->assign('jenis_sinkronisasi', 'Import Mahasiswa');
			$this->smarty->assign('url', site_url('sync_link/proses/'.$mode));
		}
		
		if ($mode == 'mahasiswa_pt')
		{
			$this->smarty->assign('jenis_sinkronisasi', 'Import Mahasiswa PT');
			$this->smarty->assign('url', site_url('sync_link/proses/'.$mode));
		}
		
		if ($mode == 'kuliah_mahasiswa')
		{
			$this->smarty->assign('jenis_sinkronisasi', 'Import Kuliah Mahasiswa');
			$this->smarty->assign('url', site_url('sync_link/proses/'.$mode));
		}
		
		if ($mode == 'program_studi')
		{
			$this->smarty->assign('jenis_sinkronisasi', 'Import Program Studi');
			$this->smarty->assign('url', site_url('sync_link/proses/'.$mode));
		}
		
		// Internal UMAHA
		if ($mode == 'lulus')
		{
			$this->smarty->assign('jenis_sinkronisasi', 'Update lulusan ke Sistem Langitan');
			$this->smarty->assign('url', site_url('sync_link/proses/'.$mode));
		}
		
		// Internal UMAHA
		if ($mode == 'mahasiswa_pt2')
		{
			$this->smarty->assign('jenis_sinkronisasi', 'Cek Mahasiswa PT belum ada di Sistem Langitan');
			$this->smarty->assign('url', site_url('sync_link/proses/'.$mode));
		}
		
		$this->smarty->display('sync/start.tpl');
	}
	
	function proses($mode)
	{
		// harus request POST 
		if ($_SERVER['REQUEST_METHOD'] != 'POST') { return; }
		
		if ($mode == 'dosen')
		{
			$this->proses_dosen();
		}
		
		else if ($mode == 'mahasiswa')
		{
			$this->proses_mahasiswa();
		}
		
		else if ($mode == 'mahasiswa_pt')
		{
			$this->proses_mahasiswa_pt();
		}
		
		else if ($mode == 'program_studi')
		{
			$this->proses_program_studi();
		}
		
		else if ($mode == 'kuliah_mahasiswa')
		{
			$this->proses_kuliah_mahasiswa();
		}
		
		else if ($mode == 'mahasiswa_pt2')
		{
			$this->proses_mahasiswa_pt2();
		}
		
		else if ($mode == 'lulus')
		{
			$this->proses_lulus();
		}
		
		else 
		{
			echo json_encode(array('status' => 'done', 'message' => 'Not Implemented()'));
		}
	}
}
