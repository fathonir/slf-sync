<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Front extends MY_Controller
{
	public function index()
	{	
		// Hapus cookie yg tabrakan dengan Feeder
		$this->load->helper('cookie');
		
		// delete_cookie('PDPT_GATE', 'localhost');
		
		if ($this->session->userdata('is_loggedin') == TRUE)
		{
			redirect('home/');
			exit();
		}
		
		$this->smarty->display('front/index.tpl');
	}
}