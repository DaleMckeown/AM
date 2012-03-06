<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller { 
	function __construct() {
        parent::__construct();

		//$this->load->helper('url');
		//$this->load->helper('html');
		//$this->load->database();
		$this->load->model('System_data');
		
		$this->data['base_url'] = $this->System_data->get_base_url();
		$this->data['am_url'] = $this->System_data->get_am_url();
		$this->data['css'] = $this->System_data->get_css_filespec();
		$this->data['javascript'] = $this->System_data->get_javascript_filespec();
		$this->data['images'] = $this->System_data->get_images_filespec();
		
		$this->data['application_id'] = $this->System_data->get_application_id();
		$this->data['application_scope'] = $this->System_data->get_application_scope();
		$this->data['mongo_db'] = $this->System_data->get_mongo_db();
	}
	 
	function index() {
		$this->load->view('public_user/home_View', $this->data);
	}
}
/* End of file index.php */
/* Location: ./application/controllers/publicUser/index.php */