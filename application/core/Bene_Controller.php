<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Bene_Controller extends CI_Controller
{
	public $rt_title				=	'';
	public $rt_name				=	'';
	public $rt_method			=	'';
	
	public $account				= FALSE;
	public $software_client		= FALSE;
	public $rep_obj				= FALSE;
	
	public $client_ip			= '';
	public $client_mac			= '';
	
	public function __construct()
	{
		parent::__construct();
		// normal helper;
		$this->load->helper('Bene_Auth');
		$this->load->helper('Bene_Utility');
		
		// normal config
		$this->config->load('Bene_Application');
		
		// load session varibles and close session file
		session_start();
		$this->account = Helper_GetAuthInfo();
		if( $this->account !== FALSE ) $this->account->TimeStamp = strtotime(date("Y-m-d H:i:s"));
		session_write_close();
		
		if( $this->account === FALSE ) Helper_MarkAuthInfo(NULL);
		
		// check ip and client type
		$this->client_ip = Hepler_GetClientIP();
		$this->software_client = Helper_IsNotFromBrowserClient();
	    
		if( $this->software_client )
		{
			$this->rep_obj = new stdClass();
			$this->rep_obj->Rep_Code = Rep_OK;
			$this->rep_obj->PHPSESSID = session_id();
			
			// check client mac
			if( isset($_GET['ClientMAC']) ) $this->client_mac = $_GET['ClientMAC'];
			else if( isset($_POST['ClientMAC']) ) $this->client_mac = $_POST['ClientMAC'];
			
			// set content type
			header("Content-Type: text/json; charset=UTF-8");
		}else 
		{
			//WebBrowser set language
			$language = isset($_COOKIE['language'])?$_COOKIE['language']:'';
			$this->set_language($language);
		}
	}
	
	// client exit entry
	public function exit_client_with_code($code)
	{
		if( $this->rep_obj != FALSE )
		{
			$php_script_execution_end = microtime();
			$this->rep_obj->Execution_Time = floatval(Helper_PrintElapsedTime($GLOBALS['php_script_execution_start'], $php_script_execution_end));
			
			$this->rep_obj->Rep_Code = $code;
			echo json_encode($this->rep_obj);
		}
		exit(0);
	}
	
	// check login
	protected function doPageIllegal()
	{
		if($this->account === FALSE) Helper_GoToLoginPage();
	}
	
	// check method privilege
	protected function check_method_privilege($method_name, $object_name, $scope = '')
	{
		$this->load->model('Bene_GroupPriv_Model');
		if( $this->Bene_GroupPriv_Model->check_privilege($method_name, $object_name, $scope) === TRUE ) return;
		Helper_ShowErrorPage(REQ_NOPRIVILEGE, FALSE);
	}
	
	protected function set_language($language = '')  //参数为english或zh-cn或japanese
	{
		//$this->load->helper('cookie');
	    if ($language == ''){
	    	if(isset( $_SERVER['HTTP_ACCEPT_LANGUAGE']))
	    	{
	    		$default_lang_arr = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
	    		$strarr = explode(",",$default_lang_arr);
	    		$default_lang = $strarr[0];
	    		if( preg_match("/en/i", $default_lang)){
	    			$this->config->set_item('language', 'english');
	    		}else if (preg_match("/ko/i", $default_lang)){
	    			$this->config->set_item('language', 'japanese' );
	    		}
	    		else if (preg_match("/zh/i", $default_lang)){
	    			$this->config->set_item('language', 'zh-cn');
	    		}else{
	    			$this->config->set_item('language', 'english');
	    		}
	    	}
		}
		else{
			if($language == 'english'){
				$this->config->set_item('language', 'english');
			}else if($language=='japanese'){
				$this->config->set_item('language', 'japanese' );
			}else if($language=='zh-cn'){
				$this->config->set_item('language', 'zh-cn');
			}else{
				$this->config->set_item('language', 'english');
			}
		}
	}

	/*根据sessionID和sessionTime检查用户登录状态，如已经登录、未登录、登录已经过期等信息*/
	public function checkLogStatus(){

		@$sessionAccount = @$_SESSION['Account'];
		@$logSession     = @$_SESSION['LogSession'];
		@$sessionTime    = @$_SESSION['ExpireTime'];

		if(empty($sessionAccount) || empty($logSession) || empty($sessionTime)){
			return false;
		}

		if( strlen($sessionAccount)>20 || $logSession != session_id() || (time()- $sessionTime >= 7200)){
			return false;
		}
		return true;
	}
	
}
?>