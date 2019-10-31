<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property CI_Remotedb $rdb Remote DB Sistem Langitan
 * @property string $token Token webservice
 * @property string $npsn Kode Perguruan Tinggi
 * @property array $satuan_pendidikan Row: satuan_pendidikan
 * @property Langitan_model $langitan_model
 * @property PerguruanTinggi_model $pt
 */
class Sync extends MY_Controller 
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
		
		// Inisialisasi Langitan_model
		$this->load->model('langitan_model');
		
		// Inisialisasi Perguruan Tinggi
		$this->pt = $this->session->userdata('pt');
	}
	
	/**
	 * GET /sync/mahasiswa
	 */
	function mahasiswa()
	{
		$jumlah = array();
		
		// Ambil jumlah mahasiswa di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_MAHASISWA, null);
		$jumlah['feeder'] = $response['result'];

		// Ambil data mahasiswa
		$sql_mahasiswa_raw = file_get_contents(APPPATH.'models/sql/mahasiswa.sql');
		$sql_mahasiswa = strtr($sql_mahasiswa_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$mhs_set = $this->rdb->QueryToArray($sql_mahasiswa);
		
		$jumlah['langitan'] = $mhs_set[0]['JUMLAH'];
		$jumlah['linked'] = $mhs_set[1]['JUMLAH'];
		$jumlah['update'] = $mhs_set[2]['JUMLAH'];
		$jumlah['insert'] = $mhs_set[3]['JUMLAH'];
		
		$this->smarty->assign('jumlah', $jumlah);
		
		// Ambil program studi
		$sql_program_studi_raw = file_get_contents(APPPATH.'models/sql/program-studi.sql');
		$sql_program_studi = strtr($sql_program_studi_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$program_studi_set = $this->rdb->QueryToArray($sql_program_studi);
		$this->smarty->assign('program_studi_set', $program_studi_set);
		
		// Ambil Semua angkatan yg ada :
		$sql_angkatan_mhs_raw = file_get_contents(APPPATH.'models/sql/mahasiswa-angkatan.sql');
		$sql_angkatan_mhs = strtr($sql_angkatan_mhs_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$angkatan_set = $this->rdb->QueryToArray($sql_angkatan_mhs);
		$this->smarty->assign('angkatan_set', $angkatan_set);
		
		$this->smarty->assign('url_sync', site_url('sync/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync/'.$this->uri->segment(2).'.tpl');
	}
	
	/**
	 * Ajax-GET /sync/mahasiswa_data/
	 * @param string $kode_prodi Kode program studi versi Feeder
	 * @param int $angkatan Tahun angkatan mahasiswa
	 */
	function mahasiswa_data($kode_prodi, $angkatan)
	{
		// Khusus UMAHA menggunakan filter nim agar match
		if ($this->satuan_pendidikan['npsn'] == '071086')
		{
			// Ambil informasi format NIM 
			$format_set = $this->rdb->QueryToArray(
				"SELECT nm_program_studi, coalesce(f.format_nim_fakultas, format_nim_pt) as format_nim, f.kode_nim_fakultas, ps.kode_nim_prodi
				FROM perguruan_tinggi pt 
				JOIN fakultas f ON f.id_perguruan_tinggi = pt.id_perguruan_tinggi
				JOIN program_studi ps ON ps.id_fakultas = f.id_fakultas
				WHERE pt.npsn = '{$this->satuan_pendidikan['npsn']}' and ps.kode_program_studi = '{$kode_prodi}'");

			$format = $format_set[0];

			$format_nim = str_replace('[F]', $format['KODE_NIM_FAKULTAS'], $format['FORMAT_NIM']);
			$format_nim = str_replace('[PS]', $format['KODE_NIM_PRODI'], $format_nim);
			$format_nim = str_replace('[A]', substr($angkatan, -2), $format_nim);
			$format_nim = str_replace('[Seri]', '___', $format_nim);

			// Jika FIKES 2014 kebawah, pakai format lama
			if ($kode_prodi == '13453' && $angkatan <= 2014)
			{
				$format_nim = substr($angkatan, -2) . '___';
			}
			
			// Ambil jumlah mahasiswa di feeder
			$response = $this->feeder->GetCountRecordset($this->token, FEEDER_MAHASISWA_PT, "p.nipd like '{$format_nim}'");
			$jumlah['feeder'] = $response['result'];
		}
		else
		{
			// Ambil jumlah mahasiswa di feeder
			$response = $this->feeder->GetCountRecordset($this->token, FEEDER_MAHASISWA_PT, "kode_prodi like '{$kode_prodi}%' and mulai_smt like '{$angkatan}%'");
			$jumlah['feeder'] = $response['result'];
		}
		
		// SQL jumlah mahasiswa di Sistem Langitan & yg sudah link
		$sql_mahasiswa_data_raw = file_get_contents(APPPATH.'models/sql/mahasiswa-data.sql');
		$sql_mahasiswa_data = strtr($sql_mahasiswa_data_raw, array(
			'@npsn' => $this->satuan_pendidikan['npsn'],
			'@kode_prodi' => $kode_prodi,
			'@angkatan' => $angkatan
		));

		// Ambil jumlah mahasiswa di Sistem Langitan & yg sudah link
		$mhs_set = $this->rdb->QueryToArray($sql_mahasiswa_data);
		
		$jumlah['langitan'] = $mhs_set[0]['JUMLAH'];
		$jumlah['linked'] = $mhs_set[1]['JUMLAH'];
		$jumlah['update'] = $mhs_set[2]['JUMLAH'];
		$jumlah['insert'] = $mhs_set[3]['JUMLAH'];
		
		echo json_encode($jumlah);
	}
	
	private function proses_mahasiswa()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		// -----------------------------------
		// Ambil data untuk Insert
		// -----------------------------------
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{
			// Filter Prodi & Angkatan
			$kode_prodi = $this->input->post('kode_prodi');
			$angkatan	= $this->input->post('angkatan');
			
			// Mendapatkan id_sms
			$response = $this->feeder->GetRecord($this->token, FEEDER_SMS, "id_sp = '{$this->satuan_pendidikan['id_sp']}' AND trim(kode_prodi) = '{$kode_prodi}'");
			$sms = $response['result'];
			
			// Ambil mahasiswa yg akan insert
			$sql_mahasiswa_insert = file_get_contents(APPPATH.'models/sql/mahasiswa-insert.sql');
			$sql_mahasiswa_insert = strtr($sql_mahasiswa_insert, array(
				'@id_sp'		=> $this->satuan_pendidikan['id_sp'],
				'@npsn'			=> $this->satuan_pendidikan['npsn'],
				'@kode_prodi'	=> $kode_prodi,
				'@angkatan'		=> $angkatan
			));
			
			$mahasiswa_set = $this->rdb->QueryToArray($sql_mahasiswa_insert);
			
			// Ambil mahasiswa_pt yg akan insert
			$sql_mahasiswa_pt_insert = file_get_contents(APPPATH.'models/sql/mahasiswa-pt-insert.sql');
			$sql_mahasiswa_pt_insert = strtr($sql_mahasiswa_pt_insert, array(
				'@id_sms' => $sms['id_sms'],
				'@id_sp' => $this->satuan_pendidikan['id_sp'],
				'@npsn' => $this->satuan_pendidikan['npsn'],
				'@kode_prodi' => $kode_prodi,
				'@angkatan' => $angkatan
			));
			
			$mahasiswa_pt_set = $this->rdb->QueryToArray($sql_mahasiswa_pt_insert);
					
			// simpan ke cache
			$this->session->set_userdata('mahasiswa_insert_set', $mahasiswa_set);
			$this->session->set_userdata('mahasiswa_pt_insert_set', $mahasiswa_pt_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Entri. Jumlah data: ' . count($mahasiswa_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_2;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Update
		// -----------------------------------
		else if ($mode == MODE_AMBIL_DATA_LANGITAN_2)
		{
			// Filter Prodi & Angkatan
			$kode_prodi = $this->input->post('kode_prodi');
			$angkatan	= $this->input->post('angkatan');
			
			// Mendapatkan id_sms
			$response = $this->feeder->GetRecord($this->token, FEEDER_SMS, "id_sp = '{$this->satuan_pendidikan['id_sp']}' AND trim(kode_prodi) = '{$kode_prodi}'");
			$sms = $response['result'];
			
			// Ambil mahasiswa yg akan UPDATE
			$sql_mahasiswa_update = file_get_contents(APPPATH.'models/sql/mahasiswa-update.sql');
			$sql_mahasiswa_update = strtr($sql_mahasiswa_update, array(
				'@id_sp'		=> $this->satuan_pendidikan['id_sp'],
				'@npsn'			=> $this->satuan_pendidikan['npsn'],
				'@kode_prodi'	=> $kode_prodi,
				'@angkatan'		=> $angkatan
			));
			
			$mahasiswa_set = $this->rdb->QueryToArray($sql_mahasiswa_update);
			
			// Ambil mahasiswa_pt yg akan di update
			$sql_mahasiswa_pt_update = file_get_contents(APPPATH.'models/sql/mahasiswa-pt-update.sql');
			$sql_mahasiswa_pt_update = strtr($sql_mahasiswa_pt_update, array(
				'@id_sms'	=> $sms['id_sms'],
				'@id_sp'	=> $this->satuan_pendidikan['id_sp'],
				'@npsn'		=> $this->satuan_pendidikan['npsn'],
				'@kode_prodi'	=> $kode_prodi,
				'@angkatan'		=> $angkatan
			));
			
			$mahasiswa_pt_set = $this->rdb->QueryToArray($sql_mahasiswa_pt_update);
					
			// simpan ke cache
			$this->session->set_userdata('mahasiswa_update_set', $mahasiswa_set);
			$this->session->set_userdata('mahasiswa_pt_update_set', $mahasiswa_pt_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Update. Jumlah data: ' . count($mahasiswa_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		// ----------------------------------------------
		// Proses Sinkronisasi dari data yg sudah diambil
		// ----------------------------------------------
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil dari cache
			$mahasiswa_insert_set = $this->session->userdata('mahasiswa_insert_set');
			$mahasiswa_pt_insert_set = $this->session->userdata('mahasiswa_pt_insert_set');
			$jumlah_insert = count($mahasiswa_insert_set);
			
			// Ambil dari cache
			$mahasiswa_update_set = $this->session->userdata('mahasiswa_update_set');
			$mahasiswa_pt_update_set = $this->session->userdata('mahasiswa_pt_update_set');
			$jumlah_update = count($mahasiswa_update_set);
			
			// Waktu Sinkronisasi
			$time_sync = date('Y-m-d H:i:s');
			
			// --------------------------------
			// Proses Insert
			// --------------------------------
			if ($index_proses < $jumlah_insert)
			{
				// Proses dalam bentuk key lowercase
				$mahasiswa_insert = array_change_key_case($mahasiswa_insert_set[$index_proses], CASE_LOWER);
				$mahasiswa_pt_insert = array_change_key_case($mahasiswa_pt_insert_set[$index_proses], CASE_LOWER);
				
				// Simpan id_mhs untuk update data di langitan
				$id_mhs			= $mahasiswa_insert['id_mhs'];
				$id_pengguna	= $mahasiswa_insert['id_pengguna'];
				
				// Hilangkan id_mhs
				unset($mahasiswa_insert['id_mhs']);
				unset($mahasiswa_insert['id_pengguna']);
				unset($mahasiswa_pt_insert['id_mhs']);
				
				// Cleansing data
				if ($mahasiswa_insert['jk'] == '*') unset($mahasiswa_insert['jk']);
				if ($mahasiswa_insert['kode_pos'] == '') unset($mahasiswa_insert['kode_pos']);
				if ( ! filter_var($mahasiswa_insert['email'], FILTER_VALIDATE_EMAIL)) unset($mahasiswa_insert['email']);
				if ($mahasiswa_insert['rt'] == '') unset($mahasiswa_insert['rt']);
				if ($mahasiswa_insert['rw'] == '') unset($mahasiswa_insert['rw']);
				if ($mahasiswa_insert['id_jns_tinggal'] == '') unset($mahasiswa_insert['id_jns_tinggal']);
				if ($mahasiswa_insert['id_alat_transport'] == '') unset($mahasiswa_insert['id_alat_transport']);
				if ($mahasiswa_insert['tgl_lahir_ayah'] == '') unset($mahasiswa_insert['tgl_lahir_ayah']);
				if ($mahasiswa_insert['id_jenjang_pendidikan_ayah'] == '') unset($mahasiswa_insert['id_jenjang_pendidikan_ayah']);
				if ($mahasiswa_insert['id_pekerjaan_ayah'] == '') unset($mahasiswa_insert['id_pekerjaan_ayah']);
				if ($mahasiswa_insert['id_penghasilan_ayah'] == '') unset($mahasiswa_insert['id_penghasilan_ayah']);
				if ($mahasiswa_insert['tgl_lahir_ibu'] == '') unset($mahasiswa_insert['tgl_lahir_ibu']);
				if ($mahasiswa_insert['id_jenjang_pendidikan_ibu'] == '') unset($mahasiswa_insert['id_jenjang_pendidikan_ibu']);
				if ($mahasiswa_insert['id_pekerjaan_ibu'] == '') unset($mahasiswa_insert['id_pekerjaan_ibu']);
				if ($mahasiswa_insert['id_penghasilan_ibu'] == '') unset($mahasiswa_insert['id_penghasilan_ibu']);
				if ($mahasiswa_insert['tgl_lahir_wali'] == '') unset($mahasiswa_insert['tgl_lahir_wali']);
				if ($mahasiswa_insert['id_jenjang_pendidikan_wali'] == '') unset($mahasiswa_insert['id_jenjang_pendidikan_wali']);
				if ($mahasiswa_insert['id_pekerjaan_wali'] == '') unset($mahasiswa_insert['id_pekerjaan_wali']);
				if ($mahasiswa_insert['id_penghasilan_wali'] == '') unset($mahasiswa_insert['id_penghasilan_wali']);
				
				
				// ---------------------------------------------------------------------------------------------
				// Khusus UMAHA : Angkatan dibawah 2014 di geser ke 2014 Ganjil sebagai mhs transfer, default SKS = 1
				// ---------------------------------------------------------------------------------------------
				if ($this->satuan_pendidikan['npsn'] == '071086' && (int)$mahasiswa_pt_insert['mulai_smt'] < 20141)
				{
					$mahasiswa_pt_insert['mulai_smt'] = '20141';			// 2014/2015 Ganjil
					$mahasiswa_pt_insert['tgl_masuk_sp'] = '2014-10-17';	// Tanggal SK Penggabungan Institusi
					$mahasiswa_pt_insert['id_jns_daftar'] = '2';			// Transfer
					$mahasiswa_pt_insert['sks_diakui'] = '1';				// SKS Diakui min 1 utk bisa di sinkronisasi
					
					/**
					 * 1	TEKNIK INFORMATIKA	a9a267c7-63ff-4cbd-bcf6-b7f957fe1d80
					 * 1	TEKNIK INDUSTRI	a9fec275-28ab-4895-894a-b545b8f7193a
					 * 1	TEKNIK MESIN	944fc32c-18f3-47f9-bc19-6e711480c858
					 * 5	TEKNIK KOMPUTER	613ec101-53d0-405c-b6e1-c4125f042f52
					 * 1	MANAJEMEN	c9e0c8b8-91c0-427a-bace-7215337e6515
					 * 1	AKUNTANSI	f0def671-d948-4d68-ab3e-a7f936f7a3b4
					 * 5	AKUNTANSI	25b084b0-b377-4369-8a3b-c196a86246bb
					 * 1	ILMU HUKUM	96f4e4f6-adba-41b6-8d92-92369808ae44
					 * 5	ANALIS KESEHATAN	f9351bde-0387-4f6e-99c8-f38ec28543e4
					 */
					
					// Asal PT & Prodi
					if ($mahasiswa_pt_insert['id_sms'] == 'a9a267c7-63ff-4cbd-bcf6-b7f957fe1d80')
					{
						$mahasiswa_pt_insert['nm_pt_asal'] = 'Sekolah Tinggi Teknik YPM Sepanjang';
						$mahasiswa_pt_insert['nm_prodi_asal'] = 'Teknik Informatika';
					}
					else if ($mahasiswa_pt_insert['id_sms'] == 'a9fec275-28ab-4895-894a-b545b8f7193a')
					{
						$mahasiswa_pt_insert['nm_pt_asal'] = 'Sekolah Tinggi Teknik YPM Sepanjang';
						$mahasiswa_pt_insert['nm_prodi_asal'] = 'Teknik Industri';
					}
					else if ($mahasiswa_pt_insert['id_sms'] == '944fc32c-18f3-47f9-bc19-6e711480c858')
					{
						$mahasiswa_pt_insert['nm_pt_asal'] = 'Sekolah Tinggi Teknik YPM Sepanjang';
						$mahasiswa_pt_insert['nm_prodi_asal'] = 'Teknik Mesin';
					}
					else if ($mahasiswa_pt_insert['id_sms'] == '613ec101-53d0-405c-b6e1-c4125f042f52')
					{
						$mahasiswa_pt_insert['nm_pt_asal'] = 'Sekolah Tinggi Teknik YPM Sepanjang';
						$mahasiswa_pt_insert['nm_prodi_asal'] = 'Teknik Komputer';
					}
					else if ($mahasiswa_pt_insert['id_sms'] == 'c9e0c8b8-91c0-427a-bace-7215337e6515')
					{
						$mahasiswa_pt_insert['nm_pt_asal'] = 'Sekolah Tinggi Ilmu Ekonomi YPM Sepanjang';
						$mahasiswa_pt_insert['nm_prodi_asal'] = 'Manajemen';
					}
					else if ($mahasiswa_pt_insert['id_sms'] == 'f0def671-d948-4d68-ab3e-a7f936f7a3b4')
					{
						$mahasiswa_pt_insert['nm_pt_asal'] = 'Sekolah Tinggi Ilmu Ekonomi YPM Sepanjang';
						$mahasiswa_pt_insert['nm_prodi_asal'] = 'Akuntansi';
					}
					else if ($mahasiswa_pt_insert['id_sms'] == '25b084b0-b377-4369-8a3b-c196a86246bb')
					{
						$mahasiswa_pt_insert['nm_pt_asal'] = 'Sekolah Tinggi Ilmu Ekonomi YPM Sepanjang';
						$mahasiswa_pt_insert['nm_prodi_asal'] = 'Akuntansi';
					}
					else if ($mahasiswa_pt_insert['id_sms'] == '96f4e4f6-adba-41b6-8d92-92369808ae44')
					{
						$mahasiswa_pt_insert['nm_pt_asal'] = 'Sekolah Tinggi Ilmu Hukum Ypm Sepanjang';
						$mahasiswa_pt_insert['nm_prodi_asal'] = 'Ilmu Hukum';
					}
					else if ($mahasiswa_pt_insert['id_sms'] == 'f9351bde-0387-4f6e-99c8-f38ec28543e4')
					{
						$mahasiswa_pt_insert['nm_pt_asal'] = 'Akademi Analis Kesehatan YPM Sidoarjo';
						$mahasiswa_pt_insert['nm_prodi_asal'] = 'Analis kesehatan';
					}
				}
				
				// Enable utk debugging only
				// $result['message'] = ($index_proses + 1) . " {$mahasiswa_pt_insert['nipd']} : " . json_encode($mahasiswa_insert) . "\n" . json_encode($mahasiswa_pt_insert);
				
				// Jika tidak ada no KTP / Identitas, diganti 0
				if ($mahasiswa_insert['nik'] == '' || !is_numeric($mahasiswa_insert['nik']))
				{
					$mahasiswa_insert['nik'] = '0';
				}
				
				// Entri ke Feeder Mahasiswa
				$insert_result = $this->feeder->InsertRecord($this->token, FEEDER_MAHASISWA, json_encode($mahasiswa_insert));
				
				// Jika berhasil insert, terdapat return id_pd
				if (isset($insert_result['result']['id_pd']))
				{
					// FK id_pd
					$mahasiswa_pt_insert['id_pd'] = $insert_result['result']['id_pd'];
					
					// Entri ke Feeder Mahasiswa_PT
					$insert_pt_result = $this->feeder->InsertRecord($this->token, FEEDER_MAHASISWA_PT, json_encode($mahasiswa_pt_insert));
					
					// Jika berhasil insert, terdapat return id_reg_pd
					if (isset($insert_pt_result['result']['id_reg_pd']))
					{	
						// Pesan Insert, nipd (nim) mengambil dari mahasiswa_pt_insert
						$result['message'] = ($index_proses + 1) . " Insert {$mahasiswa_pt_insert['nipd']} : Berhasil";
						
						// status sandbox
						$is_sandbox = ($this->session->userdata('is_sandbox') == TRUE) ? '1' : '0';
						
						// Melakukan update ke DB Langitan id_pd dan id_reg_pd hasil insert
						$this->rdb->Query("UPDATE pengguna SET fd_id_pd = '{$mahasiswa_pt_insert['id_pd']}', fd_sync_on = to_date('{$time_sync}', 'YYYY-MM-DD HH24:MI:SS') WHERE id_pengguna = {$id_pengguna}");
						$this->rdb->Query("UPDATE mahasiswa SET fd_id_reg_pd = '{$insert_pt_result['result']['id_reg_pd']}', fd_sync_on = to_date('{$time_sync}', 'YYYY-MM-DD HH24:MI:SS') WHERE id_mhs = {$id_mhs}");
					}
					else // saat insert mahasiswa_pt gagal
					{
						// Pesan Insert, nipd mengambil dari mahasiswa_pt_insert
						$result['message'] = ($index_proses + 1) . ' Insert ' . $mahasiswa_pt_insert['nipd'] . ' : ' . json_encode($insert_pt_result['result']);
						
						// Hapus lagi agar tidak terjadi penumpukan
						$this->feeder->DeleteRecord($this->token, FEEDER_MAHASISWA, json_encode(array('id_pd' => $insert_result['result']['id_pd'])));
					}
				}
				else // Saat insert mahasiswa Gagal
				{
					// Pesan Insert, nipd mengambil dari mahasiswa_pt_insert
					$result['message'] = ($index_proses + 1) . " Insert {$mahasiswa_pt_insert['nipd']} : Gagal. ({$insert_result['result']['error_code']}) {$insert_result['result']['error_desc']}";
				}
				
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Update
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update))
			{
				// index berjalan dikurangi jumlah data insert utk mendapatkan index update
				$index_proses -= $jumlah_insert;

				// Proses dalam bentuk key lowercase
				$mahasiswa_update = array_change_key_case($mahasiswa_update_set[$index_proses], CASE_LOWER);
				$mahasiswa_pt_update = array_change_key_case($mahasiswa_pt_update_set[$index_proses], CASE_LOWER);
				
				// Simpan id_mhs untuk update data di langitan
				$id_mhs		= $mahasiswa_update['id_mhs'];
				$id_pd		= $mahasiswa_update['id_pd'];
				$id_reg_pd	= $mahasiswa_pt_update['id_reg_pd'];
				
				// Hilangkan id_mhs & id_pd & id_reg_pd
				unset($mahasiswa_update['id_mhs']);
				unset($mahasiswa_update['id_pd']);
				unset($mahasiswa_pt_update['id_mhs']);
				unset($mahasiswa_pt_update['id_reg_pd']);
				
				// --------------------
				// Cleansing data
				// --------------------
				if ($mahasiswa_update['jk'] == '*') unset($mahasiswa_update['jk']);
				if ($mahasiswa_update['kode_pos'] == '') unset($mahasiswa_update['kode_pos']);
				if ( ! filter_var($mahasiswa_update['email'], FILTER_VALIDATE_EMAIL)) unset($mahasiswa_update['email']);
				if ($mahasiswa_update['rt'] == '') unset($mahasiswa_update['rt']);
				if ($mahasiswa_update['rw'] == '') unset($mahasiswa_update['rw']);
				if ($mahasiswa_update['id_jns_tinggal'] == '') unset($mahasiswa_update['id_jns_tinggal']);
				if ($mahasiswa_update['id_alat_transport'] == '') unset($mahasiswa_update['id_alat_transport']);
				if ($mahasiswa_update['tgl_lahir_ayah'] == '') unset($mahasiswa_update['tgl_lahir_ayah']);
				if ($mahasiswa_update['id_jenjang_pendidikan_ayah'] == '') unset($mahasiswa_update['id_jenjang_pendidikan_ayah']);
				if ($mahasiswa_update['id_pekerjaan_ayah'] == '') unset($mahasiswa_update['id_pekerjaan_ayah']);
				if ($mahasiswa_update['id_penghasilan_ayah'] == '') unset($mahasiswa_update['id_penghasilan_ayah']);
				if ($mahasiswa_update['tgl_lahir_ibu'] == '') unset($mahasiswa_update['tgl_lahir_ibu']);
				if ($mahasiswa_update['id_jenjang_pendidikan_ibu'] == '') unset($mahasiswa_update['id_jenjang_pendidikan_ibu']);
				if ($mahasiswa_update['id_pekerjaan_ibu'] == '') unset($mahasiswa_update['id_pekerjaan_ibu']);
				if ($mahasiswa_update['id_penghasilan_ibu'] == '') unset($mahasiswa_update['id_penghasilan_ibu']);
				if ($mahasiswa_update['tgl_lahir_wali'] == '') unset($mahasiswa_update['tgl_lahir_wali']);
				if ($mahasiswa_update['id_jenjang_pendidikan_wali'] == '') unset($mahasiswa_update['id_jenjang_pendidikan_wali']);
				if ($mahasiswa_update['id_pekerjaan_wali'] == '') unset($mahasiswa_update['id_pekerjaan_wali']);
				if ($mahasiswa_update['id_penghasilan_wali'] == '') unset($mahasiswa_update['id_penghasilan_wali']);
				
				// ---------------------------------------------------------------------------------------------
				// Khusus UMAHA : Angkatan dibawah 2014 di geser ke 2014 Ganjil sebagai mhs transfer, default SKS = 1
				// ---------------------------------------------------------------------------------------------
				if ($this->satuan_pendidikan['npsn'] == '071086' && (int)$mahasiswa_pt_update['mulai_smt'] < 20141)
				{
					$mahasiswa_pt_update['mulai_smt'] = '20141';			// 2014/2015 Ganjil
					$mahasiswa_pt_update['tgl_masuk_sp'] = '2014-10-17';	// Tanggal SK Penggabungan Institusi
					$mahasiswa_pt_update['id_jns_daftar'] = '2';			// Transfer
					
					// Asal PT & Prodi
					if ($mahasiswa_pt_update['id_sms'] == 'a9a267c7-63ff-4cbd-bcf6-b7f957fe1d80')
					{
						$mahasiswa_pt_update['nm_pt_asal'] = 'Sekolah Tinggi Teknik YPM Sepanjang';
						$mahasiswa_pt_update['nm_prodi_asal'] = 'Teknik Informatika';
					}
					else if ($mahasiswa_pt_update['id_sms'] == 'a9fec275-28ab-4895-894a-b545b8f7193a')
					{
						$mahasiswa_pt_update['nm_pt_asal'] = 'Sekolah Tinggi Teknik YPM Sepanjang';
						$mahasiswa_pt_update['nm_prodi_asal'] = 'Teknik Industri';
					}
					else if ($mahasiswa_pt_update['id_sms'] == '944fc32c-18f3-47f9-bc19-6e711480c858')
					{
						$mahasiswa_pt_update['nm_pt_asal'] = 'Sekolah Tinggi Teknik YPM Sepanjang';
						$mahasiswa_pt_update['nm_prodi_asal'] = 'Teknik Mesin';
					}
					else if ($mahasiswa_pt_update['id_sms'] == '613ec101-53d0-405c-b6e1-c4125f042f52')
					{
						$mahasiswa_pt_update['nm_pt_asal'] = 'Sekolah Tinggi Teknik YPM Sepanjang';
						$mahasiswa_pt_update['nm_prodi_asal'] = 'Teknik Komputer';
					}
					else if ($mahasiswa_pt_update['id_sms'] == 'c9e0c8b8-91c0-427a-bace-7215337e6515')
					{
						$mahasiswa_pt_update['nm_pt_asal'] = 'Sekolah Tinggi Ilmu Ekonomi YPM Sepanjang';
						$mahasiswa_pt_update['nm_prodi_asal'] = 'Manajemen';
					}
					else if ($mahasiswa_pt_update['id_sms'] == 'f0def671-d948-4d68-ab3e-a7f936f7a3b4')
					{
						$mahasiswa_pt_update['nm_pt_asal'] = 'Sekolah Tinggi Ilmu Ekonomi YPM Sepanjang';
						$mahasiswa_pt_update['nm_prodi_asal'] = 'Akuntansi';
					}
					else if ($mahasiswa_pt_update['id_sms'] == '25b084b0-b377-4369-8a3b-c196a86246bb')
					{
						$mahasiswa_pt_update['nm_pt_asal'] = 'Sekolah Tinggi Ilmu Ekonomi YPM Sepanjang';
						$mahasiswa_pt_update['nm_prodi_asal'] = 'Akuntansi';
					}
					else if ($mahasiswa_pt_update['id_sms'] == '96f4e4f6-adba-41b6-8d92-92369808ae44')
					{
						$mahasiswa_pt_update['nm_pt_asal'] = 'Sekolah Tinggi Ilmu Hukum Ypm Sepanjang';
						$mahasiswa_pt_update['nm_prodi_asal'] = 'Ilmu Hukum';
					}
					else if ($mahasiswa_pt_update['id_sms'] == 'f9351bde-0387-4f6e-99c8-f38ec28543e4')
					{
						$mahasiswa_pt_update['nm_pt_asal'] = 'Akademi Analis Kesehatan YPM Sidoarjo';
						$mahasiswa_pt_update['nm_prodi_asal'] = 'Analis kesehatan';
					}
				}
				
				// Jika tidak ada no KTP / Identitas, diganti 0
				if ($mahasiswa_update['nik'] == '') $mahasiswa_update['nik'] = '0';
				
				// Jika pendaftaran baru, sks diakui wajib 0
				if ($mahasiswa_pt_update['id_jns_daftar'] == '1') $mahasiswa_pt_update['sks_diakui'] = '0';
				// Jika transfer & sks = 0, sks diakui set minimal 1 --> agar bisa sync
				if ($mahasiswa_pt_update['id_jns_daftar'] == '2' && $mahasiswa_pt_update['sks_diakui'] == '0') $mahasiswa_pt_update['sks_diakui'] = 1;
				
				// Build data format
				$data_update = array(
					'key'	=> array('id_pd' => $id_pd),
					'data'	=> $mahasiswa_update
				);
				
				// Build data format
				$data_update_2 = array(
					'key'	=> array('id_reg_pd' => $id_reg_pd),
					'data'	=> $mahasiswa_pt_update
				);
				
				// Enable for debugging only
				// $result['message'] = ($index_proses + 1) . " {$mahasiswa_pt_update['nipd']}";
				
				// Update ke Feeder Mahasiswa
				$update_result = $this->feeder->UpdateRecord($this->token, FEEDER_MAHASISWA, json_encode($data_update));
				$update_2_result = $this->feeder->UpdateRecord($this->token, FEEDER_MAHASISWA_PT, json_encode($data_update_2));
				
				// Jika tidak ada masalah update
				if ($update_result['result']['error_code'] == 0 && $update_2_result['result']['error_code'] == 0)
				{
					$result['message'] = ($index_proses + 1) . " Update {$mahasiswa_pt_update['nipd']} : Berhasil";
					
					// Saat sandbox
					if ($this->session->userdata('is_sandbox'))
					{
						$this->rdb->Query("UPDATE feeder_mahasiswa SET last_sync_sandbox = to_date('{$time_sync}','YYYY-MM-DD HH24:MI:SS') WHERE id_mhs = {$id_mhs}");
						$this->rdb->Query("UPDATE feeder_mahasiswa_pt SET last_sync_sandbox = to_date('{$time_sync}','YYYY-MM-DD HH24:MI:SS') WHERE id_mhs = {$id_mhs}");
					}
					else
					{
						$this->rdb->Query("UPDATE feeder_mahasiswa SET last_sync = to_date('{$time_sync}','YYYY-MM-DD HH24:MI:SS') WHERE id_mhs = {$id_mhs}");
						$this->rdb->Query("UPDATE feeder_mahasiswa_pt SET last_sync = to_date('{$time_sync}','YYYY-MM-DD HH24:MI:SS') WHERE id_mhs = {$id_mhs}");
					}
				}
				// Jika terdapat masalah update
				else
				{
					$result['message'] = ($index_proses + 1) . " Update {$mahasiswa_pt_update['nipd']} : Gagal.";
					$result['message'] .= "\n({$update_result['result']['error_code']}) {$update_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update);
					$result['message'] .= "\n({$update_2_result['result']['error_code']}) {$update_2_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update_2);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert
				$index_proses += $jumlah_insert;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Selesai
			// --------------------------------
			else
			{
				$result['message'] = "Selesai";
				$result['status'] = SYNC_STATUS_DONE;
			}
		}
		
		echo json_encode($result);
	}
	
	
	/**
	 * GET /sync/mata_kuliah
	 */
	function mata_kuliah()
	{
		$jumlah = array();
		
		// Ambil jumlah mata_kuliah di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_MATA_KULIAH, null);
		$jumlah['feeder'] = $response['result'];
		
		// Ambil data mata kuliah
		$sql_mk_raw = file_get_contents(APPPATH.'models/sql/mata-kuliah.sql');
		$sql_mk = strtr($sql_mk_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$mk_set = $this->rdb->QueryToArray($sql_mk);
			
		$jumlah['langitan'] = $mk_set[0]['JUMLAH'];
		$jumlah['linked'] = $mk_set[1]['JUMLAH'];
		$jumlah['update'] = $mk_set[2]['JUMLAH'];
		$jumlah['insert'] = $mk_set[3]['JUMLAH'];
		
		$this->smarty->assign('jumlah', $jumlah);
		
		// Ambil program studi
		$sql_program_studi_raw = file_get_contents(APPPATH.'models/sql/program-studi.sql');
		$sql_program_studi = strtr($sql_program_studi_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$program_studi_set = $this->rdb->QueryToArray($sql_program_studi);
		$this->smarty->assign('program_studi_set', $program_studi_set);
		
		$this->smarty->assign('url_sync', site_url('sync/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync/'.$this->uri->segment(2).'.tpl');
	}
	
	/**
	 * Ajax-GET /sync/mata_kuliah_data/
	 * @param string $kode_prodi Kode program studi versi Feeder
	 */
	function mata_kuliah_data($kode_prodi)
	{
		// Ambil id_sms dari kode prodi
		$response = $this->feeder->GetRecord($this->token, FEEDER_SMS, "p.id_sp = '{$this->satuan_pendidikan['id_sp']}' and p.kode_prodi like '{$kode_prodi}%'");
		$id_sms = $response['result']['id_sms'];
		
		// Ambil jumlah mata kuliah di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_MATA_KULIAH, "p.id_sms = '{$id_sms}'");
		$jumlah['feeder'] = $response['result'];
		
		// Ambil data mata kuliah
		$sql_mk_raw = file_get_contents(APPPATH.'models/sql/mata-kuliah-data.sql');
		$sql_mk = strtr($sql_mk_raw, array(
			'@npsn' => $this->satuan_pendidikan['npsn'],
			'@kode_prodi' => $kode_prodi
		));
		
		$mk_set = $this->rdb->QueryToArray($sql_mk);
		
		$jumlah['langitan'] = $mk_set[0]['JUMLAH'];
		$jumlah['linked'] = $mk_set[1]['JUMLAH'];
		$jumlah['update'] = $mk_set[2]['JUMLAH'];
		$jumlah['insert'] = $mk_set[3]['JUMLAH'];
		
		echo json_encode($jumlah);
	}
	
	private function proses_mata_kuliah()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		// -----------------------------------
		// Ambil data untuk Insert
		// -----------------------------------
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{
			// Filter Prodi & Angkatan
			$kode_prodi = $this->input->post('kode_prodi');
			
			// Mendapatkan id_sms
			$response = $this->feeder->GetRecord($this->token, FEEDER_SMS, "id_sp = '{$this->satuan_pendidikan['id_sp']}' AND trim(kode_prodi) = '{$kode_prodi}'");
			$sms = $response['result'];
			
			// Ambil data mata kuliah
			$sql_mk_raw = file_get_contents(APPPATH.'models/sql/mata-kuliah-insert.sql');
			$sql_mk = strtr($sql_mk_raw, array(
				'@npsn' => $this->satuan_pendidikan['npsn'],
				'@kode_prodi' => $kode_prodi
			));
			$mk_set = $this->rdb->QueryToArray($sql_mk);
				
			$this->session->set_userdata('mata_kuliah_insert_set', $mk_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Entri. Jumlah data: ' . count($mk_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_2;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Update
		// -----------------------------------
	    else if ($mode == MODE_AMBIL_DATA_LANGITAN_2)
		{
			// Filter Prodi & Angkatan
			$kode_prodi = $this->input->post('kode_prodi');
			
			// Mendapatkan id_sms
			$response = $this->feeder->GetRecord($this->token, FEEDER_SMS, "id_sp = '{$this->satuan_pendidikan['id_sp']}' AND trim(kode_prodi) = '{$kode_prodi}'");
			$sms = $response['result'];
			
			// Ambil data mata kuliah
			$sql_mk_raw = file_get_contents(APPPATH.'models/sql/mata-kuliah-update.sql');
			$sql_mk = strtr($sql_mk_raw, array(
				'@npsn' => $this->satuan_pendidikan['npsn'],
				'@kode_prodi' => $kode_prodi
			));

			$mk_set = $this->rdb->QueryToArray($sql_mk);
				
			$this->session->set_userdata('mata_kuliah_update_set', $mk_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Update. Jumlah data: ' . count($mk_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Proses Sinkronisasi dari data yg sudah diambil
		// -----------------------------------
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil dari cache
			$mata_kuliah_insert_set = $this->session->userdata('mata_kuliah_insert_set');
			$jumlah_insert = count($mata_kuliah_insert_set);
			
			// Ambil dari cache
			$mata_kuliah_update_set = $this->session->userdata('mata_kuliah_update_set');
			$jumlah_update = count($mata_kuliah_update_set);
			
			// Waktu Sinkronisasi
			$time_sync = date('Y-m-d H:i:s');
			
			// --------------------------------
			// Proses Insert
			// --------------------------------
			if ($index_proses < $jumlah_insert)
			{
				// Proses dalam bentuk key lowercase
				$mata_kuliah_insert = array_change_key_case($mata_kuliah_insert_set[$index_proses], CASE_LOWER);
				
				// Simpan id_mata_kuliah untuk update data di langitan
				$id_mata_kuliah = $mata_kuliah_insert['id_mata_kuliah'];
				
				// Hilangkan id_mata_kuliah
				unset($mata_kuliah_insert['id_mata_kuliah']);
				
				// Entri ke Feeder Mata Kuliah
				$insert_result = $this->feeder->InsertRecord($this->token, FEEDER_MATA_KULIAH, json_encode($mata_kuliah_insert));
				
				// Jika berhasil insert, terdapat return id_mk
				if (isset($insert_result['result']['id_mk']))
				{
					// Pesan Insert, tampilkan kode mk dan nama mk
					$result['message'] = ($index_proses + 1) . " Insert {$mata_kuliah_insert['kode_mk']} {$mata_kuliah_insert['nm_mk']} : Berhasil";
					
					// status sandbox
					$is_sandbox = ($this->session->userdata('is_sandbox') == TRUE) ? '1' : '0';
					
					// Update mata_kuliah.fd_id_mk
					$this->rdb->Query("UPDATE mata_kuliah SET fd_id_mk = '{$insert_result['result']['id_mk']}', fd_sync_on = sysdate WHERE id_mata_kuliah = {$id_mata_kuliah}");
				}
				else // saat insert mata_kuliah gagal
				{
					// Pesan Insert, kode mk  mengambil dari mata_kuliah
					$result['message'] = ($index_proses + 1) . " Insert {$mata_kuliah_insert['kode_mk']} {$mata_kuliah_insert['nm_mk']} : " . json_encode($insert_result['result']);
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Update
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update))
			{
				// index berjalan dikurangi jumlah data insert utk mendapatkan index update
				$index_proses -= $jumlah_insert;
				
				// Proses dalam bentuk key lowercase
				$mata_kuliah_update = array_change_key_case($mata_kuliah_update_set[$index_proses], CASE_LOWER);
				
				// Simpan id_mk dan id_mata_kuliah untuk update data di langitan
				$id_mk			= $mata_kuliah_update['id_mk'];
				$id_mata_kuliah	= $mata_kuliah_update['id_mata_kuliah'];
				$kode_mk		= $mata_kuliah_update['kode_mk'];
				$nm_mk			= $mata_kuliah_update['nm_mk'];
				
				// Hilangkan id_mk & id_mata_kuliah
				unset($mata_kuliah_update['id_mk']);
				unset($mata_kuliah_update['id_mata_kuliah']);
				unset($mata_kuliah_update['kode_mk']);
				unset($mata_kuliah_update['nm_mk']);
				
				// Build data format
				$data_update = array(
					'key'	=> array('id_mk' => $id_mk),
					'data'	=> $mata_kuliah_update
				);
				
				// Update ke Feeder Mata Kuliah
				$update_result = $this->feeder->UpdateRecord($this->token, FEEDER_MATA_KULIAH, json_encode($data_update));
				
				// Jika tidak ada masalah update
				if ($update_result['result']['error_code'] == 0)
				{
					$result['message'] = ($index_proses + 1) . " Update {$kode_mk} {$nm_mk} : Berhasil";
					
					$this->rdb->Query("UPDATE mata_kuliah SET fd_sync_on = sysdate WHERE id_mata_kuliah = {$id_mata_kuliah}");
				}
				else
				{
					$result['message'] = ($index_proses + 1) . " Update {$kode_mk} : Gagal. ";
					$result['message'] .= "({$update_result['result']['error_code']}) {$update_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert
				$index_proses += $jumlah_insert;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Selesai
			// --------------------------------
			else
			{
				$result['message'] = "Selesai";
				$result['status'] = SYNC_STATUS_DONE;
			}
		}
		
		echo json_encode($result);
	}
	
	/**
	 * GET /sync/kurikulum/
	 */
	function kurikulum()
	{
		// Ambil jumlah kurikulum di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_KURIKULUM, null);
		$jumlah['feeder'] = $response['result'];
		
		// Ambil data kurikulum
		$sql_kurikulum_raw = file_get_contents(APPPATH.'models/sql/kurikulum.sql');
		$sql_kurikulum = strtr($sql_kurikulum_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$kurikulum_set = $this->rdb->QueryToArray($sql_kurikulum);
		
		$jumlah['langitan'] = $kurikulum_set[0]['JUMLAH'];
		$jumlah['linked'] = $kurikulum_set[1]['JUMLAH'];
		$jumlah['update'] = $kurikulum_set[2]['JUMLAH'];
		$jumlah['insert'] = $kurikulum_set[3]['JUMLAH'];
		
		$this->smarty->assign('jumlah', $jumlah);
		
		// Ambil program studi
		$sql_program_studi_raw = file_get_contents(APPPATH.'models/sql/program-studi.sql');
		$sql_program_studi = strtr($sql_program_studi_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$program_studi_set = $this->rdb->QueryToArray($sql_program_studi);
		$this->smarty->assign('program_studi_set', $program_studi_set);
		
		$this->smarty->assign('url_sync', site_url('sync/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync/'.$this->uri->segment(2).'.tpl');
	}
	
	/**
	 * Ajax-GET /sync/kurikulum_data/
	 * @param string $kode_prodi Kode program studi versi Feeder
	 */
	function kurikulum_data($kode_prodi)
	{
		// Ambil id_sms dari kode prodi
		$response = $this->feeder->GetRecord($this->token, FEEDER_SMS, "p.id_sp = '{$this->satuan_pendidikan['id_sp']}' and p.kode_prodi like '{$kode_prodi}%'");
		$id_sms = $response['result']['id_sms'];
		
		// Ambil jumlah kurikulum di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_KURIKULUM, "id_sms = '{$id_sms}'");
		$jumlah['feeder'] = $response['result'];
		
		// Ambil data kurikulum
		$sql_kurikulum_raw = file_get_contents(APPPATH.'models/sql/kurikulum-data.sql');
		$sql_kurikulum = strtr($sql_kurikulum_raw, array(
			'@npsn' => $this->satuan_pendidikan['npsn'],
			'@kode_prodi' => $kode_prodi
		));
		
		$kurikulum_set = $this->rdb->QueryToArray($sql_kurikulum);
		
		$jumlah['langitan'] = $kurikulum_set[0]['JUMLAH'];
		$jumlah['linked'] = $kurikulum_set[1]['JUMLAH'];
		$jumlah['update'] = $kurikulum_set[2]['JUMLAH'];
		$jumlah['insert'] = $kurikulum_set[3]['JUMLAH'];
		
		echo json_encode($jumlah);
	}
	
	private function proses_kurikulum()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		// -----------------------------------
		// Ambil data untuk Insert
		// -----------------------------------
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{
			// Filter Prodi
			$kode_prodi = $this->input->post('kode_prodi');
			
			// Mendapatkan id_sms
			$response = $this->feeder->GetRecord($this->token, FEEDER_SMS, "id_sp = '{$this->satuan_pendidikan['id_sp']}' AND trim(kode_prodi) = '{$kode_prodi}'");
			$sms = $response['result'];
			
			// Ambil data kurikulum yang akan di insert
			$sql_kurikulum_raw = file_get_contents(APPPATH.'models/sql/kurikulum-insert.sql');
			$sql_kurikulum = strtr($sql_kurikulum_raw, array(
				'@npsn' => $this->satuan_pendidikan['npsn'],
				'@kode_prodi' => $kode_prodi
			));

			$kurikulum_set = $this->rdb->QueryToArray($sql_kurikulum);
			
			$this->session->set_userdata('kurikulum_insert_set', $kurikulum_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Entri. Jumlah data: ' . count($kurikulum_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_2;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Update
		// -----------------------------------
		else if ($mode == MODE_AMBIL_DATA_LANGITAN_2)
		{
			// Filter Prodi & Angkatan
			$kode_prodi = $this->input->post('kode_prodi');
			
			// Mendapatkan id_sms
			$response = $this->feeder->GetRecord($this->token, FEEDER_SMS, "id_sp = '{$this->satuan_pendidikan['id_sp']}' AND trim(kode_prodi) = '{$kode_prodi}'");
			$sms = $response['result'];
			
			// Ambil data kurikulum yang akan di insert
			$sql_kurikulum_raw = file_get_contents(APPPATH.'models/sql/kurikulum-update.sql');
			$sql_kurikulum = strtr($sql_kurikulum_raw, array(
				'@npsn' => $this->satuan_pendidikan['npsn'],
				'@kode_prodi' => $kode_prodi
			));

			$kurikulum_set = $this->rdb->QueryToArray($sql_kurikulum);
					
			$this->session->set_userdata('kurikulum_update_set', $kurikulum_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Update. Jumlah data: ' . count($kurikulum_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Proses Sinkronisasi dari data yg sudah diambil
		// -----------------------------------
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil dari cache
			$kurikulum_insert_set = $this->session->userdata('kurikulum_insert_set');
			$jumlah_insert = count($kurikulum_insert_set);
			
			// Ambil dari cache
			$kurikulum_update_set = $this->session->userdata('kurikulum_update_set');
			$jumlah_update = count($kurikulum_update_set);
			
			// Waktu Sinkronisasi
			$time_sync = date('Y-m-d H:i:s');
			
			// --------------------------------
			// Proses Insert
			// --------------------------------
			if ($index_proses < $jumlah_insert)
			{
				// Proses dalam bentuk key lowercase
				$kurikulum_insert = array_change_key_case($kurikulum_insert_set[$index_proses], CASE_LOWER);
				
				// Simpan id_kurikulum untuk update data di langitan
				$id_kurikulum = $kurikulum_insert['id_kurikulum'];
				
				// Hilangkan id_kurikulum
				unset($kurikulum_insert['id_kurikulum']);
				
				// Entri ke Feeder Kurikulum
				$insert_result = $this->feeder->InsertRecord($this->token, FEEDER_KURIKULUM, json_encode($kurikulum_insert));
				
				// Jika berhasil insert, terdapat return id_kurikulum_sp
				if (isset($insert_result['result']['id_kurikulum_sp']))
				{
					// Pesan Insert, tampilkan nama kurikulum
					$result['message'] = ($index_proses + 1) . " Insert {$kurikulum_insert['nm_kurikulum_sp']} : Berhasil";
					
					// Update status sinkron fd_id_kurilum_sp 
					$fd_id_kurikulum_sp = $insert_result['result']['id_kurikulum_sp'];
					$this->rdb->Query("UPDATE kurikulum SET fd_id_kurikulum_sp = '{$fd_id_kurikulum_sp}', fd_sync_on = sysdate WHERE id_kurikulum = {$id_kurikulum}");
				}
				else // saat insert kurikulum gagal
				{
					// Pesan insert jika gagal
					$result['message'] = ($index_proses + 1) . " Insert {$kurikulum_insert['nm_kurikulum_sp']} : " . json_encode($insert_result['result']);
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Update
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update))
			{
				// index berjalan dikurangi jumlah data insert utk mendapatkan index update
				$index_proses -= $jumlah_insert;
				
				// Proses dalam bentuk key lowercase
				$kurikulum_update = array_change_key_case($kurikulum_update_set[$index_proses], CASE_LOWER);
				
				// Simpan id_kurikulum_sp dan kurikulum_prodi untuk update data di langitan
				$id_kurikulum_sp	= $kurikulum_update['id_kurikulum_sp'];
				$id_kurikulum		= $kurikulum_update['id_kurikulum'];
				
				// Hilangkan id_kurikulum_sp & id_kurikulum
				unset($kurikulum_update['id_kurikulum_sp']);
				unset($kurikulum_update['id_kurikulum']);
				
				// Build data format
				$data_update = array(
					'key'	=> array('id_kurikulum_sp' => $id_kurikulum_sp),
					'data'	=> $kurikulum_update
				);
				
				// Update ke Feeder Kurikulum
				$update_result = $this->feeder->UpdateRecord($this->token, FEEDER_KURIKULUM, json_encode($data_update));
				
				// Jika tidak ada masalah update
				if ($update_result['result']['error_code'] == 0)
				{
					$result['message'] = ($index_proses + 1) . " Update {$kurikulum_update['nm_kurikulum_sp']} : Berhasil";
					
					// Update waktu sync
					$this->rdb->Query("UPDATE kurikulum SET fd_sync_on = sysdate WHERE id_kurikulum = {$id_kurikulum}");
				}
				else
				{
					$result['message'] = ($index_proses + 1) . " Update {$kurikulum_update['nm_kurikulum_sp']} : Gagal. ";
					$result['message'] .= "({$update_result['result']['error_code']}) {$update_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert
				$index_proses += $jumlah_insert;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Selesai
			// --------------------------------
			else
			{
				$result['message'] = "Selesai";
				$result['status'] = SYNC_STATUS_DONE;
			}
		}
		
		echo json_encode($result);
	}
	
	/**
	 * GET /sync/mata_kuliah_kurikulum/
	 */
	function mata_kuliah_kurikulum()
	{
		// Ambil jumlah mk kurikulum di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_MK_KURIKULUM, null);
		$jumlah['feeder'] = $response['result'];
		
		// Ambil data mata kuliah kurikulum
		$sql_mk_kurikulum_raw = file_get_contents(APPPATH.'models/sql/mata-kuliah-kurikulum.sql');
		$sql_mk_kurikulum = strtr($sql_mk_kurikulum_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$data_set = $this->rdb->QueryToArray($sql_mk_kurikulum);
		
		$jumlah['langitan'] = $data_set[0]['JUMLAH'];
		$jumlah['linked'] = $data_set[1]['JUMLAH'];
		$jumlah['update'] = $data_set[2]['JUMLAH'];
		$jumlah['insert'] = $data_set[3]['JUMLAH'];
		
		$this->smarty->assign('jumlah', $jumlah);
		
		// Ambil program studi
		$sql_program_studi_raw = file_get_contents(APPPATH.'models/sql/program-studi.sql');
		$sql_program_studi = strtr($sql_program_studi_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$program_studi_set = $this->rdb->QueryToArray($sql_program_studi);
		$this->smarty->assign('program_studi_set', $program_studi_set);
		
		$this->smarty->assign('url_sync', site_url('sync/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync/'.$this->uri->segment(2).'.tpl');
	}
	
	/**
	 * 
	 * @param string $kode_prodi
	 * @param string $id_kurikulum_prodi
	 */
	function mata_kuliah_kurikulum_data($kode_prodi, $id_kurikulum_prodi)
	{
		// Ambil id_sms dari kode prodi
		$response = $this->feeder->GetRecord($this->token, FEEDER_SMS, "p.id_sp = '{$this->satuan_pendidikan['id_sp']}' and trim(p.kode_prodi) = '{$kode_prodi}'");
		$id_sms = $response['result']['id_sms'];
		
		// Ambil id_kurikulum_sp dari id_kurikulum_prodi
		$response = $this->rdb->QueryToArray("SELECT id_kurikulum_sp FROM feeder_kurikulum WHERE id_kurikulum_prodi = {$id_kurikulum_prodi}");
		$id_kurikulum_sp = $response[0]['ID_KURIKULUM_SP'];
		
		// Ambil jumlah mk kurikulum di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_MK_KURIKULUM, "p.id_kurikulum_sp = '{$id_kurikulum_sp}'");
		$jumlah['feeder'] = $response['result'];
			
		// Query berbasis id_kurikulum_sp & id_mk
		$sql =
			"/* Jumlah Semua Data */
			SELECT COUNT(*) as jumlah FROM (
				SELECT DISTINCT fk.id_kurikulum_sp, fmk.id_mk
				FROM kurikulum_mk kmk
				JOIN kurikulum_prodi kp ON kp.id_kurikulum_prodi = kmk.id_kurikulum_prodi
				JOIN feeder_kurikulum fk on fk.id_kurikulum_prodi = kp.id_kurikulum_prodi
				JOIN mata_kuliah mk ON mk.id_mata_kuliah = kmk.id_mata_kuliah
				JOIN feeder_mata_kuliah fmk ON fmk.id_mata_kuliah = mk.id_mata_kuliah
				JOIN program_studi ps ON ps.id_program_studi = kp.id_program_studi
				JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
				WHERE pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND kmk.id_kurikulum_prodi = {$id_kurikulum_prodi}
			)
			UNION ALL
			/* Jumlah sudah Link */
			SELECT COUNT(*) as jumlah FROM (
				SELECT DISTINCT fk.id_kurikulum_sp, fmk.id_mk
				FROM kurikulum_mk kmk
				JOIN kurikulum_prodi kp ON kp.id_kurikulum_prodi = kmk.id_kurikulum_prodi
				JOIN feeder_kurikulum fk on fk.id_kurikulum_prodi = kp.id_kurikulum_prodi
				JOIN mata_kuliah mk ON mk.id_mata_kuliah = kmk.id_mata_kuliah
				JOIN feeder_mata_kuliah fmk ON fmk.id_mata_kuliah = mk.id_mata_kuliah
				JOIN program_studi ps ON ps.id_program_studi = kp.id_program_studi
				JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
				WHERE 
					pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND kmk.id_kurikulum_prodi = {$id_kurikulum_prodi} AND 
					(fk.id_kurikulum_sp, fmk.id_mk) IN (select id_kurikulum_sp, id_mk from feeder_mk_kurikulum)
			)
			UNION ALL
			/* Jumlah bakal update */
			SELECT COUNT(*) as jumlah FROM (
				SELECT DISTINCT fk.id_kurikulum_sp, fmk.id_mk
				FROM kurikulum_mk kmk
				JOIN kurikulum_prodi kp ON kp.id_kurikulum_prodi = kmk.id_kurikulum_prodi
				JOIN feeder_kurikulum fk on fk.id_kurikulum_prodi = kp.id_kurikulum_prodi
				JOIN mata_kuliah mk ON mk.id_mata_kuliah = kmk.id_mata_kuliah
				JOIN feeder_mata_kuliah fmk ON fmk.id_mata_kuliah = mk.id_mata_kuliah
				JOIN program_studi ps ON ps.id_program_studi = kp.id_program_studi
				JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
				WHERE 
					pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND kmk.id_kurikulum_prodi = {$id_kurikulum_prodi} AND 
					(fk.id_kurikulum_sp, fmk.id_mk) IN (SELECT id_kurikulum_sp, id_mk FROM feeder_mk_kurikulum WHERE last_sync < last_update)
			)
			UNION ALL
			/* Jumlah bakal Insert */
			SELECT COUNT(*) as jumlah FROM (
				SELECT DISTINCT fk.id_kurikulum_sp, fmk.id_mk
				FROM kurikulum_mk kmk
				JOIN kurikulum_prodi kp ON kp.id_kurikulum_prodi = kmk.id_kurikulum_prodi
				JOIN feeder_kurikulum fk on fk.id_kurikulum_prodi = kp.id_kurikulum_prodi
				JOIN mata_kuliah mk ON mk.id_mata_kuliah = kmk.id_mata_kuliah
				JOIN feeder_mata_kuliah fmk ON fmk.id_mata_kuliah = mk.id_mata_kuliah
				JOIN program_studi ps ON ps.id_program_studi = kp.id_program_studi
				JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
				WHERE 
					pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND kmk.id_kurikulum_prodi = {$id_kurikulum_prodi} AND 
					(fk.id_kurikulum_sp, fmk.id_mk) NOT IN (SELECT id_kurikulum_sp, id_mk FROM feeder_mk_kurikulum)
			)";
		
		$data_set = $this->rdb->QueryToArray($sql);
		
		$jumlah['langitan'] = $data_set[0]['JUMLAH'];
		$jumlah['linked'] = $data_set[1]['JUMLAH'];
		$jumlah['update'] = $data_set[2]['JUMLAH'];
		$jumlah['insert'] = $data_set[3]['JUMLAH'];
		
		echo json_encode($jumlah);
	}
	
	private function proses_mata_kuliah_kurikulum()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		// -----------------------------------
		// Ambil data untuk Insert
		// -----------------------------------
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{
			// Filter prodi & kurikulum_prodi
			$kode_prodi			= $this->input->post('kode_prodi');
			$id_kurikulum_prodi	= $this->input->post('id_kurikulum_prodi');
			
			$sql = 
				"SELECT 
					fk.id_kurikulum_sp, 
					fmk.id_mk,
					MIN( CASE mod(nvl(kmk.tingkat_semester, 1), 2) WHEN 0 THEN 2 ELSE 1 END ) AS smt,
					MAX( nvl(kmk.kredit_semester, 0) ) AS sks_mk,
					MAX( nvl(kmk.kredit_tatap_muka, 0) ) AS sks_tm,
					MAX( nvl(kmk.kredit_praktikum, 0) ) AS sks_prak,
					MAX( nvl(kmk.kredit_prak_lapangan, 0) ) AS sks_prak_lap,
					MAX( nvl(kmk.kredit_simulasi, 0) ) AS sks_sim,
					mk.kd_mata_kuliah, mk.nm_mata_kuliah
				FROM kurikulum_mk kmk
				JOIN kurikulum_prodi kp ON kp.id_kurikulum_prodi = kmk.id_kurikulum_prodi
				JOIN feeder_kurikulum fk ON fk.id_kurikulum_prodi = kp.id_kurikulum_prodi
				JOIN mata_kuliah mk ON mk.id_mata_kuliah = kmk.id_mata_kuliah
				JOIN feeder_mata_kuliah fmk ON fmk.id_mata_kuliah = mk.id_mata_kuliah
				WHERE kmk.id_kurikulum_prodi = {$id_kurikulum_prodi} AND (fk.id_kurikulum_sp, fmk.id_mk) NOT IN (SELECT id_kurikulum_sp, id_mk FROM feeder_mk_kurikulum)
				GROUP BY fk.id_kurikulum_sp, fmk.id_mk, mk.kd_mata_kuliah, mk.nm_mata_kuliah";
			
			$data_set = $this->rdb->QueryToArray($sql);
			
			$this->session->set_userdata('data_insert_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Entri. Jumlah data: ' . count($data_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_2;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Update
		// -----------------------------------
		else if ($mode == MODE_AMBIL_DATA_LANGITAN_2)
		{
			// Filter Prodi & Semester
			$kode_prodi			= $this->input->post('kode_prodi');
			$id_kurikulum_prodi	= $this->input->post('id_kurikulum_prodi');
			
			$sql = 
				"SELECT 
					fk.id_kurikulum_sp, 
					fmk.id_mk,
					MIN( CASE mod(nvl(kmk.tingkat_semester, 1), 2) WHEN 0 THEN 2 ELSE 1 END ) AS smt,
					MAX( nvl(kmk.kredit_semester, 0) ) AS sks_mk,
					MAX( nvl(kmk.kredit_tatap_muka, 0) ) AS sks_tm,
					MAX( nvl(kmk.kredit_praktikum, 0) ) AS sks_prak,
					MAX( nvl(kmk.kredit_prak_lapangan, 0) ) AS sks_prak_lap,
					MAX( nvl(kmk.kredit_simulasi, 0) ) AS sks_sim,
					mk.kd_mata_kuliah, mk.nm_mata_kuliah
				FROM kurikulum_mk kmk
				JOIN kurikulum_prodi kp ON kp.id_kurikulum_prodi = kmk.id_kurikulum_prodi
				JOIN feeder_kurikulum fk ON fk.id_kurikulum_prodi = kp.id_kurikulum_prodi
				JOIN mata_kuliah mk ON mk.id_mata_kuliah = kmk.id_mata_kuliah
				JOIN feeder_mata_kuliah fmk ON fmk.id_mata_kuliah = mk.id_mata_kuliah
				WHERE kmk.id_kurikulum_prodi = {$id_kurikulum_prodi} AND (fk.id_kurikulum_sp, fmk.id_mk) NOT IN (SELECT id_kurikulum_sp, id_mk FROM feeder_mk_kurikulum WHERE last_sync < last_update)
				GROUP BY fk.id_kurikulum_sp, fmk.id_mk, mk.kd_mata_kuliah, mk.nm_mata_kuliah";
				
			$data_set = $this->rdb->QueryToArray($sql);
					
			$this->session->set_userdata('data_update_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Update. Jumlah data: ' . count($data_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Proses Sinkronisasi dari data yg sudah diambil
		// -----------------------------------
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil dari cache
			$data_insert_set = $this->session->userdata('data_insert_set');
			$jumlah_insert = count($data_insert_set);
			
			// Ambil dari cache
			$data_update_set = $this->session->userdata('data_update_set');
			$jumlah_update = count($data_update_set);
			
			// Waktu Sinkronisasi
			$time_sync = date('Y-m-d H:i:s');
			
			// --------------------------------
			// Proses Insert
			// --------------------------------
			if ($index_proses < $jumlah_insert)
			{
				// Proses dalam bentuk key lowercase
				$data_insert = array_change_key_case($data_insert_set[$index_proses], CASE_LOWER);
				
				// Simpan informasi mata kuliah
				$kd_mata_kuliah		= $data_insert['kd_mata_kuliah'];
				$nm_mata_kuliah		= $data_insert['nm_mata_kuliah'];
				
				// Hilangkan data tdk diperlukan untuk insert
				unset($data_insert['kd_mata_kuliah']);
				unset($data_insert['nm_mata_kuliah']);
				
				// Entri ke Feeder mata_kuliah_kurikulum
				$insert_result = $this->feeder->InsertRecord($this->token, FEEDER_MK_KURIKULUM, json_encode($data_insert));
				
				// Jika berhasil insert, terdapat return id_kurikulum_sp & id_mk
				if (isset($insert_result['result']['id_kurikulum_sp']) && isset($insert_result['result']['id_mk']))
				{
					// Pesan Insert, tampilkan nama kelas
					$result['message'] = ($index_proses + 1) . " Insert {$kd_mata_kuliah} {$nm_mata_kuliah} : Berhasil";
					
					// status sandbox
					$is_sandbox = ($this->session->userdata('is_sandbox') == TRUE) ? '1' : '0';
					
					// Melakukan insert ke feeder_mk_kurikulum hasil insert
					$this->rdb->Query(
						"INSERT INTO feeder_mk_kurikulum (ID_KURIKULUM_SP, ID_MK, LAST_SYNC, LAST_UPDATE, IS_SANDBOX)
							VALUES ('{$insert_result['result']['id_kurikulum_sp']}', '{$insert_result['result']['id_mk']}', to_date('{$time_sync}', 'YYYY-MM-DD HH24:MI:SS'), to_date('{$time_sync}', 'YYYY-MM-DD HH24:MI:SS'), {$is_sandbox})");
				}
				else // saat insert mk kurikulum gagal
				{
					// Pesan insert jika gagal
					$result['message'] = ($index_proses + 1) . " Insert {$kd_mata_kuliah} {$nm_mata_kuliah} : " . json_encode($insert_result['result']);
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Update
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update))
			{
				// index berjalan dikurangi jumlah data insert utk mendapatkan index update
				$index_proses -= $jumlah_insert;
				
				// Proses dalam bentuk key lowercase
				$data_update = array_change_key_case($data_update_set[$index_proses], CASE_LOWER);
				
				// Simpan id_kurikulum_sp & id_mk informasi mata kuliah
				$id_kurikulum_sp	= $data_update['id_kurikulum_sp'];
				$id_mk				= $data_update['id_mk'];
				$kd_mata_kuliah		= $data_update['kd_mata_kuliah'];
				$nm_mata_kuliah		= $data_update['nm_mata_kuliah'];
				
				// Hilangkan data tdk diperlukan untuk update
				unset($data_update['id_kurikulum_sp']);
				unset($data_update['id_mk']);
				unset($data_update['kd_mata_kuliah']);
				unset($data_update['nm_mata_kuliah']);
				
				// Build data format
				$data_update_json = array(
					'key'	=> array('id_kurikulum_sp' => $id_kurikulum_sp, 'id_mk' => $id_mk),
					'data'	=> $data_update
				);
				
				// Update ke Feeder kelas kuliah
				$update_result = $this->feeder->UpdateRecord($this->token, FEEDER_MK_KURIKULUM, json_encode($data_update_json));
				
				// Jika tidak ada masalah update
				if ($update_result['result']['error_code'] == 0)
				{
					$result['message'] = ($index_proses + 1) . " Update {$kd_mata_kuliah} {$nm_mata_kuliah} : Berhasil";
					
					// Saat sandbox
					if ($this->session->userdata('is_sandbox'))
					{
						$this->rdb->Query("UPDATE feeder_mk_kurikulum SET last_sync_sandbox = to_date('{$time_sync}','YYYY-MM-DD HH24:MI:SS') WHERE id_kurikulum_sp = '{$id_kurikulum_sp}' and id_mk = '{$id_mk}'");
					}
					else
					{
						$this->rdb->Query("UPDATE feeder_mk_kurikulum SET last_sync = to_date('{$time_sync}','YYYY-MM-DD HH24:MI:SS') WHERE id_kurikulum_sp = '{$id_kurikulum_sp}' and id_mk = '{$id_mk}'");
					}
				}
				else
				{
					$result['message'] = ($index_proses + 1) . " Update  {$kd_mata_kuliah} {$nm_mata_kuliah} : Gagal. ";
					$result['message'] .= "({$update_result['result']['error_code']}) {$update_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update_json);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert
				$index_proses += $jumlah_insert;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Selesai
			// --------------------------------
			else
			{
				$result['message'] = "Selesai";
				$result['status'] = SYNC_STATUS_DONE;
			}
		}
		
		echo json_encode($result);
	}
	
	/**
	 * AJAX-GET /sync/ambil_kurikulum/{$kode_prodi}
	 */
	function ambil_kurikulum($kode_prodi)
	{
		// Ambil data kurikulum yg sudah ada di feeder
		$sql = 
			"SELECT kp.id_kurikulum_prodi, k.nm_kurikulum FROM kurikulum_prodi kp
			JOIN kurikulum k ON k.id_kurikulum = kp.id_kurikulum
			JOIN program_studi ps ON ps.id_program_studi = kp.id_program_studi
			JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
			JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
			WHERE pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND ps.kode_program_studi = '{$kode_prodi}' AND kp.id_kurikulum_prodi IN (SELECT id_kurikulum_prodi FROM feeder_kurikulum)";
			
		$kurikulum_set = $this->rdb->QueryToArray($sql);
		
		echo json_encode($kurikulum_set);
	}
	
	/**
	 * GET /sync/kelas_kuliah/
	 */
	function kelas_kuliah()
	{
		// Ambil jumlah kelas kuliah di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_KELAS_KULIAH, null);
		$jumlah['feeder'] = $response['result'];
		
		// Ambil data mata kuliah
		$sql_kelas_kuliah_raw = file_get_contents(APPPATH.'models/sql/kelas-kuliah.sql');
		$sql_kelas_kuliah = strtr($sql_kelas_kuliah_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$data_set = $this->rdb->QueryToArray($sql_kelas_kuliah);
		
		$jumlah['langitan'] = $data_set[0]['JUMLAH'];
		$jumlah['linked'] = $data_set[1]['JUMLAH'];
		$jumlah['update'] = $data_set[2]['JUMLAH'];
		$jumlah['insert'] = $data_set[3]['JUMLAH'];
		
		$this->smarty->assign('jumlah', $jumlah);
		
		// Ambil program studi
		$sql_program_studi_raw = file_get_contents(APPPATH.'models/sql/program-studi.sql');
		$sql_program_studi = strtr($sql_program_studi_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$program_studi_set = $this->rdb->QueryToArray($sql_program_studi);
		$this->smarty->assign('program_studi_set', $program_studi_set);
		
		$this->smarty->assign('url_sync', site_url('sync/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync/'.$this->uri->segment(2).'.tpl');
	}
	
	/**
	 * Ajax-GET /sync/kelas_kuliah_data/
	 * @param string $kode_prodi Kode program studi versi Feeder
	 * @param string $id_smt Kode semester, format 20151 / 20152
	 */
	function kelas_kuliah_data($kode_prodi, $id_smt)
	{
		// Ambil id_sms dari kode prodi
		$response = $this->feeder->GetRecord($this->token, FEEDER_SMS, "p.id_sp = '{$this->satuan_pendidikan['id_sp']}' and p.kode_prodi like '{$kode_prodi}%'");
		$id_sms = $response['result']['id_sms'];
		
		// Ambil jumlah kelas kuliah di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_KELAS_KULIAH, "p.id_sms = '{$id_sms}' and p.id_smt = '{$id_smt}'");
		$jumlah['feeder'] = $response['result'];
		
		// Konversi format semester ke id_semester
		$semester_langitan = $this->rdb->QueryToArray(
			"SELECT id_semester FROM semester s
			JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = s.id_perguruan_tinggi
			WHERE 
				pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND 
				s.thn_akademik_semester||decode(upper(nm_semester), 'GANJIL','1','GENAP','2') = '{$id_smt}'");
		$id_semester = $semester_langitan[0]['ID_SEMESTER'];
		
		// Ambil data mata kuliah
		$sql_kelas_kuliah_raw = file_get_contents(APPPATH.'models/sql/kelas-kuliah-data.sql');
		$sql_kelas_kuliah = strtr($sql_kelas_kuliah_raw, array(
			'@npsn' => $this->satuan_pendidikan['npsn'],
			'@kode_prodi' => $kode_prodi,
			'@smt' => $id_semester
		));
		$data_set = $this->rdb->QueryToArray($sql_kelas_kuliah);
			
		$jumlah['langitan'] = $data_set[0]['JUMLAH'];
		$jumlah['linked'] = $data_set[1]['JUMLAH'];
		$jumlah['update'] = $data_set[2]['JUMLAH'];
		$jumlah['insert'] = $data_set[3]['JUMLAH'];
		
		echo json_encode($jumlah);
	}
	
	private function proses_kelas_kuliah()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		// -----------------------------------
		// Ambil data untuk Insert
		// -----------------------------------
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{
			// Filter Prodi & Semester
			$kode_prodi = $this->input->post('kode_prodi');
			$id_smt		= $this->input->post('semester');
			
			// Mendapatkan id_sms
			$response = $this->feeder->GetRecord($this->token, FEEDER_SMS, "id_sp = '{$this->satuan_pendidikan['id_sp']}' AND trim(kode_prodi) = '{$kode_prodi}'");
			$sms = $response['result'];
			
			// Konversi format semester ke id_semester
			$semester_langitan = $this->rdb->QueryToArray(
				"SELECT id_semester FROM semester s
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = s.id_perguruan_tinggi
				WHERE 
					pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND 
					s.thn_akademik_semester||decode(upper(nm_semester), 'GANJIL','1','GENAP','2') = '{$id_smt}'");
			$id_semester = $semester_langitan[0]['ID_SEMESTER'];
			
			// Ambil data kelas kuliah insert
			$sql_kelas_kuliah_raw = file_get_contents(APPPATH.'models/sql/kelas-kuliah-insert.sql');
			$sql_kelas_kuliah = strtr($sql_kelas_kuliah_raw, array(
				'@npsn' => $this->satuan_pendidikan['npsn'],
				'@kode_prodi' => $kode_prodi,
				'@smt' => $id_semester,
				'@id_smt' => $id_smt
			));
			$data_set = $this->rdb->QueryToArray($sql_kelas_kuliah);
			
			$this->session->set_userdata('data_insert_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Entri. Jumlah data: ' . count($data_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_2;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Update
		// -----------------------------------
		else if ($mode == MODE_AMBIL_DATA_LANGITAN_2)
		{
			// Filter Prodi & Semester
			$kode_prodi = $this->input->post('kode_prodi');
			$id_smt		= $this->input->post('semester');
			
			// Mendapatkan id_sms
			$response = $this->feeder->GetRecord($this->token, FEEDER_SMS, "id_sp = '{$this->satuan_pendidikan['id_sp']}' AND trim(kode_prodi) = '{$kode_prodi}'");
			$sms = $response['result'];
			
			// Konversi format semester ke id_semester
			$semester_langitan = $this->rdb->QueryToArray(
				"SELECT id_semester FROM semester s
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = s.id_perguruan_tinggi
				WHERE 
					pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND 
					s.thn_akademik_semester||decode(upper(nm_semester), 'GANJIL','1','GENAP','2') = '{$id_smt}'");
			$id_semester = $semester_langitan[0]['ID_SEMESTER'];
			
			// Ambil data kelas kuliah yang akan di update
			$sql_kelas_kuliah_raw = file_get_contents(APPPATH.'models/sql/kelas-kuliah-update.sql');
			$sql_kelas_kuliah = strtr($sql_kelas_kuliah_raw, array(
				'@npsn' => $this->satuan_pendidikan['npsn'],
				'@kode_prodi' => $kode_prodi,
				'@smt' => $id_semester,
				'@id_smt' => $id_smt
			));
			$data_set = $this->rdb->QueryToArray($sql_kelas_kuliah);
								
			$this->session->set_userdata('data_update_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Update. Jumlah data: ' . count($data_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Proses Sinkronisasi dari data yg sudah diambil
		// -----------------------------------
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil dari cache
			$data_insert_set = $this->session->userdata('data_insert_set');
			$jumlah_insert = count($data_insert_set);
			
			// Ambil dari cache
			$data_update_set = $this->session->userdata('data_update_set');
			$jumlah_update = count($data_update_set);
			
			// --------------------------------
			// Proses Insert
			// --------------------------------
			if ($index_proses < $jumlah_insert)
			{
				// Proses dalam bentuk key lowercase
				$data_insert = array_change_key_case($data_insert_set[$index_proses], CASE_LOWER);
				
				// Simpan id_kelas_mk untuk update data di langitan, nama mk untuk tampilan sync
				$id_kelas_mk	= $data_insert['id_kelas_mk'];
				$nm_mata_kuliah	= $data_insert['nm_mata_kuliah'];
				
				// Hilangkan id_kelas_mk, nm_mata_kuliah dari array
				unset($data_insert['id_kelas_mk']);
				unset($data_insert['nm_mata_kuliah']);
				
				// Entri ke Feeder kelas_kuliah
				$insert_result = $this->feeder->InsertRecord($this->token, FEEDER_KELAS_KULIAH, json_encode($data_insert));
				
				// Jika berhasil insert, terdapat return id_kls
				if (isset($insert_result['result']['id_kls']))
				{
					// Pesan Insert, tampilkan nama kelas
					$result['message'] = ($index_proses + 1) . " Insert {$nm_mata_kuliah} ({$data_insert['nm_kls']}) : Berhasil";
					
					$fd_id_kls = $insert_result['result']['id_kls'];
					$this->rdb->Query("UPDATE kelas_mk SET fd_id_kls = '{$fd_id_kls}', fd_sync_on = sysdate WHERE id_kelas_mk = {$id_kelas_mk}");
				}
				else // saat insert kelas kuliah gagal
				{
					// Pesan insert jika gagal
					$result['message'] = ($index_proses + 1) . " Insert {$nm_mata_kuliah} ({$data_insert['nm_kls']}) : " . json_encode($insert_result['result']);
					$result['message'] .= "\n" . json_encode($data_insert);
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Update
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update))
			{
				// index berjalan dikurangi jumlah data insert utk mendapatkan index update
				$index_proses -= $jumlah_insert;
				
				// Proses dalam bentuk key lowercase
				$data_update = array_change_key_case($data_update_set[$index_proses], CASE_LOWER);
				
				// Simpan id_kls, id_kelas_mk, nm_mata_kuliah
				$id_kls			= $data_update['id_kls'];
				$id_kelas_mk	= $data_update['id_kelas_mk'];
				$nm_mata_kuliah	= $data_update['nm_mata_kuliah'];
				
				// Hilangkan id_kls, id_kelas_mk, nm_mata_kuliah
				unset($data_update['id_kls']);
				unset($data_update['id_kelas_mk']);
				unset($data_update['nm_mata_kuliah']);
				
				// Build data format
				$data_update_json = array(
					'key'	=> array('id_kls' => $id_kls),
					'data'	=> $data_update
				);
				
				// Update ke Feeder kelas kuliah
				$update_result = $this->feeder->UpdateRecord($this->token, FEEDER_KELAS_KULIAH, json_encode($data_update_json));
				
				// Jika tidak ada masalah update
				if ($update_result['result']['error_code'] == 0)
				{
					$result['message'] = ($index_proses + 1) . " Update {$nm_mata_kuliah} ({$data_update['nm_kls']}) : Berhasil";
					
					// Update waktu sync
					$this->rdb->Query("UPDATE kelas_mk SET fd_sync_on = sysdate WHERE id_kelas_mk = {$id_kelas_mk}");
				}
				else
				{
					$result['message'] = ($index_proses + 1) . " Update {$nm_mata_kuliah} ({$data_update['nm_kls']}) : Gagal. ";
					$result['message'] .= "({$update_result['result']['error_code']}) {$update_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update_json);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert
				$index_proses += $jumlah_insert;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Selesai
			// --------------------------------
			else
			{
				$result['message'] = "Selesai";
				$result['status'] = SYNC_STATUS_DONE;
			}
		}
		
		echo json_encode($result);
	}
	
	/**
	 * GET /sync/ajar_dosen/
	 */
	function ajar_dosen()
	{
		// Ambil jumlah kelas kuliah di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_AJAR_DOSEN, null);
		$jumlah['feeder'] = $response['result'];
		
		// Ambil data ajar dosen / pengampu mk
		$sql_ajar_dosen_raw = file_get_contents(APPPATH.'models/sql/ajar-dosen.sql');
		$sql_ajar_dosen = strtr($sql_ajar_dosen_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$data_set = $this->rdb->QueryToArray($sql_ajar_dosen);

		$jumlah['langitan'] = $data_set[0]['JUMLAH'];
		$jumlah['linked'] = $data_set[1]['JUMLAH'];
		$jumlah['update'] = $data_set[2]['JUMLAH'];
		$jumlah['insert'] = $data_set[3]['JUMLAH'];
		
		$this->smarty->assign('jumlah', $jumlah);
		
		// Ambil program studi
		$sql_program_studi_raw = file_get_contents(APPPATH.'models/sql/program-studi.sql');
		$sql_program_studi = strtr($sql_program_studi_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$program_studi_set = $this->rdb->QueryToArray($sql_program_studi);
		$this->smarty->assign('program_studi_set', $program_studi_set);
		
		$this->smarty->assign('url_sync', site_url('sync/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync/'.$this->uri->segment(2).'.tpl');
	}

	/**
	 * Ajax-GET /sync/ajar_dosen_data/
	 * @param string $kode_prodi Kode program studi versi Feeder
	 * @param string $id_smt Kode semester, format 20151 / 20152
	 */
	function ajar_dosen_data($kode_prodi, $id_smt)
	{		
		// Dosen ajar tidak bisa di query jumlah
		// $response = $this->feeder->GetCountRecordset($this->token, FEEDER_AJAR_DOSEN, "p.id_kls = '{$id_kls}'");
		$jumlah['feeder'] = 'NaN';
		
		// Konversi format semester ke id_semester
		$sql_semester_raw = file_get_contents(APPPATH.'models/sql/semester-konversi.sql');
		$sql_semester = strtr($sql_semester_raw, array('@npsn' => $this->satuan_pendidikan['npsn'], '@id_smt' => $id_smt));
		$semester_langitan = $this->rdb->QueryToArray($sql_semester);
		$id_semester = $semester_langitan[0]['ID_SEMESTER'];

		// Ambil data ajar dosen / pengampu mk
		$sql_ajar_dosen_raw = file_get_contents(APPPATH.'models/sql/ajar-dosen-data.sql');
		$sql_ajar_dosen = strtr($sql_ajar_dosen_raw, array(
			'@npsn' => $this->satuan_pendidikan['npsn'], 
			'@kode_prodi' => $kode_prodi,
			'@id_semester' => $id_semester));
		$data_set = $this->rdb->QueryToArray($sql_ajar_dosen);
			
		$jumlah['langitan'] = $data_set[0]['JUMLAH'];
		$jumlah['linked'] = $data_set[1]['JUMLAH'];
		$jumlah['update'] = $data_set[2]['JUMLAH'];
		$jumlah['insert'] = $data_set[3]['JUMLAH'];
		
		echo json_encode($jumlah);
	}

	private function proses_ajar_dosen()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		// -----------------------------------
		// Ambil data untuk Insert
		// -----------------------------------
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{
			// Filter Prodi & Semester
			$kode_prodi = $this->input->post('kode_prodi');
			$id_smt		= $this->input->post('semester');
			
			// Konversi format semester ke id_semester
			$sql_semester_raw = file_get_contents(APPPATH.'models/sql/semester-konversi.sql');
			$sql_semester = strtr($sql_semester_raw, array('@npsn' => $this->satuan_pendidikan['npsn'], '@id_smt' => $id_smt));
			$semester_langitan = $this->rdb->QueryToArray($sql_semester);
			$id_semester = $semester_langitan[0]['ID_SEMESTER'];
			
			// Ambil data ajar dosen / pengampu mk
			$sql_ajar_dosen_raw = file_get_contents(APPPATH.'models/sql/ajar-dosen-insert.sql');
			$sql_ajar_dosen = strtr($sql_ajar_dosen_raw, array(
				'@npsn' => $this->satuan_pendidikan['npsn'],
				'@kode_prodi' => $kode_prodi,
				'@smt' => $id_semester
			));
			$data_set = $this->rdb->QueryToArray($sql_ajar_dosen);
			
			$this->session->set_userdata('data_insert_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Entri. Jumlah data: ' . count($data_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_2;
			// $_POST['mode'] = MODE_SYNC;  // skip TO SINKRON LANGSUNG
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Update
		// -----------------------------------
		else if ($mode == MODE_AMBIL_DATA_LANGITAN_2)
		{
			// Filter Prodi & Semester
			$kode_prodi = $this->input->post('kode_prodi');
			$id_smt		= $this->input->post('semester');
			
			// Konversi format semester ke id_semester
			$sql_semester_raw = file_get_contents(APPPATH.'models/sql/semester-konversi.sql');
			$sql_semester = strtr($sql_semester_raw, array('@npsn' => $this->satuan_pendidikan['npsn'], '@id_smt' => $id_smt));
			$semester_langitan = $this->rdb->QueryToArray($sql_semester);
			$id_semester = $semester_langitan[0]['ID_SEMESTER'];
			
			// Ambil data ajar dosen / pengampu mk
			$sql_ajar_dosen_raw = file_get_contents(APPPATH.'models/sql/ajar-dosen-update.sql');
			$sql_ajar_dosen = strtr($sql_ajar_dosen_raw, array(
				'@npsn' => $this->satuan_pendidikan['npsn'],
				'@kode_prodi' => $kode_prodi,
				'@smt' => $id_semester
			));
			$data_set = $this->rdb->QueryToArray($sql_ajar_dosen);
			
			$this->session->set_userdata('data_update_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Update. Jumlah data: ' . count($data_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Proses Sinkronisasi dari data yg sudah diambil
		// -----------------------------------
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil dari cache
			$data_insert_set = $this->session->userdata('data_insert_set');
			$jumlah_insert = count($data_insert_set);
			
			// Ambil dari cache
			$data_update_set = $this->session->userdata('data_update_set');
			$jumlah_update = count($data_update_set);
			
			// Waktu Sinkronisasi
			$time_sync = date('Y-m-d H:i:s');
			
			// --------------------------------
			// Proses Insert
			// --------------------------------
			if ($index_proses < $jumlah_insert)
			{
				// Proses dalam bentuk key lowercase
				$data_insert = array_change_key_case($data_insert_set[$index_proses], CASE_LOWER);
				
				// Simpan data yg diperlukan
				$id_pengampu_mk	= $data_insert['id_pengampu_mk'];
				$nm_dosen		= $data_insert['nm_dosen'];
				$nm_kelas		= $data_insert['nm_kelas'];
				
				// Hilangkan dari array
				unset($data_insert['id_pengampu_mk']);
				unset($data_insert['nm_dosen']);
				unset($data_insert['nm_kelas']);

				
				// Entri ke Feeder ajar_dosen
				$insert_result = $this->feeder->InsertRecord($this->token, FEEDER_AJAR_DOSEN, json_encode($data_insert));

				// Jika berhasil insert, terdapat return id_ajar
				if (isset($insert_result['result']['id_ajar']))
				{
					// Pesan Insert 
					$result['message'] = ($index_proses + 1) . " Insert {$nm_dosen} ke kelas {$nm_kelas} SKS {$data_insert['sks_subst_tot']}: Berhasil";

					// Update status Sync
					$this->rdb->Query("UPDATE pengampu_mk SET fd_id_ajar = '{$insert_result['result']['id_ajar']}', fd_sync_on = sysdate WHERE id_pengampu_mk = {$id_pengampu_mk}");
				}
				else // saat insert ajar dosen gagal
				{
					// Pesan insert jika gagal
					$result['message'] = ($index_proses + 1) . " Insert {$nm_dosen} ke kelas {$nm_kelas} : " . json_encode($insert_result['result']);
					$result['message'] .= "\n" . json_encode($data_insert);
				}
				
				
				$result['status'] = SYNC_STATUS_PROSES;

				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Update
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update))
			{
				// index berjalan dikurangi jumlah data insert utk mendapatkan index update
				$index_proses -= $jumlah_insert;
				
				// Proses dalam bentuk key lowercase
				$data_update = array_change_key_case($data_update_set[$index_proses], CASE_LOWER);
				
				// Simpan data yg diperlukan
				$id_pengampu_mk	= $data_update['id_pengampu_mk'];
				$id_ajar		= $data_update['id_ajar'];
				$nm_dosen		= $data_update['nm_dosen'];
				$nm_kelas		= $data_update['nm_kelas'];
				
				// Hilangkan dari array
				unset($data_update['id_pengampu_mk']);
				unset($data_update['id_ajar']);
				unset($data_update['nm_dosen']);
				unset($data_update['nm_kelas']);
				
				// Build data format
				$data_update_json = array(
					'key'	=> array('id_ajar' => $id_ajar),
					'data'	=> $data_update
				);
				
				// Update ke Feeder kelas kuliah
				$update_result = $this->feeder->UpdateRecord($this->token, FEEDER_AJAR_DOSEN, json_encode($data_update_json));
				
				// Jika tidak ada masalah update
				if ($update_result['result']['error_code'] == 0)
				{
					$result['message'] = ($index_proses + 1) . " Update {$nm_dosen} ke kelas {$nm_kelas} SKS {$data_update['sks_subst_tot']} : Berhasil";
					
					// Update waktu sync
					$this->rdb->Query("UPDATE pengampu_mk SET fd_sync_on = sysdate WHERE id_pengampu_mk = {$id_pengampu_mk}");
				}
				else
				{
					$result['message'] = ($index_proses + 1) . " Update {$nm_dosen} ke kelas {$nm_kelas} SKS {$data_update['sks_subst_tot']} : Gagal. ";
					$result['message'] .= "({$update_result['result']['error_code']}) {$update_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update_json);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert
				$index_proses += $jumlah_insert;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Selesai
			// --------------------------------
			else
			{
				$result['message'] = "Selesai";
				$result['status'] = SYNC_STATUS_DONE;
			}

		}

		echo json_encode($result);
	}
	
	
	/**
	 * GET /sync/nilai/
	 */
	function nilai()
	{
		// Ambil jumlah nilai di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_NILAI, null);
		$jumlah['feeder'] = $response['result'];
		
		// Ambil data nilai
		$sql_nilai_raw = file_get_contents(APPPATH.'models/sql/nilai.sql');
		$sql_nilai = strtr($sql_nilai_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$data_set = $this->rdb->QueryToArray($sql_nilai);
		
		$jumlah['langitan'] = $data_set[0]['JUMLAH'];
		$jumlah['linked'] = $data_set[1]['JUMLAH'];
		$jumlah['update'] = $data_set[2]['JUMLAH'];
		$jumlah['insert'] = $data_set[3]['JUMLAH'];
		
		$this->smarty->assign('jumlah', $jumlah);
		
		// Ambil program studi
		$sql_program_studi_raw = file_get_contents(APPPATH.'models/sql/program-studi.sql');
		$sql_program_studi = strtr($sql_program_studi_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$program_studi_set = $this->rdb->QueryToArray($sql_program_studi);
		$this->smarty->assign('program_studi_set', $program_studi_set);
		
		$this->smarty->assign('url_sync', site_url('sync/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync/'.$this->uri->segment(2).'.tpl');
	}
	
	/**
	 * Ajax-GET /sync/nilai_data/
	 * @param string $kode_prodi Kode program studi versi Feeder
	 * @param string $id_smt Kode semester versi Feeder
	 */
	function nilai_data($kode_prodi, $id_smt, $id_kelas_mk)
	{
		// Konversi id_kelas_mk ke id_kls feeder
		$kelas_langitan = $this->rdb->QueryToArray(
			"SELECT fd_id_kls as id_kls FROM kelas_mk kmk
			WHERE kmk.id_kelas_mk = {$id_kelas_mk}");
		$id_kls = $kelas_langitan[0]['ID_KLS'];
		
		// Ambil peserta kelas di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_NILAI, "p.id_kls = '{$id_kls}'");
		$jumlah['feeder'] = $response['result'];
		
		// Ambil data nilai
		$sql_nilai_raw = file_get_contents(APPPATH.'models/sql/nilai-data.sql');
		$sql_nilai = strtr($sql_nilai_raw, array('@id_kelas_mk' => $id_kelas_mk));
		$data_set = $this->rdb->QueryToArray($sql_nilai);
			
		$jumlah['langitan'] = $data_set[0]['JUMLAH'];
		$jumlah['linked'] = $data_set[1]['JUMLAH'];
		$jumlah['update'] = $data_set[2]['JUMLAH'];
		$jumlah['insert'] = $data_set[3]['JUMLAH'];
		
		echo json_encode($jumlah);
	}
	
	public function nilai_per_prodi()
	{
		// Ambil jumlah nilai di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_NILAI, null);
		$jumlah['feeder'] = $response['result'];
		
		// Ambil data nilai
		$sql_nilai_raw = file_get_contents(APPPATH.'models/sql/nilai.sql');
		$sql_nilai = strtr($sql_nilai_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$data_set = $this->rdb->QueryToArray($sql_nilai);
		
		$jumlah['langitan'] = $data_set[0]['JUMLAH'];
		$jumlah['linked'] = $data_set[1]['JUMLAH'];
		$jumlah['update'] = $data_set[2]['JUMLAH'];
		$jumlah['insert'] = $data_set[3]['JUMLAH'];
		$jumlah['delete'] = $data_set[4]['JUMLAH'];
		
		$this->smarty->assign('jumlah', $jumlah);
		
		// Ambil program studi
		$sql_program_studi_raw = file_get_contents(APPPATH.'models/sql/program-studi.sql');
		$sql_program_studi = strtr($sql_program_studi_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$program_studi_set = $this->rdb->QueryToArray($sql_program_studi);
		$this->smarty->assign('program_studi_set', $program_studi_set);
		
		$this->smarty->assign('url_sync', site_url('sync/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync/'.$this->uri->segment(2).'.tpl');
	}
	
	public function nilai_transfer()
	{
		$jumlah['feeder'] = '-';
		
		$sql_nilai_transfer_raw = file_get_contents(APPPATH.'models/sql/nilai-transfer.sql');
		$sql_nilai_transfer = strtr($sql_nilai_transfer_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$data_set = $this->rdb->QueryToArray($sql_nilai_transfer);
		
		$jumlah['langitan'] = $data_set[0]['JUMLAH'];
		$jumlah['linked'] = $data_set[1]['JUMLAH'];
		$jumlah['update'] = $data_set[2]['JUMLAH'];
		$jumlah['insert'] = $data_set[3]['JUMLAH'];
		$jumlah['delete'] = '';
		
		$this->smarty->assign('jumlah', $jumlah);
		
		$this->smarty->assign('url_sync', site_url('sync/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync/'.$this->uri->segment(2).'.tpl');
	}
	
	public function nilai_transfer_umaha()
	{
		$jumlah['feeder'] = '-';
		
		// Ambil data nilai transfer
		$sql_nilai_raw = file_get_contents(APPPATH.'models/sql/nilai-transfer-umaha.sql');
		$data_set = $this->rdb->QueryToArray($sql_nilai_raw);
		
		$jumlah['langitan'] = $data_set[0]['JUMLAH'];
		$jumlah['linked'] = $data_set[1]['JUMLAH'];
		$jumlah['update'] = $data_set[2]['JUMLAH'];
		$jumlah['insert'] = $data_set[3]['JUMLAH'];
		$jumlah['delete'] = 0;

		$this->smarty->assign('jumlah', $jumlah);
		
		$this->smarty->assign('url_sync', site_url('sync/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync/'.$this->uri->segment(2).'.tpl');
	}
	
	private function proses_nilai()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		// -----------------------------------
		// Ambil data untuk Insert
		// -----------------------------------
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{
			// Parameter
			$id_kelas_mk = $this->input->post('id_kelas_mk');
			
			// Ambil data nilai peserta kuliah yg akan insert
			$sql_nilai_raw = file_get_contents(APPPATH.'models/sql/nilai-insert.sql');
			$sql_nilai = strtr($sql_nilai_raw, array('@id_kelas_mk' => $id_kelas_mk));
			$data_set = $this->rdb->QueryToArray($sql_nilai);
			
			$this->session->set_userdata('data_insert_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Entri. Jumlah data: ' . count($data_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_2;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Update
		// -----------------------------------
		else if ($mode == MODE_AMBIL_DATA_LANGITAN_2)
		{
			// Parameter
			$id_kelas_mk = $this->input->post('id_kelas_mk');
			
			// Ambil peserta kuliah yg akan di update
			$sql_nilai_raw = file_get_contents(APPPATH.'models/sql/nilai-update.sql');
			$sql_nilai = strtr($sql_nilai_raw, array('@id_kelas_mk' => $id_kelas_mk));
			$data_set = $this->rdb->QueryToArray($sql_nilai);
			
			$this->session->set_userdata('data_update_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Update. Jumlah data: ' . count($data_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Proses Sinkronisasi dari data yg sudah diambil
		// -----------------------------------
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil dari cache
			$data_insert_set = $this->session->userdata('data_insert_set');
			$jumlah_insert = count($data_insert_set);
			
			// Ambil dari cache
			$data_update_set = $this->session->userdata('data_update_set');
			$jumlah_update = count($data_update_set);
			
			// Waktu Sinkronisasi
			$time_sync = date('Y-m-d H:i:s');
			
			// --------------------------------
			// Proses Insert
			// --------------------------------
			if ($index_proses < $jumlah_insert)
			{
				// Proses dalam bentuk key lowercase
				$data_insert = array_change_key_case($data_insert_set[$index_proses], CASE_LOWER);
				
				// Simpan mhs untuk tampilan sync
				$id_pengambilan_mk	= $data_insert['id_pengambilan_mk'];
				$mhs				= $data_insert['mhs'];
				$nilai_huruf		= $data_insert['nilai_huruf'];
				$nilai_angka		= $data_insert['nilai_angka'];
				$nilai_indeks		= $data_insert['nilai_indeks'];
				
				// Hilangkan mhs dari array
				unset($data_insert['id_pengambilan_mk']);
				unset($data_insert['mhs']);
				
				// Cleansing data
				if ($data_insert['nilai_huruf'] == '') { unset($data_insert['nilai_huruf']); }
				if ($data_insert['nilai_angka'] == '') { unset($data_insert['nilai_angka']); }
				
				// Entri ke Feeder nilai
				$insert_result = $this->feeder->InsertRecord($this->token, FEEDER_NILAI, json_encode($data_insert));
				
				// Jika berhasil insert, terdapat return id_kls & id_reg_pd
				if (isset($insert_result['result']['id_kls']) && isset($insert_result['result']['id_reg_pd']))
				{
					// Pesan Insert, tampilkan nama mahasiswa
					$result['message'] = ($index_proses + 1) . " Insert {$mhs} Nilai = {$nilai_angka} ({$nilai_huruf} : $nilai_indeks) : Berhasil";
					
					// Update status sync
					$this->rdb->Query("UPDATE pengambilan_mk SET fd_id_kls = '{$insert_result['result']['id_kls']}', fd_id_reg_pd = '{$insert_result['result']['id_reg_pd']}', fd_sync_on = sysdate WHERE id_pengambilan_mk = {$id_pengambilan_mk}");
				}
				else // saat insert nilai gagal
				{
					// Pesan insert jika gagal
					$result['message'] = ($index_proses + 1) . " Insert {$mhs} Nilai = {$nilai_angka} ({$nilai_huruf} : $nilai_indeks) : Gagal. " . json_encode($insert_result['result']);
					$result['message'] .= "\n" . json_encode($data_insert);
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Update
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update))
			{
				// index berjalan dikurangi jumlah data insert utk mendapatkan index update
				$index_proses -= $jumlah_insert;
				
				// Proses dalam bentuk key lowercase
				$data_update = array_change_key_case($data_update_set[$index_proses], CASE_LOWER);
				
				// Simpan informasi mahasiswa
				$id_pengambilan_mk	= $data_update['id_pengambilan_mk'];
				$mhs				= $data_update['mhs'];
				$id_kls				= $data_update['id_kls'];
				$id_reg_pd			= $data_update['id_reg_pd'];
				$nilai_huruf		= $data_update['nilai_huruf'];
				$nilai_angka		= $data_update['nilai_angka'];
				$nilai_indeks		= $data_update['nilai_indeks'];
				
				// Hilangkan data tdk diperlukan untuk update
				unset($data_update['id_pengambilan_mk']);
				unset($data_update['mhs']);
				unset($data_update['id_kls']);
				unset($data_update['id_reg_pd']);
				
				// Cleansing data
				if ($data_update['nilai_huruf'] == '') { unset($data_update['nilai_huruf']); }
				if ($data_update['nilai_angka'] == '') { unset($data_update['nilai_angka']); }
				
				// Build data format
				$data_update_json = array(
					'key'	=> array('id_kls' => $id_kls, 'id_reg_pd' => $id_reg_pd),
					'data'	=> $data_update
				);
				
				// Update ke Feeder nilai
				$update_result = $this->feeder->UpdateRecord($this->token, FEEDER_NILAI, json_encode($data_update_json));
				
				// Jika tidak ada masalah update
				if ($update_result['result']['error_code'] == 0)
				{
					$result['message'] = ($index_proses + 1) . " Update {$mhs} Nilai = {$nilai_angka} ({$nilai_huruf} : $nilai_indeks) : Berhasil";
					
					// Update status sync
					$this->rdb->Query("UPDATE pengambilan_mk SET fd_sync_on = sysdate WHERE id_pengambilan_mk = {$id_pengambilan_mk}");
				}
				else
				{
					$result['message'] = ($index_proses + 1) . " Update {$mhs} Nilai: {$nilai_huruf} ({$nilai_angka}) : Gagal. ";
					$result['message'] .= "({$update_result['result']['error_code']}) {$update_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update_json);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert
				$index_proses += $jumlah_insert;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Selesai
			// --------------------------------
			else
			{
				$result['message'] = "Selesai";
				$result['status'] = SYNC_STATUS_DONE;
			}
		}
		
		echo json_encode($result);
	}
	
	private function proses_nilai_per_prodi()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		// -----------------------------------
		// Ambil data untuk Insert
		// -----------------------------------
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{
			// Parameter
			$kode_prodi = $this->input->post('kode_prodi');
			$id_smt		= $this->input->post('semester');
			
			// Konversi format semester ke id_semester
			$sql_semester_raw = file_get_contents(APPPATH.'models/sql/semester-konversi.sql');
			$sql_semester = strtr($sql_semester_raw, array('@npsn' => $this->satuan_pendidikan['npsn'], '@id_smt' => $id_smt));
			$semester_langitan = $this->rdb->QueryToArray($sql_semester);
			$id_semester = $semester_langitan[0]['ID_SEMESTER'];
			
			// Ambil data nilai peserta kuliah yg akan insert
			$sql_nilai_prodi_raw = file_get_contents(APPPATH.'models/sql/nilai-per-prodi-insert.sql');
			$sql_nilai_prodi = strtr($sql_nilai_prodi_raw, array(
				'@npsn' => $this->satuan_pendidikan['npsn'],
				'@kode_prodi' => $kode_prodi,
				'@smt' => $id_semester
			));
			$data_set = $this->rdb->QueryToArray($sql_nilai_prodi);
			
			$this->session->set_userdata('data_insert_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Entri. Jumlah data: ' . count($data_set) ;
			$result['status'] = SYNC_STATUS_PROSES;
			//$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Entri. Jumlah data: ' . print_r($data_set);
			//$result['status'] = SYNC_STATUS_DONE;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_2;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Update
		// -----------------------------------
		else if ($mode == MODE_AMBIL_DATA_LANGITAN_2)
		{
			// Parameter
			$kode_prodi = $this->input->post('kode_prodi');
			$id_smt		= $this->input->post('semester');
			
			// Konversi format semester ke id_semester
			$sql_semester_raw = file_get_contents(APPPATH.'models/sql/semester-konversi.sql');
			$sql_semester = strtr($sql_semester_raw, array('@npsn' => $this->satuan_pendidikan['npsn'], '@id_smt' => $id_smt));
			$semester_langitan = $this->rdb->QueryToArray($sql_semester);
			$id_semester = $semester_langitan[0]['ID_SEMESTER'];
			
			// Ambil peserta kuliah yg akan di update
			$sql_nilai_raw = file_get_contents(APPPATH.'models/sql/nilai-per-prodi-update.sql');
			$sql_nilai = strtr($sql_nilai_raw, array(
				'@npsn' => $this->satuan_pendidikan['npsn'],
				'@kode_prodi' => $kode_prodi,
				'@smt' => $id_semester
			));
			$data_set = $this->rdb->QueryToArray($sql_nilai);
			
			$this->session->set_userdata('data_update_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Update. Jumlah data: ' . count($data_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_3;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Delete
		// -----------------------------------
		else if ($mode == MODE_AMBIL_DATA_LANGITAN_3)
		{
			// Parameter
			$kode_prodi = $this->input->post('kode_prodi');
			$id_smt		= $this->input->post('semester');
			
			// Konversi format semester ke id_semester
			$sql_semester_raw = file_get_contents(APPPATH.'models/sql/semester-konversi.sql');
			$sql_semester = strtr($sql_semester_raw, array('@npsn' => $this->satuan_pendidikan['npsn'], '@id_smt' => $id_smt));
			$semester_langitan = $this->rdb->QueryToArray($sql_semester);
			$id_semester = $semester_langitan[0]['ID_SEMESTER'];
			
			// Ambil peserta kuliah yg akan di update
			$sql_nilai_raw = file_get_contents(APPPATH.'models/sql/nilai-per-prodi-delete.sql');
			$sql_nilai = strtr($sql_nilai_raw, array(
				'@npsn' => $this->satuan_pendidikan['npsn'],
				'@kode_prodi' => $kode_prodi,
				'@smt' => $id_semester
			));
			$data_set = $this->rdb->QueryToArray($sql_nilai);
			
			$this->session->set_userdata('data_delete_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Delete. Jumlah data: ' . count($data_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Proses Sinkronisasi dari data yg sudah diambil
		// -----------------------------------
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil data insert
			$data_insert_set = $this->session->userdata('data_insert_set');
			$jumlah_insert = count($data_insert_set);
			
			// Ambil data update
			$data_update_set = $this->session->userdata('data_update_set');
			$jumlah_update = count($data_update_set);
			
			// Ambil data delete
			$data_delete_set = $this->session->userdata('data_delete_set');
			$jumlah_delete = count($data_delete_set);
			
			// --------------------------------
			// Proses Insert
			// --------------------------------
			if ($index_proses < $jumlah_insert)
			{
				// Proses dalam bentuk key lowercase
				$data_insert = array_change_key_case($data_insert_set[$index_proses], CASE_LOWER);
				
				// Simpan mhs untuk tampilan sync
				$id_pengambilan_mk	= $data_insert['id_pengambilan_mk'];
				$mhs				= $data_insert['mhs'];
				$nilai_huruf		= $data_insert['nilai_huruf'];
				$nilai_angka		= $data_insert['nilai_angka'];
				$nilai_indeks		= $data_insert['nilai_indeks'];
				$nama_kelas			= $data_insert['nama_kelas'];
				
				// Hilangkan mhs dari array
				unset($data_insert['id_pengambilan_mk']);
				unset($data_insert['mhs']);
				unset($data_insert['nama_kelas']);
				
				// Cleansing data
				if ($data_insert['nilai_huruf'] == '') { unset($data_insert['nilai_huruf']); }
				if ($data_insert['nilai_angka'] == '') { unset($data_insert['nilai_angka']); }
				
				// Entri ke Feeder nilai
				$insert_result = $this->feeder->InsertRecord($this->token, FEEDER_NILAI, json_encode($data_insert));
				
				// Jika berhasil insert, terdapat return id_kls & id_reg_pd
				if (isset($insert_result['result']['id_kls']) && isset($insert_result['result']['id_reg_pd']))
				{
					// Pesan Insert, tampilkan nama mahasiswa
					$result['message'] = ($index_proses + 1) . " Insert {$nama_kelas} {$mhs} Nilai = {$nilai_angka} ({$nilai_huruf} : $nilai_indeks) : Berhasil";
					
					// Update status sync
					$this->rdb->Query("UPDATE pengambilan_mk SET fd_id_kls = '{$insert_result['result']['id_kls']}', fd_id_reg_pd = '{$insert_result['result']['id_reg_pd']}', fd_sync_on = sysdate WHERE id_pengambilan_mk = {$id_pengambilan_mk}");
				}
				else // saat insert nilai gagal
				{
					// error_code 800 : Data nilai dari id_kelas_kuliah dan id_registrasi_mahasiswa ini sudah ada
					if ($insert_result['result']['error_code'] == 800)
					{
						// Pesan data sudah ada
						$result['message'] = ($index_proses + 1) . " Insert {$nama_kelas} {$mhs} Nilai = {$nilai_angka} ({$nilai_huruf} : $nilai_indeks) : Gagal insert. {$insert_result['result']['error_desc']}. Lakukan Sinkronisasi Ulang pada Prodi / Kelas ini.";
						
						// Update status sync di langitan, agar bisa di update untuk sinkron ulang
						$this->rdb->Query("UPDATE pengambilan_mk SET fd_id_kls = '{$data_insert['id_kls']}', fd_id_reg_pd = '{$data_insert['id_reg_pd']}', fd_sync_on = created_on, updated_on = sysdate WHERE id_pengambilan_mk = {$id_pengambilan_mk}");
					}
					else
					{
						// Pesan insert jika gagal
						$result['message'] = ($index_proses + 1) . " Insert {$nama_kelas} {$mhs} Nilai = {$nilai_angka} ({$nilai_huruf} : $nilai_indeks) : Gagal. " . json_encode($insert_result['result']);
						$result['message'] .= "\n" . json_encode($data_insert);
					}
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Update
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update))
			{
				// index berjalan dikurangi jumlah data insert utk mendapatkan index update
				$index_proses -= $jumlah_insert;
				
				// Proses dalam bentuk key lowercase
				$data_update = array_change_key_case($data_update_set[$index_proses], CASE_LOWER);
				
				// Simpan informasi mahasiswa
				$id_pengambilan_mk	= $data_update['id_pengambilan_mk'];
				$mhs				= $data_update['mhs'];
				$id_kls				= $data_update['id_kls'];
				$id_reg_pd			= $data_update['id_reg_pd'];
				$nilai_huruf		= $data_update['nilai_huruf'];
				$nilai_angka		= $data_update['nilai_angka'];
				$nilai_indeks		= $data_update['nilai_indeks'];
				$nama_kelas			= $data_update['nama_kelas'];
				
				// Hilangkan data tdk diperlukan untuk update
				unset($data_update['id_pengambilan_mk']);
				unset($data_update['mhs']);
				unset($data_update['id_kls']);
				unset($data_update['id_reg_pd']);
				unset($data_update['nama_kelas']);
				
				// Cleansing data
				if ($data_update['nilai_huruf'] == '') { unset($data_update['nilai_huruf']); }
				if ($data_update['nilai_angka'] == '') { unset($data_update['nilai_angka']); }
				
				// Build data format
				$data_update_json = array(
					'key'	=> array('id_kls' => $id_kls, 'id_reg_pd' => $id_reg_pd),
					'data'	=> $data_update
				);
				
				// Update ke Feeder nilai
				$update_result = $this->feeder->UpdateRecord($this->token, FEEDER_NILAI, json_encode($data_update_json));
				
				// Jika tidak ada masalah update
				if ($update_result['result']['error_code'] == 0)
				{
					$result['message'] = ($index_proses + 1) . " Update {$nama_kelas} {$mhs} Nilai = {$nilai_angka} ({$nilai_huruf} : $nilai_indeks) : Berhasil";
					
					// Update status sync
					$this->rdb->Query("UPDATE pengambilan_mk SET fd_sync_on = sysdate WHERE id_pengambilan_mk = {$id_pengambilan_mk}");
				}
				else
				{
					$result['message'] = ($index_proses + 1) . " Update {$mhs} Nilai: {$nilai_huruf} ({$nilai_angka}) : Gagal. ";
					$result['message'] .= "({$update_result['result']['error_code']}) {$update_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update_json);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert
				$index_proses += $jumlah_insert;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Delete
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update + $jumlah_delete))
			{
				// index berjalan dikurangi jumlah data insert+update utk mendapatkan index delete
				$index_proses -= ($jumlah_insert + $jumlah_update);
				
				// Proses dalam bentuk key lowercase
				$data_delete = array_change_key_case($data_delete_set[$index_proses], CASE_LOWER);
				
				// Simpan informasi mahasiswa
				$id_pengambilan_mk	= $data_delete['id_pengambilan_mk'];
				$mhs				= $data_delete['mhs'];
				$id_kls				= $data_delete['id_kls'];
				$id_reg_pd			= $data_delete['id_reg_pd'];
				$nilai_huruf		= $data_delete['nilai_huruf'];
				$nilai_angka		= $data_delete['nilai_angka'];
				$nilai_indeks		= $data_delete['nilai_indeks'];
				$nama_kelas			= $data_delete['nama_kelas'];
				
				// Hilangkan data tdk diperlukan untuk delete
				unset($data_delete['id_pengambilan_mk']);
				unset($data_delete['mhs']);
				unset($data_delete['id_kls']);
				unset($data_delete['id_reg_pd']);
				unset($data_delete['nama_kelas']);
				
				// Build data format
				$data_delete_json = array(
					'id_kls' => $id_kls, 
					'id_reg_pd' => $id_reg_pd
				);
				
				// Delete ke Feeder nilai
				$delete_result = $this->feeder->DeleteRecord($this->token, FEEDER_NILAI, json_encode($data_delete_json));
				
				// Jika tidak ada masalah delete
				if ($delete_result['result']['error_code'] == 0)
				{
					$result['message'] = ($index_proses + 1) . " Delete {$nama_kelas} {$mhs} Nilai = {$nilai_angka} ({$nilai_huruf} : $nilai_indeks) : Berhasil";
					
					// Update status sync di set null
					$this->rdb->Query("UPDATE pengambilan_mk_del SET fd_sync_on = NULL WHERE id_pengambilan_mk = {$id_pengambilan_mk}");
				}
				else
				{
					$result['message'] = ($index_proses + 1) . " Delete {$mhs} Nilai: {$nilai_huruf} ({$nilai_angka}) : Gagal. ";
					$result['message'] .= "({$update_result['result']['error_code']}) {$update_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update_json);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert+update
				$index_proses += ($jumlah_insert + $jumlah_update);
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Selesai
			// --------------------------------
			else
			{
				$result['message'] = "Selesai";
				$result['status'] = SYNC_STATUS_DONE;
			}
		}
		
		echo json_encode($result);
	}
	
	private function proses_nilai_transfer()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		// -----------------------------------
		// Ambil data untuk Insert
		// -----------------------------------
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{
			// Ambil nilai transfer yang akan di insert
			$sql_nilai_transfer_raw = file_get_contents(APPPATH.'models/sql/nilai-transfer-insert.sql');
			$sql_nilai_transfer = strtr($sql_nilai_transfer_raw, array(
				'@npsn' => $this->satuan_pendidikan['npsn'],
			));
			$data_set = $this->rdb->QueryToArray($sql_nilai_transfer);
			
			$this->session->set_userdata('data_insert_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Entri. Jumlah data: ' . count($data_set) ;
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_2;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Update
		// -----------------------------------
		else if ($mode == MODE_AMBIL_DATA_LANGITAN_2)
		{
			// Ambil nilai transfer yang akan di insert
			$sql_nilai_transfer_raw = file_get_contents(APPPATH.'models/sql/nilai-transfer-update.sql');
			$sql_nilai_transfer = strtr($sql_nilai_transfer_raw, array(
				'@npsn' => $this->satuan_pendidikan['npsn'],
			));
			$data_set = $this->rdb->QueryToArray($sql_nilai_transfer);
			
			$this->session->set_userdata('data_update_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Update. Jumlah data: ' . count($data_set) ;
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Proses Sinkronisasi dari data yg sudah diambil
		// -----------------------------------
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil data insert
			$data_insert_set = $this->session->userdata('data_insert_set');
			$jumlah_insert = count($data_insert_set);
			
			// Ambil data update
			$data_update_set = $this->session->userdata('data_update_set');
			$jumlah_update = count($data_update_set);
			
			// --------------------------------
			// Proses Insert
			// --------------------------------
			if ($index_proses < $jumlah_insert)
			{
				// Proses dalam bentuk key lowercase
				$data_insert = array_change_key_case($data_insert_set[$index_proses], CASE_LOWER);
				
				// Simpan untuk tampilan sync
				$id_pengambilan_mk	= $data_insert['id_pengambilan_mk'];
				$mhs				= $data_insert['mhs'];
				$kode_mk_asal		= $data_insert['kode_mk_asal'];
				$nm_mk_asal			= $data_insert['nm_mk_asal'];
				$sks_asal			= $data_insert['sks_asal'];
				$sks_diakui			= $data_insert['sks_diakui'];
				$nilai_huruf_asal	= $data_insert['nilai_huruf_asal'];
				$nilai_huruf_diakui	= $data_insert['nilai_huruf_diakui'];
				
				// Hilangkan variabel tak dibutuhkan
				unset($data_insert['id_pengambilan_mk']);
				unset($data_insert['mhs']);
				
				// Entri ke Feeder nilai transfer
				$insert_result = $this->feeder->InsertRecord($this->token, FEEDER_NILAI_TRANSFER, json_encode($data_insert));
				
				// Jika berhasil insert, terdapat return id_ekuivalensi
				if (isset($insert_result['result']['id_ekuivalensi']))
				{
					// Pesan Insert, tampilkan nama mahasiswa
					$result['message'] = ($index_proses + 1) . " Insert {$mhs} {$kode_mk_asal} {$nm_mk_asal} ({$sks_asal} SKS) = {$nilai_huruf_asal} diakui ({$sks_diakui} SKS) {$nilai_huruf_diakui} : Berhasil";
					
					// Update status sync
					$this->rdb->Query("UPDATE pengambilan_mk SET fd_id_ekuivalensi = '{$insert_result['result']['id_ekuivalensi']}', fd_sync_on = sysdate WHERE id_pengambilan_mk = {$id_pengambilan_mk}");
				}
				else // saat insert nilai transfer gagal
				{
					// Pesan insert jika gagal
					$result['message'] = ($index_proses + 1) . " Insert {$mhs} {$kode_mk_asal} {$nm_mk_asal} ({$sks_asal} SKS) = {$nilai_huruf_asal} diakui ({$sks_diakui} SKS) {$nilai_huruf_diakui} : Gagal. " . json_encode($insert_result['result']);
					$result['message'] .= "\n" . json_encode($data_insert);
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Update
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update))
			{
				// index berjalan dikurangi jumlah data insert utk mendapatkan index update
				$index_proses -= $jumlah_insert;
				
				// Proses dalam bentuk key lowercase
				$data_update = array_change_key_case($data_update_set[$index_proses], CASE_LOWER);
				
				// Simpan untuk tampilan sync
				$id_pengambilan_mk	= $data_update['id_pengambilan_mk'];
				$mhs				= $data_update['mhs'];
				$kode_mk_asal		= $data_update['kode_mk_asal'];
				$nm_mk_asal			= $data_update['nm_mk_asal'];
				$sks_asal			= $data_update['sks_asal'];
				$sks_diakui			= $data_update['sks_diakui'];
				$nilai_huruf_asal	= $data_update['nilai_huruf_asal'];
				$nilai_huruf_diakui	= $data_update['nilai_huruf_diakui'];
				$id_ekuivalensi		= $data_update['id_ekuivalensi'];
				
				// Hilangkan variabel tak dibutuhkan
				unset($data_update['id_pengambilan_mk']);
				unset($data_update['mhs']);
				unset($data_update['id_ekuivalensi']);
				
				// Build data format
				$data_update_json = array(
					'key'	=> array('id_ekuivalensi' => $id_ekuivalensi),
					'data'	=> $data_update
				);
				
				// Update ke Feeder Nilai Transfer
				$update_result = $this->feeder->UpdateRecord($this->token, FEEDER_NILAI_TRANSFER, json_encode($data_update_json));
				
				// Jika tidak ada masalah update
				if ($update_result['result']['error_code'] == 0)
				{
					$result['message'] = ($index_proses + 1) . " Update {$mhs} {$kode_mk_asal} {$nm_mk_asal} ({$sks_asal} SKS) = {$nilai_huruf_asal} diakui ({$sks_diakui} SKS) {$nilai_huruf_diakui} : Berhasil";
					
					// Update status sync
					$this->rdb->Query("UPDATE pengambilan_mk SET fd_sync_on = sysdate WHERE id_pengambilan_mk = {$id_pengambilan_mk}");
				}
				else
				{
					$result['message'] = ($index_proses + 1) . " Update {$mhs} {$kode_mk_asal} {$nm_mk_asal} ({$sks_asal} SKS) = {$nilai_huruf_asal} diakui ({$sks_diakui} SKS) {$nilai_huruf_diakui} : Gagal. ";
					$result['message'] .= "({$update_result['result']['error_code']}) {$update_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update_json);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert
				$index_proses += $jumlah_insert;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Selesai
			// --------------------------------
			else
			{
				$result['message'] = "Selesai";
				$result['status'] = SYNC_STATUS_DONE;
			}
		}
		
		echo json_encode($result);
	}
	
	private function proses_nilai_transfer_umaha()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		// -----------------------------------
		// Ambil data untuk Insert
		// -----------------------------------
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{						
			// Ambil data nilai transfer kuliah yg akan insert
			$sql_nilai_transfer_umaha_raw = file_get_contents(APPPATH.'models/sql/nilai-transfer-umaha-insert.sql');
			$data_set = $this->rdb->QueryToArray($sql_nilai_transfer_umaha_raw);
			
			$this->session->set_userdata('data_insert_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Entri. Jumlah data: ' . count($data_set) ;
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_2;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Update
		// -----------------------------------
		else if ($mode == MODE_AMBIL_DATA_LANGITAN_2)
		{
			// Ambil peserta kuliah yg akan di update
			$sql_nilai_transfer_umaha_raw = file_get_contents(APPPATH.'models/sql/nilai-transfer-umaha-update.sql');
			$data_set = $this->rdb->QueryToArray($sql_nilai_transfer_umaha_raw);
			
			$this->session->set_userdata('data_update_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Update. Jumlah data: ' . count($data_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_3;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Delete
		// -----------------------------------
		else if ($mode == MODE_AMBIL_DATA_LANGITAN_3)
		{
			// Ambil peserta kuliah yg akan di update
			$sql_nilai_transfer_umaha_raw = file_get_contents(APPPATH.'models/sql/nilai-transfer-umaha-delete.sql');
			$data_set = $this->rdb->QueryToArray($sql_nilai_transfer_umaha_raw);
			
			$this->session->set_userdata('data_delete_set', $data_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Delete. Jumlah data: ' . count($data_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Proses Sinkronisasi dari data yg sudah diambil
		// -----------------------------------
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil data insert
			$data_insert_set = $this->session->userdata('data_insert_set');
			$jumlah_insert = count($data_insert_set);
			
			// Ambil data update
			$data_update_set = $this->session->userdata('data_update_set');
			$jumlah_update = count($data_update_set);
			
			// Ambil data delete
			$data_delete_set = $this->session->userdata('data_delete_set');
			$jumlah_delete = count($data_delete_set);
			
			// --------------------------------
			// Proses Insert
			// --------------------------------
			if ($index_proses < $jumlah_insert)
			{
				// Proses dalam bentuk key lowercase
				$data_insert = array_change_key_case($data_insert_set[$index_proses], CASE_LOWER);
				
				// Simpan untuk tampilan sync
				$id_pengambilan_mk	= $data_insert['id_pengambilan_mk'];
				$mhs				= $data_insert['mhs'];
				$kode_mk_asal		= $data_insert['kode_mk_asal'];
				$nm_mk_asal			= $data_insert['nm_mk_asal'];
				$sks_asal			= $data_insert['sks_asal'];
				$sks_diakui			= $data_insert['sks_diakui'];
				$nilai_huruf_asal	= $data_insert['nilai_huruf_asal'];
				$nilai_huruf_diakui	= $data_insert['nilai_huruf_diakui'];
				
				// Hilangkan variabel tak dibutuhkan
				unset($data_insert['id_pengambilan_mk']);
				unset($data_insert['mhs']);
				
				// Entri ke Feeder nilai transfer
				$insert_result = $this->feeder->InsertRecord($this->token, FEEDER_NILAI_TRANSFER, json_encode($data_insert));
				
				// Jika berhasil insert, terdapat return id_ekuivalensi
				if (isset($insert_result['result']['id_ekuivalensi']))
				{
					// Pesan Insert, tampilkan nama mahasiswa
					$result['message'] = ($index_proses + 1) . " Insert {$mhs} {$kode_mk_asal} {$nm_mk_asal} ({$sks_asal} SKS) = {$nilai_huruf_asal} diakui ({$sks_diakui} SKS) {$nilai_huruf_diakui} : Berhasil";
					
					// Update status sync
					$this->rdb->Query("UPDATE pengambilan_mk SET fd_id_ekuivalensi = '{$insert_result['result']['id_ekuivalensi']}', fd_sync_on = sysdate WHERE id_pengambilan_mk = {$id_pengambilan_mk}");
				}
				else // saat insert nilai transfer gagal
				{
					// Pesan insert jika gagal
					$result['message'] = ($index_proses + 1) . " Insert {$mhs} {$kode_mk_asal} {$nm_mk_asal} ({$sks_asal} SKS) = {$nilai_huruf_asal} diakui ({$sks_diakui} SKS) {$nilai_huruf_diakui} : Gagal. " . json_encode($insert_result['result']);
					$result['message'] .= "\n" . json_encode($data_insert);
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Update
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update))
			{
				// index berjalan dikurangi jumlah data insert utk mendapatkan index update
				$index_proses -= $jumlah_insert;
				
				// Proses dalam bentuk key lowercase
				$data_update = array_change_key_case($data_update_set[$index_proses], CASE_LOWER);
				
				// Simpan untuk tampilan sync
				$id_pengambilan_mk	= $data_update['id_pengambilan_mk'];
				$mhs				= $data_update['mhs'];
				$kode_mk_asal		= $data_update['kode_mk_asal'];
				$nm_mk_asal			= $data_update['nm_mk_asal'];
				$sks_asal			= $data_update['sks_asal'];
				$sks_diakui			= $data_update['sks_diakui'];
				$nilai_huruf_asal	= $data_update['nilai_huruf_asal'];
				$nilai_huruf_diakui	= $data_update['nilai_huruf_diakui'];
				$id_ekuivalensi		= $data_update['id_ekuivalensi'];
				
				// Hilangkan variabel tak dibutuhkan
				unset($data_update['id_pengambilan_mk']);
				unset($data_update['mhs']);
				unset($data_update['id_ekuivalensi']);
				
				// Build data format
				$data_update_json = array(
					'key'	=> array('id_ekuivalensi' => $id_ekuivalensi),
					'data'	=> $data_update
				);
				
				// Update ke Feeder Nilai Transfer
				$update_result = $this->feeder->UpdateRecord($this->token, FEEDER_NILAI_TRANSFER, json_encode($data_update_json));
				
				// Jika tidak ada masalah update
				if ($update_result['result']['error_code'] == 0)
				{
					$result['message'] = ($index_proses + 1) . " Update {$mhs} {$kode_mk_asal} {$nm_mk_asal} ({$sks_asal} SKS) = {$nilai_huruf_asal} diakui ({$sks_diakui} SKS) {$nilai_huruf_diakui} : Berhasil";
					
					// Update status sync
					$this->rdb->Query("UPDATE pengambilan_mk SET fd_sync_on = sysdate WHERE id_pengambilan_mk = {$id_pengambilan_mk}");
				}
				else
				{
					$result['message'] = ($index_proses + 1) . " Update {$mhs} {$kode_mk_asal} {$nm_mk_asal} ({$sks_asal} SKS) = {$nilai_huruf_asal} diakui ({$sks_diakui} SKS) {$nilai_huruf_diakui} : Gagal. ";
					$result['message'] .= "({$update_result['result']['error_code']}) {$update_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update_json);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert
				$index_proses += $jumlah_insert;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Delete
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update + $jumlah_delete))
			{
				// index berjalan dikurangi jumlah data insert+update utk mendapatkan index delete
				$index_proses -= ($jumlah_insert + $jumlah_update);
				
				// Proses dalam bentuk key lowercase
				$data_delete = array_change_key_case($data_delete_set[$index_proses], CASE_LOWER);
				
				// Simpan untuk tampilan sync
				$id_pengambilan_mk	= $data_delete['id_pengambilan_mk'];
				$mhs				= $data_delete['mhs'];
				$kode_mk_asal		= $data_delete['kode_mk_asal'];
				$nm_mk_asal			= $data_delete['nm_mk_asal'];
				$sks_asal			= $data_delete['sks_asal'];
				$sks_diakui			= $data_delete['sks_diakui'];
				$nilai_huruf_asal	= $data_delete['nilai_huruf_asal'];
				$nilai_huruf_diakui	= $data_delete['nilai_huruf_diakui'];
				$id_ekuivalensi		= $data_delete['id_ekuivalensi'];
				
				// Hilangkan variabel tak dibutuhkan
				unset($data_update['id_pengambilan_mk']);
				unset($data_update['mhs']);
				
				// Build data format
				$data_delete_json = array(
					'id_ekuivalensi' => $id_ekuivalensi
				);
				
				// Delete ke Feeder nilai transfer
				$delete_result = $this->feeder->DeleteRecord($this->token, FEEDER_NILAI_TRANSFER, json_encode($data_delete_json));
				
				// Jika tidak ada masalah delete
				if ($delete_result['result']['error_code'] == 0)
				{
					$result['message'] = ($index_proses + 1) . " Delete {$mhs} {$kode_mk_asal} {$nm_mk_asal} ({$sks_asal} SKS) = {$nilai_huruf_asal} diakui ({$sks_diakui} SKS) {$nilai_huruf_diakui} : Berhasil";
					
					// Update status sync di set null
					$this->rdb->Query("UPDATE pengambilan_mk_del SET fd_sync_on = NULL WHERE id_pengambilan_mk = {$id_pengambilan_mk}");
				}
				else
				{
					$result['message'] = ($index_proses + 1) . " Delete {$mhs} {$kode_mk_asal} {$nm_mk_asal} ({$sks_asal} SKS) = {$nilai_huruf_asal} diakui ({$sks_diakui} SKS) {$nilai_huruf_diakui} : Gagal. ";
					$result['message'] .= "({$update_result['result']['error_code']}) {$update_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update_json);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert+update
				$index_proses += ($jumlah_insert + $jumlah_update);
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Selesai
			// --------------------------------
			else
			{
				$result['message'] = "Selesai";
				$result['status'] = SYNC_STATUS_DONE;
			}
		}
		
		echo json_encode($result);
	}
	
	/**
	 * Ajax-GET /sync/ambil_kelas/ Ambil kelas untuk nilai perkuliahan
	 * @param string $kode_prodi Kode program studi versi Feeder
	 * @param string $id_smt Kode semester versi Feeder
	 */
	function ambil_kelas($kode_prodi, $id_smt)
	{
		// Konversi format semester ke id_semester
		$sql_semester_raw = file_get_contents(APPPATH.'models/sql/semester-konversi.sql');
		$sql_semester = strtr($sql_semester_raw, array('@npsn' => $this->satuan_pendidikan['npsn'], '@id_smt' => $id_smt));
		$semester_langitan = $this->rdb->QueryToArray($sql_semester);
		$id_semester = $semester_langitan[0]['ID_SEMESTER'];
		
		// Ambil data kelas yang ada di langitan yg sudah ter-sync (sudah ada fd_id_kls di kelas_mk)
		$sql_ambil_kelas_raw = file_get_contents(APPPATH.'models/sql/ambil-kelas-kuliah.sql');
		$sql_ambil_kelas = strtr($sql_ambil_kelas_raw, array('@npsn' => $this->satuan_pendidikan['npsn'], '@kode_prodi' => $kode_prodi, '@smt' => $id_semester));
		$data_set = $this->rdb->QueryToArray($sql_ambil_kelas);
		
		echo json_encode($data_set);
	}
	
	
	/**
	 * GET /sync/kuliah_mahasiswa/
	 */
	function kuliah_mahasiswa()
	{
		$jumlah = array();
		
		// Ambil jumlah kuliah mahasiswa di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_KULIAH_MAHASISWA, null);
		$jumlah['feeder'] = $response['result'];
		
		// SQL jumlah kuliah mahasiswa di sistem langitan & yg sudah link
		$sql_kuliah_mahasiswa = file_get_contents(APPPATH.'models/sql/kuliah-mahasiswa.sql');
		$sql_kuliah_mahasiswa = strtr($sql_kuliah_mahasiswa, array(
			'@npsn' => $this->satuan_pendidikan['npsn']
		));
		
		// Ambil data
		$kuliah_mahasiswa_set = $this->rdb->QueryToArray($sql_kuliah_mahasiswa);
		
		$jumlah['langitan'] = $kuliah_mahasiswa_set[0]['JUMLAH'];
		$jumlah['linked'] = $kuliah_mahasiswa_set[1]['JUMLAH'];
		$jumlah['update'] = $kuliah_mahasiswa_set[2]['JUMLAH'];
		$jumlah['insert'] = $kuliah_mahasiswa_set[3]['JUMLAH'];
		$this->smarty->assign('jumlah', $jumlah);
		
		// Data program studi
		$this->smarty->assign('program_studi_set', $this->langitan_model->list_program_studi($this->satuan_pendidikan['npsn']));
		
		$this->smarty->assign('url_sync', site_url('sync/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync/'.$this->uri->segment(2).'.tpl');
	}
	
	/**
	 * Ajax-GET /sync/kuliah_mahasiswa_data/
	 */
	function kuliah_mahasiswa_data($id_smt)
	{
		// Ambil jumlah kuliah mahasiswa di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_KULIAH_MAHASISWA, "p.id_smt = '{$id_smt}'");
		$jumlah['feeder'] = $response['result'];
		
		// Ambil id semester
		$id_semester = $this->langitan_model->get_id_semester($this->satuan_pendidikan['npsn'], $id_smt);
		
		// SQL jumlah kuliah mahasiswa di Sistem Langitan & yg sudah link
		$sql_kuliah_mahasiswa_data = file_get_contents(APPPATH.'models/sql/kuliah-mahasiswa-data.sql');
		$sql_kuliah_mahasiswa_data = strtr($sql_kuliah_mahasiswa_data, array(
			'@npsn' => $this->satuan_pendidikan['npsn'],
			'@id_semester' => $id_semester
		));

		// Ambil jumlah mahasiswa di Sistem Langitan & yg sudah link
		$kuliah_mahasiswa_set = $this->rdb->QueryToArray($sql_kuliah_mahasiswa_data);
		
		$jumlah['langitan'] = $kuliah_mahasiswa_set[0]['JUMLAH'];
		$jumlah['linked'] = $kuliah_mahasiswa_set[1]['JUMLAH'];
		$jumlah['update'] = $kuliah_mahasiswa_set[2]['JUMLAH'];
		$jumlah['insert'] = $kuliah_mahasiswa_set[3]['JUMLAH'];
		
		echo json_encode($jumlah);
	}
	
	private function proses_kuliah_mahasiswa()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		// -----------------------------------
		// Ambil data untuk Insert
		// -----------------------------------
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{
			$id_smt = $this->input->post('semester');
			
			$sql_kuliah_mahasiswa_insert = file_get_contents(APPPATH.'models/sql/kuliah-mahasiswa-insert.sql');
			$sql_kuliah_mahasiswa_insert = strtr($sql_kuliah_mahasiswa_insert, array(
				'@npsn'		=> $this->satuan_pendidikan['npsn'],
				'@id_smt'	=> $id_smt
			));
			
			$kuliah_mahasiswa_set = $this->rdb->QueryToArray($sql_kuliah_mahasiswa_insert);
			
			$this->session->set_userdata('kuliah_mahasiswa_insert_set', $kuliah_mahasiswa_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Entri. Jumlah data: ' . count($kuliah_mahasiswa_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_2;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Update
		// -----------------------------------
		else if ($mode == MODE_AMBIL_DATA_LANGITAN_2)
		{
			$id_smt = $this->input->post('semester');
			
			$sql_kuliah_mahasiswa_update = file_get_contents(APPPATH.'models/sql/kuliah-mahasiswa-update.sql');
			$sql_kuliah_mahasiswa_update = strtr($sql_kuliah_mahasiswa_update, array(
				'@npsn'		=> $this->satuan_pendidikan['npsn'],
				'@id_smt'	=> $id_smt
			));
			
			$kuliah_mahasiswa_set = $this->rdb->QueryToArray($sql_kuliah_mahasiswa_update);
			
			// simpan ke cache
			$this->session->set_userdata('kuliah_mahasiswa_update_set', $kuliah_mahasiswa_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Update. Jumlah data: ' . count($kuliah_mahasiswa_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		// ----------------------------------------------
		// Proses Sinkronisasi dari data yg sudah diambil
		// ----------------------------------------------
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil dari cache
			$kuliah_mahasiswa_insert_set = $this->session->userdata('kuliah_mahasiswa_insert_set');
			$jumlah_insert = count($kuliah_mahasiswa_insert_set);
			
			// Ambil dari cache
			$kuliah_mahasiswa_update_set = $this->session->userdata('kuliah_mahasiswa_update_set');
			$jumlah_update = count($kuliah_mahasiswa_update_set);
			
			// --------------------------------
			// Proses Insert
			// --------------------------------
			if ($index_proses < $jumlah_insert)
			{
				// Proses dalam bentuk key lowercase
				$kuliah_mahasiswa_insert = array_change_key_case($kuliah_mahasiswa_insert_set[$index_proses], CASE_LOWER);
				
				// Simpan id_mhs_status & nim untuk update data di langitan
				$id_mhs_status	= $kuliah_mahasiswa_insert['id_mhs_status'];
				$nim_mhs		= $kuliah_mahasiswa_insert['nim_mhs'];
				
				// Hilangkan yang tidak diperlukan di tabel kuliah_mahasiswa
				unset($kuliah_mahasiswa_insert['id_mhs_status']);
				unset($kuliah_mahasiswa_insert['nim_mhs']);
			
				// Cleansing data
				if ($kuliah_mahasiswa_insert['sks_smt'] > 30) $kuliah_mahasiswa_insert['sks_smt'] = 30;
				
				// print_r($kuliah_mahasiswa_insert); exit();
				
				// Entri ke Feeder Kuliah Mahasiswa
				$insert_result = $this->feeder->InsertRecord($this->token, FEEDER_KULIAH_MAHASISWA, json_encode($kuliah_mahasiswa_insert));
				
				// Jika berhasil insert, terdapat return id_smt dan id_reg_pd
				if (isset($insert_result['result']['id_smt']) && isset($insert_result['result']['id_reg_pd']))
				{
					// Pesan Insert, tampilkan nim
					$result['message'] = ($index_proses + 1) . " Insert Aktivitas {$nim_mhs} : Berhasil";
					
					// Update status sync
					$this->rdb->Query("UPDATE mahasiswa_status SET fd_id_smt = '{$insert_result['result']['id_smt']}', fd_id_reg_pd = '{$insert_result['result']['id_reg_pd']}', fd_sync_on = sysdate WHERE id_mhs_status = {$id_mhs_status}");
				}
				else // saat insert kuliah_mahasiswa gagal
				{
					// Pesan Insert, NIM Mahasiswa
					$result['message'] = ($index_proses + 1) . " Insert {$nim_mhs} : " . json_encode($insert_result['result']);
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Update
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update))
			{
				// index berjalan dikurangi jumlah data insert utk mendapatkan index update
				$index_proses -= $jumlah_insert;
				
				// Proses dalam bentuk key lowercase
				$kuliah_mahasiswa_update = array_change_key_case($kuliah_mahasiswa_update_set[$index_proses], CASE_LOWER);
				
				// Simpan id_mhs_status untuk keperluan update di langitan
				$id_mhs_status = $kuliah_mahasiswa_update['id_mhs_status'];
				$nim_mhs = $kuliah_mahasiswa_update['nim_mhs'];
				$id_smt = $kuliah_mahasiswa_update['id_smt'];
				$id_reg_pd = $kuliah_mahasiswa_update['id_reg_pd'];
				
				// Hilangkan id_mhs_status & nim_mhs
				unset($kuliah_mahasiswa_update['id_mhs_status']);
				unset($kuliah_mahasiswa_update['nim_mhs']);
				unset($kuliah_mahasiswa_update['id_smt']);
				unset($kuliah_mahasiswa_update['id_reg_pd']);
				
				// Build data format
				$data_update = array(
					'key'	=> array('id_smt' => $id_smt, 'id_reg_pd' => $id_reg_pd),
					'data'	=> $kuliah_mahasiswa_update
				);
				
				// Update ke Feeder Kuliah Mahasiswa
				$update_result = $this->feeder->UpdateRecord($this->token, FEEDER_KULIAH_MAHASISWA, json_encode($data_update));
				
				// Jika tidak ada masalah update
				if ($update_result['result']['error_code'] == 0)
				{
					$result['message'] = ($index_proses + 1) . " Update {$nim_mhs} : Berhasil";
					
					$this->rdb->Query("UPDATE mahasiswa_status SET fd_sync_on = sysdate WHERE id_mhs_status = {$id_mhs_status}");
				}
				else
				{
					$result['message'] = ($index_proses + 1) . " Update {$nim_mhs} : Gagal. ";
					$result['message'] .= "({$update_result['result']['error_code']}) {$update_result['result']['error_desc']}";
					$result['message'] .= "\n" . json_encode($data_update);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert
				$index_proses += $jumlah_insert;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Selesai
			// --------------------------------
			else
			{
				$result['message'] = "Selesai";
				$result['status'] = SYNC_STATUS_DONE;
			}
		}
		
		echo json_encode($result);
	}
	
	/**
	 * Mahasiswa Lulus / DO / Keluar
	 */
	public function lulusan_do()
	{
		$jumlah = array();
		
		// Jumlah lulusan tidak bisa di dapat di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_MAHASISWA_PT, "p.id_jns_keluar is not null");
		$jumlah['feeder'] = $response['result'];

		// Ambil data mahasiswa lulus
		$sql_lulusan_raw = file_get_contents(APPPATH.'models/sql/lulusan-do.sql');
		$sql_lulusan = strtr($sql_lulusan_raw, array('@npsn' => $this->satuan_pendidikan['npsn']));
		$mhs_set = $this->rdb->QueryToArray($sql_lulusan);
		
		$jumlah['langitan'] = $mhs_set[0]['JUMLAH'];
		$jumlah['linked'] = $mhs_set[1]['JUMLAH'];
		$jumlah['update'] = $mhs_set[2]['JUMLAH'];
		$jumlah['insert'] = $mhs_set[3]['JUMLAH'];
		
		$this->smarty->assign('jumlah', $jumlah);
		
		$this->smarty->assign('url_sync', site_url('sync/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync/'.$this->uri->segment(2).'.tpl');
	}
	
	private function proses_lulusan_do()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_LANGITAN;
		
		// -----------------------------------
		// Ambil data untuk Insert
		// -----------------------------------
		if ($mode == MODE_AMBIL_DATA_LANGITAN)
		{
			$sql_lulusan_do_insert = file_get_contents(APPPATH.'models/sql/lulusan-do-insert.sql');
			
			$lulusan_do_set = $this->rdb->QueryToArray($sql_lulusan_do_insert);
			
			$this->session->set_userdata('lulusan_do_insert_set', $lulusan_do_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Entri. Jumlah data: ' . count($lulusan_do_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_AMBIL_DATA_LANGITAN_2;
			$result['params'] = http_build_query($_POST);
		}
		// -----------------------------------
		// Ambil data untuk Update
		// -----------------------------------
		else if ($mode == MODE_AMBIL_DATA_LANGITAN_2)
		{
			$sql_lulusan_do_update = file_get_contents(APPPATH.'models/sql/lulusan-do-update.sql');
			
			$lulusan_do_set = $this->rdb->QueryToArray($sql_lulusan_do_update);
			
			// simpan ke cache
			$this->session->set_userdata('lulusan_do_update_set', $lulusan_do_set);
			
			$result['message'] = 'Ambil data Sistem Langitan yang akan di proses Update. Jumlah data: ' . count($lulusan_do_set);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		// ----------------------------------------------
		// Proses Sinkronisasi dari data yg sudah diambil
		// ----------------------------------------------
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil dari cache
			$lulusan_do_insert_set = $this->session->userdata('lulusan_do_insert_set');
			$jumlah_insert = count($lulusan_do_insert_set);
			
			// Ambil dari cache
			$lulusan_do_update_set = $this->session->userdata('lulusan_do_update_set');
			$jumlah_update = count($lulusan_do_update_set);
			
			// --------------------------------
			// Proses Insert. Insert lulusan == update mahasiswa_pt keluar
			// --------------------------------
			if ($index_proses < $jumlah_insert)
			{
				// Proses dalam bentuk key lowercase
				$lulusan_do_insert = array_change_key_case($lulusan_do_insert_set[$index_proses], CASE_LOWER);
				
				// Simpan id_admisi & nim untuk update data di langitan
				$id_admisi	= $lulusan_do_insert['id_admisi'];
				$nim_mhs	= $lulusan_do_insert['nim_mhs'];
				$id_reg_pd	= $lulusan_do_insert['id_reg_pd'];
				$nm_status	= $lulusan_do_insert['nm_status_pengguna'];
				
				// Hilangkan yang tidak diperlukan di tabel mahasiswa_pt
				unset($lulusan_do_insert['id_admisi']);
				unset($lulusan_do_insert['nim_mhs']);
				unset($lulusan_do_insert['id_reg_pd']);
				unset($lulusan_do_insert['nm_status_pengguna']);
				
				// Build data format
				$data_update = array(
					'key'	=> array('id_reg_pd' => $id_reg_pd),
					'data'	=> $lulusan_do_insert
				);
				
				// Update ke Feeder Mahasiswa PT
				$update_result = $this->feeder->UpdateRecord($this->token, FEEDER_MAHASISWA_PT, json_encode($data_update));
				
				// Jika berhasil update, tidak ada error
				if ($update_result['result']['error_code'] == 0)
				{
					// Pesan Insert lulusan, tampilkan nim
					$result['message'] = ($index_proses + 1) . " Insert {$nim_mhs} {$nm_status} : Berhasil";
					
					// Update status sync
					$this->rdb->Query("UPDATE admisi SET fd_sync_on = sysdate WHERE id_admisi = {$id_admisi}");
				}
				else // saat update mahasiswa_pt gagal
				{
					// Tampilkan pesan gagal
					$result['message'] = ($index_proses + 1) . " Insert {$nim_mhs} {$nm_status} : " . json_encode($update_result['result']) . "\n" . json_encode($data_update);
				}
				
				$result['status'] = SYNC_STATUS_PROSES;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Proses Update
			// --------------------------------
			else if ($index_proses < ($jumlah_insert + $jumlah_update))
			{
				// index berjalan dikurangi jumlah data insert utk mendapatkan index update
				$index_proses -= $jumlah_insert;
				
				// Proses dalam bentuk key lowercase
				$lulusan_do_update = array_change_key_case($lulusan_do_update_set[$index_proses], CASE_LOWER);
				
				// Simpan variabel untuk keperluan update di langitan dan tampilan
				$id_admisi	= $lulusan_do_update['id_admisi'];
				$nim_mhs	= $lulusan_do_update['nim_mhs'];
				$id_reg_pd	= $lulusan_do_update['id_reg_pd'];
				$nm_status	= $lulusan_do_update['nm_status_pengguna'];
				
				// Hilangkan yang tidak diperlukan di tabel mahasiswa_pt
				unset($lulusan_do_insert['id_admisi']);
				unset($lulusan_do_insert['nim_mhs']);
				unset($lulusan_do_insert['id_reg_pd']);
				unset($lulusan_do_insert['nm_status_pengguna']);
				
				// Build data format
				$data_update = array(
					'key'	=> array('id_reg_pd' => $id_reg_pd),
					'data'	=> $lulusan_do_update
				);
				
				// Update ke Feeder Mahasiswa PT
				$update_result = $this->feeder->UpdateRecord($this->token, FEEDER_MAHASISWA_PT, json_encode($data_update));
				
				// Jika berhasil update, tidak ada error
				if ($update_result['result']['error_code'] == 0)
				{
					// Pesan Insert lulusan, tampilkan nim
					$result['message'] = ($index_proses + 1) . " Update {$nim_mhs} {$nm_status} : Berhasil";
					
					// Update status sync
					$this->rdb->Query("UPDATE admisi SET fd_sync_on = sysdate WHERE id_admisi = {$id_admisi}");
				}
				else // saat update mahasiswa_pt gagal
				{
					// Tampilkan pesan gagal
					$result['message'] = ($index_proses + 1) . " Update {$nim_mhs} {$nm_status} : " . json_encode($update_result['result']);
				}
				
				// Status proses
				$result['status'] = SYNC_STATUS_PROSES;
				
				// meneruskan index proses ditambah lagi dengan jumlah data insert
				$index_proses += $jumlah_insert;
				
				// ganti parameter
				$_POST['index_proses'] = $index_proses + 1;
				$result['params'] = http_build_query($_POST);
			}
			// --------------------------------
			// Selesai
			// --------------------------------
			else
			{
				$result['message'] = "Selesai";
				$result['status'] = SYNC_STATUS_DONE;
			}
		}
		
		echo json_encode($result);
	}
	
	/**
	 * Tampilan awal sebelum start sinkronisasi
	 */
	function start($mode)
	{
		if ($mode == 'mahasiswa')
		{
			$kode_prodi	= $this->input->post('kode_prodi');
			$angkatan	= $this->input->post('angkatan');
			
			$program_studi_set = $this->rdb->QueryToArray(
				"SELECT nm_jenjang, nm_program_studi FROM program_studi ps
				JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
				JOIN jenjang j ON j.id_jenjang = ps.id_jenjang
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
				WHERE pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND ps.kode_program_studi = '{$kode_prodi}'");
			
			$this->smarty->assign('jenis_sinkronisasi', 'Mahasiswa '.$program_studi_set[0]['NM_JENJANG'].' '.$program_studi_set[0]['NM_PROGRAM_STUDI'].' Angkatan '.$angkatan);
			$this->smarty->assign('url', site_url('sync/proses/'.$mode));
		}
		
		if ($mode == 'mata_kuliah')
		{
			$kode_prodi	= $this->input->post('kode_prodi');
			
			$program_studi_set = $this->rdb->QueryToArray(
				"SELECT nm_jenjang, nm_program_studi FROM program_studi ps
				JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
				JOIN jenjang j ON j.id_jenjang = ps.id_jenjang
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
				WHERE pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND ps.kode_program_studi = '{$kode_prodi}'");
				
			$this->smarty->assign('jenis_sinkronisasi', 'Mata Kuliah '.$program_studi_set[0]['NM_JENJANG'].' '.$program_studi_set[0]['NM_PROGRAM_STUDI']);
			$this->smarty->assign('url', site_url('sync/proses/'.$mode));
		}
		
		if ($mode == 'kurikulum')
		{
			$kode_prodi	= $this->input->post('kode_prodi');
			
			$program_studi_set = $this->rdb->QueryToArray(
				"SELECT nm_jenjang, nm_program_studi FROM program_studi ps
				JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
				JOIN jenjang j ON j.id_jenjang = ps.id_jenjang
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
				WHERE pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND ps.kode_program_studi = '{$kode_prodi}'");
				
			$this->smarty->assign('jenis_sinkronisasi', 'Kurikulum '.$program_studi_set[0]['NM_JENJANG'].' '.$program_studi_set[0]['NM_PROGRAM_STUDI']);
			$this->smarty->assign('url', site_url('sync/proses/'.$mode));
		}
		
		if ($mode == 'mata_kuliah_kurikulum')
		{
			$kode_prodi			= $this->input->post('kode_prodi');
			$id_kurikulum_prodi	= $this->input->post('id_kurikulum_prodi');
			
			$program_studi_set = $this->rdb->QueryToArray(
				"SELECT nm_jenjang, nm_program_studi, k.nm_kurikulum
				FROM kurikulum_prodi kp
				JOIN kurikulum k ON k.id_kurikulum = kp.id_kurikulum
				JOIN program_studi ps ON ps.id_program_studi = kp.id_program_studi
				JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
				JOIN jenjang j ON j.id_jenjang = ps.id_jenjang
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
				WHERE pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND ps.kode_program_studi = '{$kode_prodi}' AND kp.id_kurikulum_prodi = {$id_kurikulum_prodi}");
				
			$this->smarty->assign('jenis_sinkronisasi', 'Mata Kuliah Kurikulum '.$program_studi_set[0]['NM_KURIKULUM']);
			$this->smarty->assign('url', site_url('sync/proses/'.$mode));
		}
		
		if ($mode == 'kelas_kuliah')
		{
			$kode_prodi	= $this->input->post('kode_prodi');
			$id_smt		= $this->input->post('semester');
			
			$program_studi_set = $this->rdb->QueryToArray(
				"SELECT nm_jenjang, nm_program_studi FROM program_studi ps
				JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
				JOIN jenjang j ON j.id_jenjang = ps.id_jenjang
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
				WHERE pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND ps.kode_program_studi = '{$kode_prodi}'");
				
			// Konversi format semester ke id_semester
			$semester_langitan = $this->rdb->QueryToArray(
				"SELECT thn_akademik_semester as thn, nm_semester FROM semester s
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = s.id_perguruan_tinggi
				WHERE 
					pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND 
					s.thn_akademik_semester||decode(upper(nm_semester), 'GANJIL','1','GENAP','2') = '{$id_smt}'");
				
			$this->smarty->assign('jenis_sinkronisasi', 'Kelas Perkuliahan '.$program_studi_set[0]['NM_JENJANG'].' '.$program_studi_set[0]['NM_PROGRAM_STUDI'].' - '.$semester_langitan[0]['THN'].'/'. ($semester_langitan[0]['THN'] + 1).' '.$semester_langitan[0]['NM_SEMESTER']);
			$this->smarty->assign('url', site_url('sync/proses/'.$mode));
		}

		if ($mode == 'ajar_dosen')
		{
			$kode_prodi	= $this->input->post('kode_prodi');
			$id_smt		= $this->input->post('semester');
			
			$program_studi_set = $this->rdb->QueryToArray(
				"SELECT nm_jenjang, nm_program_studi FROM program_studi ps
				JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
				JOIN jenjang j ON j.id_jenjang = ps.id_jenjang
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
				WHERE pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND ps.kode_program_studi = '{$kode_prodi}'");
				
			// Konversi format semester ke id_semester
			$semester_langitan = $this->rdb->QueryToArray(
				"SELECT thn_akademik_semester as thn, nm_semester FROM semester s
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = s.id_perguruan_tinggi
				WHERE 
					pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND 
					s.thn_akademik_semester||decode(upper(nm_semester), 'GANJIL','1','GENAP','2') = '{$id_smt}'");
				
			$this->smarty->assign('jenis_sinkronisasi', 'Dosen Kelas '.$program_studi_set[0]['NM_JENJANG'].' '.$program_studi_set[0]['NM_PROGRAM_STUDI'].' - '.$semester_langitan[0]['THN'].'/'. ($semester_langitan[0]['THN'] + 1).' '.$semester_langitan[0]['NM_SEMESTER']);
			$this->smarty->assign('url', site_url('sync/proses/'.$mode));
		}
		
		if ($mode == 'nilai')
		{
			$kode_prodi		= $this->input->post('kode_prodi');
			$id_smt			= $this->input->post('semester');
			$id_kelas_mk	= $this->input->post('id_kelas_mk');
			
			// Ambil informasi data kelas
			$kelas_set = $this->rdb->QueryToArray(
				"SELECT kls.id_kelas_mk, mk.kd_mata_kuliah||' '||mk.nm_mata_kuliah||' ('||nk.nama_kelas||') ' as nm_kelas, nm_jenjang, nm_program_studi
				FROM kelas_mk kls
				JOIN kurikulum_mk kmk ON kmk.id_kurikulum_mk = kls.id_kurikulum_mk
				JOIN mata_kuliah mk ON mk.id_mata_kuliah = kmk.id_mata_kuliah
				JOIN nama_kelas nk ON nk.id_nama_kelas = kls.no_kelas_mk
				JOIN program_studi ps on ps.id_program_studi= kls.id_program_studi
				JOIN jenjang j on j.id_jenjang = ps.id_jenjang
				WHERE kls.id_kelas_mk = {$id_kelas_mk}");
				
			// Konversi format semester ke id_semester
			$semester_langitan = $this->rdb->QueryToArray(
				"SELECT thn_akademik_semester as thn, nm_semester FROM semester s
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = s.id_perguruan_tinggi
				WHERE 
					pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND 
					s.thn_akademik_semester||decode(upper(nm_semester), 'GANJIL','1','GENAP','2') = '{$id_smt}'");
			
			$this->smarty->assign('jenis_sinkronisasi', "Nilai Perkuliahan {$kelas_set[0]['NM_JENJANG']} {$kelas_set[0]['NM_PROGRAM_STUDI']} - {$kelas_set[0]['NM_KELAS']} {$semester_langitan[0]['THN']}/" . ($semester_langitan[0]['THN'] + 1)." {$semester_langitan[0]['NM_SEMESTER']}");
			$this->smarty->assign('url', site_url('sync/proses/'.$mode));
		}
		
		if ($mode == 'nilai_per_prodi')
		{
			$kode_prodi		= $this->input->post('kode_prodi');
			$id_smt			= $this->input->post('semester');
			
			$program_studi_set = $this->rdb->QueryToArray(
				"SELECT nm_jenjang, nm_program_studi FROM program_studi ps
				JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
				JOIN jenjang j ON j.id_jenjang = ps.id_jenjang
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = f.id_perguruan_tinggi
				WHERE pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND ps.kode_program_studi = '{$kode_prodi}'");
			
			// Konversi format semester ke id_semester
			$semester_langitan = $this->rdb->QueryToArray(
				"SELECT thn_akademik_semester as thn, nm_semester FROM semester s
				JOIN perguruan_tinggi pt ON pt.id_perguruan_tinggi = s.id_perguruan_tinggi
				WHERE 
					pt.npsn = '{$this->satuan_pendidikan['npsn']}' AND 
					s.thn_akademik_semester||decode(upper(nm_semester), 'GANJIL','1','GENAP','2') = '{$id_smt}'");
					
			$this->smarty->assign('jenis_sinkronisasi', 'Nilai Perkuliahan '.$program_studi_set[0]['NM_JENJANG'].' '.$program_studi_set[0]['NM_PROGRAM_STUDI'].' - '.$semester_langitan[0]['THN'].'/'. ($semester_langitan[0]['THN'] + 1).' '.$semester_langitan[0]['NM_SEMESTER']);
			$this->smarty->assign('url', site_url('sync/proses/'.$mode));
		}
		
		if ($mode == 'nilai_transfer')
		{
			$this->smarty->assign('jenis_sinkronisasi', 'Nilai Transfer Mahasiswa');
			$this->smarty->assign('url', site_url('sync/proses/'.$mode));
		}
		
		if ($mode == 'nilai_transfer_umaha')
		{
			$this->smarty->assign('jenis_sinkronisasi', 'Nilai Perkuliahan UMAHA Angkatan &lt; 2014');
			$this->smarty->assign('url', site_url('sync/proses/'.$mode));
		}
		
		if ($mode == 'kuliah_mahasiswa')
		{
			$id_smt			= $this->input->post('semester');
			
			$semester_langitan = $this->langitan_model->get_semester_langitan($this->satuan_pendidikan['npsn'], $id_smt);
			
			$this->smarty->assign('jenis_sinkronisasi', 'Aktivitas Mahasiswa - '.$semester_langitan);
			$this->smarty->assign('url', site_url('sync/proses/'.$mode));
		}
		
		if ($mode == 'hapus_mk_kurikulum')
		{
			$this->smarty->assign('jenis_sinkronisasi', 'Hapus Mata Kuliah Kurikulum');
			$this->smarty->assign('url', site_url('sync/proses/'.$mode));
		}
		
		if ($mode == 'lulusan_do')
		{
			$this->smarty->assign('jenis_sinkronisasi', 'Lulusan / DO / Keluar');
			$this->smarty->assign('url', site_url('sync/proses/'.$mode));
		}
		
		$this->smarty->assign('mode', $mode);
		$this->smarty->display('sync/start.tpl');
	}
	
	/**
	 * Pemrosesan sinkronisasi
	 */
	function proses($mode)
	{
		// harus request POST 
		if ($_SERVER['REQUEST_METHOD'] != 'POST') { return; }
		
		if ($mode == 'mahasiswa')
		{
			$this->proses_mahasiswa();
		}
		
		else if ($mode == 'mata_kuliah')
		{
			$this->proses_mata_kuliah();
		}
		
		else if ($mode == 'kurikulum')
		{
			$this->proses_kurikulum();
		}
		
		else if ($mode == 'mata_kuliah_kurikulum')
		{
			$this->proses_mata_kuliah_kurikulum();
		}
		
		else if ($mode == 'kelas_kuliah')
		{
			$this->proses_kelas_kuliah();
		}

		else if ($mode == 'ajar_dosen')
		{
			$this->proses_ajar_dosen();
		}
		
		else if ($mode == 'nilai')
		{
			$this->proses_nilai();
		}
		
		else if ($mode == 'nilai_per_prodi')
		{
			$this->proses_nilai_per_prodi();
		}
		
		else if ($mode == 'nilai_transfer')
		{
			$this->proses_nilai_transfer();
		}
		
		else if ($mode == 'nilai_transfer_umaha')
		{
			$this->proses_nilai_transfer_umaha();
		}
		
		else if ($mode == 'kuliah_mahasiswa')
		{
			$this->proses_kuliah_mahasiswa();
		}
		
		else if ($mode == 'hapus_mk_kurikulum')
		{
			$this->proses_hapus_mk_kurikulum();
		}
		
		else if ($mode == 'lulusan_do')
		{
			$this->proses_lulusan_do();
		}
		
		else 
		{
			echo json_encode(array('status' => 'done', 'message' => 'Not Implemented()'));
		}
	}
	
}
