<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

// Include nusoap class
require APPPATH . 'third_party/nusoap/lib/nusoap.php';

/**
 * @property nusoap_client $client Description
 */
class Feeder
{
	private $client;
	
	function __construct($params)
	{
		$this->client = new nusoap_client($params['url'], TRUE);
	}
	
	public function GetToken($username, $password)
	{
		return $this->client->getProxy()->GetToken($username, $password);
	}
	
	public function GetRecord($token, $table, $filter = null)
	{
		$return = $this->client->getProxy()->GetRecord($token, $table, $filter);
		
		if ($return['error_code'] != 0)
		{
			show_error($return['error_desc'].'<br/><a class="btn btn-default" href="'. site_url('auth/logout').'">Logout</a>');
		}
		
		return $return;
	}
	
	public function GetRecordset($token, $table, $filter = null, $order = null, $limit = null, $offset = null)
	{
		$return = $this->client->getProxy()->GetRecordset($token, $table, $filter, $order, $limit, $offset);

		if ($return['error_code'] != 0)
		{
			show_error($return['error_desc'].'<br/><a class="btn btn-default" href="'. site_url('auth/logout').'">Logout</a>');
		}
		
		return $return;
	}
	
	public function GetDeletedRecordset($token, $table, $filter = null, $order = null, $limit = null, $offset = null)
	{
		$return = $this->client->getProxy()->GetDeletedRecordset($token, $table, $filter, $order, $limit, $offset);

		if ($return['error_code'] != 0)
		{
			show_error($return['error_desc'].'<br/><a class="btn btn-default" href="'. site_url('auth/logout').'">Logout</a>');
		}
		
		return $return;
	}
	
	public function GetCountRecordset($token, $table, $filter = null)
	{
		$return = $this->client->getProxy()->GetCountRecordset($token, $table, $filter);
		
		if ($return['error_code'] != 0)
		{
			show_error($return['error_desc'].'<br/><a class="btn btn-default" href="'. site_url('auth/logout').'">Logout</a>');
		}
		
		return $return;
	}
	
	public function GetCountDeletedRecordset($token, $table, $filter = null)
	{
		$return = $this->client->getProxy()->GetCountDeletedRecordset($token, $table, $filter);
		
		if ($return['error_code'] != 0)
		{
			show_error($return['error_desc'].'<br/><a class="btn btn-default" href="'. site_url('auth/logout').'">Logout</a>');
		}
		
		return $return;
	}
	
	public function ListTable($token)
	{
		$return  = $this->client->getProxy()->ListTable($token);
		
		if ($return['error_code'] != 0)
		{
			show_error($return['error_desc'].'<br/><a class="btn btn-default" href="'. site_url('auth/logout').'">Logout</a>');
		}
		
		return $return;
	}
	
	public function GetDictionary($token, $table)
	{
		$return = $this->client->getProxy()->GetDictionary($token, $table);
		
		if ($return['error_code'] != 0)
		{
			show_error($return['error_desc'].'<br/><a class="btn btn-default" href="'. site_url('auth/logout').'">Logout</a>');
		}
		
		return $return;
	}
	
	/**
	 * Menambah satu record data
	 */
	public function InsertRecord($token, $table, $data)
	{
		$return = $this->client->getProxy()->InsertRecord($token, $table, $data);
		
		if ($return['error_code'] != 0)
		{
			show_error($return['error_desc'].'<br/><a class="btn btn-default" href="'. site_url('auth/logout').'">Logout</a>');
		}
		
		return $return;
	}
	
	/**
	 * Update 1 record.
	 * @param string $token
	 * @param string $table
	 * @param json $data
	 */
	public function UpdateRecord($token, $table, $data)
	{
		$return = $this->client->getProxy()->UpdateRecord($token, $table, $data);
		
		if ($return['error_code'] != 0)
		{
			show_error($return['error_desc'].'<br/><a class="btn btn-default" href="'. site_url('auth/logout').'">Logout</a>');
		}
		
		return $return;
	}
	
	/**
	 * Hapus 1 record.
	 * 
	 * Misal SQL: WHERE kolom = 'value' maka $data = '{"kolom":"value"}';
	 * 
	 * Misal SQL: WHERE kolom = 'value' AND kolom2 = 'value2'  maka $data = '{"kolom":"value", "kolom2":"value2"}';
	 */
	public function DeleteRecord($token, $table, $data)
	{
		return $this->client->getProxy()->DeleteRecord($token, $table, $data);
	}
	
	/**
	 * Hapus multirecord
	 * 
	 * Misal cuma 1 data maka $data = '[{"kolom":"value"}]';
	 * 
	 * Misal multi data maka $data = '[{"kolom":"value1"},{"kolom":"value2"}]';
	 */
	public function DeleteRecordset($token, $table, $data)
	{
		return $this->client->getProxy()->DeleteRecordset($token, $table, $data);
	}
	
	public function GetChangeLog($token)
	{
		return $this->client->getProxy()->GetChangeLog($token);
	}
	
	public function GetExpired($token)
	{
		return $this->client->getProxy()->GetExpired($token);
	}
	
	public function GetListPenugasanDosen($token, $filter = null, $order = null, $limit = null, $offset = null)
	{
		return $this->client->getProxy()->GetListPenugasanDosen($token, $filter, $order, $limit, $offset);
	}
}

/* End of file Feeder.php */