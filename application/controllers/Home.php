<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends MY_Controller {
	
	function __construct()
	{
		parent::__construct();
		
		$this->check_credentials();
		
		$this->load->library('feeder', array('url' => $this->session->userdata('wsdl')));
		
		$this->token = $this->session->userdata('token');
	}
	
	function index()
	{
		$username = $this->session->userdata('username');

		// --------------------------------------------
		// Ambil data PT
		// --------------------------------------------
		$response = $this->feeder->GetRecord($this->token, FEEDER_SATUAN_PENDIDIKAN, "npsn = '{$username}'");
		$this->satuan_pendidikan = $response['result'];
		$this->smarty->assign('satuan_pendidikan', $this->satuan_pendidikan);
		
		// cleansing
		$this->satuan_pendidikan['npsn'] = trim($this->satuan_pendidikan['npsn']);
		
		// Simpan ke session
		// xcache_set(FEEDER_SATUAN_PENDIDIKAN, $this->satuan_pendidikan);
		$this->session->set_userdata(FEEDER_SATUAN_PENDIDIKAN, $this->satuan_pendidikan);
		


		// --------------------------------------------
		// Ambil data Jenjang
		// --------------------------------------------
		$response = $this->feeder->GetRecordset($this->token, FEEDER_JENJANG_PENDIDIKAN);
		$jenjang_set = $response['result'];
		
		// Simpan ke session
		//xcache_set(FEEDER_JENJANG_PENDIDIKAN.'_set', $response['result']);
		$this->session->set_userdata(FEEDER_JENJANG_PENDIDIKAN.'_set', $response['result']);
		
		
		
		// --------------------------------------------
		// Ambil data Program Studi
		// --------------------------------------------
		$response = $this->feeder->GetRecordset($this->token, FEEDER_SMS, "id_sp = '{$this->satuan_pendidikan['id_sp']}'");
		
		foreach ($response['result'] as &$sms)
		{
			foreach ($jenjang_set as $jenjang)
			{
				if ($sms['id_jenj_didik'] == $jenjang['id_jenj_didik'])
				{
					// Permak Nama Prodi biar ada jenjang
					$sms['nm_lemb'] = "{$jenjang['nm_jenj_didik']} {$sms['nm_lemb']} [{$sms['kode_prodi']}]";
					break;
				}
			}
		}
		
		// Simpan ke session
		//xcache_set(FEEDER_SMS.'_set', $response['result']);
		$this->session->set_userdata(FEEDER_SMS.'_set', $response['result']);
		
		// Ambil Expired
		$this->smarty->assign('expired', $this->feeder->GetExpired($this->token));
		
		// Ambil ChangeLog
		$this->smarty->assign('changelog', $this->feeder->GetChangeLog($this->token));

		$this->smarty->display('home/index.tpl');
	}
}
