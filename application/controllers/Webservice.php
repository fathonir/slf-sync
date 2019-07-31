<?php 
ini_set('memory_limit', '-1');
set_time_limit(0);
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property string $token Token
 */
class Webservice extends MY_Controller 
{
	function __construct()
	{
		parent::__construct();
		
		$this->check_credentials();

		$this->token = $this->session->userdata('token');
		
		$this->load->library('feeder', array('url' => $this->session->userdata('wsdl')));
		
		//$this->satuan_pendidikan = xcache_get(FEEDER_SATUAN_PENDIDIKAN);
		$this->satuan_pendidikan = $this->session->userdata(FEEDER_SATUAN_PENDIDIKAN);
		
	}
	
	function list_table()
	{
		$result = $this->feeder->ListTable($this->token);

		foreach ($result['result'] as &$table)
		{
			// $column = $this->client->getProxy()->GetDictionary($this->token, $table['table']);
			// $table['column_set'] = $column['result'];
		}

		$this->smarty->assign('data_set', $result['result']);

		$this->smarty->display('webservice/list_table.tpl');
	}

	function table_column($table)
	{
		// Ambil kolom
		$result = $this->feeder->GetDictionary($this->token, $table);

		$this->smarty->assign('data_set', $result['result']);

		$this->smarty->display('webservice/table_column.tpl');
	}

	function table_data($table, $raw = '')
	{
		// Inisialisasi parameter
		$filter	= null;
		$order	= null;
		$limit	= 50;  // per page
		$offset	= null;
		
		// Ambil kolom
		$column = $this->feeder->GetDictionary($this->token, $table);
		
		// Filter Khusus
		if ($table == FEEDER_SATUAN_PENDIDIKAN)
		{
			$filter = "npsn = '".$this->session->userdata('username')."'";
		}
		
		// Jika tabel sms (program studi)
		if ($table == FEEDER_SMS)
		{			
			// Modify parameter
			$filter = "p.id_sp = '{$this->satuan_pendidikan['id_sp']}'";
		}
		
		// jika tabel semester
		if ($table == FEEDER_SEMESTER)
		{
			$order = 'id_smt desc';
		}
		
		// jika ada query filter
		if (isset($_GET['filter']))
		{
			$filter = ($filter == null) ? $_GET['filter'] : $filter . ' and ' . $_GET['filter'];
		}
		
		// Jika raw
		if ($raw == 'raw')
		{
			$table = "{$table}.raw";
		}

		// Ambil data --> params: token, tabel, filter, order, limit, offset
		$result = $this->feeder->GetRecordset($this->token, $table, $filter, $order, $limit, $offset);
		
		foreach ($result['result'] as &$data)
		{
			if ($table == FEEDER_KELAS_KULIAH)
			{
				$data['pkey'] = json_encode(array('id_kls' => $data['id_kls']));
			}
		}

		
		$this->smarty->assign('column_set', $column['result']);
		$this->smarty->assign('data_set', $result['result']);
		$this->smarty->assign('table_name', $table);

		$this->smarty->display('webservice/table_data.tpl');
	}
	
	function table_data_csv($table)
	{
		
		
		// Nama File
		$file_name = "{$this->satuan_pendidikan['npsn']}_{$table}_" . date('Y-m-d');
		
		// header
		header('Content-type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $file_name . '.csv"');
		
		$handle = fopen("php://output", 'w');
		
		// Parameter pengambilan record
		$order	= null;
		$limit	= 500;  // per page
		$filter = null;
		
		// Jika tabel sms (program studi)
		if ($table == FEEDER_SMS)
		{			
			// Modify parameter
			$filter = "p.id_sp = '{$this->satuan_pendidikan['id_sp']}'";
		}
		
		if ($table == FEEDER_MATA_KULIAH)
			$order = 'id_mk';
		
		if ($table == FEEDER_KURIKULUM)
			$order = 'id_kurikulum_sp';
		
		if ($table == FEEDER_MK_KURIKULUM)
			$order = 'id_kurikulum_sp,id_mk';
		
		if ($table == FEEDER_MAHASISWA)
			$order = 'id_pd';
		
		if ($table == FEEDER_MAHASISWA_PT)
			$order = 'id_reg_pd';
		
		if ($table == FEEDER_DOSEN)
			$order = 'id_sdm';
		
		if ($table == FEEDER_DOSEN_PT)
			$order = 'id_reg_ptk';
		
		if ($table == FEEDER_KELAS_KULIAH)
			$order = 'id_kls';
		
		if ($table == FEEDER_NILAI)
			$order = 'id_kls,id_reg_pd';
		
		// Ambil jumlah
		$result = $this->feeder->GetCountRecordset($this->token, $table);
		$count = $result['result'];
		
		$max_page = (int)($count / $limit);
		
		$data_set = array();
		
		$i_row = 0;
		
		// 662
		for ($page = 601; $page <= 662; $page++)
		{
			$offset = $page * $limit;
			$result = $this->feeder->GetRecordset($this->token, $table, $filter, $order, $limit, $offset);
			$row_set = $result['result'];
			
			foreach ($row_set as $row)
			{
				// Row pertama untuk cetak nama kolom
				if ($i_row == 0)
				{
					$column_names = array_keys($row);
					fputcsv($handle, $column_names);
				}
				
				$row_values = array_values($row);
				
				// Replace karakter enter dengan spasi
				for ($i_value = 0; $i_value < count($row_values); $i_value++)
					$row_values[$i_value] = trim(preg_replace('/\s+/', ' ', $row_values[$i_value]));
				
				fputcsv($handle, $row_values);
				
				$i_row++;
			}
		}
		
		fclose($handle);
	}
	
	/**
	 * Menghapus data yang ada dalam feeder
	 * @param string $table Nama tabel
	 * @param json $data Disesuakan dengan primary key untuk hapus
	 */
	function remove_data()
	{
		if ($this->input->method() == 'post')
		{
			$table = $this->input->post('table');
			$pkey = $this->input->post('pkey');
		}
	}
	
	function list_penugasan_dosen()
	{
		// Disable execution limit
		set_time_limit(0);
		
		// Disable memory_limit
		ini_set('memory_limit', '-1');
		
		// Nama File
		$file_name = "penugasan_dosen_" . date('Y-m-d');
		
		// header
		header('Content-type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $file_name . '.csv"');
		
		// File output handle
		$handle = fopen("php://output", 'w');
		
		$result = $this->feeder->GetListPenugasanDosen($this->token, null, null, 500);
		
		// get columns from first row
		$columns = array_keys($result['result'][0]);
		
		// Write column
		fputcsv($handle, $columns);
		
		foreach ($result['result'] as $row)
		{
			$row_values = array_values($row);

			// Write row values
			fputcsv($handle, $row_values);
		}
		
		fclose($handle);
	}
}
