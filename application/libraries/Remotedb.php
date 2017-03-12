<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

/**
 * Remote DB for Sistem Langitan
 */
class Remotedb
{
	/**
	 *
	 * @var type cUrl Handle
	 */
	private $ch;
	
	function __construct()
	{
		$this->ch = curl_init();
		
		// Request POST
		curl_setopt($this->ch, CURLOPT_POST,			TRUE);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,	TRUE);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION,	TRUE);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER,  FALSE);
	}
	
	function set_url($url)
	{
		curl_setopt($this->ch, CURLOPT_URL, $url);
	}
	
	function QueryToArray($sql, $as_array = TRUE, $offset = '', $limit = '')
	{
		$param = array(
			'function'	=> 'QueryToArray',
			'sql'		=> $sql
		);
		
		// Jika pakai format paging
		if ($limit > 0)
		{
			// permak Sql
			$param['sql'] = "SELECT b.* FROM (SELECT A.*, ROWNUM rnum FROM ({$sql}) A where rownum <= ({$offset} + {$limit})) b WHERE rnum > {$offset}";
		}
		
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $param);
		
		if ($as_array)
		{
			$result = json_decode($this->exec(), true);
			return $result;
		}
		else
		{
			return $this->exec();
		}
	}
	
	function Query($sql)
	{
		$param = array(
			'function'	=> 'Query',
			'sql'		=> $sql
		);
		
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $param);
		
		$result = $this->exec();
		
		if ($result == 'TRUE')
		{
			return TRUE;
		}
		else if ($result == 'FALSE')
		{
			return FALSE;
		}
	}
	
	private function exec()
	{
		$result = curl_exec($this->ch);
		
		if ( ! $result)
		{
			$error = curl_error($this->ch);
			return "Akses ke Sistem Langitan gagal. Pesan: ".$error;
		}
		else
		{
			return $result;
		}
	}
}

/* End of file Remotedb.php */