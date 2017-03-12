<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

require APPPATH . 'third_party/smarty/libs/Smarty.class.php';

class Smarty_wrapper extends Smarty
{
	protected $CI;
	
	function __construct()
	{
		parent::__construct();
		
		$this->setTemplateDir(APPPATH . 'views');
		$this->setCompileDir(APPPATH . 'views_compiled');
	}
}

/* End of file Smarty.php */