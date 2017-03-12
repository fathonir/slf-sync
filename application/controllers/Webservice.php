<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Webservice extends MY_Controller {
	
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

	function table_data($table)
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
		if ($table == 'sms')
		{			
			// Modify parameter
			$filter = "p.id_sp = '{$this->satuan_pendidikan['id_sp']}'";
		}

		// Ambil data --> params: token, tabel, filter, order, limit, offset
		$result = $this->feeder->GetRecordset($this->token, $table, $filter, $order, $limit, $offset);

		
		$this->smarty->assign('column_set', $column['result']);
		$this->smarty->assign('data_set', $result['result']);

		$this->smarty->display('webservice/table_data.tpl');
	}
	
	function table_data_csv($table)
	{
		// Disable execution limit
		set_time_limit(0);
		
		// Disable memory_limit
		ini_set('memory_limit', '-1');
		
		// Nama File
		$file_name = "{$this->satuan_pendidikan['npsn']}_{$table}_" . date('d-m-Y');
		
		// header
		header('Content-type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $file_name . '.csv"');
		
		
		// Ambil kolom
		$result = $this->feeder->GetDictionary($this->token, $table);
		// $this->smarty->assignByRef('column_set', $result['result']);
		$column_set = $result['result'];
		
		// {foreach $column_set as $column}"{$column.column_name}"{if not $column@last},{/if}{/foreach}
		
		// Print Nama Kolom, menambahkan petik ganda pada nama kolom
		$column_names = array_keys($column_set);
		foreach ($column_names as &$column_name)
			$column_name = "\"{$column_name}\"";
		
		echo implode(",", $column_names) . "\r\n";
		
		flush();
		
		// Parameter pengambilan record
		$order	= null;
		$limit	= 500;  // per page
		$filter = null;
		
		// Jika tabel sms (program studi)
		if ($table == 'sms')
		{			
			// Modify parameter
			$filter = "p.id_sp = '{$this->satuan_pendidikan['id_sp']}'";
		}
		
		if ($table == 'mata_kuliah')
		{
			$order = 'id_mk';
		}
		
		// Ambil jumlah
		$result = $this->feeder->GetCountRecordset($this->token, $table);
		$count = $result['result'];
		
		$max_page = (int)($count / $limit);
		
		$data_set = array();
		
		if ($table != FEEDER_SEMESTER)
		{
			$table_raw = $table.'.raw';
		}
		else
		{
			$table_raw = $table;
		}
		
		for ($page = 0; $page <= $max_page; $page++)
		{
			$offset = $page * $limit;
			$result = $this->feeder->GetRecordset($this->token, $table_raw, $filter, $order, $limit, $offset);
			// $data_set = array_merge($data_set, $result['result']);
			$row_set = $result['result'];
			
			// foreach $data_set as $data
			// {foreach $column_set as $column}"{preg_replace('/\s+/', ' ', str_replace('"', '""', $data[$column.column_name]))}"{if not $column@last},{/if}{/foreach}
			// endforeach
			
			foreach ($row_set as $row)
			{
				$row_values = array_values($row);
				
				foreach ($row_values as &$row_value)
				{
					$row_value = preg_replace('/\s+/', ' ', str_replace('"', '""', $row_value));
				}
				
				echo implode(',', $row_values) . "\r\n";		
			}
			
			flush();
		}
	}
}
