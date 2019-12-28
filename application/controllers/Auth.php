<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property Remotedb $rdb 
 */
class Auth extends MY_Controller
{	
	public function __construct()
	{
		parent::__construct();
		
		// Inisialisasi Library RemoteDB
		$this->load->library('remotedb', NULL, 'rdb');
	}

	function login()
	{		
		if ($this->input->server('REQUEST_METHOD') == 'POST')
		{
			$feeder_url	= $this->input->post('feeder_url');
			$langitan	= $this->input->post('langitan');
			$username	= $this->input->post('username');
			$password	= $this->input->post('password');

			$ws2url = '';
			
			// Clean trailing slash
			if(substr($feeder_url, -1) == '/') { $feeder_url = substr($feeder_url, 0, -1); }
			if(substr($langitan, -1) == '/') { $langitan = substr($langitan, 0, -1); }
			
			// Mode Live
			if ($this->input->post('mode') == 1)
			{
				$wsdl = $feeder_url . '/ws/live.php?wsdl';
				$ws2url = $feeder_url . '/ws/live2.php';
			}
			// Mode Sandbox
			else if ($this->input->post('mode') == 2)
			{
				$wsdl = $feeder_url . '/ws/sandbox.php?wsdl';
				$ws2url = $feeder_url . '/ws/sandbox2.php';
			}
			
			// WS Sistem Langitan
			$langitan .= '/modul/webservice/remotedb.php';

			// Get Token WSDL
			$this->load->library('feeder', array('url' => $wsdl));
			$result = $this->feeder->GetToken($username, $password);

			// Get Token WS2
            $this->load->library('feederws', ['url' => $ws2url]);
            $result2 = json_decode(
                $this->feederws->runWS(json_encode([
                    'act' => 'GetToken',
                    'username' => $username,
                    'password' => $password
                ])),
                true);
			
			// Jika ketemu error
			if (strpos($result, "ERROR") !== FALSE)
			{
				$this->smarty->assign('error_message', $result);
				$this->smarty->display('front/index.tpl');
				return;
			}
			else if($result2['error_code'] !== "0")
            {
                $this->smarty->assign('error_message', $result2['error_desc']);
                $this->smarty->display('front/index.tpl');
                return;
            }
			else // simpan token
			{
				
				//Set alamat langitan
				$this->rdb->set_url($langitan);
				
				// Ambil data perguruan tinggi
				$pt_set = $this->rdb->QueryToArray("SELECT * FROM perguruan_tinggi WHERE npsn = '{$username}'");
				
				if (count($pt_set) != 1)
				{
					$this->smarty->assign('error_message', "Kode PT di Langitan belum di set");
					$this->smarty->display('front/index.tpl');
					return;
				}
				else
				{
					// Status Sandbox
					if ($this->input->post('mode') == 1)
						$this->session->set_userdata('is_sandbox', FALSE);
					else if ($this->input->post('mode') == 2)
						$this->session->set_userdata('is_sandbox', TRUE);

					$this->session->set_userdata('wsdl', $wsdl);
                    $this->session->set_userdata('ws2url', $ws2url);
					$this->session->set_userdata('token', $result);
                    $this->session->set_userdata('token2', $result2['data']['token']);
					$this->session->set_userdata('langitan', $langitan);
					$this->session->set_userdata('username', $username);
					$this->session->set_userdata('password', $password);
					$this->session->set_userdata('is_loggedin', TRUE);
					
					// Data perguruan tinggi langitan
					$this->session->set_userdata('pt', array_change_key_case($pt_set[0]));

					redirect('home'); 
					return;
				}
			}
		}
		
		// Langsung redirect jika bukan POST
		redirect(site_url());
	}

	function logout()
	{
		// Logout
		$this->session->set_userdata('is_loggedin', FALSE);
		$this->session->unset_userdata('token');
		$this->session->unset_userdata('wsdl');
		$this->session->unset_userdata('langitan');
		$this->session->unset_userdata('username');
		$this->session->unset_userdata('password');
		
		// Full Destroy
		$this->session->sess_destroy();
		
		redirect(site_url()); 
		exit();
	}
}