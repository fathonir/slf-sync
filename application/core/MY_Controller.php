<?php

/**
 * Description of controller
 *
 * @author Fathoni <fathoni@staf.unair.ac.id>
 * @property Smarty_wrapper $smarty
 * @property CI_Form_validation $form_validation
 * @property CI_Input $input
 * @property CI_Loader $load
 * @property CI_DB_active_record $db
 * @property CI_DB_oci8_result $query
 * @property CI_Email $email
 * @property CI_Session $session
 * @property CI_Upload $upload
 * @property CI_Output $output
 * @property CI_URI $uri
 * @property CI_Cache $cache
 * @property Feeder $feeder
 * @property mixed $satuan_pendidikan
 */
class MY_Controller extends CI_Controller {
	//put your code here
	
	function __construct()
	{
		parent::__construct();
		
		// Library loader
		$this->load->library('smarty_wrapper', NULL, 'smarty');
		$this->load->library('form_validation');
		$this->load->library('session');
		
		$this->load->helper('url');
		
		// Session initialization
		if ($this->session->userdata('is_loggedin') == '')
		{
			$this->session->set_userdata('is_loggedin', FALSE);
		}

		// CI $this reference
		$this->smarty->assignByRef('ci', $this);
		
		// disable cache page
		header('Access-Control-Allow-Origin: *');  // untuk keperluan ajax
		header('Last-Modified:'.gmdate('D, d M Y H:i:s').'GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0',false);
		header('Pragma: no-cache');
	}
	
	/**
	 * Mendeteksi credential
	 */
	function check_credentials()
	{
		if ($this->session->userdata('is_loggedin') == FALSE)
		{
			redirect(site_url());
			return;
		}
	}
}
