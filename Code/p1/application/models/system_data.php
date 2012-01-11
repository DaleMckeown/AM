<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class System_data extends CI_Model {
   
     function __construct()
    {
        parent::__construct();
    }
	
	function get_base_url(){
      return $this->config->item('base_url');
    }
	function get_am_url(){
      return $this->config->item('am_url');
    }
    function get_css_filespec(){
      return $this->config->item('css');
    }
    function get_javascript_filespec(){
      return $this->config->item('javascript');
    }
    function get_images_filespec(){
      return  $this->config->item('images');
    }
	function get_application_id(){
      return  $this->config->item('application_id');
    }
	function get_application_scope(){
      return  $this->config->item('application_scope');
    }
}
?>