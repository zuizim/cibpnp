<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Bene_Model extends CI_Model
{
	protected $CI;
	
	public function __construct()
	{
		parent::__construct();
		$this->CI = & get_instance();
	}
	
	protected function connect_to_db()
	{
		if( ! isset($this->db) ) $this->load->database();
	}
	
	protected function db_field_like(& $like_field_list, $check_type = 'post')
	{
		$check_cache = FALSE;
		if($check_type == 'post') $check_cache = & $_POST;
		else $check_cache = & $_GET;
		
		foreach($like_field_list as $field_key)
		{
			if( ! isset($check_cache[$field_key]) ) continue;
			$like_value = trim( $check_cache[$field_key] );
			if( ! empty($like_value) ) $this->db->like($field_key, $like_value);
		}
	}
	
}

?>