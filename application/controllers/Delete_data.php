<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author Fathoni <m.fathoni@mail.com>
 * @property CI_Remotedb $rdb Remote DB Sistem Langitan
 * @property Feeder $feeder
 */
class Delete_data extends MY_Controller
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
	}
	
	public function kuliah_mahasiswa()
	{
		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$nipd = $this->input->post('nipd');
			
			$result = $this->feeder->GetRecord($this->token, FEEDER_MAHASISWA_PT, "p.nipd = '{$nipd}'");
			
			$mahasiswa_pt = $result['result'];
			
			$delete_result = $this->feeder->DeleteRecord($this->token, FEEDER_KULIAH_MAHASISWA, json_encode(array(
				'id_reg_pd' => $mahasiswa_pt['id_reg_pd'],
				'id_smt'	=> $this->input->post('id_smt')
			)));
			
			// print_r($delete_result); exit();
			
			if (is_array($delete_result['result']))
			{
				$this->smarty->assign('deleted', true);
			}
		}
		
		$this->smarty->display('delete_data/kuliah_mahasiswa.tpl');
	}
}
