<?php

/**
 * @property CI_Remotedb $rdb Remote DB Sistem Langitan
 * @property Feeder $feeder
 * @property string $token Token webservice
 * @property string $npsn Kode Perguruan Tinggi
 * @property array $satuan_pendidikan Row: satuan_pendidikan
 * @property Langitan_model $langitan_model Description
 */
class Sync_del extends MY_Controller 
{
	
	function __construct()
	{
		parent::__construct();
		
		$this->check_credentials();

		// Inisialisasi Token dan Satuan Pendidikan
		$this->token = $this->session->userdata('token');
		$this->satuan_pendidikan = xcache_get(FEEDER_SATUAN_PENDIDIKAN);
		
		// Inisialisasi URL Feeder
		$this->load->library('feeder', array('url' => $this->session->userdata('wsdl')));
		
		// Inisialisasi Langitan_model
		$this->load->model('langitan_model');
	}
	
	function start($mode)
	{
		if ($mode == 'kuliah_mahasiswa')
		{
			// Ambil Semester
			$id_semester = $this->input->post('semester');
			$semester = $this->langitan_model->get_semester($id_semester);
			
			$this->smarty->assign('jenis_sinkronisasi', 'Aktivitas Kuliah '.$semester['TAHUN_AJARAN'].' '.$semester['NM_SEMESTER']);
			$this->smarty->assign('url', site_url('sync_del/proses/'.$mode));
		}
		
		if ($mode == 'kuliah_mahasiswa_restore')
		{
			// Ambil Semester
			$id_semester = $this->input->post('semester');
			$semester = $this->langitan_model->get_semester($id_semester);
			
			$this->smarty->assign('jenis_sinkronisasi', 'Restore Aktivitas Kuliah '.$semester['TAHUN_AJARAN'].' '.$semester['NM_SEMESTER']);
			$this->smarty->assign('url', site_url('sync_del/proses/'.$mode));
		}
		
		$this->smarty->display('sync_del/start.tpl');
	}
	
	function proses($mode)
	{
		// Wajib POST
		if ($_SERVER['REQUEST_METHOD'] != 'POST') { return; }
		
		if ($mode == 'kuliah_mahasiswa')
		{
			$this->proses_kuliah_mahasiswa();
		}
		
		if ($mode == 'kuliah_mahasiswa_restore')
		{
			$this->proses_kuliah_mahasiswa_restore();
		}
	}
	
	function kuliah_mahasiswa()
	{
		$jumlah = array();
		
		// Ambil jumlah mahasiswa di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_KULIAH_MAHASISWA, null);
		$jumlah['feeder'] = $response['result'];
		
		$jumlah['langitan'] = 0;
		$jumlah['linked'] = 0;
		$jumlah['update'] = 0;
		$jumlah['insert'] = 0;
		$this->smarty->assign('jumlah', $jumlah);
		
		// Ambil Semua Semester yg ada :
		$semester_set = $this->langitan_model->list_semester($this->satuan_pendidikan['npsn']);
		$this->smarty->assign('semester_set', $semester_set);
		
		$this->smarty->assign('url_sync', site_url('sync_del/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync_del/'.$this->uri->segment(2).'.tpl');
	}
	
	function kuliah_mahasiswa_data($id_semester)
	{
		$jumlah = array();
		
		$semester = $this->langitan_model->get_semester($id_semester);
		
		// Ambil jumlah mahasiswa di feeder
		$response = $this->feeder->GetCountRecordset($this->token, FEEDER_KULIAH_MAHASISWA, "p.id_smt = '{$semester['ID_SMT']}'");
		$jumlah['feeder'] = $response['result'];
		
		echo json_encode($jumlah);
	}
	
	private function proses_kuliah_mahasiswa()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync_del/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_FEEDER;
		
		
		if ($mode == MODE_AMBIL_DATA_FEEDER)
		{
			$id_semester = $this->input->post('semester');
			$semester = $this->langitan_model->get_semester($id_semester);
			
			$feeder_result = $this->feeder->GetRecordset($this->token, FEEDER_KULIAH_MAHASISWA.'.raw', "p.id_smt = '{$semester['ID_SMT']}'");
			
			// simpan ke cache
			xcache_set('data_delete_set', $feeder_result['result']);
			
			$result['message'] = 'Ambil data Feeder yang akan di proses Delete. Jumlah data: ' . count($feeder_result['result']);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil dari cache
			$data_delete_set = xcache_get('data_delete_set');
			$jumlah_delete = count($data_delete_set);
			
			// Waktu Sinkronisasi
			$time_sync = date('Y-m-d H:i:s');
			
			// --------------------------------
			// Proses Delete
			// --------------------------------
			if ($index_proses < $jumlah_delete)
			{
				$data_delete = array(
					'id_smt' => $data_delete_set[$index_proses]['id_smt'],
					'id_reg_pd' => $data_delete_set[$index_proses]['id_reg_pd'],
				);
				
				$this->feeder->DeleteRecord($this->token, FEEDER_KULIAH_MAHASISWA, json_encode($data_delete));
				
				$result['message'] = 'Proses data ke '. ($index_proses + 1);
				
				$result['status'] = SYNC_STATUS_PROSES;
				
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
	
	function kuliah_mahasiswa_restore()
	{
		$jumlah = array();
		
		// Ambil jumlah mahasiswa di feeder
		$response = $this->feeder->GetCountDeletedRecordset($this->token, FEEDER_KULIAH_MAHASISWA, null);
		$jumlah['feeder'] = $response['result'];
		
		$jumlah['langitan'] = 0;
		$jumlah['linked'] = 0;
		$jumlah['update'] = 0;
		$jumlah['insert'] = 0;
		$this->smarty->assign('jumlah', $jumlah);
		
		// Ambil Semua Semester yg ada :
		$semester_set = $this->langitan_model->list_semester($this->satuan_pendidikan['npsn']);
		$this->smarty->assign('semester_set', $semester_set);
		
		$this->smarty->assign('judul_sync_del', 'Restore Hapus Aktivitas Kuliah Mahasiswa');
		$this->smarty->assign('sub_judul_sync_del', 'Data yg direstore adalah data aktivitas mahasiswa yg sudah lulus yang telah terhapus dan gagal di sinkronisasi');
		$this->smarty->assign('nama_data', 'Deleted Aktivitas Kuliah');
		$this->smarty->assign('url_data', site_url('sync_del/kuliah_mahasiswa_restore_data/'));
		
		$this->smarty->assign('url_sync', site_url('sync_del/start/'.$this->uri->segment(2)));
		$this->smarty->display('sync_del/pre_start_1.tpl');
	}
	
	function kuliah_mahasiswa_restore_data($id_semester)
	{
		$jumlah = array();		
		$jumlah['feeder'] = '-';
		
		echo json_encode($jumlah);
	}
	
	private function proses_kuliah_mahasiswa_restore()
	{
		$result = array('status'=> '', 'time' => '', 'message' => '', 'nextUrl' => site_url('sync_del/proses/'. $this->uri->segment(3)), 'params'	=> '');
		
		$mode	= isset($_POST['mode']) ? $_POST['mode'] : MODE_AMBIL_DATA_FEEDER;
		
		// ------------------------------------------------------------
		// Ambil data Kuliah Mahasiswa terhapus pada Semester terpilih
		// ------------------------------------------------------------
		if ($mode == MODE_AMBIL_DATA_FEEDER)
		{
			$id_semester = $this->input->post('semester');
			$semester = $this->langitan_model->get_semester($id_semester);
			
			// data kuliah mahasiswa terhapus filter semester
			$feeder_result = $this->feeder->GetDeletedRecordset($this->token, FEEDER_KULIAH_MAHASISWA.'.raw', "p.id_smt = '{$semester['ID_SMT']}'");
			
			// simpan ke cache
			xcache_set('deleted_data_set', $feeder_result['result']);
			
			$result['message'] = 'Ambil data Feeder yang akan di proses Restore. Jumlah data: ' . count($feeder_result['result']);
			$result['status'] = SYNC_STATUS_PROSES;
			
			// ganti parameter
			$_POST['mode'] = MODE_SYNC;
			$result['params'] = http_build_query($_POST);
		}
		else if ($mode == MODE_SYNC)
		{
			$index_proses = isset($_POST['index_proses']) ? $_POST['index_proses'] : 0;
			
			// Ambil dari cache
			$deleted_data_set = xcache_get('deleted_data_set');
			$jumlah_delete = count($deleted_data_set);
			
			// Waktu Sinkronisasi
			$time_sync = date('Y-m-d H:i:s');
			
			// Reset Counter
			if ($index_proses == 0)
				xcache_set('counter', 0); 
			
			// --------------------------------
			// Proses Delete
			// --------------------------------
			if ($index_proses < $jumlah_delete)
			{
				$deleted_data = array(
					'id_smt' => $deleted_data_set[$index_proses]['id_smt'],
					'id_reg_pd' => $deleted_data_set[$index_proses]['id_reg_pd'],
				);
				
				// SAMPAI SINI : TO DO
				// Cek mahasiswa sudah lulus / tdk berdasarkan id_reg_pd
				// LULUS -> Restore
				// BELUM LULUS -> Tdk perlu di restore
				
				// Ambil data mahasiswa_pt (cek kelulusan)
				$feeder_result = $this->feeder->GetRecord($this->token, FEEDER_MAHASISWA_PT, "p.id_reg_pd = '{$deleted_data_set[$index_proses]['id_reg_pd']}'");
				$mahasiswa_pt = $feeder_result['result'];
				
				$mahasiswa_pt['fk__sms'] = trim($mahasiswa_pt['fk__sms']);
				
				$lulus = ($mahasiswa_pt['id_jns_keluar'] == '1') ? 'Lulus' : 'Belum Lulus';
				
				// Increment Counter
				$result['message'] = ",{$mahasiswa_pt['nipd']},{$mahasiswa_pt['fk__sms']},{$lulus}";
				
				
				$result['status'] = SYNC_STATUS_PROSES;
				
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
}
