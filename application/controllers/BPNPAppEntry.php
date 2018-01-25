<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class BPNPAppEntry extends Bene_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->rt_name = 'BPNPAppEntry';
	}
	
	private function go_to_index_page()
	{
		header("Location: /user");
	}
	
	public function index()
	{
		$this->login();
	}
	
    public function login()
	{
		$this->rt_method = 'login';
		
		$this->load->helper('Bene_Auth');
		$this->load->model('Bene_Login_Model');
		$chek_code = $this->Bene_Login_Model->login();
		
		$this->account = Helper_GetAuthInfo();
		if($chek_code==Rep_SkipPsdCheck)
		{
			if($this->account === FALSE)
			{
				if($this->software_client === TRUE) 
					$this->exit_client_with_code($chek_code);
			}
		}
		else if($chek_code!=Rep_OK)
		{
			Helper_MarkAuthInfo();
			if($this->software_client === TRUE) 
				$this->exit_client_with_code($chek_code);
		}
		
		if($this->account === FALSE)
		{
			if($this->software_client === TRUE) $this->exit_client_with_code($chek_code);
		}
		else if($this->account)
		{
			if($this->software_client === TRUE) 
			{
				//////////////////////////////////////////////////////////////////////////////////////
				$this->load->model('Bene_Online_Model');
				$user_id = $this->account->ID;
				$row = $this->Bene_Online_Model->get_online_user_info_by_user_id($user_id);
				if ($row===FALSE) $this->Bene_Online_Model->insert_new_online_user_info($user_id);
				else  $this->Bene_Online_Model->update_online_user_info($user_id);
				//////////////////////////////////////////////////////////////////////////////////////
				$this->exit_client_with_code(Rep_OK);
			}
		}
	}
	
    public function logout()
	{
		$this->rt_method = 'logout';
		
		if($this->account != FALSE)
		{
			$this->load->model('Bene_Action_Model');
			$this->load->model('Bene_Online_Model');
			$this->Bene_Action_Model->insert_new_action(ACTION_LOGOUT, OBJ_System, $this->account->ID);
			if($this->software_client === TRUE) 
			{
				$this->load->model('Bene_Online_Model');
				$user_id = $this->account->ID;
				$this->Bene_Online_Model->delete_online_user($user_id);
			}
		}
		session_start();
		$_SESSION = array();
		if(isset($_COOKIE[session_name()])){
			setcookie(session_name(),'',time()-42000,'/');
		}
		session_destroy();
		
		if($this->software_client === TRUE) $this->exit_client_with_code(Rep_OK);
	}
	
	public function test_connection()
	{
	    if($this->software_client === TRUE)
		{
			$this->rep_obj->Version = BPNP_Version;
			$this->rep_obj->Feature = BPNP_Feature;
			$this->exit_client_with_code(Rep_OK);
		}
	}
	
    private function get_password()
	{
		$ArrPwd = array();
		$TokeKey = "beneapp";
		$TokeKey = $TokeKey."hxt_op1";
		$date = new DateTime();
		$datastamp = $date->format("U");
		$datastamp = floor($datastamp/300) * 300;
		$datastampEx = $datastamp - 300;
		$date = date('Y-m-d H:i',$datastamp);
		$dateEx = date('Y-m-d H:i',$datastampEx);
		$strdate = $TokeKey.$date;
		$strdateEx = $TokeKey.$dateEx;
		$pwd = $TokeKey.md5($strdate);
		$pwdEx = $TokeKey.md5($strdateEx);
		$pwd = md5($pwd);
		$pwdEx = md5($pwdEx);
		$pwd = substr($pwd,0,8);
		$pwdEx = substr($pwdEx,0,8);
		$ArrPwd[0] = $pwd;
		$ArrPwd[1] = $pwdEx;
		echo $date.'<br/>';
		echo $dateEx.'<br/>';
		echo $pwd;
		echo '<br/>';
		echo $pwdEx;
	}
	
//    private function request_post($url = '', $param = '') {
//		if (empty ( $url ) || empty ( $param )) {
//			return false;
//		}
//		$postdata = http_build_query ( $param );
//		$opts = array (
//				'http' => array (
//						'method' => 'POST',
//						'header' => array (
//								"Content-type: application/x-www-form-urlencoded" 
//						),
//						'content' => $postdata 
//				) 
//		);
//		$context = stream_context_create ( $opts );
//		$result = file_get_contents ( $url, false, $context );
//		return $result;
//	}
//	
//    public function go()
//    {
//    	echo $_POST['Name'];
//    	echo $_POST['User'];
//    	$this->rep_obj = new stdClass();
//    	$this->rep_obj->Name = $_POST['Name'];
//	    $this->exit_client_with_code(Rep_OK);
//    }
//    
//	public function test()
//	{
//		$RecordInfo = array(
//		              'Name'					=>		'name',
//		              'User'                    =>      'user',
//				  	  'FileName'				=>		'FileName',
//					  'MAC'			            =>		'00-E0-18-0E-B2-21',
//					  'Md5'					    =>		'e7dae464f181d5fb',
//		              'Gender'					=>		'M',
//		              'PID'					    =>		'001002256',
//					  'DOB'					    =>		'1999-09-01 00:00:00',
//		              'ImportTime'              =>		'2016-07-04 17:44:00',
//		              'RecTime'					=>		'2016-07-04 17:24:00', 
//		              'Note'                    =>      'note',  
//		);
//		$url = 'http://localhost:13485/ECGNotify';
//		$result = $this->request_post($url,$RecordInfo);
//		$obj_result=json_decode($result,true);
//		if(is_null($obj_result))
//		   $this->exit_client_with_code(Rep_Failed);
//		$ret = $obj_result['Rep_Code']; 
//		if($ret==0)
//		{
//			 $this->rep_obj = new stdClass();
//			 $this->exit_client_with_code(Rep_OK);
//		}else 
//		{
//			 $this->rep_obj = new stdClass();
//			 $this->exit_client_with_code(Rep_Failed);
//		}
//	}
	
}
?>